  public function executeNew($request)
  {
    $this->form = $this->configuration->getForm();
    $this-><?php echo $this->getSingularName() ?> = $this->form->getObject();
  }
