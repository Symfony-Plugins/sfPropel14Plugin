<?php

/**
 * ProductI18n form base class.
 *
 * @package    form
 * @subpackage product_i18n
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 7157 2008-01-23 02:51:45Z dwhittle $
 */
class BaseProductI18nForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'      => new sfWidgetFormInputHidden(),
      'culture' => new sfWidgetFormInputHidden(),
      'name'    => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'      => new sfValidatorPropelChoice(array('model' => 'Product', 'required' => false)),
      'culture' => new sfValidatorPropelChoice(array('max_length' => 7, 'model' => 'ProductI18n', 'column' => 'Culture', 'required' => false)),
      'name'    => new sfValidatorString(array('max_length' => 50, 'required' => false)),
    ));

    $this->widgetSchema->setNameFormat('product_i18n[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'ProductI18n';
  }


}
