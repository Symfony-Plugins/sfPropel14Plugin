  public function executeUpdate($request)
  {
<?php if (isset($this->params['with_propel_route']) && $this->params['with_propel_route']): ?>
    $this->form = new <?php echo $this->getFormClassName() ?>($request->getAttribute('<?php echo $this->getSingularName() ?>'));
<?php else: ?>
    $this->forward404Unless($request->isMethod('post') || $request->isMethod('put'));
    $this->forward404Unless($<?php echo $this->getSingularName() ?> = <?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(43, '$request->getParameter') ?>), sprintf('Object <?php echo $this->getSingularName() ?> does not exist (%s).', <?php echo $this->getRetrieveByPkParamsForAction(43, '$request->getParameter') ?>));
    $this->form = new <?php echo $this->getFormClassName() ?>($<?php echo $this->getSingularName() ?>);
<?php endif; ?>

    $this->processForm($request, $this->form);

    $this->setTemplate('edit');
  }
