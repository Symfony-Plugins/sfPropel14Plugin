<div class="sf_admin_pagination">
  <a href="[?php echo url_for('<?php echo $this->getUrlForAction('list') ?>') ?]?page=1">
    <img src="[?php echo sfConfig::get('sf_admin_module_web_dir').'/images/first.png' ?]" alt="[?php echo __('First page', array(), 'sf_admin') ?]" title="[?php echo __('First page', array(), 'sf_admin') ?]" />
  </a>

  <a href="[?php echo url_for('<?php echo $this->getUrlForAction('list') ?>') ?]?page=[?php echo $pager->getPreviousPage() ?]">
    <img src="[?php echo sfConfig::get('sf_admin_module_web_dir').'/images/previous.png' ?]" alt="[?php echo __('Previous page', array(), 'sf_admin') ?]" title="[?php echo __('Previous page', array(), 'sf_admin') ?]" />
  </a>

  [?php foreach ($pager->getLinks() as $page): ?]
    [?php if ($page == $pager->getPage()): ?]
      [?php echo $page ?]
    [?php else: ?]
      <a href="<?php echo $this->getUrlForAction('list') ?>?page=[?php echo $page ?]">[?php echo $page ?]</a>
    [?php endif; ?]
  [?php endforeach; ?]

  <a href="[?php echo url_for('<?php echo $this->getUrlForAction('list') ?>') ?]?page=[?php echo $pager->getNextPage() ?]">
    <img src="[?php echo sfConfig::get('sf_admin_module_web_dir').'/images/next.png' ?]" alt="[?php echo __('Next page', array(), 'sf_admin') ?]" title="[?php echo __('Next page', array(), 'sf_admin') ?]" />
  </a>

  <a href="[?php echo url_for('<?php echo $this->getUrlForAction('list') ?>') ?]?page=[?php echo $pager->getLastPage() ?]">
    <img src="[?php echo sfConfig::get('sf_admin_module_web_dir').'/images/last.png' ?]" alt="[?php echo __('Last page', array(), 'sf_admin') ?]" title="[?php echo __('Last page', array(), 'sf_admin') ?]" />
  </a>
</div>
