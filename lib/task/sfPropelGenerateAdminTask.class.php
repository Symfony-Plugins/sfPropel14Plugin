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
      new sfCommandArgument('route_or_model', sfCommandArgument::REQUIRED, 'The route name or the model class'),
    ));

    $this->addOptions(array(
      new sfCommandOption('module', null, sfCommandOption::PARAMETER_REQUIRED, 'The module name', null),
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

  [./symfony propel:generate-admin frontend Article|INFO]

The task creates a module in the [%frontend%|COMMENT] application for the
[%Article%|COMMENT] model.

The task creates a route for you in the application [routing.yml|COMMENT].

You can also generate a Propel admin module by passing a route name:

  [./symfony propel:generate-admin frontend article|INFO]

The task creates a module in the [%frontend%|COMMENT] application for the
[%article%|COMMENT] route definition found in [routing.yml|COMMENT].

For the filters and batch actions to work properly, you need to add
the [with_wildcard_routes|COMMENT] option to the route:

  article:
    class: sfPropelRouteCollection
    options:
      model:                Article
      with_wildcard_routes: true
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // get configuration for the given route
    if (false !== ($route = $this->getRouteFromName($arguments['route_or_model'])))
    {
      $arguments['route'] = $route;
      $arguments['route_name'] = $arguments['route_or_model'];

      return $this->generateForRoute($arguments, $options);
    }

    // is it a model class name
    if (!class_exists($arguments['route_or_model']))
    {
      throw new sfCommandException(sprintf('The route "%s" does not exist and there is no "%s" class.', $arguments['route_or_model'], $arguments['route_or_model']));
    }

    $r = new ReflectionClass($arguments['route_or_model']);
    if (!$r->isSubclassOf('BaseObject'))
    {
      throw new sfCommandException(sprintf('"%s" is not a Propel class.', $arguments['route_or_model']));
    }

    // create a route
    $model = $arguments['route_or_model'];
    $name = strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), '\\1_\\2', $model));

    $routing = sfConfig::get('sf_app_config_dir').'/routing.yml';
    $content = file_get_contents($routing);
    $routesArray = sfYaml::load($content);

    if (!isset($routesArray[$name]))
    {
      $primaryKey = 'id';
      $map = call_user_func(array($model.'PEER', 'getTableMap'));
      foreach ($map->getColumns() as $column)
      {
        if ($column->isPrimaryKey())
        {
          $primaryKey = call_user_func(array(constant($model.'::PEER'), 'translateFieldName'), $column->getPhpName(), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_FIELDNAME);
          break;
        }
      }

      $module = $options['module'] ? $options['module'] : $name;
      $content = sprintf(<<<EOF
%s:
  class: sfPropelRouteCollection
  options:
    model:                %s
    module:               %s
    prefix_path:          /%s
    column:               %s
    with_wildcard_routes: true


EOF
      , $name, $model, $module, isset($options['plural']) ? $options['plural'] : $module, $primaryKey).$content;

      $this->logSection('file+', $routing);
      file_put_contents($routing, $content);
    }

    $arguments['route'] = $this->getRouteFromName($name);
    $arguments['route_name'] = $name;

    return $this->generateForRoute($arguments, $options);
  }

  protected function generateForRoute($arguments, $options)
  {
    $routeOptions = $arguments['route']->getOptions();

    if (!$arguments['route'] instanceof sfPropelRouteCollection)
    {
      throw new sfCommandException(sprintf('The route "%s" is not a Propel collection route.', $arguments['route_name']));
    }

    $module = $routeOptions['module'];
    $model = $routeOptions['model'];

    // execute the propel:generate-module task
    $task = new sfPropelGenerateModuleTask($this->dispatcher, $this->formatter);
    $task->setCommandApplication($this->commandApplication);
    $task->setConfiguration($this->configuration);

    $this->logSection('app', sprintf('Generating admin module "%s" for model "%s"', $module, $model));

    return $task->run(array($arguments['application'], $module, $model), array(
      'theme'                 => $options['theme'],
      'route-prefix'          => $routeOptions['name'],
      'with-propel-route'     => true,
      'generate-in-cache'     => true,
      'non-verbose-templates' => true,
      'singular'              => $options['singular'],
      'plural'                => $options['plural'],
    ));
  }

  protected function getRouteFromName($name)
  {
    $config = new sfRoutingConfigHandler();
    $routes = $config->evaluate($this->configuration->getConfigPaths('config/routing.yml'));

    if (isset($routes[$name]))
    {
      return $routes[$name];
    }

    return false;
  }
}
