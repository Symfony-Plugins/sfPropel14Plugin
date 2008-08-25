<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(38, new lime_output_color);

class sfPropelBehaviorCompatTest extends sfPropelBehaviorCompat
{
  static public function translateHookToEventNameTest($hook)
  {
    return self::translateHookToEventName($hook);
  }

  static public function doSelectHookMatchesMethodTest($hook, $method)
  {
    return self::doSelectHookMatchesMethod($hook, $method);
  }
}

class Behavior
{
  static public function customMethod($object)
  {
    if ('foo' == $object)
    {
      return '==RETURN==';
    }
  }

  static public function preSave1($object, $connection)
  {
    return 1;
  }

  static public function preSave2($object, $connection)
  {
  }
}

$t->diag('->translateHookToEventName()');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest(':save:pre'), 'propel.pre_save');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest(':save:post'), 'propel.post_save');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest(':delete:pre'), 'propel.pre_delete');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest(':delete:post'), 'propel.post_delete');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doCount'), 'propel.do_count');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doSelectRS'), 'propel.do_select');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doSelectStmt'), 'propel.do_select');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doSelectJoin'), 'propel.do_select');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doSelectJoinAll'), 'propel.do_select');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doSelectJoinAllExcept'), 'propel.do_select');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doUpdate:pre'), 'propel.pre_do_update');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doUpdate:post'), 'propel.post_do_update');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doInsert:pre'), 'propel.pre_do_insert');
$t->is(sfPropelBehaviorCompatTest::translateHookToEventNameTest('Peer:doInsert:post'), 'propel.post_do_insert');

$t->diag('->doSelectHookMatchesMethod()');
$t->ok(sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectStmt', 'doSelectStmt'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectStmt', 'doSelectJoinFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectStmt', 'doSelectJoinAll'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectStmt', 'doSelectJoinAllExcepFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoin', 'doSelectStmt'));
$t->ok(sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoin', 'doSelectJoinFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoin', 'doSelectJoinAll'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoin', 'doSelectJoinAllExcepFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAll', 'doSelectStmt'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAll', 'doSelectJoinFoo'));
$t->ok(sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAll', 'doSelectJoinAll'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAll', 'doSelectJoinAllExcepFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAllExcept', 'doSelectStmt'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAllExcept', 'doSelectJoinFoo'));
$t->ok(!sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAllExcept', 'doSelectJoinAll'));
$t->ok(sfPropelBehaviorCompatTest::doSelectHookMatchesMethodTest('Peer:doSelectJoinAllExcept', 'doSelectJoinAllExceptFoo'));

$t->diag('->listenForMethod()');
$processed = sfPropelBehaviorCompat::listenForMethod($event = new sfEventPropel('foo', 'propel.method_not_found', array(
  'method'    => 'customMethod',
  'arguments' => array(),
)), array('Behavior', 'customMethod'));
$t->ok($processed, '->listenForMethod() processes a mixer method if method names match');
$t->is($event->getReturnValue(), '==RETURN==', '->listenForMethod() sets a return value');
$processed = sfPropelBehaviorCompat::listenForMethod($event = new sfEventPropel('foo', 'propel.method_not_found', array(
  'method'    => 'someOtherMethod',
  'arguments' => array(),
)), array('Behavior', 'customMethod'));
$t->ok(!$processed, '->listenForMethod() does not process if the method name does not match');
$t->ok(!$event->getReturnValue(), '->listenForMethod() does not set a return value if method names do not match');

$t->diag('->listenForHook()');
$processed = sfPropelBehaviorCompat::listenForHook($event = new sfEventPropel('foo', 'propel.pre_save', array('connection' => null)), ':save:pre', array('Behavior', 'preSave1'));
$t->ok($processed, '->listenForHook() processes a mixer hook');
$t->is($event->getReturnValue(), 1, '->listenForHook() captures a return value');
$processed = sfPropelBehaviorCompat::listenForHook($event = new sfEventPropel('foo', 'propel.pre_save', array('connection' => null)), ':save:pre', array('Behavior', 'preSave2'));
$t->ok($processed, '->listenForHook() processes a mixer hook that does not return anything');
$t->ok(is_null($event->getReturnValue()), '->listenForHook() has no return value when hook has none');
