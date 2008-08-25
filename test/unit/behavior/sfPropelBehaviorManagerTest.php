<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(8, new lime_output_color);

class SomeBehavior
{
  static public $parameters = array();

  static public function someCallable(sfEventPropel $event)
  {
    self::$parameters = $event['parameters'];

    return true;
  }
}

class BaseObject
{
}

class Duck extends BaseObject
{
  public function getPeer()
  {
    return new DuckPeer;
  }
}

class DuckPeer
{
  const CLASS_DEFAULT = 'foo';

  static public $behaviors = array();

  static public function addBehavior($name, $parameters = array())
  {
    self::$behaviors[$name] = $parameters;
  }

  static public function getBehaviorNames()
  {
    return array_keys(self::$behaviors);
  }

  static public function getBehaviorParameters($name)
  {
    return self::$behaviors[$name];
  }
}

class GoosePeer
{
  const CLASS_DEFAULT = 'foo';
}

$dispatcher = new sfEventDispatcher;
$manager    = new sfPropelBehaviorManager($dispatcher);

$t->diag('->register()');
$manager->register('some_behavior', array('method_not_found' => array('SomeBehavior', 'someCallable')));
$t->ok(in_array(array($manager, 'listen'), $dispatcher->getListeners('propel.method_not_found')), '->register() connects a listener to the event dispatcher');

$t->diag('->add()');
$manager->add('Duck', array('some_behavior' => array('foo' => 'bar')));
$t->is_deeply(DuckPeer::$behaviors, array('some_behavior' => array('foo' => 'bar')), '->add() stores parameters to peer class');
$t->is(sfConfig::get('propel_behavior_some_behavior_Duck_foo'), 'bar', '->add() stores parameters to sfConfig');

try
{
  $manager->add('BaseObject', 'some_behavior');
  $t->fail('->add() throws an exception if peer class cannot be determined');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->add() throws an exception if peer class cannot be determined');
}

try
{
  $manager->add('GoosePeer', 'some_behavior');
  $t->fail('->add() throws an exception if behaviors are not enabled');
}
catch (RuntimeException $e)
{
  $t->pass('->add() throws an exception if behaviors are not enabled');
}

try
{
  $manager->add('Duck', 'unregistered_behavior');
  $t->fail('->add() throws an exception if the behavior name has not been registered');
}
catch (InvalidArgumentException $e)
{
  $t->pass('->add() throws an exception if the behavior name has not been registered');
}

$t->diag('->listen()');
$event = $dispatcher->notifyUntil(new sfEventPropel(new Duck, 'propel.method_not_found'));
$t->ok($event->isProcessed(), '->listen() processes the event object');
$t->is_deeply(SomeBehavior::$parameters, array('foo' => 'bar'), '->listen() adds user parameters to event object');
