  public function executeDelete($request)
  {
<?php if (isset($this->params['with_propel_route']) && $this->params['with_propel_route']): ?>
    $this->getRoute()->getObject()->delete();
<?php else: ?>
    $this->forward404Unless($<?php echo $this->getSingularName() ?> = <?php echo $this->getPeerClassName() ?>::retrieveByPk(<?php echo $this->getRetrieveByPkParamsForAction(43, '$request->getParameter') ?>), sprintf('Object <?php echo $this->getSingularName() ?> does not exist (%s).', <?php echo $this->getRetrieveByPkParamsForAction(43, '$request->getParameter') ?>));
    $<?php echo $this->getSingularName() ?>->delete();
<?php endif; ?>

<?php if (isset($this->params['route_prefix']) && $this->params['route_prefix']): ?>
    $this->redirect('@<?php echo $this->getUrlForAction('list') ?>');
<?php else: ?>
    $this->redirect('<?php echo $this->getModuleName() ?>/index');
<?php endif; ?>
  }
