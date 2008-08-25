<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A simple API shell for convenience and backward compatibility.
 *
 * @package     sfPropelPlugin
 * @subpackage  behavior
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author      Kris Wallsmith <kris.wallsmith@gmail.com>
 * @version     SVN: $Id$
 */
class sfPropelBehavior
{
  static public function add($class, $behaviors)
  {
    sfPropelBehaviorManager::getInstance()->add($class, $behaviors);
  }

  static public function registerListeners($name, $listeners)
  {
    sfPropelBehaviorManager::getInstance()->register($name, $listeners);
  }

  static public function registerMethods($name, $callables)
  {
    sfPropelBehaviorCompat::registerMethods($name, $callables);
  }

  static public function registerHooks($name, $hooks)
  {
    sfPropelBehaviorCompat::registerHooks($name, $hooks);
  }
}
