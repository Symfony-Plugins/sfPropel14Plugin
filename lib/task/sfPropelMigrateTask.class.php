<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Execute a database migration.
 */
class sfPropelMigrateTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
    ));
    
    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environement', 'cli'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      new sfCommandOption('revision', 'r', sfCommandOption::PARAMETER_REQUIRED, 'The target schema revision'),
      new sfCommandOption('down', null, sfCommandOption::PARAMETER_NONE, 'Migrate down a certain number of revisions'),
      new sfCommandOption('up', null, sfCommandOption::PARAMETER_NONE, 'Migrate up a certain number of revisions'),
      new sfCommandOption('manual', null, sfCommandOption::PARAMETER_NONE, 'Manually set the current schema revision but do not run any migrations'),
    ));
    
    $this->aliases = array('propel-migrate');
    $this->namespace = 'propel';
    $this->name = 'migrate';
    $this->briefDescription = 'Migrate your database to a schema revision';
    
    $this->detailedDescription = <<<EOF
The [propel:migrate|INFO] task executes a chain of schema migrations:

  [./symfony propel:migrate frontend|INFO]

You can define which schema revision you would like to migrate to using the
[--revision|COMMENT] option.

  [./symfony propel:migrate frontend --revision=10|INFO]

Alternatively, if you would like to migrate up or down one revision, you can 
use the [--up|COMMENT] and [--down|COMMENT] options, respectively:

  [./symfony propel:migrate frontend --down|INFO]

If you would like to manually set the schema revision number stored in your 
database without running any migrations, use the [--manual|COMMENT] option:

  [./symfony propel:migrate frontend --revision=1 --manual|INFO]

EOF;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager  = new sfDatabaseManager($this->configuration);
    
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter, $options['connection']);
    $migrationManager->setIsManual($options['manual']);
    
    $currentRevision = $migrationManager->getCurrentRevision();
    
    if (!is_null($options['revision']))
    {
      $migrationManager->setTargetRevision($options['revision']);
    }
    elseif ($options['up'])
    {
      $migrationManager->setTargetRevision($currentRevision + 1);
    }
    elseif ($options['down'])
    {
      $migrationManager->setTargetRevision($currentRevision - 1);
    }
    else
    {
      $migrationManager->setTargetRevision($migrationManager->getHeadRevision());
    }
    
    $migrationManager->execute();
  }
}
