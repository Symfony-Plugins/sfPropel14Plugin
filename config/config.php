<?php

set_include_path(sfConfig::get('sf_root_dir').PATH_SEPARATOR.sfConfig::get('sf_symfony_lib_dir').PATH_SEPARATOR.realpath(dirname(__FILE__).'/../lib/vendor/').'/'.PATH_SEPARATOR.get_include_path());

if (sfConfig::get('sf_web_debug'))
{
  require_once dirname(__FILE__).'/../lib/propel/debug/sfWebDebugPanelPropel.class.php';

  $this->dispatcher->connect('debug.web.load_panels', array('sfWebDebugPanelPropel', 'listenToAddPanelEvent'));
}
