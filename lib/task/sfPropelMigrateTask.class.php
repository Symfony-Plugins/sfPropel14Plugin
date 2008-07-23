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
      $migrationManager->setTargetRevision(sfPropelMigrationManager::HEAD);
    }
    
    $migrationManager->execute();
  }
}
