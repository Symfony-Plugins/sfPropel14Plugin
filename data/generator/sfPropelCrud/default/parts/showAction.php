  public function executeShow($request)
  {
<?php if (isset($this->params['with_propel_route']) && $this->params['with_propel_route']): ?>
    $this-><?php echo $this->getSingularName() ?> = $this->getRoute()->getObject();
<?php else: ?>
    $this-><?php echo $this->getSingularName() ?> = <?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(49, '$request->getParameter') ?>);
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);
<?php endif; ?>
  }
