<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Output the database's migration log.
 */
class sfPropelMigrationLogTask extends sfPropelBaseTask
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
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Limit the number of log entries displayed'),
    ));
    
    $this->namespace = 'propel';
    $this->name = 'migration-log';
    $this->briefDescription = 'Display the database\'s migration log';
    
    $this->detailedDescription = <<<EOF
The [propel:migration-log|INFO] task displays a log of migrations performed on your
database:

  [./symfony propel:migration-log frontend|INFO]

You can limit the number of log entries displayed with the [--limit|COMMENT] option:

  [./symfony propel:migration-log frontend --limit=4|INFO]
EOF;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager  = new sfDatabaseManager($this->configuration);
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter);
    
    foreach ($migrationManager->getMigrationLog($options['limit'])->fetchAll() as $row)
    {
      $this->logSection('migration', sprintf('[%s] migrated from r%d to r%d%s', date('M d H:i:s', strtotime($row['migrated_at'])), $row['revision_from'], $row['revision_to'], $row['manual'] ? ' (manually)' : null));
    }
  }
}
