<?php

require_once dirname(__FILE__).'/sfPropelBaseTask.class.php';

/**
 * Base class for all migration tasks.
 * 
 * @package     sfPropelPlugin
 * @subpackage  task
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
abstract class sfPropelMigrationBaseTask extends sfPropelBaseTask
{
  /**
   * Constructor.
   * 
   * Enables or disables all migrations tasks per the propel.ini directive.
   * 
   * @see sfTask
   */
  public function __construct(sfEventDispatcher $dispatcher, sfFormatter $formatter)
  {
    $this->initialize($dispatcher, $formatter);

    if (!$this->getBuildProperty('disableMigrations'))
    {
      $this->configure();
    }
  }
}
