<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2008 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/sfPhing.class.php';

/**
 * Base class for all symfony Propel tasks.
 *
 * @package     sfPropelPlugin
 * @subpackage  task
 * @author      Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version     SVN: $Id$
 */
abstract class sfPropelBaseTask extends sfBaseTask
{
  const
    CHECK_SCHEMA        = true,
    DO_NOT_CHECK_SCHEMA = false;

  static protected
    $done = false,
    $generatorConfig = null;

  public function initialize(sfEventDispatcher $dispatcher, sfFormatter $formatter)
  {
    parent::initialize($dispatcher, $formatter);

    if (!self::$done)
    {
      set_include_path(sfConfig::get('sf_root_dir').PATH_SEPARATOR.dirname(__FILE__).'/../vendor/propel-generator/classes'.PATH_SEPARATOR.get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../vendor');

      $libDir = dirname(__FILE__).'/..';

      $autoloader = sfSimpleAutoload::getInstance();
      $autoloader->addDirectory($libDir.'/vendor/creole');
      $autoloader->addDirectory($libDir.'/vendor/propel');
      $autoloader->addDirectory($libDir.'/creole');
      $autoloader->addDirectory($libDir.'/propel');
      $autoloader->addDirectory($libDir.'/task');

      $autoloader->setClassPath('Propel', $libDir.'/propel/addon/sfPropelAutoload.php');

      $autoloader->addDirectory(sfConfig::get('sf_lib_dir').'/model');
      $autoloader->addDirectory(sfConfig::get('sf_lib_dir').'/form');
      $autoloader->register();

      // enable output buffering
      sfPhing::setOutputStream(new OutputStream(fopen('php://output', 'w')));
      sfPhing::startup();
      sfPhing::setProperty('phing.home', getenv('PHING_HOME'));

      self::$done = true;
    }
  }

  protected function schemaToYML($checkSchema = self::CHECK_SCHEMA, $prefix = '')
  {
    $schemas = sfFinder::type('file')->name('*schema.xml')->prune('doctrine')->in($this->getConfigPaths());
    if (self::CHECK_SCHEMA === $checkSchema && !count($schemas))
    {
      throw new sfCommandException('You must create a schema.xml file.');
    }

    $dbSchema = new sfPropelDatabaseSchema();
    foreach ($schemas as $schema)
    {
      $dbSchema->loadXML($schema);

      $this->logSection('schema', sprintf('converting "%s" to YML', $schema));

      $localprefix = $prefix;

      // change prefix for plugins
      if (preg_match('#plugins[/\\\\]([^/\\\\]+)[/\\\\]#', $schema, $match))
      {
        $localprefix = $prefix.$match[1].'-';
      }

      // save converted xml files in original directories
      $yml_file_name = str_replace('.xml', '.yml', basename($schema));

      $file = str_replace(basename($schema), $prefix.$yml_file_name,  $schema);
      $this->logSection('schema', sprintf('putting %s', $file));
      file_put_contents($file, $dbSchema->asYAML());
    }
  }

  protected function schemaToXML($checkSchema = self::CHECK_SCHEMA, $prefix = '')
  {
    $configPaths = $this->getConfigPaths();
    $schemas = sfFinder::type('file')->name('*schema.yml')->prune('doctrine')->in($configPaths);
    if (self::CHECK_SCHEMA === $checkSchema && !count($schemas))
    {
      throw new sfCommandException('You must create a schema.yml file.');
    }

    $dbSchema = new sfPropelDatabaseSchema();

    foreach ($schemas as $schema)
    {
      $schemaArray = sfYaml::load($schema);

      if (!is_array($schemaArray))
      {
        continue; // No defined schema here, skipping
      }

      if (!isset($schemaArray['classes']))
      {
        // Old schema syntax: we convert it
        $schemaArray = $dbSchema->convertOldToNewYaml($schemaArray);
      }

      $customSchemaFilename = str_replace(array(
        str_replace(DIRECTORY_SEPARATOR, '/', sfConfig::get('sf_root_dir')).'/',
        'plugins/',
        'config/',
        '/',
        'schema.yml'
      ), array('', '', '', '_', 'schema.custom.yml'), $schema);
      $customSchemas = sfFinder::type('file')->name($customSchemaFilename)->in($configPaths);

      foreach ($customSchemas as $customSchema)
      {
        $this->logSection('schema', sprintf('found custom schema %s', $customSchema));

        $customSchemaArray = sfYaml::load($customSchema);
        if (!isset($customSchemaArray['classes']))
        {
          // Old schema syntax: we convert it
          $customSchemaArray = $dbSchema->convertOldToNewYaml($customSchemaArray);
        }
        $schemaArray = sfToolkit::arrayDeepMerge($schemaArray, $customSchemaArray);
      }

      $dbSchema->loadArray($schemaArray);

      $this->logSection('schema', sprintf('converting "%s" to XML', $schema));

      $localprefix = $prefix;

      // change prefix for plugins
      if (preg_match('#plugins[/\\\\]([^/\\\\]+)[/\\\\]#', $schema, $match))
      {
        $localprefix = $prefix.$match[1].'-';
      }

      // save converted xml files in original directories
      $xml_file_name = str_replace('.yml', '.xml', basename($schema));

      $file = str_replace(basename($schema), $localprefix.$xml_file_name,  $schema);
      $this->logSection('schema', sprintf('putting %s', $file));
      file_put_contents($file, $dbSchema->asXML());
    }
  }

  protected function copyXmlSchemaFromPlugins($prefix = '')
  {
    $schemas = sfFinder::type('file')->name('*schema.xml')->prune('doctrine')->in($this->getPluginConfigPaths());
    foreach ($schemas as $schema)
    {
      // reset local prefix
      $localprefix = '';

      // change prefix for plugins
      if (preg_match('#plugins[/\\\\]([^/\\\\]+)[/\\\\]#', $schema, $match))
      {
        // if the plugin name is not in the schema filename, add it
        if (!strstr(basename($schema), $match[1]))
        {
          $localprefix = $match[1].'-';
        }
      }

      // if the prefix is not in the schema filename, add it
      if (!strstr(basename($schema), $prefix))
      {
        $localprefix = $prefix.$localprefix;
      }

      $this->getFilesystem()->copy($schema, 'config/'.$localprefix.basename($schema));
      if ('' === $localprefix)
      {
        $this->getFilesystem()->remove($schema);
      }
    }
  }

  protected function cleanup()
  {
    $this->getFilesystem()->remove(sfFinder::type('file')->name('generated-*schema.xml')->in('config', 'plugins'));
    $this->getFilesystem()->remove(sfFinder::type('file')->name('*schema-transformed.xml')->in('config', 'plugins'));
  }

  protected function callPhing($taskName, $checkSchema)
  {
    self::doCallPhing($taskName, $checkSchema, array(
      'quiet' => is_null($this->commandApplication) || !$this->commandApplication->isVerbose(),
      'debug' => !is_null($this->commandApplication) && $this->commandApplication->withTrace(),
    ));
  }

  protected function getConfigPaths()
  {
    return array_merge(array('config'), $this->getPluginConfigPaths());
  }

  protected function getPluginConfigPaths()
  {
    return array_map(create_function('$path', 'return $path.\'/config\';'), $this->configuration->getPluginPaths());
  }

  static public function doCallPhing($taskName, $checkSchema, $options = array())
  {
    $schemas = sfFinder::type('file')->name('*schema.xml')->relative()->follow_link()->in('config');
    if (self::CHECK_SCHEMA === $checkSchema && !$schemas)
    {
      throw new sfCommandException('You must create a schema.yml or schema.xml file.');
    }

    // Call phing targets
    $args = array();

    $options = array(
      'project.dir'       => sfConfig::get('sf_config_dir'),
      'build.properties'  => 'propel.ini',
      'propel.output.dir' => sfConfig::get('sf_root_dir'),
    );
    foreach ($options as $key => $value)
    {
      $args[] = "-D$key=$value";
    }

    // Build listener
    $args[] = '-listener';
    $args[] = 'plugins.sfPropelPlugin.lib.propel.builder.SfBuildListener';

    // Build file
    $args[] = '-f';
    $args[] = realpath(dirname(__FILE__).'/../vendor/propel-generator/build.xml');

    if (isset($options['quiet']) && $options['quiet'])
    {
      $args[] = '-quiet';
    }

    if (isset($options['debug']) && $options['debug'])
    {
      $args[] = '-debug';
    }

    // Logger
    if (DIRECTORY_SEPARATOR != '\\' && (function_exists('posix_isatty') && @posix_isatty(STDOUT)))
    {
      $args[] = '-logger';
      $args[] = 'phing.listener.AnsiColorLogger';
    }

    $args[] = $taskName;

    $m = new sfPhing();
    $m->execute($args);
    $m->runBuild();

    chdir(sfConfig::get('sf_root_dir'));
  }

  /**
   * @see GeneratorConfig::getBuildProperty()
   */
  static public function getBuildProperty($name, $default = null)
  {
    if (is_null(self::$generatorConfig))
    {
      $dispatcher = sfProjectConfiguration::getActive()->getEventDispatcher();
      $listener   = array(__CLASS__, 'listenForBuildFinished');

      $dispatcher->connect('phing.build_finished', $listener);

      ob_start();
      self::doCallPhing('configure', self::DO_NOT_CHECK_SCHEMA);
      ob_end_clean();

      $dispatcher->disconnect('phing.build_finished', $listener);
    }

    if (is_null(self::$generatorConfig))
    {
      throw new RuntimeException('Unable to capture an instance of GeneratorConfig.');
    }

    return is_null($property = self::$generatorConfig->getBuildProperty($name)) ? $default : $property;
  }

  static public function listenForBuildFinished(sfEvent $event)
  {
    require_once 'propel/engine/GeneratorConfig.php';

    self::$generatorConfig = new GeneratorConfig($event->getSubject()->getProject()->getProperties());
  }
}
