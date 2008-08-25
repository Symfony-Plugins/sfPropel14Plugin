<?php

require_once 'propel/engine/builder/om/php5/PHP5PeerBuilder.php';

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version    SVN: $Id$
 */
class SfPeerBuilder extends PHP5PeerBuilder
{
  public function build()
  {
    $peerCode = parent::build();
    if (!DataModelBuilder::getBuildProperty('builderAddComments'))
    {
      $peerCode = sfToolkit::stripComments($peerCode);
    }

    if (!DataModelBuilder::getBuildProperty('builderAddIncludes'))
    {
      // remove all inline includes: peer class include inline the mapbuilder classes
      $peerCode = preg_replace("/(include|require)_once\s*.*Base.*Peer\.php.*\s*/", "", $peerCode);
      $peerCode = preg_replace("/(include|require)_once\s*.*MapBuilder\.php.*\s*/", "", $peerCode);
    }

    // change Propel::import() calls to sfPropel::import()
    $peerCode = str_replace('Propel::import(', 'sfPropel::import(', $peerCode);

    return $peerCode;
  }

  protected function addIncludes(& $script)
  {
    if (!DataModelBuilder::getBuildProperty('builderAddIncludes'))
    {
      return;
    }

    parent::addIncludes($script);
  }

  protected function addConstantsAndAttributes(& $script)
  {
    parent::addConstantsAndAttributes($script);
    
    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $script .= "
  /**
   * An associative array of behavior names and user parameters.
   * 
   * @var array
   * @see sfPropelBehavior::add()
   */
  protected static \$behaviors = array();
";
    }
  }

  protected function addSelectMethods(& $script)
  {
    parent::addSelectMethods($script);

    if ($this->getTable()->getAttribute('isI18N'))
    {
      $this->addDoSelectWithI18n($script);
      $this->addI18nMethods($script);
    }

    $this->addUniqueColumnNamesMethod($script);
    
    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $this->addBehaviorMethods($script);
    }
  }

  protected function addI18nMethods(& $script)
  {
    $table = $this->getTable();
    foreach ($table->getReferrers() as $fk)
    {
      $tblFK = $fk->getTable();
      if ($tblFK->getName() == $table->getAttribute('i18nTable'))
      {
        $i18nClassName = $tblFK->getPhpName();
        break;
      }
    }

    $script .= "

  /**
   * Returns the i18n model class name.
   *
   * @return string The i18n model class name
   */
  public static function getI18nModel()
  {
    return '$i18nClassName';
  }
";
  }

  protected function addDoSelectWithI18n(& $script)
  {
    $table = $this->getTable();
    $thisTableObjectBuilder = OMBuilder::getNewObjectBuilder($table);
    $className = $table->getPhpName();
    $pks = $table->getPrimaryKey();
    $pk = PeerBuilder::getColumnName($pks[0], $className);

    // get i18n table name and culture column name
    foreach ($table->getReferrers() as $fk)
    {
      $tblFK = $fk->getTable();
      if ($tblFK->getName() == $table->getAttribute('i18nTable'))
      {
        $i18nClassName = $tblFK->getPhpName();

        // FIXME
        $i18nPeerClassName = $i18nClassName.'Peer';

        $i18nTable = $table->getDatabase()->getTable($tblFK->getName());
        $i18nTableObjectBuilder = OMBuilder::getNewObjectBuilder($i18nTable);
        $i18nTablePeerBuilder = OMBuilder::getNewPeerBuilder($i18nTable);
        $i18nPks = $i18nTable->getPrimaryKey();
        $i18nPk = PeerBuilder::getColumnName($i18nPks[0], $i18nClassName);

        $culturePhpName = '';
        $cultureColumnName = '';
        foreach ($tblFK->getColumns() as $col)
        {
          if (('true' == trim(strtolower($col->getAttribute('isCulture')))))
          {
            $culturePhpName = $col->getPhpName();
            $cultureColumnName = PeerBuilder::getColumnName($col, $i18nClassName);
          }
        }
      }
    }

    $script .= "

  /**
   * Selects a collection of $className objects pre-filled with their i18n objects.
   *
   * @return array Array of $className objects.
   * @throws PropelException Any exceptions caught during processing will be
   *     rethrown wrapped into a PropelException.
   */
  public static function doSelectWithI18n(Criteria \$c, \$culture = null, PropelPDO \$con = null)
  {
    if (\$culture === null)
    {
      \$culture = sfPropel::getDefaultCulture();
    }
";

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $script .= "
    sfProjectConfiguration::getActive()->getEventDispatcher()->notify(new sfEventPropel('{$this->getClassname()}', 'propel.do_select_i18n', array(
      'criteria'   => \$c,
      'culture'    => \$culture,
      'connection' => \$con,
    )));
";
    }

    $script .= "
    // Set the correct dbName if it has not been overridden
    if (\$c->getDbName() == Propel::getDefaultDB())
    {
      \$c->setDbName(self::DATABASE_NAME);
    }

    ".$this->getPeerClassname()."::addSelectColumns(\$c);
    \$startcol = (".$this->getPeerClassname()."::NUM_COLUMNS - ".$this->getPeerClassname()."::NUM_LAZY_LOAD_COLUMNS);

    ".$i18nPeerClassName."::addSelectColumns(\$c);

    \$c->addJoin(".$pk.", ".$i18nPk.");
    \$c->add(".$cultureColumnName.", \$culture);

    \$stmt = ".$this->basePeerClassname."::doSelect(\$c, \$con);
    \$results = array();

    while(\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
";
            if ($table->getChildrenColumn()) {
              $script .= "
      \$omClass = ".$this->getPeerClassname()."::getOMClass(\$row, \$startcol);
";
            } else {
              $script .= "
      \$omClass = ".$this->getPeerClassname()."::getOMClass();
";
            }
            $script .= "
      \$cls = Propel::importClass(\$omClass);
      \$obj1 = new \$cls();
      \$obj1->hydrate(\$row);
      \$obj1->setCulture(\$culture);
";
            if ($i18nTable->getChildrenColumn()) {
              $script .= "
      \$omClass = ".$i18nTablePeerBuilder->getPeerClassname()."::getOMClass(\$row, \$startcol);
";
            } else {
              $script .= "
      \$omClass = ".$i18nTablePeerBuilder->getPeerClassname()."::getOMClass();
";
            }

            $script .= "
      \$cls = Propel::importClass(\$omClass);
      \$obj2 = new \$cls();
      \$obj2->hydrate(\$row, \$startcol);

      \$obj1->set".$i18nClassName."ForCulture(\$obj2, \$culture);
      \$obj2->set".$className."(\$obj1);

      \$results[] = \$obj1;
    }
    return \$results;
  }
";
  }

  protected function addDoValidate(& $script)
  {
    $tmp = '';
    parent::addDoValidate($tmp);

    /**
     * @todo setup 1.1 global validation errors for propel model validation
     */
    $replacer = "
    \$res = {$this->basePeerClassname}::doValidate({$this->getPeerClassname()}::DATABASE_NAME, {$this->getPeerClassname()}::TABLE_NAME, \$columns);
    if (true !== \$res)
    {
      \$request = sfContext::getInstance()->getRequest();
      foreach (\$res as \$failed)
      {
        \$col = {$this->getPeerClassname()}::translateFieldname(\$failed->getColumn(), BasePeer::TYPE_COLNAME, BasePeer::TYPE_PHPNAME);
      }
    }

    return \$res;
";

    $script .= str_replace(sprintf('return %s::doValidate(%s::DATABASE_NAME, %2$s::TABLE_NAME, $columns);', $this->basePeerClassname, $this->getPeerClassname()), $replacer, $tmp);
  }

  protected function addDoSelectStmt(& $script)
  {
    $tmp = '';
    parent::addDoSelectStmt($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      // insert hook just before return
      $pos = strrpos($tmp, 'return');
      $tmp = substr($tmp, 0, $pos).$this->getDoSelectHook().substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addDoSelectJoin(& $script)
  {
    $tmp = '';
    parent::addDoSelectJoin($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $table   = $this->getTable();
      $builder = $this->getNewObjectBuilder($table);
      foreach ($table->getForeignKeys() as $fk)
      {
        $method = 'doSelectJoin'.$builder->getFKPhpNameAffix($fk, $plural = false);
        if (false !== $pos = strpos($tmp, $method))
        {
          // insert hook just before the $stmt variable is defined
          $pos = $pos + strpos(substr($tmp, $pos), '$stmt = ');
          $tmp = substr($tmp, 0, $pos).$this->getDoSelectHook(true, '$c').substr($tmp, $pos);
        }
      }
    }

    $script .= $tmp;
  }

  protected function addDoSelectJoinAll(& $script)
  {
    $tmp = '';
    parent::addDoSelectJoinAll($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      // insert hook just before the $stmt variable is defined
      $pos = strpos($tmp, '$stmt = ');
      $tmp = substr($tmp, 0, $pos).$this->getDoSelectHook(true, '$c').substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addDoSelectJoinAllExcept(& $script)
  {
    $tmp = '';
    parent::addDoSelectJoinAllExcept($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $table   = $this->getTable();
      $builder = $this->getNewObjectBuilder($table);
      foreach ($table->getForeignKeys() as $fk)
      {
        $method = 'doSelectJoinAllExcept'.$builder->getFKPhpNameAffix($fk, $plural = false);
        if (false !== $pos = strpos($tmp, $method))
        {
          // insert hook just before the $stmt variable is defined
          $pos = $pos + strpos(substr($tmp, $pos), '$stmt = ');
          $tmp = substr($tmp, 0, $pos).$this->getDoSelectHook(true, '$c').substr($tmp, $pos);
        }
      }
    }

    $script .= $tmp;
  }

  protected function addDoUpdate(& $script)
  {
    $tmp = '';
    parent::addDoUpdate($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $preHook = "
    // dispatch pre-update hook
    \$dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();
    \$event = \$dispatcher->notifyUntil(new sfEventPropel('{$this->getClassname()}', 'propel.pre_do_update', array(
      'values'          => \$values,
      'select_criteria' => \$selectCriteria,
      'update_criteria' => \$criteria,
      'connection'      => \$con,
    )));
    if (\$event->isProcessed() && is_int(\$affectedRows = \$event->getReturnValue()))
    {
      return \$affectedRows;
    }

    ";

      $postHook = "
    // dispatch post-update hook
    \$dispatcher->notify(new sfEventPropel('{$this->getClassname()}', 'propel.post_do_update', array(
      'values'          => \$values,
      'select_criteria' => \$selectCriteria,
      'update_criteria' => \$criteria,
      'connection'      => \$con,
      'affected_rows'   => \$affectedRows,
    )));

    return \$affectedRows;
  ";

      // insert pre hook just before return
      $pos = strrpos($tmp, 'return');
      $tmp = substr($tmp, 0, $pos).$preHook.substr($tmp, $pos);

      // capture return value
      $tmp = str_replace('return BasePeer', '$affectedRows = BasePeer', $tmp);

      // insert post hook just before function close
      $pos = strrpos($tmp, '}');
      $tmp = substr($tmp, 0, $pos).$postHook.substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addDoInsert(& $script)
  {
    $tmp = '';
    parent::addDoInsert($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $preHook = "
    // dispatch pre-insert hook
    \$dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();
    \$event = \$dispatcher->notifyUntil(new sfEventPropel('{$this->getClassname()}', 'propel.pre_do_insert', array(
      'values'     => \$values,
      'criteria'   => \$criteria,
      'connection' => \$con,
    )));
    if (\$event->isProcessed() && \$pk = \$event->getReturnValue())
    {
      return \$pk;
    }

    ";

      $postHook = "
    // disaptch post-insert hook
    \$dispatcher->notify(new sfEventPropel('{$this->getClassname()}', 'propel.post_do_insert', array(
      'values'     => \$values,
      'criteria'   => \$criteria,
      'connection' => \$con,
      'insert_id'  => \$pk,
    )));

    ";

      // insert pre-hook just before the try statement
      $pos = strpos($tmp, 'try');
      $tmp = substr($tmp, 0, $pos).$preHook.substr($tmp, $pos);

      // insert post-hook just before return
      $pos = strrpos($tmp, 'return');
      $tmp = substr($tmp, 0, $pos).$postHook.substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addClassClose(& $script)
  {
    parent::addClassClose($script);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $behavior_file_name = 'Base'.$this->getTable()->getPhpName().'Behaviors';
      $behavior_file_path = ClassTools::getFilePath($this->getStubObjectBuilder()->getPackage().'.om', $behavior_file_name);

      $absolute_behavior_file_path = sfConfig::get('sf_root_dir').'/'.$behavior_file_path;

      if (file_exists($absolute_behavior_file_path))
      {
        unlink($absolute_behavior_file_path);
      }

      $behaviors = $this->getTable()->getAttribute('behaviors');
      if ($behaviors)
      {
        file_put_contents($absolute_behavior_file_path, sprintf("<?php\n\nsfPropelBehavior::add('%s', %s);\n", $this->getTable()->getPhpName(), var_export(unserialize($behaviors), true)));

        $behavior_include_script = "

if (sfProjectConfiguration::getActive() instanceof sfApplicationConfiguration)
{
  include_once '$behavior_file_path';
}
";

        $script .= $behavior_include_script;
      }
    }
  }

  protected function addUniqueColumnNamesMethod(& $script)
  {
    $unices = array();
    foreach ($this->getTable()->getUnices() as $unique)
    {
      $unices[] = sprintf("array('%s')", implode("', '", $unique->getColumns()));
    }
    $unices = array_unique($unices);
    $unices = implode(', ', $unices);

    $script .= "

  static public function getUniqueColumnNames()
  {
    return array($unices);
  }
";
  }

  protected function addDoCountJoin(& $script)
  {
    $tmp = '';
    parent::addDoCountJoin($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $table   = $this->getTable();
      $builder = $this->getNewObjectBuilder($table);
      foreach ($table->getForeignKeys() as $fk)
      {
        $method = 'doCountJoin'.$builder->getFKPhpNameAffix($fk, $plural = false);
        if (false !== $pos = strpos($tmp, $method))
        {
          // insert hook just before the $stmt variable is defined
          $pos = $pos + strpos(substr($tmp, $pos), '$stmt = ');
          $tmp = substr($tmp, 0, $pos).$this->getDoCountHook(true).substr($tmp, $pos);
        }
      }
    }

    $script .= $tmp;
  }

  protected function addDoCountJoinAll(& $script)
  {
    $tmp = '';
    parent::addDoCountJoinAll($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      // insert hook just before the $stmt variable is defined
      $pos = strpos($tmp, '$stmt = ');
      $tmp = substr($tmp, 0, $pos).$this->getDoCountHook(true).substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addDoCountJoinAllExcept(& $script)
  {
    $tmp = '';
    parent::addDoCountJoinAllExcept($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      $table   = $this->getTable();
      $builder = $this->getNewObjectBuilder($table);
      foreach ($table->getForeignKeys() as $fk)
      {
        $method = 'doCountJoinAllExcept'.$builder->getFKPhpNameAffix($fk, $plural = false);
        if (false !== $pos = strpos($tmp, $method))
        {
          // insert hook just before the $stmt variable is defined
          $pos = $pos + strpos(substr($tmp, $pos), '$stmt = ');
          $tmp = substr($tmp, 0, $pos).$this->getDoCountHook(true).substr($tmp, $pos);
        }
      }
    }

    $script .= $tmp;
  }

  protected function addDoCount(& $script)
  {
    $tmp = '';
    parent::addDoCount($tmp);

    if (DataModelBuilder::getBuildProperty('builderAddBehaviors'))
    {
      // insert hook just before the $stmt variable is defined
      $pos = strpos($tmp, '$stmt = ');
      $tmp = substr($tmp, 0, $pos).$this->getDoCountHook().substr($tmp, $pos);
    }

    $script .= $tmp;
  }

  protected function addBehaviorMethods(& $script)
  {
    $script .= "

  /**
   * Adds a behavior.
   * 
   * @param   string \$name
   * @param   array  \$parameters
   * 
   * @throws  LogicException If the behavior has already been added
   */
  public static function addBehavior(\$name, \$parameters = array())
  {
    if (isset(self::\$behaviors[\$name]))
    {
      throw new LogicException(sprintf('The \"%s\" behavior has already been added to {$this->getPeerClassname()}.', \$name));
    }

    self::\$behaviors[\$name] = \$parameters;
  }

  /**
   * Returns true if the behavior has been added.
   * 
   * Provides functionality similar to {@link instanceof}.
   * 
   * @param   string \$name
   * 
   * @return  boolean
   */
  public static function hasBehavior(\$name)
  {
    return isset(self::\$behaviors[\$name]);
  }

  /**
   * Returns all behavior names.
   * 
   * @return  array
   */
  public static function getBehaviorNames()
  {
    return array_keys(self::\$behaviors);
  }

  /**
   * Returns behavior parameters.
   * 
   * @param   string \$name
   * 
   * @return  array
   *
   * @throws  InvalidArgumentException If the behavior has not been added
   */
  public static function getBehaviorParameters(\$name)
  {
    if (!self::hasBehavior(\$name))
    {
      throw new InvalidArgumentException(sprintf('The \"%s\" behavior has not been added to {$this->getPeerClassname()}.', \$name));
    }

    return self::\$behaviors[\$name];
  }
";
  }

  protected function getDoCountHook($join = false)
  {
    $hook = "
    // dispatch behavior hook
    sfProjectConfiguration::getActive()->getEventDispatcher()->notify(new sfEventPropel('{$this->getClassname()}', 'propel.do_count', array(
      'criteria'      => \$criteria,
      'distinct'      => \$distinct,
      'connection'    => \$con,%s
      'method'        => __FUNCTION__,
    )));

    ";

    return sprintf($hook, $join ? "\n      'join_behavior' => \$join_behavior," : null);
  }

  protected function getDoSelectHook($join = false, $criteriaVar = '$criteria')
  {
    $hook = "
    // dispatch behavior hook
    sfProjectConfiguration::getActive()->getEventDispatcher()->notify(new sfEventPropel('{$this->getClassname()}', 'propel.do_select', array(
      'criteria'      => %s,
      'connection'    => \$con,%s
      'method'        => __FUNCTION__,
    )));

    ";

    return sprintf($hook, $criteriaVar, $join ? "\n      'join_behavior' => \$join_behavior," : null);
  }
}
