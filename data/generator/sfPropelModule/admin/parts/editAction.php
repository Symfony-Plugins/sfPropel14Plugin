  public function executeEdit($request)
  {
    $this-><?php echo $this->getSingularName() ?> = $this->getRoute()->getObject();
    $this->form = $this->configuration->getForm($this-><?php echo $this->getSingularName() ?>);
  }
