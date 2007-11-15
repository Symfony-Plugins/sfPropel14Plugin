<?php



class AuthorArticleMapBuilder implements MapBuilder {

	
	const CLASS_NAME = 'lib.model.map.AuthorArticleMapBuilder';

	
	private $dbMap;

	
	public function isBuilt()
	{
		return ($this->dbMap !== null);
	}

	
	public function getDatabaseMap()
	{
		return $this->dbMap;
	}

	
	public function doBuild()
	{
		$this->dbMap = Propel::getDatabaseMap(AuthorArticlePeer::DATABASE_NAME);

		$tMap = $this->dbMap->addTable(AuthorArticlePeer::TABLE_NAME);
		$tMap->setPhpName('AuthorArticle');
		$tMap->setClassname('AuthorArticle');

		$tMap->setUseIdGenerator(true);

		$tMap->addForeignKey('AUTHOR_ID', 'AuthorId', 'INTEGER', 'author', 'ID', false, null);

		$tMap->addForeignKey('ARTICLE_ID', 'ArticleId', 'INTEGER', 'article', 'ID', false, null);

		$tMap->addPrimaryKey('ID', 'Id', 'INTEGER', true, null);

	} 
} 