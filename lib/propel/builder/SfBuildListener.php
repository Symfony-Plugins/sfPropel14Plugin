<?php

/**
 * Translates phing to symfony events.
 * 
 * @package     sfPropelPlugin
 * @subpackage  builder
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
class SfBuildListener implements BuildListener
{
  protected
    $dispatcher = null;

  public function __construct()
  {
    $this->dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();
  }

  public function buildStarted(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.build_started'));
  }

  public function buildFinished(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.build_finished'));
  }

  public function targetStarted(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.target_started'));
  }

  public function targetFinished(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.target_finished'));
  }

  public function taskStarted(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.task_started'));
  }

  public function taskFinished(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.task_finished'));
  }

  public function messageLogged(BuildEvent $event)
  {
    $this->dispatcher->notify(new sfEvent($event, 'phing.message_logged'));
  }
}
