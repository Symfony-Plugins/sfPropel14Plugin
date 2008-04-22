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

  /**
   * Initialize sfymfony propel
   *
   * @param sfEventDispatcher $dispatcher
   * @param string $culture
   */
  static public function initialize(sfEventDispatcher $dispatcher, $culture = null)
  {
    if (sfConfig::get('sf_debug') && sfConfig::get('sf_logging_enabled'))
    {
      // add propel logger
      Propel::setLogger(new sfPropelLogger($dispatcher));
    }

    // propel initialization
    $configuration = sfPropelDatabase::getConfiguration();
    if($configuration)
    {
      Propel::setConfiguration($configuration);

      if(!Propel::isInit())
      {
        Propel::initialize();
      }
    }

    $dispatcher->connect('user.change_culture', array('sfPropel', 'listenToChangeCultureEvent'));

    if (!is_null($culture))
    {
      self::setDefaultCulture($culture);
    }
    else if (class_exists('sfContext', false) && sfContext::hasInstance() && $user = sfContext::getInstance()->getUser())
    {
      self::setDefaultCulture($user->getCulture());
    }
  }

  /**
   * Sets the default culture
   *
   * @param string $culture
   */
  static public function setDefaultCulture($culture)
  {
    self::$defaultCulture = $culture;
  }

  /**
   * Return the default culture
   *
   * @return string the default culture
   */
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
