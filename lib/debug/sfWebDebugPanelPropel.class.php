<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWebDebugPanelPropel adds a panel to the web debug toolbar with Propel information.
 *
 * @package    symfony
 * @subpackage debug
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class sfWebDebugPanelPropel extends sfWebDebugPanel
{
  public function getTitle()
  {
    if ($sqlLogs = $this->getSqlLogs())
    {
      return '<img src="'.$this->webDebug->getOption('image_root_path').'/database.png" alt="SQL queries" /> '.count($sqlLogs);
    }
  }

  public function getPanelTitle()
  {
    return 'SQL queries';
  }

  public function getPanelContent()
  {
    $logs = array();
    foreach ($this->getSqlLogs() as $log)
    {
      $logs[] = sprintf('
        <li>
          <p class="sfWebDebugDatabaseQuery">%s</p>
        </li>
        ',
        $this->formatSql(htmlspecialchars($log, ENT_QUOTES, sfConfig::get('sf_charset')))
      );
    }

    return '
      <div id="sfWebDebugDatabaseLogs">
        <ol>
          '.implode("\n", $logs).'
        </ol>
      </div>
    ';
  }

  static public function listenToAddPanelEvent(sfEvent $event)
  {
    $event->getSubject()->setPanel('db', new self($event->getSubject()));
  }

  protected function getSqlLogs()
  {
    $logs = array();
    $bindings = array();
    $i = 0;
    foreach ($this->webDebug->getLogger()->getLogs() as $log)
    {
      if ('sfPropelLogger' != $log['type'])
      {
        continue;
      }

      $logs[$i++] = $log['message'];
    }

    return $logs;
  }
}
