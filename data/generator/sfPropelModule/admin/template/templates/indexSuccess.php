[?php use_helper('I18N', 'Date') ?]
[?php include_partial('<?php echo $this->getModuleName() ?>/assets') ?]

<div id="sf_admin_container">
  <h1>[?php echo <?php echo $this->getI18NString('list.title') ?> ?]</h1>

  [?php include_partial('<?php echo $this->getModuleName() ?>/flashes') ?]

  <div id="sf_admin_header">
    [?php include_partial('<?php echo $this->getModuleName() ?>/list_header', array('pager' => $pager)) ?]
  </div>

<?php if ($this->configuration->hasFilterForm()): ?>
  <div id="sf_admin_bar">
    [?php include_partial('<?php echo $this->getModuleName() ?>/filters', array('form' => $filters, 'configuration' => $configuration)) ?]
  </div>
<?php endif; ?>

  <div id="sf_admin_content">
    [?php include_partial('<?php echo $this->getModuleName() ?>/list_actions', array('helper' => $helper)) ?]
    [?php include_partial('<?php echo $this->getModuleName() ?>/list', array('pager' => $pager, 'sort' => $sort, 'helper' => $helper)) ?]
    [?php include_partial('<?php echo $this->getModuleName() ?>/list_actions', array('helper' => $helper)) ?]
  </div>

  <div id="sf_admin_footer">
    [?php include_partial('<?php echo $this->getModuleName() ?>/list_footer', array('pager' => $pager)) ?]
  </div>
</div>
