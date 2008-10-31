<?php if ($listActions = $this->configuration->getValue('list.batch_actions')): ?>
  <li class="sf_admin_batch_actions_choice">
    <select name="batch_action">
      <option value="">[?php echo __('Choose an action') ?]</option>
<?php foreach ((array) $listActions as $action => $params): ?>
<?php echo $this->addCredentialCondition('<option value="'.$action.'">'.$params['label'].'</option>', $params) ?>
<?php endforeach; ?>
    </select>
    <input type="submit" value="[?php echo __('go') ?]" />
  </li>
<?php endif; ?>
