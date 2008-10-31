<div class="[?php echo $class ?][?php $form[$name]->hasError() and print ' errors' ?]">
  [?php echo $form[$name]->renderError() ?]
  <div>
    [?php echo $form[$name]->renderLabel($label) ?]

    [?php if ($field->isPartial()): ?]
      [?php include_partial('<?php echo $this->getModuleName() ?>/'.$name, array('form' => $form, 'attributes' => $attributes->getRawValue())) ?]
    [?php elseif ($field->isComponent()): ?]
      [?php include_component('<?php echo $this->getModuleName() ?>', $name, array('form' => $form, 'attributes' => $attributes->getRawValue())) ?]
    [?php else: ?]
      [?php echo $form[$name]->render($attributes->getRawValue()) ?]
    [?php endif; ?]

    [?php if ($help || $help = $form[$name]->renderHelp()): ?]
      <div class="help">[?php echo __($help, array(), '<?php echo $this->getI18nCatalogue() ?>') ?]</div>
    [?php endif; ?]
  </div>
</div>
