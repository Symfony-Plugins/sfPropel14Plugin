<ul class="sf_admin_actions">
<?php foreach ($this->configuration->getValue('edit.actions') as $name => $params): ?>
<?php if ('_delete' == $name): ?>
<?php echo $this->addCredentialCondition('[?php echo $helper->linkToDelete($form->getObject(), '.$this->asPhp($params).') ?]', $params) ?>
<?php elseif ('_list' == $name): ?>
<?php echo $this->addCredentialCondition('[?php echo $helper->linkToList('.$this->asPhp($params).') ?]', $params) ?>
<?php elseif ('_save' == $name): ?>
<?php echo $this->addCredentialCondition('[?php echo $helper->linkToSave($form->getObject(), '.$this->asPhp($params).') ?]', $params) ?>
<?php elseif ('_save_and_add' == $name): ?>
<?php echo $this->addCredentialCondition('[?php echo $helper->linkToSaveAndAdd($form->getObject(), '.$this->asPhp($params).') ?]', $params) ?>
<?php else: ?>
  <li class="sf_admin_action_<?php echo $params['class_suffix'] ?>">
<?php echo $this->addCredentialCondition($this->getLinkToAction($name, $params, true), $params) ?>
  </li>
<?php endif; ?>
<?php endforeach; ?>
</ul>
