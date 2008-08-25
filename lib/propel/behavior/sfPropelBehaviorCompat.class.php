<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Encapsulates backward compatibility for sfMixer-style behaviors.
 * 
 * @package     sfPropelPlugin
 * @subpackage  behavior
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
class sfPropelBehaviorCompat
{
  /**
   * Registers sfMixer-style methods to a behavior name.
   * 
   * @param   string  $name
   * @param   array   $callables
   */
  static public function registerMethods($name, array $callables)
  {
    $manager = sfPropelBehaviorManager::getInstance();
    foreach ($callables as $callable)
    {
      $func = create_function('$e', sprintf('return %s::listenForMethod($e, %s);', __CLASS__, var_export($callable, true)));
      $manager->register($name, array('method_not_found' => $func));
    }
  }

  /**
   * Registers sfMixer-style hooks to a behavior name.
   * 
   * @param   string  $name
   * @param   array   $hooks
   */
  static public function registerHooks($name, array $hooks)
  {
    $manager = sfPropelBehaviorManager::getInstance();
    foreach ($hooks as $hook => $callable)
    {
      $func = create_function('$e', sprintf('return %s::listenFor%sHook($e, \'%s\', %s);', __CLASS__, false !== strpos($hook, 'Peer') ? 'Peer' : null, $hook, var_export($callable, true)));
      $manager->register($name, array(self::translateHookToEventName($hook) => $func));
    }
  }

  /**
   * Registers user parameters for a class-behavior combination.
   * 
   * @param   string  $name
   * @param   string  $class
   * @param   array   $parameters
   */
  static public function registerParameters($name, $class, array $parameters)
  {
    foreach ($parameters as $key => $value)
    {
      sfConfig::set('propel_behavior_'.$name.'_'.$class.'_'.$key, $value);
    }
  }

  /**
   * Listens for a propel event on behalf of a sfMixer-style method.
   * 
   * @param   sfEventPropel $event
   * @param   mixed         $callable
   * 
   * @return  boolean
   */
  static public function listenForMethod(sfEventPropel $event, $callable)
  {
    $method = is_array($callable) ? $callable[1] : $callable;
    if ($method == $event['method'])
    {
      $arguments = $event['arguments'];
      array_unshift($arguments, $event->getSubject());

      $event->setReturnValue(call_user_func_array($callable, $arguments));

      return true;
    }

    return false;
  }

  /**
   * Listens for a propel event on behalf of a sfMixer-style hook.
   * 
   * @param   sfEventPropel $event
   * @param   string        $hook
   * @param   mixed         $callable
   * 
   * @return  boolean
   */
  static public function listenForHook(sfEventPropel $event, $hook, $callable)
  {
    $arguments = array($event->getSubject(), $event['connection']);

    if (':save:post' == $hook)
    {
      $arguments[] = $event['affected_rows'];
    }

    $event->setReturnValue(call_user_func_array($callable, $arguments));

    return true;
  }

  /**
   * Listens for a propel event on behalf of a sfMixer-style peer hook.
   * 
   * @param   sfEventPropel $event
   * @param   string        $hook
   * @param   mixed         $callable
   * 
   * @return  boolean
   */
  static public function listenForPeerHook(sfEventPropel $event, $hook, $callable)
  {
    // what was many hooks is now one event
    if ('propel.do_select' == $event->getName() && !self::doSelectHookMatchesMethod($hook, $event['method']))
    {
      return false;
    }

    $arguments = array($event->getSubject());

    // arguments ordered before the connection object
    if (isset($event['values']))
    {
      $arguments[] = $event['values'];
    }
    elseif (isset($event['criteria']))
    {
      $arguments[] = $event['criteria'];
    }

    $arguments[] = $event['connection'];

    // arguments ordered after the connection object
    switch ($hook)
    {
      case 'Peer:doUpdate:post':
        $arguments[] = $event['affected_rows'];
        break;

      case 'Peer:doInsert:post':
        $arguments[] = $event['insert_id'];
        break;
    }

    $event->setReturnValue(call_user_func_array($callable, $arguments));

    return true;
  }

  /**
   * Returns true if the hook and method names match.
   * 
   * @param   string $hook
   * @param   string $method
   * 
   * @return  boolean
   */
  static protected function doSelectHookMatchesMethod($hook, $method)
  {
    switch ($hook)
    {
      case 'Peer:doSelectRS':
        return 'doSelectStmt' == $method;

      case 'Peer:doSelectStmt':
      case 'Peer:doSelectJoinAll':
        return str_replace('Peer:', '', $hook) == $method;

      case 'Peer:doSelectJoin':
        return 0 === strpos($method, 'doSelectJoin') && false === strpos($method, 'doSelectJoinAll');

      case 'Peer:doSelectJoinAllExcept':
        return 0 === strpos($method, 'doSelectJoinAllExcept');
    }
  }

  /**
   * Translates a sfMixer-style hook name to a propel event name.
   * 
   * @param   string $hook
   * 
   * @return  string
   */
  static protected function translateHookToEventName($hook)
  {
    if (false !== strpos($hook, 'doSelect'))
    {
      return 'propel.do_select';
    }

    $eventName = $hook;
    $eventName = substr($eventName, 0 === strpos($eventName, 'Peer:') ? 5 : 1);
    $eventName = implode('_', array_reverse(array_unique(explode(':', $eventName))));
    $eventName = sfInflector::underscore($eventName);

    return 'propel.'.$eventName;
  }
}
