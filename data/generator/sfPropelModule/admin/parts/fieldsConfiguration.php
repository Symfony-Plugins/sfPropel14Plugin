  public function getListParams()
  {
    return <?php echo $this->asPhp(isset($this->config['list']['params']) ? $this->config['list']['params'] : '%%'.implode('%% - %%', isset($this->config['list']['display']) ? $this->config['list']['display'] : $this->getAllFieldNames()).'%%') ?>;
<?php unset($this->config['list']['params']) ?>
  }

  public function getListLayout()
  {
    return '<?php echo isset($this->config['list']['layout']) ? $this->config['list']['layout'] : 'tabular' ?>';
<?php unset($this->config['list']['layout']) ?>
  }

  public function getListTitle()
  {
    return '<?php echo isset($this->config['list']['title']) ? $this->config['list']['title'] : sfInflector::humanize($this->getModuleName()).' List' ?>';
<?php unset($this->config['list']['title']) ?>
  }

  public function getEditTitle()
  {
    return '<?php echo isset($this->config['edit']['title']) ? $this->config['edit']['title'] : 'Edit '.sfInflector::humanize($this->getModuleName()) ?>';
<?php unset($this->config['edit']['title']) ?>
  }

  public function getNewTitle()
  {
    return '<?php echo isset($this->config['new']['title']) ? $this->config['new']['title'] : 'New '.sfInflector::humanize($this->getModuleName()) ?>';
<?php unset($this->config['new']['title']) ?>
  }

  public function getFilterDisplay()
  {
    return <?php echo $this->asPhp(isset($this->config['filter']['display']) ? $this->config['filter']['display'] : $this->getAllFieldNames()) ?>;
<?php unset($this->config['filter']['display']) ?>
  }

  public function getFormDisplay()
  {
    return <?php echo $this->asPhp(isset($this->config['form']['display']) ? $this->config['form']['display'] : array('NONE' => $this->getAllFieldNames())) ?>;
<?php unset($this->config['form']['display']) ?>
  }

  public function getEditDisplay()
  {
    return <?php echo $this->asPhp(isset($this->config['edit']['display']) ? $this->config['edit']['display'] : array()) ?>;
<?php unset($this->config['edit']['display']) ?>
  }

  public function getNewDisplay()
  {
    return <?php echo $this->asPhp(isset($this->config['new']['display']) ? $this->config['new']['display'] : array()) ?>;
<?php unset($this->config['new']['display']) ?>
  }

  public function getListDisplay()
  {
    return <?php echo $this->asPhp(isset($this->config['list']['display']) ? $this->config['list']['display'] : $this->getAllFieldNames()) ?>;
<?php unset($this->config['list']['display']) ?>
  }

  public function getFieldsDefault()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo $this->asPhp(array_merge(array(
        'is_link'      => (Boolean) $column->isPrimaryKey(),
        'is_real'      => true,
        'is_partial'   => false,
        'is_component' => false,
        'type'         => $this->getType($column),
      ), isset($this->config['fields'][sfInflector::underscore($column->getName())]) ? $this->config['fields'][sfInflector::underscore($column->getName())] : array())) ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo $this->asPhp(array_merge(array(
        'is_link'      => false,
        'is_real'      => false,
        'is_partial'   => false,
        'is_component' => false,
        'type'         => 'Text',
      ), isset($this->config['fields'][$name]) ? $this->config['fields'][$name] : array())) ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['fields']) ?>
  }

  public function getFieldsList()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['list']['fields'][$name]) ? $this->asPhp($this->config['list']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['list']['fields'][$name]) ? $this->asPhp($this->config['list']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['list']['fields']) ?>
  }

  public function getFieldsFilter()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['filter']['fields'][$name]) ? $this->asPhp($this->config['filter']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['filter']['fields'][$name]) ? $this->asPhp($this->config['filter']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['filter']['fields']) ?>
  }

  public function getFieldsForm()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['form']['fields'][$name]) ? $this->asPhp($this->config['form']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['form']['fields'][$name]) ? $this->asPhp($this->config['form']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['form']['fields']) ?>
  }

  public function getFieldsEdit()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['edit']['fields'][$name]) ? $this->asPhp($this->config['edit']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['edit']['fields'][$name]) ? $this->asPhp($this->config['edit']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['edit']['fields']) ?>
  }

  public function getFieldsNew()
  {
    return array(
<?php foreach ($this->getTableMap()->getColumns() as $column): $name = sfInflector::underscore($column->getName()) ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['new']['fields'][$name]) ? $this->asPhp($this->config['new']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
<?php foreach ($this->getManyToManyTables() as $tables): $name = sfInflector::underscore($tables['middleTable']->getClassname()).'_list' ?>
      '<?php echo $name ?>' => <?php echo isset($this->config['new']['fields'][$name]) ? $this->asPhp($this->config['new']['fields'][$name]) : 'array()' ?>,
<?php endforeach; ?>
    );
<?php unset($this->config['new']['fields']) ?>
  }
