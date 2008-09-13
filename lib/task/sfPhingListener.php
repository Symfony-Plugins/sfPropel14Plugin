<?php

class sfPhingListener implements BuildListener
{
  static protected
    $errors = array();

  static public function getErrors()
  {
    return self::$errors;
  }

  /**
   * Fired before any targets are started.
   *
   * @param BuildEvent The BuildEvent
   */
  public function buildStarted(BuildEvent $event)
  {
    self::$errors = array();
  }

  /**
   * Fired after the last target has finished.
   *
   * @param BuildEvent The BuildEvent
   * @see BuildEvent::getException()
   */
  public function buildFinished(BuildEvent $event)
  {
  }

  /**
   * Fired when a target is started.
   *
   * @param BuildEvent The BuildEvent
   * @see BuildEvent::getTarget()
   */
  public function targetStarted(BuildEvent $event)
  {
  }

  /**
   * Fired when a target has finished.
   *
   * @param BuildEvent The BuildEvent
   * @see BuildEvent#getException()
   */
  public function targetFinished(BuildEvent $event)
  {
    if (!is_null($event->getException()))
    {
      self::$errors[] = $event->getException();
    }
  }

  /**
   * Fired when a task is started.
   *
   * @param BuildEvent The BuildEvent
   * @see BuildEvent::getTask()
   */
  public function taskStarted(BuildEvent $event)
  {
  }

  /**
   *  Fired when a task has finished.
   *
   *  @param BuildEvent The BuildEvent
   *  @see BuildEvent::getException()
   */
  public function taskFinished(BuildEvent $event)
  {
  }

  /**
   *  Fired whenever a message is logged.
   *
   *  @param BuildEvent The BuildEvent
   *  @see BuildEvent::getMessage()
   */
  public function messageLogged(BuildEvent $event)
  {
  }
}
