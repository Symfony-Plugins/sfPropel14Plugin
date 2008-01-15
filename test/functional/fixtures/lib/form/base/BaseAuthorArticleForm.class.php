<?php

/**
 * AuthorArticle form base class.
 *
 * @package    form
 * @subpackage author_article
 * @version    SVN: $Id$
 */
class BaseAuthorArticleForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'author_id'  => new sfWidgetFormSelect(array('choices' => new sfCallable(array($this, 'getAuthorIdChoices')))),
      'article_id' => new sfWidgetFormSelect(array('choices' => new sfCallable(array($this, 'getArticleIdChoices')))),
      'id'         => new sfWidgetFormInputHidden(),
    ));

    $this->setValidators(array(
      'author_id'  => new sfValidatorChoice(array('choices' => new sfCallable(array($this, 'getAuthorIdIdentifierChoices')), 'required' => false)),
      'article_id' => new sfValidatorChoice(array('choices' => new sfCallable(array($this, 'getArticleIdIdentifierChoices')), 'required' => false)),
      'id'         => new sfValidatorInteger(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('author_article[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'AuthorArticle';
  }


  public function getAuthorIdIdentifierChoices()
  {
    return array_keys($this->getAuthorIdChoices());
  }

  public function getAuthorIdChoices()
  {
    if (!isset($this->AuthorIdChoices))
    {
      $this->AuthorIdChoices = array('' => '');
      foreach (AuthorPeer::doSelect(new Criteria(), $this->getConnection()) as $object)
      {
        $this->AuthorIdChoices[$object->getId()] = $object->__toString();
      }
    }

    return $this->AuthorIdChoices;
  }
  public function getArticleIdIdentifierChoices()
  {
    return array_keys($this->getArticleIdChoices());
  }

  public function getArticleIdChoices()
  {
    if (!isset($this->ArticleIdChoices))
    {
      $this->ArticleIdChoices = array('' => '');
      foreach (ArticlePeer::doSelect(new Criteria(), $this->getConnection()) as $object)
      {
        $this->ArticleIdChoices[$object->getId()] = $object->__toString();
      }
    }

    return $this->ArticleIdChoices;
  }

}
