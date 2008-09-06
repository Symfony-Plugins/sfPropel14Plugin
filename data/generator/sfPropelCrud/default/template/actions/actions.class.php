[?php

/**
 * <?php echo $this->getModuleName() ?> actions.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage <?php echo $this->getModuleName()."\n" ?>
 * @author     ##AUTHOR_NAME##
 * @version    SVN: $Id$
 */
class <?php echo $this->getGeneratedModuleName() ?>Actions extends sfActions
{
  public function executeIndex($request)
  {
    $this-><?php echo $this->getSingularName() ?>List = <?php echo $this->getPeerClassName() ?>::doSelect(new Criteria());
  }

<?php if (isset($this->params['with_show']) && $this->params['with_show']): ?>
  public function executeShow($request)
  {
    $this-><?php echo $this->getSingularName() ?> = <?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(49, '$request->getParameter') ?>);
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);
  }

<?php endif; ?>
<?php if (isset($this->params['non_atomic_actions']) && $this->params['non_atomic_actions']): ?>
  public function executeEdit($request)
  {
    $this->form = new <?php echo $this->getFormClassName() ?>(<?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForEdit(49, $this->getSingularName()) ?>));

    if ($request->isMethod('post'))
    {
      $this->form->bind($request->getParameter('<?php echo $this->getFormParameterName() ?>'));
      if ($this->form->isValid())
      {
        $<?php echo $this->getSingularName() ?> = $this->form->save();

        $this->redirect('<?php echo $this->getModuleName() ?>/edit?<?php echo $this->getPrimaryKeyUrlParams() ?>);
      }
    }
  }
<?php else: ?>
  public function executeCreate($request)
  {
    $this->form = new <?php echo $this->getFormClassName() ?>();

    $this->setTemplate('edit');
  }

  public function executeEdit($request)
  {
    $this->form = new <?php echo $this->getFormClassName() ?>(<?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(49, '$request->getParameter') ?>));
  }

  public function executeUpdate($request)
  {
    $this->forward404Unless($request->isMethod('post'));

    $this->form = new <?php echo $this->getFormClassName() ?>(<?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(49, '$request->getParameter') ?>));

    $this->form->bind($request->getParameter('<?php echo $this->getFormParameterName() ?>'));
    if ($this->form->isValid())
    {
      $<?php echo $this->getSingularName() ?> = $this->form->save();

      $this->redirect('<?php echo $this->getModuleName() ?>/edit?<?php echo $this->getPrimaryKeyUrlParams() ?>);
    }

    $this->setTemplate('edit');
  }
<?php endif; ?>

  public function executeDelete($request)
  {
    $this->forward404Unless($<?php echo $this->getSingularName() ?> = <?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(43, '$request->getParameter') ?>));

    $<?php echo $this->getSingularName() ?>->delete();

    $this->redirect('<?php echo $this->getModuleName() ?>/index');
  }
}
