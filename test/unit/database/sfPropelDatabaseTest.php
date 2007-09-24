<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/../../../../../../test/bootstrap/unit.php');
set_include_path(sfConfig::get('sf_symfony_lib_dir').'/plugins/sfPropelPlugin/lib/vendor'.PATH_SEPARATOR.get_include_path());

$t = new lime_test(2, new lime_output_color());

$p = new sfPropelDatabase();

$configuration = array(
  'propel' => array(
    'datasources' => array(
      'propel' => array(
        'adapter' => 'mysql',
        'connection' => array(
          'dsn'        => 'mysql:dbname=testdb;host=localhost',
          'user'       => 'foo',
          'password'   => 'bar',
          'encoding'   => 'utf8',
          'persistent' => true,
        ),
      ),
      'default' => 'propel',
    ),
  ),
);

$parametersTests = array(
  array(
    'dsn'        => 'mysql:dbname=testdb;host=localhost',
    'username'   => 'foo',
    'password'   => 'bar',
    'encoding'   => 'utf8',
    'persistent' => true,
  )
);

foreach ($parametersTests as $parameters)
{
  $p->initialize($parameters);
  $t->is($p->getConfiguration(), $configuration);
}
