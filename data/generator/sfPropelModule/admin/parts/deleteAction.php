  public function executeDelete($request)
  {
    $this->getRoute()->getObject()->delete();

    $this->getUser()->setFlash('notice', 'The item was deleted successfully.');

    $this->redirect('@<?php echo $this->getUrlForAction('list') ?>');
  }
