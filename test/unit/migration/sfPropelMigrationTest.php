<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(6, new lime_output_color);

class BadMigrationClassName extends sfPropelMigration
{
  protected function up() { }
  protected function down() { }
}

class Migration001 extends sfPropelMigration
{
  public function configure()
  {
    $this->description = 'A test schema migration';
  }
  
  protected function up() { }
  protected function down() { }
  
  public function testExecuteSqlFromFile($file)
  {
    return $this->executeSqlFromFile($file);
  }
}

try
{
  new BadMigrationClassName;
  $t->fail('"Constructor" throws an exception on bad class name');
}
catch (Exception $e)
{
  $t->pass('"Constructor" throws an exception on bad class name');
}

$migration = new Migration001;
$t->isa_ok($migration, 'Migration001', '"Object" created');

$t->ok(1 === $migration->executeUp(), '->executeUp() returns the resulting revision');
$t->ok(0 === $migration->executeDown(), '->executeDown() returns the resulting revision');

$t->is($migration->getDescription(), 'A test schema migration', '->getDescription() returns the description');

try
{
  $migration->testExecuteSqlFromFile('/tmp/'.time());
  $t->fail('->executeSqlFromFile() throws an exception if the file does not exist');
}
catch (Exception $e)
{
  $t->pass('->executeSqlFromFile() throws an exception if the file does not exist');
}



