<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Manages behaviors for propel.
 * 
 * Implements listeners for propel events and distributes events to behaviors.
 * 
 * @package     sfPropelPlugin
 * @subpackage  behavior
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
class sfPropelBehaviorManager
{
  static protected
    $instance = null;

  protected
    $dispatcher = null,
    $behaviors  = array();

  /**
   * Returns the current behavior manager.
   * 
   * @return  sfPropelBehaviorManager
   */
  static public function getInstance()
  {
    $dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();

    // compare dispatchers so functional tests work
    if (is_null(self::$instance) || $dispatcher != self::$instance->getEventDispatcher())
    {
      self::$instance = new sfPropelBehaviorManager($dispatcher);
    }

    return self::$instance;
  }

  /**
   * Constructor.
   * 
   * @param   sfEventDispatcher $dispatcher
   */
  public function __construct(sfEventDispatcher $dispatcher)
  {
    $this->initialize($dispatcher);
  }

  /**
   * Initializes the object.
   * 
   * @param   sfEventDispatcher $dispatcher
   */
  public function initialize(sfEventDispatcher $dispatcher)
  {
    $this->dispatcher = $dispatcher;
  }

  /**
   * Returns the event dispatcher.
   * 
   * @return  sfEventDispatcher
   */
  public function getEventDispatcher()
  {
    return $this->dispatcher;
  }

  /**
   * Registers event listeners to a behavior name.
   * 
   * @param   string  $name
   * @param   array   $listeners
   */
  public function register($name, array $listeners)
  {
    $listener = array($this, 'listen');

    if (!isset($this->behaviors[$name]))
    {
      $this->behaviors[$name] = array();
    }
    foreach ($listeners as $eventName => $callable)
    {
      if (0 !== strpos($eventName, 'propel.'))
      {
        $eventName = 'propel.'.$eventName;
      }
      if (!isset($this->behaviors[$name][$eventName]))
      {
        $this->behaviors[$name][$eventName] = array();
      }
      $this->behaviors[$name][$eventName][] = $callable;

      if (!in_array($listener, $this->dispatcher->getListeners($eventName)))
      {
        $this->dispatcher->connect($eventName, $listener);
      }
    }
  }

  /**
   * Adds a behavior to a model class.
   * 
   * @param   string  $class
   * @param   mixed   $behaviors
   */
  public function add($class, $behaviors)
  {
    // translate to a valid peer class name
    $peer = $class;
    if (@is_subclass_of($peer, 'BaseObject'))
    {
      $peer .= 'Peer';
    }
    if ((!class_exists($peer) && !class_exists($peer = 'Base'.$peer)) || is_null(@constant($peer.'::CLASS_DEFAULT')))
    {
      throw new InvalidArgumentException(sprintf('The corresponding peer class for "%s" could not be determined.', $class));
    }
    if (!method_exists($peer, 'addBehavior'))
    {
      throw new RuntimeException('Behaviors do not appear to be enabled. Please add "propel.builder.addBehaviors = true" to your propel.ini and run "symfony propel:build-model".');
    }

    if (!is_array($behaviors))
    {
      $behaviors = array($behaviors);
    }

    foreach ($behaviors as $name => $parameters)
    {
      if (is_int($name))
      {
        $name = $parameters;
        $parameters = array();
      }

      if (!isset($this->behaviors[$name]))
      {
        throw new InvalidArgumentException(sprintf('Propel behavior "%s" is not registered', $name));
      }

      // filter parameters
      $event = $this->dispatcher->filter(new sfEvent($this, 'propel_behavior.filter_parameters', array('peer' => $peer, 'behavior' => $name)), $parameters);
      $parameters = $event->getReturnValue();

      // pre-add event
      $event = $this->dispatcher->notifyUntil(new sfEvent($this, 'propel_behavior.pre_add', array('peer' => $peer, 'behavior' => $name, 'parameters' => $parameters)));
      if ($event->isProcessed())
      {
        return;
      }

      // provides backward compatibility
      sfPropelBehaviorCompat::registerParameters($name, $class, $parameters);

      // add behavior and parameters to the peer class
      call_user_func(array($peer, 'addBehavior'), $name, $parameters);

      // post-add event
      $this->dispatcher->notify(new sfEvent($this, 'propel_behavior.post_add', array('peer' => $peer, 'behavior' => $name, 'parameters' => $parameters)));
    }
  }

  /**
   * Listens to and distributes propel events.
   * 
   * @param   sfEventPropel $event
   * 
   * @return  boolean
   */
  public function listen(sfEventPropel $event)
  {
    // subject is either object or peer class name
    $subject = $event->getSubject();
    $peer = $subject instanceof BaseObject ? $subject->getPeer() : $subject;

    // loop through subject's behaviors for a match to the event name
    foreach (call_user_func(array($peer, 'getBehaviorNames')) as $name)
    {
      if (isset($this->behaviors[$name][$event->getName()]))
      {
        // user parameters
        $event['parameters'] = call_user_func(array($peer, 'getBehaviorParameters'), $name);

        foreach ($this->behaviors[$name][$event->getName()] as $callable)
        {
          if (call_user_func($callable, $event) && $event->expectsReturnValue())
          {
            return true;
          }
        }

        unset($event['parameters']);
      }
    }

    return false;
  }
}
