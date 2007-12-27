<?php

/*
* This file is part of the symfony package.
* (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * A symfony logging adapter for Propel
 *
 * @package    symfony
 * @subpackage log
 * @author     Dustin Whittle <dustin.whittle@symfony-project.com>
 * @version    $Id:$
 */
class sfPropelLogger implements BasicLogger
{

	private $dispatcher = null;

	/**
	 * constructor for
	 *
	 * @param sfEventDispatcher $dispatcher
	 */
	public function __construct(sfEventDispatcher $dispatcher = null)
	{
	  if(is_null($this->dispatcher))
	  {
	    $this->dispatcher = sfContext::getInstance()->getEventDispatcher();
	  }
	  else
	  {
	    $this->dispatcher = $dispatcher;
	  }
	}

	/**
	 * A convenience function for logging an alert event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function alert($message)
	{
		$this->log($message, sfLogger::ALERT);
	}

	/**
	 * A convenience function for logging a critical event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function crit($message)
	{
		$this->log($message, sfLogger::CRITICAL);
	}

	/**
	 * A convenience function for logging an error event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function err($message)
	{
		$this->log($message, sfLogger::ERROR);
	}

	/**
	 * A convenience function for logging a warning event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function warning($message)
	{
		$this->log($message, sfLogger::WARNING);
	}


	/**
	 * A convenience function for logging an critical event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function notice($message)
	{
		$this->log($message, sfLogger::NOTICE);
	}
	/**
	 * A convenience function for logging an critical event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function info($message)
	{
		$this->log($message, sfLogger::INFO);
	}

	/**
	 * A convenience function for logging a debug event.
	 *
	 * @param mixed $message the message to log.
	 */
	public function debug($message)
	{
		$this->log($message, sfLogger::DEBUG);
	}

	/**
	 * Primary method to handle logging.
	 *
	 * @param mixed $message the message to log.
	 * @param int $severity The numeric severity. Defaults to null so that no assumptions are made about the logging backend.
	 */
	public function log($message, $severity = sfLogger::DEBUG)
	{
    // get a backtrace to pass class, function, file, & line to logger
		// $trace = debug_backtrace();
		// sprintf('%s->%s on line %s in file %s', $trace[2]['class'], $trace[2]['function'], $trace[1]['file'], $trace[1]['line']);

		$message = stripos('SELECT', $message) ? 'executeQuery: '.$message : 'executeUpdate: '.$message;

    $this->dispatcher->notify(new sfEvent($this, 'application.log', array('{Propel} '.$message, 'priority' => $severity)));
	}
}
