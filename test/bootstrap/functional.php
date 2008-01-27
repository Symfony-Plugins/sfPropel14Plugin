<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// we need sqlite for functional tests
if (!extension_loaded('SQLite'))
{
  return false;
}

if (!isset($root_dir))
{
  $root_dir = realpath(dirname(__FILE__).sprintf('/../%s/fixtures', isset($type) ? $type : 'functional'));
}
define('SF_ROOT_DIR',    $root_dir);
define('SF_APP',         $app);
define('SF_ENVIRONMENT', 'test');
define('SF_DEBUG',       isset($debug) ? $debug : true);

// initialize symfony
require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

// remove all cache
sfToolkit::clearDirectory(sfConfig::get('sf_app_cache_dir'));

$dispatcher = sfContext::getInstance()->getEventDispatcher();
$formatter = new sfFormatter();

if (isset($fixtures))
{
  chdir(sfConfig::get('sf_root_dir'));

  // update propel configuration paths
  $config = file_get_contents(sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR.'propel.ini');
  $propel = sfToolkit::replaceConstants($config);
  file_put_contents(sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR.'propel.ini', $propel);

  // build Propel om/map/sql/forms
  $files = glob(sfConfig::get('sf_lib_dir').'/model/om/*.php');
  if (false === $files || !count($files))
  {
    $task = new sfPropelBuildModelTask($dispatcher, $formatter);
    $task->run();
  }

  $files = glob(sfConfig::get('sf_data_dir').'/sql/*.sql');
  if (false === $files || !count($files))
  {
    $task = new sfPropelBuildSqlTask($dispatcher, $formatter);
    ob_start();
    $task->run();
    ob_end_clean();
  }

  $files = glob(sfConfig::get('sf_lib_dir').'/form/base/*.php');
  if (false === $files || !count($files))
  {
    $task = new sfPropelBuildFormsTask($dispatcher, $formatter);
    ob_start();
    $task->run();
    ob_end_clean();
  }

  $task = new sfCacheClearTask($dispatcher, $formatter);
  ob_start();
  $task->run();
  ob_end_clean();

  // initialize database manager
  $databaseManager = new sfDatabaseManager();

  // cleanup database
  $db = sfConfig::get('sf_data_dir').DIRECTORY_SEPARATOR.'database.sqlite';
  if (file_exists($db))
  {
    unlink($db);
  }

  // initialize database
  $sql = file_get_contents(sfConfig::get('sf_data_dir').DIRECTORY_SEPARATOR.'sql'.DIRECTORY_SEPARATOR.'lib.model.schema.sql');
  $sql = preg_replace('/^\s*\-\-.+$/m', '', $sql);
  $sql = preg_replace('/^\s*DROP TABLE .+?$/m', '', $sql);

  $con = Propel::getConnection();

  $tables = preg_split('/CREATE TABLE/', $sql);
  foreach ($tables as $table)
  {
    $table = trim($table);
    if (!$table)
    {
      continue;
    }

    $con->exec('CREATE TABLE '.$table);
  }

  // load fixtures
  $data = new sfPropelData();
  if (is_array($fixtures))
  {
    $data->loadDataFromArray($fixtures);
  }
  else
  {
    $data->loadData(sfConfig::get('sf_data_dir').'/'.$fixtures);
  }

  // restore original propel config
  file_put_contents(sfConfig::get('sf_config_dir').DIRECTORY_SEPARATOR.'propel.ini', $config);
}

return true;
