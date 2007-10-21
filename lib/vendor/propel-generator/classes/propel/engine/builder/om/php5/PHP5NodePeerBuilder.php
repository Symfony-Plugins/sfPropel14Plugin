<?php

/*
 *  $Id: PHP5NodePeerBuilder.php 711 2007-10-19 12:40:28Z hans $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'propel/engine/builder/om/PeerBuilder.php';

/**
 * Generates a PHP5 tree node Peer class for user object model (OM).
 *
 * This class produces the base tree node object class (e.g. BaseMyTable) which contains all
 * the custom-built accessor and setter methods.
 *
 * This class replaces the Node.tpl, with the intent of being easier for users
 * to customize (through extending & overriding).
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 * @package    propel.engine.builder.om.php5
 */
class PHP5NodePeerBuilder extends PeerBuilder {

	/**
	 * Gets the package for the [base] object classes.
	 * @return     string
	 */
	public function getPackage()
	{
		return parent::getPackage() . ".om";
	}

	/**
	 * Returns the name of the current class being built.
	 * @return     string
	 */
	public function getUnprefixedClassname()
	{
		return $this->getBuildProperty('basePrefix') . $this->getStubNodePeerBuilder()->getClassname();
	}

	/**
	 * Adds the include() statements for files that this class depends on or utilizes.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addIncludes(&$script)
	{
	} // addIncludes()

	/**
	 * Adds class phpdoc comment and openning of class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassOpen(&$script)
	{

		$table = $this->getTable();
		$tableName = $table->getName();
		$tableDesc = $table->getDescription();

		$script .= "
/**
 * Base  static class for performing query operations on the tree contained by the '$tableName' table.
 *
 * $tableDesc
 *";
		if ($this->getBuildProperty('addTimeStamp')) {
			$now = strftime('%c');
			$script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
 *
 * $now
 *";
		}
		$script .= "
 * @package    ".$this->getPackage()."
 */
abstract class ".$this->getClassname()." {
";
	}

	/**
	 * Specifies the methods that are added as part of the basic OM class.
	 * This can be overridden by subclasses that wish to add more methods.
	 * @see        ObjectBuilder::addClassBody()
	 */
	protected function addClassBody(&$script)
	{
		$table = $this->getTable();

		// FIXME
		// - Probably the build needs to be customized for supporting
		// tables that are "aliases".  -- definitely a fringe usecase, though.

		$this->addConstants($script);

		$this->addIsCodeBase($script);

		$this->addRetrieveMethods($script);

		$this->addCreateNewRootNode($script);
		$this->addInsertNewRootNode($script);
		$this->addMoveNodeSubTree($script);
		$this->addDeleteNodeSubTree($script);

		$this->addBuildFamilyCriteria($script);
		$this->addBuildTree($script);

		$this->addPopulateNodes($script);

	}

	/**
	 * Closes class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassClose(&$script)
	{
		$script .= "
} // " . $this->getClassname() . "
";
	}

	protected function addConstants(&$script)
	{
		$table = $this->getTable();

		$npath_colname = '';
		$npath_phpname = '';
		$npath_len = 0;
		$npath_sep = '';
		foreach ($table->getColumns() as $col) {
			if ($col->isNodeKey()) {
				$npath_colname = $table->getName() . '.' . strtoupper($col->getName());
				$npath_phpname = $col->getPhpName();
				$npath_len = $col->getSize();
				$npath_sep = $col->getNodeKeySep();
				break;
			}
		}
		$script .= "
	const NPATH_COLNAME = '$npath_colname';
	const NPATH_PHPNAME = '$npath_phpname';
	const NPATH_SEP		= '$npath_sep';
	const NPATH_LEN		= $npath_len;
";
	}


	protected function addIsCodeBase(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();

		$script .= "
	/**
	 * Temp function for CodeBase hacks that will go away.
	 */
	public static function isCodeBase(\$con = null)
	{
		if (\$con === null)
			\$con = Propel::getConnection($peerClassname::DATABASE_NAME);

		return (get_class(\$con) == 'ODBCConnection' &&
				get_class(\$con->getAdapter()) == 'CodeBaseAdapter');
	}
";
	}


	protected function addCreateNewRootNode(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Create a new Node at the top of tree. This method will destroy any
	 * existing root node (along with its children).
	 *
	 * Use at your own risk!
	 *
	 * @param      $objectClassname Object wrapped by new node.
	 * @param      PDO Connection to use.
	 * @return     $nodeObjectClassname
	 * @throws     PropelException
	 */
	public static function createNewRootNode(\$obj, PDO \$con = null)
	{
		if (\$con === null)
			\$con = Propel::getConnection($peerClassname::DATABASE_NAME);

		try {
			\$con->beginTransaction();

			self::deleteNodeSubTree('1', \$con);

			\$setNodePath = 'set' . self::NPATH_PHPNAME;

			\$obj->\$setNodePath('1');
			\$obj->save(\$con);

			\$con->commit();

		} catch (PropelException \$e) {
			\$con->rollback();
			throw \$e;
		}

		return new $nodeObjectClassname(\$obj);
	}
";
	}

	protected function addInsertNewRootNode(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Inserts a new Node at the top of tree. Any existing root node (along with
	 * its children) will be made a child of the new root node. This is a
	 * safer alternative to createNewRootNode().
	 *
	 * @param      $objectClassname Object wrapped by new node.
	 * @param      PDO Connection to use.
	 * @return     $nodeObjectClassname
	 * @throws     PropelException
	 */
	public static function insertNewRootNode(\$obj, PDO \$con = null)
	{
		if (\$con === null)
			\$con = Propel::getConnection($peerClassname::DATABASE_NAME);

		try {

			\$con->beginTransaction();

			// Move root tree to an invalid node path.
			$nodePeerClassname::moveNodeSubTree('1', '0', \$con);

			\$setNodePath = 'set' . self::NPATH_PHPNAME;

			// Insert the new root node.
			\$obj->\$setNodePath('1');
			\$obj->save(\$con);

			// Move the old root tree as a child of the new root.
			$nodePeerClassname::moveNodeSubTree('0', '1' . self::NPATH_SEP . '1', \$con);

			\$con->commit();

		} catch (PropelException \$e) {
			\$con->rollback();
			throw \$e;
		}

		return new $nodeObjectClassname(\$obj);
	}
";
	}

	/**
	 * Adds the methods for retrieving nodes.
	 */
	protected function addRetrieveMethods(&$script)
	{
		$this->addRetrieveNodes($script);
		$this->addRetrieveNodeByPK($script);
		$this->addRetrieveNodeByNP($script);
		$this->addRetrieveRootNode($script);

	}

	protected function addRetrieveNodes(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();

		$script .= "
	/**
	 * Retrieves an array of tree nodes based on specified criteria. Optionally
	 * includes all parent and/or child nodes of the matching nodes.
	 *
	 * @param      Criteria Criteria to use.
	 * @param      boolean True if ancestors should also be retrieved.
	 * @param      boolean True if descendants should also be retrieved.
	 * @param      PDO Connection to use.
	 * @return     array Array of root nodes.
	 */
	public static function retrieveNodes(\$criteria, \$ancestors = false, \$descendants = false, PDO \$con = null)
	{
		\$criteria = $nodePeerClassname::buildFamilyCriteria(\$criteria, \$ancestors, \$descendants);
		\$rs = ".$this->getStubPeerBuilder()->getClassname()."::doSelectStmt(\$criteria, \$con);
		return self::populateNodes(\$rs, \$criteria);
	}
";
	}

	protected function addRetrieveNodeByPK(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Retrieves a tree node based on a primary key. Optionally includes all
	 * parent and/or child nodes of the matching node.
	 *
	 * @param      mixed $objectClassname primary key (array for composite keys)
	 * @param      boolean True if ancestors should also be retrieved.
	 * @param      boolean True if descendants should also be retrieved.
	 * @param      PDO Connection to use.
	 * @return     $nodeObjectClassname
	 */
	public static function retrieveNodeByPK(\$pk, \$ancestors = false, \$descendants = false, PDO \$con = null)
	{
		throw new PropelException('retrieveNodeByPK() not implemented yet.');
	}
";
	}

	protected function addRetrieveNodeByNP(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Retrieves a tree node based on a node path. Optionally includes all
	 * parent and/or child nodes of the matching node.
	 *
	 * @param      string Node path to retrieve.
	 * @param      boolean True if ancestors should also be retrieved.
	 * @param      boolean True if descendants should also be retrieved.
	 * @param      PDO Connection to use.
	 * @return     $objectClassname
	 */
	public static function retrieveNodeByNP(\$np, \$ancestors = false, \$descendants = false, PDO \$con = null)
	{
		\$criteria = new Criteria($peerClassname::DATABASE_NAME);
		\$criteria->add(self::NPATH_COLNAME, \$np, Criteria::EQUAL);
		\$criteria = self::buildFamilyCriteria(\$criteria, \$ancestors, \$descendants);
		\$rs = $peerClassname::doSelectStmt(\$criteria, \$con);
		\$nodes = self::populateNodes(\$rs, \$criteria);
		return (count(\$nodes) == 1 ? \$nodes[0] : null);
	}
";
	}

	protected function addRetrieveRootNode(&$script)
	{
		$script .= "
	/**
	 * Retrieves the root node.
	 *
	 * @param      string Node path to retrieve.
	 * @param      boolean True if descendants should also be retrieved.
	 * @param      PDO Connection to use.
	 * @return     ".$this->getStubNodeBuilder()->getClassname()."
	 */
	public static function retrieveRootNode(\$descendants = false, PDO \$con = null)
	{
		return self::retrieveNodeByNP('1', false, \$descendants, \$con);
	}
";
	}

	protected function addMoveNodeSubTree(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Moves the node subtree at srcpath to the dstpath. This method is intended
	 * for internal use by the BaseNode object. Note that it does not check for
	 * preexisting nodes at the dstpath. It also does not update the  node path
	 * of any Node objects that might currently be in memory.
	 *
	 * Use at your own risk!
	 *
	 * @param      string Source node path to move (root of the src subtree).
	 * @param      string Destination node path to move to (root of the dst subtree).
	 * @param      PDO Connection to use.
	 * @return     void
	 * @throws     PropelException
	 * @todo       This is currently broken for simulated 'onCascadeDelete's.
	 * @todo       Need to abstract the SQL better. The CONCAT sql function doesn't
	 *       seem to be standardized (i.e. mssql), so maybe it needs to be moved
	 *       to DBAdapter.
	 */
	public static function moveNodeSubTree(\$srcPath, \$dstPath, PDO \$con = null)
	{
		if (substr(\$dstPath, 0, strlen(\$srcPath)) == \$srcPath)
			throw new PropelException('Cannot move a node subtree within itself.');

		if (\$con === null)
			\$con = Propel::getConnection($peerClassname::DATABASE_NAME);

		/**
		 * Example:
		 * UPDATE table
		 * SET npath = CONCAT('1.3', SUBSTRING(npath, 6, 74))
		 * WHERE npath = '1.2.2' OR npath LIKE '1.2.2.%'
		 */

		\$npath = $nodePeerClassname::NPATH_COLNAME;
		//the following dot isn`t mean`t a nodeKeySeperator
		\$setcol = substr(\$npath, strpos(\$npath, '.')+1);
		\$setcollen = $nodePeerClassname::NPATH_LEN;
		\$db = Propel::getDb($peerClassname::DATABASE_NAME);

		// <hack>
		if ($nodePeerClassname::isCodeBase(\$con))
		{
			// This is a hack to get CodeBase working. It will eventually be removed.
			// It is a workaround for the following CodeBase bug:
			//   -Prepared statement parameters cannot be embedded in SQL functions (i.e. CONCAT)
			\$sql = \"UPDATE \" . $peerClassname::TABLE_NAME . \" \" .
				   \"SET \$setcol=\" . \$db->concatString(\"'\$dstPath'\", \$db->subString(\$npath, strlen(\$srcPath)+1, \$setcollen)) . \" \" .
				   \"WHERE \$npath = '\$srcPath' OR \$npath LIKE '\" . \$srcPath . $nodePeerClassname::NPATH_SEP . \"%'\";

			\$con->executeUpdate(\$sql);
		}
		else
		{
		// </hack>
			\$sql = \"UPDATE \" . $peerClassname::TABLE_NAME . \" \" .
				   \"SET \$setcol=\" . \$db->concatString('?', \$db->subString(\$npath, '?', '?')) . \" \" .
				   \"WHERE \$npath = ? OR \$npath LIKE ?\";

			\$stmt = \$con->prepareStatement(\$sql);
			\$stmt->setString(1, \$dstPath);
			\$stmt->setInt(2, strlen(\$srcPath)+1);
			\$stmt->setInt(3, \$setcollen);
			\$stmt->setString(4, \$srcPath);
			\$stmt->setString(5, \$srcPath . $nodePeerClassname::NPATH_SEP . '%');
			\$stmt->executeUpdate();
		// <hack>
		}
		// </hack>
	}
";
	}

	protected function addDeleteNodeSubTree(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Deletes the node subtree at the specified node path from the database.
	 *
	 * @param      string Node path to delete
	 * @param      PDO Connection to use.
	 * @return     void
	 * @throws     PropelException
	 * @todo       This is currently broken for simulated 'onCascadeDelete's.
	 */
	public static function deleteNodeSubTree(\$nodePath, PDO \$con = null)
	{
		if (\$con === null)
			\$con = Propel::getConnection($peerClassname::DATABASE_NAME);

		/**
		 * DELETE FROM table
		 * WHERE npath = '1.2.2' OR npath LIKE '1.2.2.%'
		 */

		\$criteria = new Criteria($peerClassname::DATABASE_NAME);
		\$criteria->add($nodePeerClassname::NPATH_COLNAME, \$nodePath, Criteria::EQUAL);
		\$criteria->addOr($nodePeerClassname::NPATH_COLNAME, \$nodePath . self::NPATH_SEP . '%', Criteria::LIKE);
// For now, we call BasePeer directly since $peerClassname tries to
// do a cascade delete.
//          $peerClassname::doDelete(\$criteria, \$con);
		BasePeer::doDelete(\$criteria, \$con);
	}
";
	}

	protected function addBuildFamilyCriteria(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Builds the criteria needed to retrieve node ancestors and/or descendants.
	 *
	 * @param      Criteria Criteria to start with
	 * @param      boolean True if ancestors should be retrieved.
	 * @param      boolean True if descendants should be retrieved.
	 * @return     Criteria
	 */
	public static function buildFamilyCriteria(\$criteria, \$ancestors = false, \$descendants = false)
	{
		/*
			Example SQL to retrieve nodepath '1.2.3' with both ancestors and descendants:

			SELECT L.NPATH, L.LABEL, test.NPATH, UCASE(L.NPATH)
			FROM test L, test
			WHERE test.NPATH='1.2.3' AND
				 (L.NPATH=SUBSTRING(test.NPATH, 1, LENGTH(L.NPATH)) OR
				  test.NPATH=SUBSTRING(L.NPATH, 1, LENGTH(test.NPATH)))
			ORDER BY UCASE(L.NPATH) ASC
		*/

		if (\$criteria === null)
			\$criteria = new Criteria($peerClassname::DATABASE_NAME);

		if (!\$criteria->getSelectColumns())
			$peerClassname::addSelectColumns(\$criteria);

		\$db = Propel::getDb(\$criteria->getDbName());

		if ((\$ancestors || \$descendants) && \$criteria->size())
		{
			// If we are retrieving ancestors/descendants, we need to do a
			// self-join to locate them. The exception to this is if no search
			// criteria is specified. In this case we're retrieving all nodes
			// anyway, so there is no need to do a self-join.

			// The left-side of the self-join will contain the columns we'll
			// use to build node objects (target node records along with their
			// ancestors and/or descendants). The right-side of the join will
			// contain the target node records specified by the initial criteria.
			// These are used to match the appropriate ancestor/descendant on
			// the left.

			// Specify an alias for the left-side table to use.
			\$criteria->addAlias('L', $peerClassname::TABLE_NAME);

			// Make sure we have select columns to begin with.
			if (!\$criteria->getSelectColumns())
				$peerClassname::addSelectColumns(\$criteria);

			// Replace any existing columns for the right-side table with the
			// left-side alias.
			\$selectColumns = \$criteria->getSelectColumns();
			\$criteria->clearSelectColumns();
			foreach (\$selectColumns as \$colName)
				\$criteria->addSelectColumn(str_replace($peerClassname::TABLE_NAME, 'L', \$colName));

			\$a = null;
			\$d = null;

			\$npathL = $peerClassname::alias('L', $nodePeerClassname::NPATH_COLNAME);
			\$npathR = $nodePeerClassname::NPATH_COLNAME;
			\$npath_len = $nodePeerClassname::NPATH_LEN;

			if (\$ancestors)
			{
				// For ancestors, match left-side node paths which are contained
				// by right-side node paths.
				\$a = \$criteria->getNewCriterion(\$npathL,
								                \"\$npathL=\" . \$db->subString(\$npathR, 1, \$db->strLength(\$npathL), \$npath_len),
								                Criteria::CUSTOM);
			}

			if (\$descendants)
			{
				// For descendants, match left-side node paths which contain
				// right-side node paths.
				\$d = \$criteria->getNewCriterion(\$npathR,
								                \"\$npathR=\" . \$db->subString(\$npathL, 1, \$db->strLength(\$npathR), \$npath_len),
								                Criteria::CUSTOM);
			}

			if (\$a)
			{
				if (\$d) \$a->addOr(\$d);
				\$criteria->addAnd(\$a);
			}
			else if (\$d)
			{
				\$criteria->addAnd(\$d);
			}

			// Add the target node path column. This is used by populateNodes().
			\$criteria->addSelectColumn(\$npathR);

			// Sort by node path to speed up tree construction in populateNodes()
			\$criteria->addAsColumn('npathlen', \$db->strLength(\$npathL));
			\$criteria->addAscendingOrderByColumn('npathlen');
			\$criteria->addAscendingOrderByColumn(\$npathL);
		}
		else
		{
			// Add the target node path column. This is used by populateNodes().
			\$criteria->addSelectColumn($nodePeerClassname::NPATH_COLNAME);

			// Sort by node path to speed up tree construction in populateNodes()
			\$criteria->addAsColumn('npathlen', \$db->strLength($nodePeerClassname::NPATH_COLNAME));
			\$criteria->addAscendingOrderByColumn('npathlen');
			\$criteria->addAscendingOrderByColumn($nodePeerClassname::NPATH_COLNAME);
		}

		return \$criteria;
	}
";
	}

	protected function addBuildTree(&$script)
	{
		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * This method reconstructs as much of the tree structure as possible from
	 * the given array of objects. Depending on how you execute your query, it
	 * is possible for the ResultSet to contain multiple tree fragments (i.e.
	 * subtrees). The array returned by this method will contain one entry
	 * for each subtree root node it finds. The remaining subtree nodes are
	 * accessible from the $nodeObjectClassname methods of the
	 * subtree root nodes.
	 *
	 * @param      array Array of $nodeObjectClassname objects
	 * @return     array Array of $nodeObjectClassname objects
	 */
	public static function buildTree(\$nodes)
	{
		// Subtree root nodes to return
		\$rootNodes = array();

		// Build the tree relations
		foreach (\$nodes as \$node)
		{
			\$sep = strrpos(\$node->getNodePath(), $nodePeerClassname::NPATH_SEP);
			\$parentPath = (\$sep !== false ? substr(\$node->getNodePath(), 0, \$sep) : '');
			\$parentNode = null;

			// Scan other nodes for parent.
			foreach (\$nodes as \$pnode)
			{
				if (\$pnode->getNodePath() === \$parentPath)
				{
					\$parentNode = \$pnode;
					break;
				}
			}

			// If parent was found, attach as child, otherwise its a subtree root
			if (\$parentNode)
				\$parentNode->attachChildNode(\$node);
			else
				\$rootNodes[] = \$node;
		}

		return \$rootNodes;
	}
";
	}

	protected function addPopulateNodes(&$script)
	{
		$table = $this->getTable();

		$peerClassname = $this->getStubPeerBuilder()->getClassname();
		$objectClassname = $this->getStubObjectBuilder()->getClassname();

		$nodePeerClassname = $this->getStubNodePeerBuilder()->getClassname();
		$nodeObjectClassname = $this->getStubNodeBuilder()->getClassname();

		$script .= "
	/**
	 * Populates the $objectClassname objects from the
	 * specified ResultSet, wraps them in $nodeObjectClassname
	 * objects and build the appropriate node relationships.
	 * The array returned by this method will only include the initial targets
	 * of the query, even if ancestors/descendants were also requested.
	 * The ancestors/descendants will be cached in memory and are accessible via
	 * the getNode() methods.
	 *
	 * @param      ResultSet
	 * @param      Criteria
	 * @return     array Array of $nodeObjectClassname objects.
	 */
	public static function populateNodes(\$rs, \$criteria)
	{
		\$nodes = array();
		\$targets = array();
		\$targetfld = count(\$criteria->getSelectColumns());
";

		if (!$table->getChildrenColumn()) {
			$script .= "
		// set the class once to avoid overhead in the loop
		\$cls = $peerClassname::getOMClass();
		\$cls = substr('.'.\$cls, strrpos('.'.\$cls, '.') + 1);
";
		}

		$script .= "
		// populate the object(s)
		while (\$rs->next())
		{
			if (!isset(\$nodes[\$rs->getString(1)]))
			{
";
		if ($table->getChildrenColumn()) {
			$script .= "
				// class must be set each time from the record row
				\$cls = $peerClassname::getOMClass(\$rs, 1);
				\$cls = substr('.'.\$cls, strrpos('.'.\$cls, '.') + 1);
";
		}

		$script .= "
		" . $this->buildObjectInstanceCreationCode('$obj', '$cls') . "
				\$obj->hydrate(\$rs);

				\$nodes[\$rs->getString(1)] = new $nodeObjectClassname(\$obj);
			}

			\$node = \$nodes[\$rs->getString(1)];

			if (\$node->getNodePath() === \$rs->getString(\$targetfld))
				\$targets[\$node->getNodePath()] = \$node;
		}

		$nodePeerClassname::buildTree(\$nodes);

		return array_values(\$targets);
	}
";
	}

} // PHP5NodePeerBuilder
