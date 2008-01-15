<?php

/**
 * Article form base class.
 *
 * @package    form
 * @subpackage article
 * @version    SVN: $Id$
 */
class BaseArticleForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'          => new sfWidgetFormInputHidden(),
      'title'       => new sfWidgetFormInput(),
      'body'        => new sfWidgetFormTextarea(),
      'online'      => new sfWidgetFormInputCheckbox(),
      'category_id' => new sfWidgetFormSelect(array('choices' => new sfCallable(array($this, 'getCategoryIdChoices')))),
      'created_at'  => new sfWidgetFormDateTime(),
      'end_date'    => new sfWidgetFormDateTime(),
      'book_id'     => new sfWidgetFormSelect(array('choices' => new sfCallable(array($this, 'getBookIdChoices')))),
    ));

    $this->setValidators(array(
      'id'          => new sfValidatorInteger(array('required' => false)),
      'title'       => new sfValidatorString(array('max_length' => 255)),
      'body'        => new sfValidatorString(array('required' => false)),
      'online'      => new sfValidatorBoolean(array('required' => false)),
      'category_id' => new sfValidatorChoice(array('choices' => new sfCallable(array($this, 'getCategoryIdIdentifierChoices')))),
      'created_at'  => new sfValidatorDateTime(array('required' => false)),
      'end_date'    => new sfValidatorDateTime(array('required' => false)),
      'book_id'     => new sfValidatorChoice(array('choices' => new sfCallable(array($this, 'getBookIdIdentifierChoices')), 'required' => false)),
    ));

    $this->widgetSchema->setNameFormat('article[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'Article';
  }


  public function getCategoryIdIdentifierChoices()
  {
    return array_keys($this->getCategoryIdChoices());
  }

  public function getCategoryIdChoices()
  {
    if (!isset($this->CategoryIdChoices))
    {
      $this->CategoryIdChoices = array();
      foreach (CategoryPeer::doSelect(new Criteria(), $this->getConnection()) as $object)
      {
        $this->CategoryIdChoices[$object->getId()] = $object->__toString();
      }
    }

    return $this->CategoryIdChoices;
  }
  public function getBookIdIdentifierChoices()
  {
    return array_keys($this->getBookIdChoices());
  }

  public function getBookIdChoices()
  {
    if (!isset($this->BookIdChoices))
    {
      $this->BookIdChoices = array('' => '');
      foreach (BookPeer::doSelect(new Criteria(), $this->getConnection()) as $object)
      {
        $this->BookIdChoices[$object->getId()] = $object->__toString();
      }
    }

    return $this->BookIdChoices;
  }

}
