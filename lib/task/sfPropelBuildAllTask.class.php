<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/sfPropelBaseTask.class.php');

/**
 * Generates Propel model, SQL and initializes the database.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelBuildAllTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', null),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      new sfCommandOption('no-confirmation', null, sfCommandOption::PARAMETER_NONE, 'Do not ask for confirmation'),
      new sfCommandOption('skip-forms', 'F', sfCommandOption::PARAMETER_NONE, 'Skip generating forms')
    ));

    $this->aliases = array('propel-build-all');
    $this->namespace = 'propel';
    $this->name = 'build-all';
    $this->briefDescription = 'Generates Propel model, SQL and initializes the database';

    $this->detailedDescription = <<<EOF
The [propel:build-all|INFO] task is a shortcut for three other tasks:

  [./symfony propel:build-all|INFO]

The task is equivalent to:

  [./symfony propel:build-model|INFO]
  [./symfony propel:build-sql|INFO]
  [./symfony propel:build-forms|INFO]
  [./symfony propel:insert-sql|INFO]

See those three tasks help page for more information.

To bypass the confirmation, you can pass the [no-confirmation|COMMENT]
option:

  [./symfony propel:buil-all-load --no-confirmation|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $buildModel = new sfPropelBuildModelTask($this->dispatcher, $this->formatter);
    $buildModel->setCommandApplication($this->commandApplication);
    $ret = $buildModel->run();

    if ($ret)
    {
      return $ret;
    }

    $buildSql = new sfPropelBuildSqlTask($this->dispatcher, $this->formatter);
    $buildSql->setCommandApplication($this->commandApplication);
    $ret = $buildSql->run();

    if ($ret)
    {
      return $ret;
    }

    if (!$options['skip-forms'])
    {
      $buildForms = new sfPropelBuildFormsTask($this->dispatcher, $this->formatter);
      $buildForms->setCommandApplication($this->commandApplication);
      $ret = $buildForms->run();

      if ($ret)
      {
        return $ret;
      }
    }

    $insertSql = new sfPropelInsertSqlTask($this->dispatcher, $this->formatter);
    $insertSql->setCommandApplication($this->commandApplication);

    $insertSqlOptions = array('--env='.$options['env'], '--connection='.$options['connection']);
    if ($options['application'])
    {
      $insertSqlOptions[] = '--application='.$options['application'];
    }
    if ($options['no-confirmation'])
    {
      $insertSqlOptions[] = '--no-confirmation';
    }

    return $insertSql->run(array(), $insertSqlOptions);
  }
}
