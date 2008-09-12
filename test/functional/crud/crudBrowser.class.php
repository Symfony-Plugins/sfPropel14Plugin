<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CrudBrowser extends sfTestBrowser
{
  protected
    $urlPrefix = 'article',
    $projectDir = '';

  public function setup($options)
  {
    $this->projectDir = dirname(__FILE__).'/../fixtures';
    $this->cleanup();

    chdir($this->projectDir);
    $task = new sfPropelGenerateModuleTask(new sfEventDispatcher(), new sfFormatter());
    $options[] = 'env=test';
    $task->run(array('crud', 'article', 'Article'), $options);

    require_once($this->projectDir.'/config/ProjectConfiguration.class.php');
    sfContext::createInstance(ProjectConfiguration::getApplicationConfiguration('crud', 'test', true, $this->projectDir));

    return $options;
  }

  public function teardown()
  {
    $this->cleanup();

    return $this;
  }

  public function browse($options)
  {
    $options = $this->setup($options);

    // list page
    $this->test()->diag('list page');
    $this->
      get('/'.$this->urlPrefix)->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'index')->

      checkResponseElement('h1', ucfirst($this->urlPrefix).' List')->

      checkResponseElement('table thead tr th:nth(0)', 'Id')->
      checkResponseElement('table thead tr th:nth(1)', 'Title')->
      checkResponseElement('table thead tr th:nth(2)', 'Body')->
      checkResponseElement('table thead tr th:nth(3)', 'Online')->
      checkResponseElement('table thead tr th:nth(4)', 'Excerpt')->
      checkResponseElement('table thead tr th:nth(5)', 'Category')->
      checkResponseElement('table thead tr th:nth(6)', 'Created at')->
      checkResponseElement('table thead tr th:nth(7)', 'End date')->
      checkResponseElement('table thead tr th:nth(8)', 'Book')->

      checkResponseElement('table tbody tr td:nth(0)', '1')->
      checkResponseElement('table tbody tr td:nth(1)', 'foo title')->
      checkResponseElement('table tbody tr td:nth(2)', 'bar body')->
      checkResponseElement('table tbody tr td:nth(3)', '1')->
      checkResponseElement('table tbody tr td:nth(4)', 'foo excerpt')->
      checkResponseElement('table tbody tr td:nth(5)', '1')->
      checkResponseElement('table tbody tr td:nth(6)', '/^\d{4}\-\d{2}\-\d{2} \d{2}\:\d{2}\:\d{2}$/')->
      checkResponseElement('table tbody tr td:nth(7)', '')->
      checkResponseElement('table tbody tr td:nth(8)', '')->
      checkResponseElement(sprintf('a[href*="/%s/new"]', $this->urlPrefix))->
      checkResponseElement(sprintf('tbody a[href*="/%s/1%s"]', $this->urlPrefix, in_array('with-show', $options) ? '' : '/edit'))->
      checkResponseElement(sprintf('tbody a[href*="/%s/2%s"]', $this->urlPrefix, in_array('with-show', $options) ? '' : '/edit'))
    ;

    // create page
    $this->test()->diag('create page');
    $this->
      click('New')->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'new')->
      isRequestParameter('id', null)->
      checkResponseElement('h1', 'New '.ucfirst($this->urlPrefix))->
      checkResponseElement(sprintf('a[href*="/%s"]', $this->urlPrefix), 'Cancel')->
      checkResponseElement(sprintf('a[href*="/%s/"]', $this->urlPrefix), false)->
      checkFormValues(array(
        'title'               => '',
        'body'                => '',
        'online'              => false,
        'category_id'         => 0,
        'end_date'            => array('year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''),
        'book_id'             => 0,
        'author_article_list' => array(),
      ))
    ;

    // save
    $this->test()->diag('save');
    $this->saveValues($options, array(
      'title'               => 'my real title',
      'body'                => 'my real body',
      'online'              => true,
      'category_id'         => 2,
      'end_date'            => array('year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => ''),
      'book_id'             => null,
      'author_article_list' => array(1, 2),
    ), 3, true);

    // go back to the list
    $this->test()->diag('go back to the list');
    $this->
      click('Cancel')->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'index')
    ;

    // edit page
    $this->test()->diag('edit page');
    if (!in_array('with-show', $options) && ($options['with-show'] === true))
    {
      $this->click('3');
    }
    else
    {
      $this->get(sprintf('/%s/3/edit', $this->urlPrefix));
    }

    $this->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'edit')->
      isRequestParameter('id', 3)->
      checkResponseElement('h1', 'Edit '.ucfirst($this->urlPrefix))->
      checkResponseElement(sprintf('a[href*="/%s"]', $this->urlPrefix), 'Cancel')->
      checkResponseElement(sprintf('a[href*="/%s/3"]', $this->urlPrefix), 'Delete')->
      checkResponseElement(sprintf('a[href*="/%s/3"][onclick*="confirm"]', $this->urlPrefix))->
      checkResponseElement('table tbody th:nth(0)', 'Title')->
      checkResponseElement('table tbody th:nth(1)', 'Body')->
      checkResponseElement('table tbody th:nth(2)', 'Online')->
      checkResponseElement('table tbody th:nth(3)', 'Excerpt')->
      checkResponseElement('table tbody th:nth(4)', 'Category id')->
      checkResponseElement('table tbody th:nth(5)', 'Created at')->
      checkResponseElement('table tbody th:nth(6)', 'End date')->
      checkResponseElement('table tbody th:nth(7)', 'Book id')->
      checkResponseElement('table tbody th:nth(8)', 'Author article list')->
      checkResponseElement('table tbody th', 9)->
      checkResponseElement('table tbody td', 9)->
      checkResponseElement('table tbody td select[id="article_category_id"][name="article[category_id]"] option', 2)->
      checkResponseElement('table tbody td select[id="article_book_id"][name="article[book_id]"] option', 2)
    ;

    // save / validation
    $this->test()->diag('save / validation');
    $values = array(
      'id'                  => 1009299,
      'title'               => '',
      'body'                => 'my body',
      'online'              => true,
      'excerpt'             => 'my excerpt',
      'category_id'         => null,
      'end_date'            => array('year' => 0, 'month' => 0, 'day' => 15, 'hour' => '10', 'minute' => '20'),
      'book_id'             => 149999,
      'author_article_list' => array(0, 5),
    );

    $this->
      click('Save', array('article' => $values))->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'update')->
      checkFormValues(array_merge($values, array(
        'end_date' => array('year' => null, 'month' => null, 'day' => 15, 'hour' => '10', 'minute' => '20')))
      )->
      checkResponseElement('ul[class="error_list"] li:contains("Required.")', 2)->
      checkResponseElement('ul[class="error_list"] li:contains("Invalid.")', 4)
    ;

    // save
    $this->test()->diag('save');
    $this->saveValues($options, array(
      'id'                  => 3,
      'title'               => 'my title',
      'body'                => 'my body',
      'online'              => false,
      'category_id'         => 1,
      'end_date'            => array('year' => 2005, 'month' => 10, 'day' => 15, 'hour' => '10', 'minute' => '20'),
      'book_id'             => 1,
      'author_article_list' => array(1, 3),
    ), 3, false);

    // go back to the list
    $this->test()->diag('go back to the list');
    $this->
      click('Cancel')->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'index')
    ;

    // delete
    $this->test()->diag('delete');
    $this->
      get(sprintf('/%s/3/edit', $this->urlPrefix))->

      click('Delete', array(), array('method' => 'delete'))->
      isStatusCode(302)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'delete')->
      isRedirected()->
      followRedirect()->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'index')->

      get(sprintf('/%s/3/edit', $this->urlPrefix))->
      isStatusCode(404)
    ;

    if (in_array('with-show', $options))
    {
      // show page
      $this->test()->diag('show page');
      $this->
        get(sprintf('/%s/2', $this->urlPrefix))->
        isStatusCode(200)->
        isRequestParameter('module', $this->urlPrefix)->
        isRequestParameter('action', 'show')->
        isRequestParameter('id', 2)->
        checkResponseElement(sprintf('a[href*="/%s/2%s"]', $this->urlPrefix, in_array('with-show', $options) ? '' : '/edit'), 'Edit')->
        checkResponseElement(sprintf('a[href*="/%s"]', $this->urlPrefix), 'List', array('position' => 1))->
        checkResponseElement('body table tbody tr:nth(0)', '/Id\:\s+2/')->
        checkResponseElement('body table tbody tr:nth(1)', '/Title\:\s+foo foo title/')->
        checkResponseElement('body table tbody tr:nth(2)', '/Body\:\s+bar bar body/')->
        checkResponseElement('body table tbody tr:nth(3)', '/Online\:\s+/')->
        checkResponseElement('body table tbody tr:nth(4)', '/Excerpt\:\s+foo excerpt/')->
        checkResponseElement('body table tbody tr:nth(5)', '/Category\:\s+2/')->
        checkResponseElement('body table tbody tr:nth(6)', '/Created at\:\s+[0-9\-\:\s]+/')->
        checkResponseElement('body table tbody tr:nth(7)', '/End date\:\s+[0-9\-\:\s]+/')->
        checkResponseElement('body table tbody tr:nth(8)', '/Book\:\s+/')
      ;
    }
    else
    {
      $this->get(sprintf('/%s/show/id/2', $this->urlPrefix))->isStatusCode(404);
    }

    $this->teardown();

    return $this;
  }

  public function saveValues($options, $values, $id, $creation)
  {
    $this->
      click('Save', array('article' => $values))->
      isRedirected()->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', $creation ? 'create' : 'update')
    ;

    $this->
      followRedirect()->
      isStatusCode(200)->
      isRequestParameter('module', $this->urlPrefix)->
      isRequestParameter('action', 'edit')->
      isRequestParameter('id', $id)->
      checkFormValues($values)
    ;

    return $this;
  }

  public function checkFormValues(array $values)
  {
    return $this->
      checkResponseElement(sprintf('table tbody td input[id="article_title"][name="article[title]"][value="%s"]', $values['title']))->

      checkResponseElement('table tbody td textarea[id="article_body"][name="article[body]"]', $values['body'])->

      checkResponseElement(sprintf('table tbody td input[id="article_online"][name="article[online]"][type="checkbox"]%s', $values['online'] ? '[checked="checked"]' : ''))->

      checkResponseElement(sprintf('table tbody td select[id="article_category_id"][name="article[category_id]"] option[value="1"]%s', $values['category_id'] == 1 ? '[selected="selected"]' : ''), 'Category 1')->
      checkResponseElement(sprintf('table tbody td select[id="article_category_id"][name="article[category_id]"] option[value="2"]%s', $values['category_id'] == 2 ? '[selected="selected"]' : ''), 'Category 2')->

      checkResponseElement(sprintf('table tbody td select[id="article_book_id"][name="article[book_id]"] option[value=""]%s', $values['book_id'] == '' ? '[selected="selected"]' : ''), '')->
      checkResponseElement(sprintf('table tbody td select[id="article_book_id"][name="article[book_id]"] option[value="1"]%s', $values['book_id'] == 1 ? '[selected="selected"]' : ''), 'The definitive guide to symfony')->

      checkResponseElement(sprintf('table tbody td select[id="article_author_article_list"][name="article[author_article_list][]"] option[value="1"]%s', in_array(1, $values['author_article_list']) ? '[selected="selected"]' : ''), 'Fabien')->
      checkResponseElement(sprintf('table tbody td select[id="article_author_article_list"][name="article[author_article_list][]"] option[value="2"]%s', in_array(2, $values['author_article_list']) ? '[selected="selected"]' : ''), 'Thomas')->
      checkResponseElement(sprintf('table tbody td select[id="article_author_article_list"][name="article[author_article_list][]"] option[value="3"]%s', in_array(3, $values['author_article_list']) ? '[selected="selected"]' : ''), 'Hélène')->

      checkResponseElement('table tbody td select[id="article_end_date_year"][name="article[end_date][year]"] option[selected="selected"]', (string) $values['end_date']['year'])->
      checkResponseElement('table tbody td select[id="article_end_date_month"][name="article[end_date][month]"] option[selected="selected"]', (string) $values['end_date']['month'])->
      checkResponseElement('table tbody td select[id="article_end_date_day"][name="article[end_date][day]"] option[selected="selected"]', (string) $values['end_date']['day'])->
      checkResponseElement('table tbody td select[id="article_end_date_hour"][name="article[end_date][hour]"] option[selected="selected"]', (string) $values['end_date']['hour'])->
      checkResponseElement('table tbody td select[id="article_end_date_minute"][name="article[end_date][minute]"] option[selected="selected"]', (string) $values['end_date']['minute'])
    ;
  }

  protected function clearDirectory($dir)
  {
    sfToolkit::clearDirectory($dir);
    if (is_dir($dir))
    {
      rmdir($dir);
    }
  }

  protected function cleanup()
  {
    $this->clearDirectory(sprintf($this->projectDir.'/apps/crud/modules/%s', $this->urlPrefix));
    $this->clearDirectory(sprintf($this->projectDir.'/cache/crud/test/modules/auto%s', ucfirst($this->urlPrefix)));
    $this->clearDirectory($this->projectDir.'/test/functional/crud');
  }
}
