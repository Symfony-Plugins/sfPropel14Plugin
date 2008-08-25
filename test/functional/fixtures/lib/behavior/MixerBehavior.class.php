<?php

/**
 * Tests backward compatbility with sfMixer-style behaviors.
 */
class MixerBehavior
{
  static public
    $traces          = array(),
    $preSaveReturn   = null,
    $preDeleteReturn = null,
    $preInsertReturn = null,
    $preUpdateReturn = null;

  static public function reset()
  {
    self::$traces = array();
    self::$preSaveReturn = null;
    self::$preDeleteReturn = null;
    self::$preInsertReturn = null;
    self::$preUpdateReturn = null;
  }

  // object hooks

  static public function preSave(BaseObject $object, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
    return self::$preSaveReturn;
  }

  static public function postSave(BaseObject $object, $con, $affectedRows)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function preDelete(BaseObject $object, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
    return self::$preDeleteReturn;
  }

  static public function postDelete(BaseObject $object, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  // peer hooks

  static public function preInsert($peer, $values, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
    return self::$preInsertReturn;
  }

  static public function postInsert($peer, $values, $con, $insertId)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function preUpdate($peer, $values, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
    return self::$preUpdateReturn;
  }

  static public function postUpdate($peer, $values, $con, $affectedRows)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function doSelectStmt($peer, $c, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function doSelectJoin($peer, $c, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function doSelectJoinAll($peer, $c, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function doSelectJoinAllExcept($peer, $c, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }

  static public function doCount($peer, $c, $con)
  {
    self::$traces[] = array('method' => __FUNCTION__, 'arguments' => func_get_args());
  }
}
