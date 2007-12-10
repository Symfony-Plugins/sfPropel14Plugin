<?php

/*
* This file is part of the symfony package.
* (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * A symfony database driver for Propel, derived from the native Creole driver.
 *
 * <b>Optional parameters:</b>
 *
 * # <b>datasource</b>     - [symfony] - datasource to use for the connection
 * # <b>is_default</b>     - [false]   - use as default if multiple connections
 *                                       are specified. The parameters
 *                                       that has been flagged using this param
 *                                       is be used when Propel is initialized
 *                                       via sfPropelAutoload.
 *
 * @package    symfony
 * @subpackage database
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelDatabase extends sfDatabase
{
  static protected $config = array();

  public function initialize($parameters = null, $name = 'propel')
  {
    parent::initialize($parameters);

    if (!$this->hasParameter('datasource'))
    {
      $this->setParameter('datasource', $name);
    }

    $this->addConfig();

    $is_default = $this->getParameter('is_default', false);

    // first defined if none listed as default
    if ($is_default || count(self::$config['propel']['datasources']) == 1)
    {
      $this->setDefaultConfig();
    }

    Propel::setConfiguration(self::$config);

    if($this->getParameter('pooling', false))
    {
      Propel::enableInstancePooling();
    }
    else
    {
      Propel::disableInstancePooling();
    }

    Propel::initialize();
  }

  public function setDefaultConfig()
  {
    self::$config['propel']['datasources']['default'] = $this->getParameter('datasource');
  }

  public function addConfig()
  {
    if ($dsn = $this->getParameter('dsn'))
    {
      $params = array();

      // check for non-pdo dsn - to be backwards compatable
      if (false !== strpos($dsn, '//'))
      {
        // derive pdo dsn (etc) from old style dsn
        $params = Creole::parseDSN($dsn);;

        $dsn = $params['phptype'] . ':dbname=' . $params['database'] . ';host=' . $params['hostspec'];
        $this->setParameter('dsn', $dsn);
      }
      else
      {
        $params = $this->parseDsn($dsn);
      }

      $options = array('dsn', 'phptype', 'hostspec', 'database', 'username', 'password', 'port', 'protocol');
      foreach ($options as $option)
      {
        if (!$this->getParameter($option) && isset($params[$option]))
        {
          $this->setParameter($option, $params[$option]);
        }
      }

    }

    self::$config['propel']['datasources'][$this->getParameter('datasource')] =
    array(
    'adapter'      => $this->getParameter('phptype'),
    'connection'   =>
    array(
    'dsn'        => $this->getParameter('dsn'),
    'user'       => $this->getParameter('username'),
    'password'   => $this->getParameter('password'),
    'encoding'   => $this->getParameter('encoding'),
    'persistent' => $this->getParameter('persistent'),
    )
    );
  }

  /**
   * parse the new styled dsn, really i only want to grab the 'phptype' out
   *
   * @param string $dsn
   * @return array
   */
  private function parseDsn($dsn)
  {
    return array('phptype' => substr($dsn, 0, strpos($dsn, ':')));
  }

  public static function getConfiguration()
  {
    return self::$config;
  }

  public function setConnectionParameter($key, $value)
  {
    if ($key == 'host')
    {
      $key = 'hostspec';
    }

    self::$config['propel']['datasources'][$this->getParameter('datasource')]['connection'][$key] = $value;
    $this->setParameter($key, $value);
  }

  /**
   * Connect to the database.
   * Stores the PDO connection in $connection
   *
   */
  public function connect ()
  {
    Propel::setConfiguration(self::$config);
    $this->connection = Propel::getConnection();
  }

  /**
   * Execute the shutdown procedure.
   *
   * @return void
   *
   * @throws <b>sfDatabaseException</b> If an error occurs while shutting down this database.
   */
  public function shutdown ()
  {
    if ($this->connection !== null)
    {
      @$this->connection = null;
    }
  }
}
