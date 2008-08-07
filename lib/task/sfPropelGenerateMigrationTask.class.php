<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Initialize a new migration class.
 */
class sfPropelGenerateMigrationTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('description', null, sfCommandOption::PARAMETER_REQUIRED, 'Brief description of this migration'),
    ));
    
    $this->aliases = array('propel-generate-migration');
    $this->namespace = 'propel';
    $this->name = 'generate-migration';
    $this->briefDescription = 'Generate a new Propel migration';
    
    $this->detailedDescription = <<<EOF
The [propel:generate-migration|INFO] task generates a migration class file:

  [./symfony propel:generate-migration|INFO]

You can optionally define a description of this migration using the 
[--description|COMMENT] option:

  [./symfony propel:generate-migration --description="Added sfGuardPlugin tables"|INFO]
EOF;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $migrationManager = new sfPropelMigrationManager($this->configuration, $this->formatter);
    
    $revision = $migrationManager->getHeadRevision() + 1;
    $class    = $migrationManager->translateRevisionToClassName($revision);
    $file     = $migrationManager->getMigrationsDir().'/'.$class.'.class.php';
    
    $configure = null;
    $up        = null;
    $down      = null;
    
    if (isset($options['description']))
    {
      $configure = sprintf("\n    \$this->description = %s;", var_export($options['description'], true));
    }
    
    if (1 == $revision && $sqlFiles = sfFinder::type('file')->name('*.sql')->maxdepth(0)->in($this->configuration->getRootDir().'/data/sql'))
    {
      $this->getFilesystem()->mkdirs($migrationManager->getMigrationsDir(), 0755);

      // generate up sql file
      $upSql = null;
      foreach ($sqlFiles as $sqlFile)
      {
        $upSql .= file_get_contents($sqlFile)."\n";
      }
      
      $this->logSection('file+', $upFile = $migrationManager->getMigrationsDir().'/'.$class.'_up.sql');
      file_put_contents($upFile, $upSql);
      $up = "\n    \$this->executeSqlFromFile(dirname(__FILE__).'/{$class}_up.sql');";
      
      // generate down sql file
      if (preg_match_all('/DROP TABLE IF EXISTS `(\w+)`;/', $upSql, $matches))
      {
        $downSql = null;
        if (false !== $fkChecks = strpos($upSql, 'FOREIGN_KEY_CHECKS'))
        {
          $downSql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        }
        foreach ($matches[0] as $match)
        {
          $downSql .= $match."\n";
        }
        if (false !== $fkChecks)
        {
          $downSql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        }
        
        $this->logSection('file+', $downFile = $migrationManager->getMigrationsDir().'/'.$class.'_down.sql');
        file_put_contents($downFile, $downSql);
        $down = "\n    \$this->executeSqlFromFile(dirname(__FILE__).'/{$class}_down.sql');";
      }
    }
    
    $this->getFilesystem()->copy(dirname(__FILE__).'/skeleton/migration/Migration.class.php', $file);
    $this->getFilesystem()->replaceTokens($file, '##', '##', array(
      'CLASS'     => $class,
      'CONFIGURE' => $configure,
      'UP'        => $up,
      'DOWN'      => $down,
    ));
  }
}
