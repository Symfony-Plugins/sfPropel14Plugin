<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Output information about a single migration.
 */
class sfPropelMigrationInfoTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('revision', sfCommandArgument::OPTIONAL, 'Revision number of the migration to inspect', sfPropelMigrationManager::HEAD),
    ));
    
    $this->namespace = 'propel';
    $this->name = 'migration-info';
    $this->briefDescription = 'Display information about a migration';
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter);
    
    if (preg_match('/^(\d+):(\d+)$/', $arguments['revision'], $match))
    {
      $revisions = range($match[1], $match[2]);
    }
    else
    {
      $revisions = array($arguments['revision']);
    }
    
    foreach ($revisions as $revision)
    {
      $migration = $migrationManager[$revision];
      
      $this->logSection('migration', sprintf('Revision %d: %s', $revision, ($description = $migration->getDescription()) ? $description : '(no description)'));
    }
  }
}
