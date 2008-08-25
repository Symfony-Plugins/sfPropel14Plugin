<?php

/**
 * Event class used for propel behaviors.
 * 
 * @package     sfPropelPlugin
 * @subpackage  event
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
class sfEventPropel extends sfEvent
{
  protected
    $mutations = array();

  /**
   * Returns true if the event expects a return value.
   * 
   * This static logic identifies those events dispatched using
   * {@link sfEventDispatcher::notifyUntil()}.
   * 
   * @return  boolean
   */
  public function expectsReturnValue()
  {
    return 'propel.method_not_found' == $this->name || 0 === strpos($this->name, 'propel.pre_');
  }

  /**
   * Embeds a mutation to be processed by the subject.
   * 
   * @param   string  $key
   * @param   mixed   $value
   * 
   * @throws  InvalidArgumentException If the event subject is not a propel object
   */
  public function mutateObject($property, $value)
  {
    if (!$this->subject instanceof BaseObject)
    {
      throw new LogicException(sprintf('%s can only be used on object events.', __METHOD__));
    }

    $this->mutations[$property] = $value;
  }

  /**
   * Returns an associative array of values to be applied to object members.
   * 
   * @return  array
   */
  public function getMutations()
  {
    return $this->mutations;
  }
}
