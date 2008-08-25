<?php

class behaviorActions extends sfActions
{
  public function executeHas()
  {
    return $this->renderText(BookPeer::hasBehavior('mixer_behavior') ? 'yes' : 'no');
  }

  public function executeSave()
  {
    $book = new Book;
    $book->save();

    $book->setName('my book');
    $book->save();

    return sfView::NONE;
  }

  public function executeDelete()
  {
    $book = new Book;
    $book->save();

    $book->delete();

    return sfView::NONE;
  }

  public function executeSelect()
  {
    ArticlePeer::doSelect(new Criteria);

    return sfView::NONE;
  }

  public function executeSelectJoin()
  {
    ArticlePeer::doSelectJoinBook(new Criteria);

    return sfView::NONE;
  }

  public function executeSelectJoinAll()
  {
    ArticlePeer::doSelectJoinAll(new Criteria);

    return sfView::NONE;
  }

  public function executeSelectJoinAllExcept()
  {
    ArticlePeer::doSelectJoinAllExceptBook(new Criteria);

    return sfView::NONE;
  }

  public function executeCount()
  {
    ArticlePeer::doCount(new Criteria);

    return sfView::NONE;
  }
}
