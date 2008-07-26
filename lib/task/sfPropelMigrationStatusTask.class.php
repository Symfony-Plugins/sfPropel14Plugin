<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Output current migration status.
 */
class sfPropelMigrationStatusTask extends sfPropelBaseTask
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
    ));
    
    $this->namespace = 'propel';
    $this->name = 'migration-status';
    $this->briefDescription = 'Display the current schema revision';
    
    $this->detailedDescription = <<<EOF
The [propel:migration-status|INFO] task displays the database's current schema 
revision and the highest schema revision available:

  [./symfony propel:migration-status frontend|INFO]
EOF;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager  = new sfDatabaseManager($this->configuration);
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter);
    
    $this->logSection('migration', 'Current schema revision: '.$migrationManager->getCurrentRevision());
    $this->logSection('migration', 'Highest schema revision available: '.$migrationManager->getHeadRevision());
  }
}
