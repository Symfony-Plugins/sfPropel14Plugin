<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWidgetFormPropelSelectRadio represents a radio HTML tag for a model.
 *
 * @package    symfony
 * @subpackage widget
 * @author     Francesco Fullone <ff@ideato.it>
 * @version    SVN: $Id: sfWidgetFormPropelSelect.class.php 11129 2008-08-25 19:52:08Z fabien $
 */
class sfWidgetFormPropelSelectRadio extends sfWidgetFormSelectRadio 
{
  /**
   * @see sfWidget
   */
  public function __construct($options = array(), $attributes = array())
  {
    $options['choices'] = new sfCallable(array($this, 'getChoices'));

    parent::__construct($options, $attributes);
  }

  /**
   * Constructor.
   *
   * Available options:
   *
   *  * model:      The model class (required)
   *  * add_empty:  Whether to add a first empty value or not (false by default)
   *                If the option is not a Boolean, the value will be used as the text value
   *  * method:     The method to use to display object values (__toString by default)
   *  * order_by:   An array composed of two fields:
   *                  * The column to order by the results (must be in the PhpName format)
   *                  * asc or desc
   *  * criteria:   A criteria to use when retrieving objects
   *  * connection: The Propel connection to use (null by default)
   *  * checked:    true if the first value must be checked, int for the ID of the propel obj key, false to disable
   *
   * @see sfWidgetFormSelect
   */
  protected function configure($options = array(), $attributes = array())
  {
    $this->addRequiredOption('model');
    $this->addOption('method', '__toString');
    $this->addOption('order_by', null);
    $this->addOption('criteria', null);
    $this->addOption('connection', null);
    $this->addOption('checked', false);
    
    $this->addOption('label_separator', '&nbsp;');
    $this->addOption('separator', "\n");
    $this->addOption('formatter', array($this, 'formatter'));    

    parent::configure($options, $attributes);
  }

  /**
   * Returns the choices associated to the model.
   *
   * @return array An array of choices
   */
  public function getChoices()
  {
    $choices = array();

    $class = constant($this->getOption('model').'::PEER');

    $criteria = is_null($this->getOption('criteria')) ? new Criteria() : $this->getOption('criteria');
    if ($order = $this->getOption('order_by'))
    {
      $method = sprintf('add%sOrderByColumn', 0 === strpos(strtoupper($order[1]), 'ASC') ? 'Ascending' : 'Descending');
      $criteria->$method(call_user_func(array($class, 'translateFieldName'), $order[0], BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME));
    }
    $objects = call_user_func(array($class, 'doSelect'), $criteria, $this->getOption('connection'));

    $method = $this->getOption('method');
    
    if (!method_exists($this->getOption('model'), $method))
    {
      throw new RuntimeException(sprintf('Class "%s" must implement a "%s" method to be rendered in a "%s" widget', $this->getOption('model'), $method, __CLASS__));
    }
    
    foreach ($objects as $object)
    {
      $choices[$object->getPrimaryKey()] = $object->$method();
    }

    return $choices;
  }

  public function render($name, $value = null, $attributes = array(), $errors = array())
  {  
    $choices = $this->getOption('choices');
    if ($choices instanceof sfCallable)
    {
      $choices = $choices->call();
    }

    $check_first = false;
    if ($this->getOption('checked') === true)
    {
      $check_first = true;
    }
    elseif (intval($this->getOption('checked')) != 0 )
    {
      $value = intval($this->getOption('checked'));
    }    
    
    $inputs = array();
    foreach ($choices as $key => $option)
    {
      $baseAttributes = array(
        'name'  => $name,
        'type'  => 'radio',
        'value' => self::escapeOnce($key),
        'id'    => $id = $this->generateId($name.'[]', self::escapeOnce($key)),
      );

      if (strval($key) == strval($value === false ? 0 : $value) or $check_first)
      {
        $baseAttributes['checked'] = 'checked';
        $check_first = false;
      }

      $inputs[] = array(
        'input' => $this->renderTag('input', array_merge($baseAttributes, $attributes)),
        'label' => $this->renderContentTag('label', $option, array('for' => $id)),
      );
    }
    return call_user_func($this->getOption('formatter'), $this, $inputs);
  }
  
  public function __clone()
  {
    if ($this->getOption('choices') instanceof sfCallable)
    {
      $callable = $this->getOption('choices')->getCallable();
      if (is_array($callable))
      {
        $callable[0] = $this;
        $this->setOption('choices', new sfCallable($callable));
      }
    }
  }
}
