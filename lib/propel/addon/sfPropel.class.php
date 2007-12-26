<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Initialization for propel and i18n propel integration.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropel
{
  static protected
    $defaultCulture = 'en';

  static public function initialize(sfEventDispatcher $dispatcher, $culture = null)
  {
    if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled'))
    {
      // add propel logger
      Propel::setLogger(new sfPropelLogger($dispatcher));
    }

    // propel initialization
    Propel::setConfiguration(sfPropelDatabase::getConfiguration());

    Propel::initialize();

    $dispatcher->connect('user.change_culture', array('sfPropel', 'listenToChangeCultureEvent'));

    if (!is_null($culture))
    {
      self::setDefaultCulture($culture);
    }
  }

  static public function setDefaultCulture($culture)
  {
    self::$defaultCulture = $culture;
  }

  static public function getDefaultCulture()
  {
    return self::$defaultCulture;
  }

  /**
   * Listens to the user.change_culture event.
   *
   * @param sfEvent An sfEvent instance
   *
   */
  static public function listenToChangeCultureEvent(sfEvent $event)
  {
    self::setDefaultCulture($event['culture']);
  }
}
