<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$app = 'frontend';
if (!include dirname(__FILE__).'/../bootstrap/functional.php')
{
  return;
}

$b = new sfTestBrowser;
$t = $b->test();

$t->is($b->get('behavior/has')->getResponse()->getContent(), 'yes', 'model has behavior');

$t->diag(':save:pre Peer:doInsert:pre Peer:doInsert:post :save:post');
MixerBehavior::reset();
$b->get('behavior/save');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 8, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'preSave', '":save:pre" hook was run');
$t->is(count($traces[0]['arguments']), 2, '":save:pre" hook received the correct number of arguments');
$t->isa_ok($traces[0]['arguments'][0], 'Book', '":save:pre" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'PropelPDO', '":save:pre" hook received the correct second argument');
$t->is($traces[1]['method'], 'preInsert', '"Peer:doInsert:pre" hook was run');
$t->is(count($traces[1]['arguments']), 3, '"Peer:doInsert:pre" hook received the correct number of arguments');
$t->is($traces[1]['arguments'][0], 'BaseBookPeer', '"Peer:doInsert:pre" hook received the correct first argument');
$t->isa_ok($traces[1]['arguments'][1], 'Book', '"Peer:doInsert:pre" hook received the correct second argument');
$t->isa_ok($traces[1]['arguments'][2], 'PropelPDO', '"Peer:doInsert:pre" hook received the correct third argument');
$t->is($traces[2]['method'], 'postInsert', '"Peer:doInsert:post" hook was run');
$t->is(count($traces[2]['arguments']), 4, '"Peer:doInsert:post" received the correct number of arguments');
$t->is($traces[2]['arguments'][0], 'BaseBookPeer', '"Peer:doInsert:post" hook received the correct first argument');
$t->isa_ok($traces[2]['arguments'][1], 'Book', '"Peer:doInsert:post" hook received the correct second argument');
$t->isa_ok($traces[2]['arguments'][2], 'PropelPDO', '"Peer:doInsert:post" hook received the correct third argument');
$t->ok(is_numeric($traces[2]['arguments'][3]), '"Peer:doInsert:post" hook received the correct fourth argument');
$t->is($traces[3]['method'], 'postSave', '":save:post" hook was run');
$t->is(count($traces[3]['arguments']), 3, '":save:post" hook received the correct number of arguments');
$t->isa_ok($traces[3]['arguments'][0], 'Book', '":save:post" hook received the correct first argument');
$t->isa_ok($traces[3]['arguments'][1], 'PropelPDO', '":save:post" hook received the correct second argument');
$t->ok(is_numeric($traces[3]['arguments'][2]), '":save:post" hook received the correct third argument');
$t->is($traces[5]['method'], 'preUpdate', '"Peer:doUpdate:pre" hook was run');
$t->is(count($traces[5]['arguments']), 3, '"Peer:doUpdate:pre" hook received the correct number of arguments');
$t->is($traces[5]['arguments'][0], 'BaseBookPeer', '"Peer:doUpdate:pre" hook received the correct first argument');
$t->isa_ok($traces[5]['arguments'][1], 'Book', '"Peer:doUpdate:pre" hook received the correct second argument');
$t->isa_ok($traces[5]['arguments'][2], 'PropelPDO', '"Peer:doUpdate:pre" hook received the correct third argument');
$t->is($traces[6]['method'], 'postUpdate', '"Peer:doUpdate:post" hook was run');
$t->is(count($traces[6]['arguments']), 4, '"Peer:doUpdate:post" hook received the correct number of arguments');
$t->is($traces[6]['arguments'][0], 'BaseBookPeer', '"Peer:doUpdate:post" hook received the correct first argument');
$t->isa_ok($traces[6]['arguments'][1], 'Book', '"Peer:doUpdate:post" hook received the correct second argument');
$t->isa_ok($traces[6]['arguments'][2], 'PropelPDO', '"Peer:doUpdate:post" hook received the correct third argument');
$t->ok(is_numeric($traces[6]['arguments'][3]), '"Peer:doUpdate:post" hook received the correct fourth argument');

$t->diag(':save:pre with break');
MixerBehavior::reset();
MixerBehavior::$preSaveReturn = 1;
$b->get('behavior/save');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 2, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'preSave', '":save:pre" hook was run (insert)');
$t->is($traces[1]['method'], 'preSave', '":save:pre" hook was run (update)');

$t->diag(':delete:pre :delete:post');
MixerBehavior::reset();
$b->get('behavior/delete');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 6, 'the correct number of hooks were run');
$t->is($traces[4]['method'], 'preDelete', '":delete:pre" hook was run');
$t->is(count($traces[4]['arguments']), 2, '":delete:pre" hook received the correct number of arguments');
$t->isa_ok($traces[4]['arguments'][0], 'Book', '":delete:pre" hook received the correct first argument');
$t->isa_ok($traces[4]['arguments'][1], 'PropelPDO', '":delete:pre" hook received the correct second argument');
$t->is($traces[5]['method'], 'postDelete', '":delete:post" hook was run');
$t->is(count($traces[5]['arguments']), 2, '":delete:post" hook received the correct number of arguments');
$t->isa_ok($traces[5]['arguments'][0], 'Book', '":delete:post" hook received the correct first argument');
$t->isa_ok($traces[5]['arguments'][1], 'PropelPDO', '":delete:post" hook received the correct second argument');

$t->diag(':delete:pre with break');
MixerBehavior::reset();
MixerBehavior::$preDeleteReturn = 1;
$b->get('behavior/delete');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 5, 'the correct number of hooks were run');
$t->is($traces[4]['method'], 'preDelete', '":delete:pre" hook was run');

$t->diag('Peer:doSelectStmt');
MixerBehavior::reset();
$b->get('behavior/select');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 1, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'doSelectStmt', '"Peer:doSelectStmt" hook was run');
$t->is(count($traces[0]['arguments']), 3, '"Peer:doSelectStmt" hook received the correct number of arguments');
$t->is($traces[0]['arguments'][0], 'BaseArticlePeer', '"Peer:doSelectStmt" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'Criteria', '"Peer:doSelectStmt" hook received the correct second argument');
$t->isa_ok($traces[0]['arguments'][2], 'PropelPDO', '"Peer:doSelectStmt" hook received the correct third argument');

$t->diag('Peer:doSelectJoin');
MixerBehavior::reset();
$b->get('behavior/selectJoin');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 1, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'doSelectJoin', '"Peer:doSelectJoin" hook was run');
$t->is(count($traces[0]['arguments']), 3, '"Peer:doSelectJoin" hook received the correct number of arguments');
$t->is($traces[0]['arguments'][0], 'BaseArticlePeer', '"Peer:doSelectJoin" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'Criteria', '"Peer:doSelectJoin" hook received the correct second argument');
$t->ok(is_null($traces[0]['arguments'][2]), '"Peer:doSelectJoin" hook received the correct third argument');

$t->diag('Peer:doSelectJoinAll');
MixerBehavior::reset();
$b->get('behavior/selectJoinAll');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 1, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'doSelectJoinAll', '"Peer:doSelectJoinAll" hook was run');
$t->is(count($traces[0]['arguments']), 3, '"Peer:doSelectJoinAll" hook received the correct number of arguments');
$t->is($traces[0]['arguments'][0], 'BaseArticlePeer', '"Peer:doSelectJoinAll" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'Criteria', '"Peer:doSelectJoinAll" hook received the correct second argument');
$t->ok(is_null($traces[0]['arguments'][2]), '"Peer:doSelectJoinAll" hook received the correct third argument');

$t->diag('Peer:doSelectJoinAllExcept');
MixerBehavior::reset();
$b->get('behavior/selectJoinAllExcept');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 1, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'doSelectJoinAllExcept', '"Peer:doSelectJoinAllExcept" hook was run');
$t->is(count($traces[0]['arguments']), 3, '"Peer:doSelectJoinAllExcept" hook received the correct number of arguments');
$t->is($traces[0]['arguments'][0], 'BaseArticlePeer', '"Peer:doSelectJoinAllExcept" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'Criteria', '"Peer:doSelectJoinAllExcept" hook received the correct second argument');
$t->ok(is_null($traces[0]['arguments'][2]), '"Peer:doSelectJoinAllExcept" hook received the correct third argument');

$t->diag('Peer:doCount');
MixerBehavior::reset();
$b->get('behavior/count');
$traces = MixerBehavior::$traces;

$t->is(count($traces), 1, 'the correct number of hooks were run');
$t->is($traces[0]['method'], 'doCount', '"Peer:doCount" hook was run');
$t->is(count($traces[0]['arguments']), 3, '"Peer:doCount" hook received the correct number of arguments');
$t->is($traces[0]['arguments'][0], 'BaseArticlePeer', '"Peer:doCount" hook received the correct first argument');
$t->isa_ok($traces[0]['arguments'][1], 'Criteria', '"Peer:doCount" hook received the correct second argument');
$t->isa_ok($traces[0]['arguments'][2], 'PropelPDO', '"Peer:doCount" hook received the correct third argument');
