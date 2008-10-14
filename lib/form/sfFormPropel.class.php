<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * sfFormPropel is the base class for forms based on Propel objects.
 *
 * @package    symfony
 * @subpackage form
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
abstract class sfFormPropel extends sfForm
{
  protected
    $isNew    = true,
    $cultures = array(),
    $object   = null;

  /**
   * Constructor.
   *
   * @param BaseObject $object      A Propel object used to initialize default values
   * @param array      $options     An array of options
   * @param string     $CSRFSecret  A CSRF secret (false to disable CSRF protection, null to use the global CSRF secret)
   *
   * @see sfForm
   */
  public function __construct(BaseObject $object = null, $options = array(), $CSRFSecret = null)
  {
    $class = $this->getModelName();
    if (is_null($object))
    {
      $this->object = new $class();
    }
    else
    {
      if (!$object instanceof $class)
      {
        throw new sfException(sprintf('The "%s" form only accepts a "%s" object.', get_class($this), $class));
      }

      $this->object = $object;
      $this->isNew = false;
    }

    parent::__construct(array(), $options, $CSRFSecret);

    $this->updateDefaultsFromObject();
  }

  /**
   * Returns the default connection for the current model.
   *
   * @return PropelPDO A database connection
   */
  public function getConnection()
  {
    return Propel::getConnection(constant(sprintf('%s::DATABASE_NAME', get_class($this->object->getPeer()))));
  }

  /**
   * Returns the current model name.
   */
  abstract public function getModelName();

  /**
   * Returns true if the current form embeds a new object.
   *
   * @return Boolean true if the current form embeds a new object, false otherwise
   */
  public function isNew()
  {
    return $this->isNew;
  }

  /**
   * Embeds i18n objects into the current form.
   *
   * @param array   $cultures   An array of cultures
   * @param string  $decorator  A HTML decorator for the embedded form
   */
  public function embedI18n($cultures, $decorator = null)
  {
    if (!$this->isI18n())
    {
      throw new sfException(sprintf('The model "%s" is not internationalized.', $this->getModelName()));
    }

    $this->cultures = $cultures;

    $class = $this->getI18nFormClass();
    $i18n = new $class();
    foreach ($cultures as $culture)
    {
      $this->embedForm($culture, $i18n, $decorator);
    }
  }

  /**
   * Returns the current object for this form.
   *
   * @return BaseObject The current object.
   */
  public function getObject()
  {
    return $this->object;
  }

  /**
   * Binds the current form and save the to the database in one step.
   *
   * @param  array      $taintedValues    An array of tainted values to use to bind the form
   * @param  array      $taintedFiles     An array of uploaded files (in the $_FILES or $_GET format)
   * @param  PropelPDO  $con              An optional PropelPDO object
   *
   * @return Boolean    true if the form is valid, false otherwise
   */
  public function bindAndSave($taintedValues, $taintedFiles = null, $con = null)
  {
    $this->bind($taintedValues, $taintedFiles);
    if ($this->isValid())
    {
      $this->save($con);

      return true;
    }

    return false;
  }

  /**
   * Saves the current object to the database.
   *
   * The object saving is done in a transaction and handled by the doSave() method.
   *
   * If the form is not valid, it throws an sfValidatorError.
   *
   * @param PropelPDO $con An optional PropelPDO object
   *
   * @return BaseObject The current saved object
   *
   * @see doSave()
   */
  public function save($con = null)
  {
    if (!$this->isValid())
    {
      throw $this->getErrorSchema();
    }

    if (is_null($con))
    {
      $con = $this->getConnection();
    }

    try
    {
      $con->beginTransaction();

      $this->doSave($con);

      $con->commit();
    }
    catch (Exception $e)
    {
      $con->rollBack();

      throw $e;
    }

    return $this->object;
  }

  /**
   * Updates the values of the object with the cleaned up values.
   *
   * @return BaseObject The current updated object
   */
  public function updateObject()
  {
    if (!$this->isValid())
    {
      throw $this->getErrorSchema();
    }

    $this->object->fromArray($this->processValues(), BasePeer::TYPE_FIELDNAME);

    return $this->object;
  }

  /**
   * Processes cleaned up values with user defined methods.
   *
   * To process a value before it is used by the updateObject() method,
   * you need to define an updateXXXColumn() method where XXX is the PHP name
   * of the column.
   *
   * The method must return the processed value or false to remove the value
   * from the array of cleaned up values.
   *
   * @return array An array of cleaned up values processed by the user defined methods
   */
  public function processValues()
  {
    // see if the user has overridden some column setter
    $values = $this->values;
    foreach ($this->values as $field => $value)
    {
      try
      {
        $method = sprintf('update%sColumn', call_user_func(array(constant(get_class($this->object).'::PEER'), 'translateFieldName'), $field, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME));
      }
      catch (Exception $e)
      {
        // not a "real" column of this object
        if (!method_exists($this, $method = sprintf('update%sColumn', self::camelize($field))))
        {
          continue;
        }
      }

      if (method_exists($this, $method))
      {
        if (false === $ret = $this->$method($value))
        {
          unset($values[$field]);
        }
        else
        {
          $values[$field] = $ret;
        }
      }
      else
      {
        // save files
        if ($this->validatorSchema[$field] instanceof sfValidatorFile)
        {
          $values[$field] = $this->processUploadedFile($field);
        }
      }
    }

    return $values;
  }

  /**
   * Updates the associated i18n objects values.
   *
   * @param PropelPDO $con An optional PropelPDO object
   */
  public function updateI18nObjects()
  {
    if (!$this->isValid())
    {
      throw $this->getErrorSchema();
    }

    if (!$this->isI18n())
    {
      throw new sfException(sprintf('The model "%s" is not internationalized.', $this->getModelName()));
    }

    $values = $this->getValues();
    $method = sprintf('getCurrent%s', $this->getI18nModelName());
    foreach ($this->cultures as $culture)
    {
      unset($values[$culture]['id'], $values[$culture]['culture']);

      $i18n = $this->object->$method($culture);
      $i18n->fromArray($values[$culture], BasePeer::TYPE_FIELDNAME);
    }
  }

  /**
   * Returns true if the current form has some associated i18n objects.
   *
   * @return Boolean true if the current form has some associated i18n objects, false otherwise
   */
  public function isI18n()
  {
    return !is_null($this->getI18nFormClass());
  }

  /**
   * Returns the name of the i18n model.
   *
   * @return string The name of the i18n model
   */
  public function getI18nModelName()
  {
    return null;
  }

  /**
   * Returns the name of the i18n form class.
   *
   * @return string The name of the i18n form class
   */
  public function getI18nFormClass()
  {
    return null;
  }

  /**
   * Renders a form tag suitable for the related Propel object.
   *
   * The method is automatically guessed based on the Propel object:
   *
   *  * if the object is new, the method is POST
   *  * if the object already exists, the method is PUT
   *
   * @param  string $url         The URL for the action
   * @param  array  $attributes  An array of HTML attributes
   *
   * @return string An HTML representation of the opening form tag
   *
   * @see sfForm
   */
  public function renderFormTag($url, array $attributes = array())
  {
    $attributes['method'] = $this->getObject()->isNew() ? 'POST' : 'PUT';

    return parent::renderFormTag($url, $attributes);
  }

  /**
   * Updates and saves the current object.
   *
   * If you want to add some logic before saving or save other associated objects,
   * this is the method to override.
   *
   * @param PropelPDO $con An optional PropelPDO object
   */
  protected function doSave($con = null)
  {
    if (is_null($con))
    {
      $con = $this->getConnection();
    }

    $this->updateObject();

    // i18n table
    if ($this->isI18n())
    {
      $this->updateI18nObjects($con);
    }

    $this->object->save($con);
  }

  /**
   * Updates the default values of the form with the current values of the current object.
   */
  protected function updateDefaultsFromObject()
  {
    // update defaults for the main object
    if ($this->isNew)
    {
      $this->setDefaults(array_merge($this->object->toArray(BasePeer::TYPE_FIELDNAME), $this->getDefaults()));
    }
    else
    {
      $this->setDefaults(array_merge($this->getDefaults(), $this->object->toArray(BasePeer::TYPE_FIELDNAME)));
    }

    // update defaults for i18n
    if ($this->isI18n())
    {
      $method = sprintf('getCurrent%s', $this->getI18nModelName());
      foreach ($this->cultures as $culture)
      {
        if ($this->isNew)
        {
          $this->setDefault($culture, array_merge($this->object->$method($culture)->toArray(BasePeer::TYPE_FIELDNAME), $this->getDefault($culture)));
        }
        else
        {
          $this->setDefault($culture, array_merge($this->getDefault($culture), $this->object->$method($culture)->toArray(BasePeer::TYPE_FIELDNAME)));
        }
      }
    }
  }

  /**
   * Saves the uploaded file for the given field.
   *
   * @param  string $field The field name
   * @param  string $filename The file name of the file to save
   *
   * @return string The filename used to save the file
   */
  protected function processUploadedFile($field, $filename = null)
  {
    if (!$this->validatorSchema[$field] instanceof sfValidatorFile)
    {
      throw new LogicException(sprintf('You cannot save the current file for field "%s" as the field is not a file.', $field));
    }

    if ($this->getValue($field.'_delete'))
    {
      $this->removeFile($field);

      return '';
    }

    if (!$this->getValue($field))
    {
      $column = call_user_func(array(constant(get_class($this->object).'::PEER'), 'translateFieldName'), $field, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
      $getter = 'get'.$column;

      return $this->object->$getter();
    }

    // we need the base directory
    if (!$this->validatorSchema[$field]->getOption('path'))
    {
      return $this->getValue($field);
    }

    $this->removeFile($field);

    return $this->saveFile($field, $filename);
  }

  /**
   * Removes the current file for the field.
   *
   * @param string $field The field name
   */
  protected function removeFile($field)
  {
    if (!$this->validatorSchema[$field] instanceof sfValidatorFile)
    {
      throw new LogicException(sprintf('You cannot remove the current file for field "%s" as the field is not a file.', $field));
    }

    $column = call_user_func(array(constant(get_class($this->object).'::PEER'), 'translateFieldName'), $field, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
    $getter = 'get'.$column;

    if (($directory = $this->validatorSchema[$field]->getOption('path')) && is_file($directory.$this->object->$getter()))
    {
      unlink($directory.$this->object->$getter());
    }
  }

  /**
   * Saves the current file for the field.
   *
   * @param  string $field    The field name
   * @param  string $filename The file name of the file to save
   *
   * @return string The filename used to save the file
   */
  protected function saveFile($field, $filename = null)
  {
    if (!$this->validatorSchema[$field] instanceof sfValidatorFile)
    {
      throw new LogicException(sprintf('You cannot save the current file for field "%s" as the field is not a file.', $field));
    }

    $column = call_user_func(array(constant(get_class($this->object).'::PEER'), 'translateFieldName'), $field, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
    $method = sprintf('generate%sFilename', $column);

    if (!is_null($filename))
    {
      return $this->getValue($field)->save();
    }
    else if (method_exists($this->object, $method))
    {
      return $this->getValue($field)->save($this->object->$method($this->getValue($field)));
    }
    else
    {
      return $this->getValue($field)->save();
    }
  }

  protected function camelize($text)
  {
    return sfToolkit::pregtr($text, array('#/(.?)#e' => "'::'.strtoupper('\\1')", '/(^|_|-)+(.)/e' => "strtoupper('\\2')"));
  }
}
