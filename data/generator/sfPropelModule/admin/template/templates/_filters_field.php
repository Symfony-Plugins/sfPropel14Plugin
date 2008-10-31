[?php if ($field->isPartial()): ?]
  [?php include_partial('<?php echo $this->getModuleName() ?>/'.$name, array('type' => 'filter', 'form' => $form, 'attributes' => $attributes->getRawValue())) ?]
[?php elseif ($field->isComponent()): ?]
  [?php include_component('<?php echo $this->getModuleName() ?>', $name, array('type' => 'filter', 'form' => $form, 'attributes' => $attributes->getRawValue())) ?]
[?php else: ?]
  <tr class="[?php echo $class ?]">
    <td>
      [?php echo $form[$name]->renderLabel($label) ?]
    </td>
    <td>
      [?php echo $form[$name]->renderError() ?]

      [?php echo $form[$name]->render($attributes->getRawValue()) ?]

      [?php if ($help || $help = $form[$name]->renderHelp()): ?]
        <div class="help">[?php echo __($help, array(), '<?php echo $this->getI18nCatalogue() ?>') ?]</div>
      [?php endif; ?]
    </td>
  </tr>
[?php endif; ?]
