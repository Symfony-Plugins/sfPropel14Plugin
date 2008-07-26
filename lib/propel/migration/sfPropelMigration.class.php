<?php

/**
 * Base class for all migrations.
 * 
 * @package     sfPropelPlugin
 * @subpackage  migration
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version     SVN: $Id: sfMigration.class.php 10225 2008-07-11 18:29:49Z Kris.Wallsmith $
 */
abstract class sfPropelMigration
{
  protected
    $revision       = null,
    $connectionName = null,
    $connection     = null,
    $description    = null;
  
  /**
   * Constructor.
   */
  public function __construct()
  {
    if (preg_match('/\d+/', get_class($this), $match))
    {
      $this->revision = (int) $match[0];
    }
    else
    {
      throw new RuntimeException(sprintf('Schema revision number could not be extracted from migration class name (%s).', get_class($this)));
    }
    
    $this->configure();
  }
  
  /**
   * Configure this migration.
   */
  public function configure()
  {
  }
  
  /**
   * Execute the up migration.
   * 
   * @return  integer The resulting schema revision
   */
  public function executeUp()
  {
    $this->up();
    
    return $this->revision;
  }
  
  /**
   * Migrate the schema up from the previous version to the current one.
   */
  abstract protected function up();
  
  /**
   * Execute the down migration.
   * 
   * @return  integer The resulting schema revision
   */
  public function executeDown()
  {
    $this->down();
    
    return $this->revision - 1;
  }
  
  /**
   * Migrate the schema down to the previous version, i.e. undo the modifications made in up().
   */
  abstract protected function down();
  
  /**
   * Get a description of this migration.
   * 
   * @return  string
   */
  public function getDescription()
  {
    return $this->description;
  }
  
  /**
   * Execute SQL statements from a file.
   * 
   * @param   string $file
   * 
   * @return  integer Affected rows
   */
  protected function executeSqlFromFile($file)
  {
    if (!is_readable($file))
    {
      throw new InvalidArgumentException(sprintf('The SQL file %s does not exist or is not readable.', $file));
    }
    
    $affectedRows = 0;
    if ($queries = explode(';', file_get_contents($file)))
    {
      foreach ($queries as $query)
      {
        if ($query = trim($query))
        {
          $affectedRows += $this->exec($query);
        }
      }
    }
    
    return $affectedRows;
  }
  
  /**
   * Get the database connection object.
   * 
   * @return  PropelPDO
   */
  protected function getConnection()
  {
    if (is_null($this->connection))
    {
      $this->connection = Propel::getConnection($this->connectionName);
    }
    
    return $this->connection;
  }
  
  /**
   * @see PDO::exec()
   */
  protected function exec()
  {
    $args = func_get_args();
    return call_user_func_array(array($this->getConnection(), 'exec'), $args);
  }
  
  /**
   * @see PDO::query()
   */
  protected function query()
  {
    $args = func_get_args();
    return call_user_func_array(array($this->getConnection(), 'query'), $args);
  }
  
  /**
   * @see PropelPDO::prepare()
   */
  protected function prepare()
  {
    $args = func_get_args();
    return call_user_func_array(array($this->getConnection(), 'prepare'), $args);
  }
}
