<?php

class frontendConfiguration extends sfApplicationConfiguration
{
  public function configure()
  {
  }

  public function loadPluginConfig()
  {
    parent::loadPluginConfig();

    sfPropelBehavior::registerHooks('mixer_behavior', array(
      ':save:pre'                  => array('MixerBehavior', 'preSave'),
      ':save:post'                 => array('MixerBehavior', 'postSave'),
      ':delete:pre'                => array('MixerBehavior', 'preDelete'),
      ':delete:post'               => array('MixerBehavior', 'postDelete'),
      'Peer:doSelectStmt'          => array('MixerBehavior', 'doSelectStmt'),
      'Peer:doSelectJoin'          => array('MixerBehavior', 'doSelectJoin'),
      'Peer:doSelectJoinAll'       => array('MixerBehavior', 'doSelectJoinAll'),
      'Peer:doSelectJoinAllExcept' => array('MixerBehavior', 'doSelectJoinAllExcept'),
      'Peer:doInsert:pre'          => array('MixerBehavior', 'preInsert'),
      'Peer:doInsert:post'         => array('MixerBehavior', 'postInsert'),
      'Peer:doUpdate:pre'          => array('MixerBehavior', 'preUpdate'),
      'Peer:doUpdate:post'         => array('MixerBehavior', 'postUpdate'),
      'Peer:doCount'               => array('Mixerbehavior', 'doCount'),
    ));
  }
}
