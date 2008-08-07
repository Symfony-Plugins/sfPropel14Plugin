<?php

/**
 * Manage the execution of migrations.
 * 
 * @package     sfPropelPlugin
 * @subpackage  migration
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version     SVN: $Id: sfMigrator.class.php 10225 2008-07-11 18:29:49Z Kris.Wallsmith $
 */
class sfPropelMigrationManager implements ArrayAccess, Countable
{
  static protected
    $autoloadRegistered = false,
    $migrationLogTable = 'sf_propel_plugin_migration_log',
    $queries = array(
      'mysql' => array(
        'create'      => 'CREATE TABLE %table% (id INT PRIMARY KEY AUTO_INCREMENT, revision_from INT, revision_to INT NOT NULL, manual INT NOT NULL DEFAULT 0, migrated_at DATETIME NOT NULL)',
        'insert'      => 'INSERT INTO %table% SET revision_from = :revision_from, revision_to = :revision_to, manual = :manual, migrated_at = NOW()',
        'current'     => 'SELECT revision_to FROM %table% ORDER BY id DESC LIMIT 1',
        'log'         => 'SELECT * FROM %table% ORDER BY id DESC',
        'log_limited' => 'SELECT * FROM %table% ORDER BY id DESC LIMIT %limit%',
      ),
    );
  
  protected
    $configuration   = null,
    $formatter       = null,
    $connection      = null,
    $connectionName  = null,
    $revisions       = null,
    $currentRevision = null,
    $targetRevision  = null,
    $isManual        = false;
  
  /**
   * Get a query appropriate for the current database driver.
   * 
   * @param   string  $key
   * @param   array   $vars
   * 
   * @return  string
   * 
   * @throws  <b>RuntimeException</b> If the current PDO driver is not supported
   */
  static protected function getLogQuery($key, $vars = array())
  {
    $driver = Propel::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if (isset(self::$queries[$driver][$key]))
    {
      $vars['%table%'] = self::$migrationLogTable;
      
      $query = strtr(self::$queries[$driver][$key], $vars);
    }
    else
    {
      throw new RuntimeException(sprintf('The PDO driver "%s" is not currently supported.', $driver));
    }
    
    return $query;
  }
  
  /**
   * Constructor.
   * 
   * @param   sfProjectConfiguration  $configuration
   * @param   string                  $connectionName
   */
  public function __construct(sfProjectConfiguration $configuration, sfFormatter $formatter = null, $connectionName = null)
  {
    $this->configuration  = $configuration;
    $this->formatter      = $formatter;
    $this->connectionName = $connectionName;
    
    $this->revisions = array();
    foreach (sfFinder::type('file')->name('/^Migration\d{3}\.class\.php$/')->sort_by_name()->maxdepth(0)->in($this->getMigrationsDir()) as $file)
    {
      $this->revisions[] = $this->translateNameToRevision(basename($file));
    }
  }
  
  /**
   * Execute migration(s).
   * 
   * @throws <b>LogicException</b> If a target revision has not been set
   */
  public function execute()
  {
    if (is_null($this->getTargetRevision()))
    {
      throw new LogicException(sprintf('%s requires a target revision be set.', __METHOD__));
    }
    
    $queue = array();
    if ($this->getTargetRevision() > $this->getCurrentRevision())
    {
      // migrate up
      $queue = range($this->getCurrentRevision() + 1, $this->getTargetRevision());
      $direction = 'up';
    }
    elseif ($this->getTargetRevision() < $this->getCurrentRevision())
    {
      // migrate down
      $queue = range($this->getCurrentRevision(), $this->getTargetRevision() + 1);
      $direction = 'down';
    }
    
    if ($queue)
    {
      $con = $this->getConnection();
      
      if ($this->getIsManual())
      {
        $this->logMigration($this->getTargetRevision(), true);
      }
      else
      {
        $this->logMessage(sprintf('Preparing migration from revision %d to %d.', $this->getCurrentRevision(), $this->getTargetRevision()));
        
        $method = 'execute'.ucwords($direction);
        foreach ($queue as $revision)
        {
          try
          {
            $con->beginTransaction();
            
            $this->logMigration($this[$revision]->$method());
            
            $con->commit();
          }
          catch (Exception $e)
          {
            $con->rollBack();
            throw $e;
          }
        }
        
        $this->logMessage(sprintf('Successfully migrated %s %d step(s).', $direction, count($queue)));
      }
    }
    else
    {
      $this->logMessage(sprintf('No migration necessary; currently at revision %d.', $this->getCurrentRevision()));
    }
  }
  
  /**
   * Set whether this is a manual migration.
   * 
   * @param   boolean $isManual
   */
  public function setIsManual($isManual)
  {
    $this->isManual = (bool) $isManual;
  }
  
  /**
   * Returns true if this is a manual migration.
   * 
   * @return  boolean
   */
  public function getIsManual()
  {
    return $this->isManual;
  }
  
  /**
   * Get revision number of the current database.
   * 
   * @return  integer
   */
  public function getCurrentRevision()
  {
    if (is_null($this->currentRevision))
    {
      try
      {
        $currentRevision = (int) $this->getConnection()->query($this->getLogQuery('current'))->fetchColumn();
        
        if ($currentRevision && !isset($this[$currentRevision]) && !$this->getIsManual())
        {
          throw new RuntimeException(sprintf('Your database is at revision %d, but no migration for that revision exists!', $currentRevision));
        }
      }
      catch (PDOException $e)
      {
        $currentRevision = 0;
      }
      
      $this->currentRevision = $currentRevision;
    }
    
    return $this->currentRevision;
  }
  
  /**
   * Get the maximum available revision.
   * 
   * @return  integer
   */
  public function getHeadRevision()
  {
    return count($this) ? $this->revisions[count($this) - 1] : 0;
  }
  
  /**
   * Set a target revision for this migration.
   * 
   * @param   integer $revision
   */
  public function setTargetRevision($revision)
  {
    $this->targetRevision = (int) $revision;
  }
  
  /**
   * Get the target revision set for this migration.
   * 
   * @return  integer
   */
  public function getTargetRevision()
  {
    return $this->targetRevision;
  }
  
  /**
   * Get the directory where migration classes are saved.
   * 
   * @return  string
   */
  public function getMigrationsDir()
  {
    return $this->configuration->getRootDir().'/data/migrations';
  }
  
  /**
   * A PDO statement populated with values from the migration log table.
   * 
   * @param   integer $limit
   * 
   * @return  PDOStatement
   */
  public function getMigrationLog($limit = null)
  {
    $sql = is_null($limit) ? $this->getLogQuery('log') : $this->getLogQuery('log_limited', array('%limit%' => $limit));
    
    return $this->getConnection()->query($sql);
  }
  
  /**
   * Translate a migration class or file name to a revision number.
   * 
   * @param   string $name
   * 
   * @return  integer
   */
  public function translateNameToRevision($name)
  {
    return (int) substr($name, strlen('Migration'), 3);
  }
  
  /**
   * Translate a revision number to a class name.
   * 
   * @param   integer $revision
   * 
   * @return  string
   */
  public function translateRevisionToClassName($revision)
  {
    return sprintf('Migration%03d', $revision);
  }
  
  /**
   * Get the database connection object.
   * 
   * @return  PropelPDO
   */
  public function getConnection()
  {
    if (is_null($this->connection))
    {
      $this->connection = Propel::getConnection($this->connectionName);
    }
    
    return $this->connection;
  }
  
  /**
   * Returns true if there is a migration for the requested revision number (implements ArrayAccess interface).
   * 
   * @param   integer $offset
   * 
   * @return  boolean
   */
  public function offsetExists($offset)
  {
    return in_array($offset, $this->revisions);
  }
  
  /**
   * Returns a new instance of the migration for the requested revision number (implements ArrayAccess interface).
   * 
   * @param   integer $offset A revision number
   * 
   * @return  sfPropelMigration
   * 
   * @throws  <b>InvalidArgumentException</b> If the revision number if invalid
   * @throws  <b>RuntimeException</b> If the migration class for the supplied revision number could not be found
   */
  public function offsetGet($offset)
  {
    if (!isset($this[$offset]))
    {
      throw new InvalidArgumentException(sprintf('No migration exists for revision number "%s".', $offset));
    }
    
    if (!self::$autoloadRegistered)
    {
      $autoload = sfSimpleAutoload::getInstance();
      $autoload->addDirectory($this->getMigrationsDir());
      $autoload->register();
      
      self::$autoloadRegistered = true;
    }
    
    if (!class_exists($class = $this->translateRevisionToClassName($offset)))
    {
      throw new RuntimeException(sprintf('The migration class "%s" does not exist.', $class));
    }
    
    return new $class;
  }
  
  /**
   * Throws an exception (read-only).
   * 
   * @throws LogicException
   */
  public function offsetSet($offset, $value)
  {
    throw new LogicException('Cannot set (read-only).');
  }
  
  /**
   * Throws an exception (read-only).
   * 
   * @throws LogicException
   */
  public function offsetUnset($offest)
  {
    throw new LogicException('Cannot unset (read-only).');
  }
  
  /**
   * Returns the number of migrations (implements Countable interface).
   * 
   * @return  integer
   */
  public function count()
  {
    return count($this->revisions);
  }
  
  /**
   * Log a message.
   */
  protected function logMessage($message)
  {
    if (!is_null($this->formatter))
    {
      $message = $this->formatter->formatSection('migration', $message);
    }
    
    $this->configuration->getEventDispatcher()->notify(new sfEvent($this, 'command.log', array($message)));
  }
  
  /**
   * Log a migration to the database.
   * 
   * @param   integer $to
   * @param   boolean $manual
   * 
   * @return  boolean
   */
  protected function logMigration($to, $manual = false)
  {
    $from = $this->getCurrentRevision();
    
    $stmt = $this->getConnection()->prepare($this->getLogQuery('insert'));
    $stmt->bindValue(':revision_from', $from, PDO::PARAM_INT);
    $stmt->bindValue(':revision_to', $to, PDO::PARAM_INT);
    $stmt->bindValue(':manual', $manual ? 1 : 0, PDO::PARAM_INT);
    
    try
    {
      $stmt->execute();
    }
    catch (PDOException $e)
    {
      $this->getConnection()->exec($this->getLogQuery('create'));
      $stmt->execute();
    }
    
    $this->currentRevision = $to;
    
    $this->logMessage(sprintf('Schema%s migrated from revision %d to %d.', $manual ? ' manually' : null, $from, $to));
  }
}
