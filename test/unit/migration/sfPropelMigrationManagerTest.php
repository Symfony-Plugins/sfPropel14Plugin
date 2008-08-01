<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(14, new lime_output_color);

$_root_dir = realpath(dirname(__FILE__).'/../../functional/fixtures');
$configuration = new sfProjectConfiguration($_root_dir);
$manager = new sfPropelMigrationManager($configuration);

$t->ok(is_null($manager->getTargetRevision()), '"->targetRevision" defaults to "null"');

try
{
  $manager->execute();
  $t->fail('->execute() throws an exception if no target revision has been set');
}
catch (Exception $e)
{
  $t->pass('->execute() throws an exception if no target revision has been set');
}

$t->is($manager->getHeadRevision(), 2, '->getHeadRevision() returns the maximum available revision');
$t->is($manager->getMigrationsDir(), $_root_dir.'/data/migrations', '->getMigrationsDir() returns the migrations directory');
$t->is($manager->translateNameToRevision('Migration010'), 10, '->translateNameToRevision() understands a class name');
$t->is($manager->translateNameToRevision('Migration010.class.php'), 10, '->translateNameToRevision() understands a basename');
$t->is($manager->translateRevisionToClassName(10), 'Migration010', '->translateRevisionToClassName() creates a proper class name');

$t->ok(isset($manager[1]), '->offsetExists() returns "true" if revision exists');
$t->ok(!isset($manager[100]), '->offsetExists() returns "false" if no revision exists');
$t->isa_ok($manager[1], 'Migration001', '->offsetGet() returns a migration instance');
try
{
  $manager[100];
  $t->fail('->offsetGet() throws an exception if no revision exists');
}
catch (Exception $e)
{
  $t->pass('->offsetGet() throws an exception if no revision exists');
}

try
{
  $manager[100] = true;
  $t->fail('->offsetSet() throws an exception (read-only)');
}
catch (Exception $e)
{
  $t->pass('->offsetSet() throws an exception (read-only)');
}

try
{
  unset($manager[1]);
  $t->fail('->offsetUnset() throws an exception (read-only)');
}
catch (Exception $e)
{
  $t->pass('->offsetUnset() throws an exception (read-only)');
}

$t->is(count($manager), 2, '->count() returns the number of revisions available');




