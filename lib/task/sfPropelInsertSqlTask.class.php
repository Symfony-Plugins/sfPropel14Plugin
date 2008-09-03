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
 * Inserts SQL for current model.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelInsertSqlTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('no-confirmation', null, sfCommandOption::PARAMETER_NONE, 'Do not ask for confirmation'),
    ));

    $this->aliases = array('propel-insert-sql');
    $this->namespace = 'propel';
    $this->name = 'insert-sql';
    $this->briefDescription = 'Inserts SQL for current model';

    $this->detailedDescription = <<<EOF
The [propel:insert-sql|INFO] task creates database tables:

  [./symfony propel:insert-sql|INFO]

The task connects to the database and executes all SQL statements
found in [config/sql/*schema.sql|COMMENT] files.

Before execution, the task will ask you to confirm the execution
as it deletes all data in your database.

To bypass the confirmation, you can pass the [no-confirmation|COMMENT]
option:

  [./symfony propel:insert-sql --no-confirmation|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    if (
      !$options['no-confirmation']
      &&
      !$this->askConfirmation(array('This command will remove all data in your database.', 'Are you sure you want to proceed? (y/N)'), null, false)
    )
    {
      $this->logSection('propel', 'Task aborted.');

      return 1;
    }

    $this->schemaToXML(self::DO_NOT_CHECK_SCHEMA, 'generated-');
    $this->copyXmlSchemaFromPlugins('generated-');
    $this->callPhing('insert-sql', self::CHECK_SCHEMA);
    $this->cleanup();

    return 0;
  }
}
