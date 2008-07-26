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
    $this->addOptions(array(
      new sfCommandOption('revision', 'r', sfCommandOption::PARAMETER_REQUIRED, 'Revision number of the migration to inspect'),
    ));
    
    $this->namespace = 'propel';
    $this->name = 'migration-info';
    $this->briefDescription = 'Display information about a migration';
    
    $this->detailedDescription = <<<EOF
The [propel:migration-info|INFO] displays a description of the latest migration:

  [./symfony propel:migration-info|INFO]

You can also specify a specific migration to inspect by using the [--revision|COMMENT]
option:

  [./symfony propel:migration-info --revision=10|INFO]

Or inspect a range of revisions:

  [./symfony propel:migration-info --revision=5:10|INFO]
EOF;
  }
  
  /**
   * @see sfTask
   * 
   * @throws InvalidArgumentException If the revision option is not recognized
   */
  protected function execute($arguments = array(), $options = array())
  {
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter);
    
    if (preg_match('/^(\d+):(\d+)$/', $options['revision'], $match))
    {
      $revisions = range($match[1], $match[2]);
    }
    elseif (ctype_digit($options['revision']))
    {
      $revisions = array($options['revision']);
    }
    elseif (is_null($options['revision']))
    {
      $revisions = array($migrationManager->getHeadRevision());
    }
    else
    {
      throw new InvalidArgumentException(sprintf('Unrecognized --revision option: "%s"', $options['revision']));
    }
    
    foreach ($revisions as $revision)
    {
      $this->logSection('migration', sprintf('Revision %d: %s', $revision, ($description = $migrationManager[$revision]->getDescription()) ? $description : '(no description)'));
    }
  }
}
