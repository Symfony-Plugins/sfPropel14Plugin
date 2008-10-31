<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once(dirname(__FILE__).'/sfPropelBaseTask.class.php');

/**
 * Generates a Propel admin module.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfPropelGenerateAdminTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('route', sfCommandArgument::REQUIRED, 'The route name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('theme', null, sfCommandOption::PARAMETER_REQUIRED, 'The theme name', 'admin'),
      new sfCommandOption('singular', null, sfCommandOption::PARAMETER_REQUIRED, 'The singular name', null),
      new sfCommandOption('plural', null, sfCommandOption::PARAMETER_REQUIRED, 'The plural name', null),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'propel';
    $this->name = 'generate-admin';
    $this->briefDescription = 'Generates a Propel admin module';

    $this->detailedDescription = <<<EOF
The [propel:generate-admin|INFO] task generates a Propel admin module:

  [./symfony propel:generate-admin frontend article|INFO]

The task creates a module in the [%frontend%|COMMENT] application for the
[%article%|COMMENT] route definition found in [routing.yml|COMMENT].

For the filters to work properly, you need to add a collection route for it:

  articles:
    class: sfPropelRouteCollection
    options:
      model:              Article
      module:             article
      collection_actions: { filter: post }

EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // get configuration for the given route
    $config = new sfRoutingConfigHandler();
    $routes = $config->evaluate($this->configuration->getConfigPaths('config/routing.yml'));

    if (!isset($routes[$arguments['route']]))
    {
      throw new sfCommandException(sprintf('The route "%s" does not exist.', $arguments['route']));
    }

    $routeOptions = $routes[$arguments['route']]->getOptions();

    if (!$routes[$arguments['route']] instanceof sfPropelRouteCollection)
    {
      throw new sfCommandException(sprintf('The route "%s" is not a Propel collection route.', $arguments['route']));
    }

    $module = $routeOptions['module'];
    $model = $routeOptions['model'];

    // execute the propel:generate-module task
    $task = new sfPropelGenerateModuleTask($this->dispatcher, $this->formatter);
    $task->setCommandApplication($this->commandApplication);

    $taskOptions = array(
      '--theme='.$options['theme'],
      '--env='.$options['env'],
      '--route-prefix='.$routeOptions['name'],
      '--with-propel-route',
      '--generate-in-cache',
      '--non-verbose-templates',
    );

    if (!is_null($options['singular']))
    {
      $taskOptions[] = '--singular='.$options['singular'];
    }

    if (!is_null($options['plural']))
    {
      $taskOptions[] = '--plural='.$options['plural'];
    }

    $this->logSection('app', sprintf('Generating admin module "%s" for model "%s"', $module, $model));

    return $task->run(array($arguments['application'], $module, $model), $taskOptions);
  }
}
