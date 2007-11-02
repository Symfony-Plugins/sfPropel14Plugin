<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony
 * @subpackage addon
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
require_once 'propel/Propel.php';

$dispatcher = sfContext::getInstance()->getEventDispatcher();

if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled'))
{
  // add propel logger
  Propel::setLogger(new sfPropelLogger($dispatcher));
}

// propel initialization
Propel::setConfiguration(sfPropelDatabase::getConfiguration());
Propel::initialize();

sfPropel::initialize($dispatcher, sfContext::getInstance()->getUser()->getCulture());
