<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/*
	Class: ItemTable
		The table class for items.
*/
class ItemTable extends AppTable {

	public function __construct($app) {
		parent::__construct($app, ZOO_TABLE_ITEM);
	}

	protected function _initObject($object) {

		parent::_initObject($object);

		// workaround for php bug, which calls constructor before filling values
		if (is_string($object->params) || is_null($object->params)) {
			// decorate data as object
			$object->params = $this->app->parameter->create($object->params);
		}

		if (is_string($object->elements) || is_null($object->elements)) {
			// decorate data as object
			$object->elements = $this->app->data->create($object->elements);
		}

		// add to cache
		$key_name = $this->key;

		if ($object->$key_name && !key_exists($object->$key_name, $this->_objects)) {
			$this->_objects[$object->$key_name] = $object;
		}

		// trigger init event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'item:init'));

		return $object;
	}

	/*
		Function: save
			Override. Save object to database table.

		Returns:
			Boolean.
	*/
	public function save($object) {

		if (!($application = $object->getApplication())) {
			throw new ItemTableException('Invalid application id');
		}

		if (!is_string($object->type) || empty($object->type)) {
			throw new ItemTableException('Invalid type id');
		}

		if ($object->name == '') {
			throw new ItemTableException('Invalid name');
		}

		if ($object->alias == '' || $object->alias != $this->app->string->sluggify($object->alias)) {
			throw new ItemTableException('Invalid slug');
		}

		if ($this->app->alias->item->checkAliasExists($object->alias, $object->id)) {
			throw new ItemTableException('Alias already exists, please choose a unique alias');
		}

		$new = !(bool) $object->id;

		// trigger save event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'item:save', compact('new')));

		$result = parent::save($object);

		// init vars
		$db           = $this->database;
		$search_data  = array();

		foreach ($object->getElements() as $id => $element) {
			// get search data
			if ($data = $element->getSearchData()) {
				$search_data[] = "(".$object->id.", ".$db->quote($id).", ".$db->quote($data).")";
			}
		}

		// delete old search data
		$query = "DELETE FROM ".ZOO_TABLE_SEARCH
				." WHERE item_id = ".(int) $object->id;
		$db->query($query);

		// insert new search data
		if (count($search_data)) {
			$query = "INSERT INTO ".ZOO_TABLE_SEARCH
					." VALUES ".implode(", ", $search_data);
			$db->query($query);
		}

		// save tags
		$this->app->table->tag->save($object);

		// trigger saved event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'item:saved', compact('new')));

		return $result;
	}

	/*
		Function: delete
			Override. Delete object from database table.

		Returns:
			Boolean.
	*/
	public function delete($object) {

		// get database
		$db = $this->database;

		// delete item to category relations
		$query = "DELETE FROM ".ZOO_TABLE_CATEGORY_ITEM
			    ." WHERE item_id = ".(int) $object->id;
		$db->query($query);

		// delete related comments
		$query = "DELETE FROM ".ZOO_TABLE_COMMENT
			    ." WHERE item_id = ".(int) $object->id;
		$db->query($query);

		// delete related search data rows
		$query = "DELETE FROM ".ZOO_TABLE_SEARCH
				." WHERE item_id = ". (int) $object->id;
		$db->query($query);

		// delete related rating data rows
		$query = "DELETE FROM ".ZOO_TABLE_RATING
				." WHERE item_id = ". (int) $object->id;
		$db->query($query);

		// delete related tag data rows
		$query = "DELETE FROM ".ZOO_TABLE_TAG
				." WHERE item_id = ". (int) $object->id;
		$db->query($query);

		$result = parent::delete($object);

		// trigger deleted event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'item:deleted'));

		return $result;
	}

	/*
		Function: hit
			Increment item hits.

		Returns:
			Boolean.
	*/
	public function hit($object) {

		// get database
		$db  = $this->database;
		$key = $this->key;

		// increment hits
		if ($object->$key) {
			$query = "UPDATE ".$this->name
				." SET hits = (hits + 1)"
				." WHERE $key = ".(int) $object->$key;
			$db->query($query);
			$object->hits++;
			return true;
		}

		return false;
	}

	/*
		Function: getApplicationItemCount
			Method to get application related item count.

		Parameters:
			$application_id - Application id

		Returns:
			Int
	*/
	public function getApplicationItemCount($application_id) {
 		$query = "SELECT count(a.id) AS item_count"
		        ." FROM ".$this->name." AS a"
		        ." WHERE a.application_id = ".(int) $application_id;

		return (int) $this->_queryResult($query);
	}

	/*
		Function: getTypeItemCount
			Method to get types related item count.

		Parameters:
			$type - Type

		Returns:
			Int
	*/
	public function getTypeItemCount($type){

		// get database
		$db = $this->database;

		$group = $type->getApplication()->getGroup();
 		$query = "SELECT count(a.id) AS item_count"
		        ." FROM ".$this->name." AS a"
		        ." JOIN ".ZOO_TABLE_APPLICATION." AS b ON a.application_id = b.id"
		        ." WHERE a.type = ".$db->Quote($type->id)
       			." AND b.application_group = ".$db->Quote($group);

		return (int) $this->_queryResult($query);

	}

	/*
		@deprecated version 2.5 beta
	*/
	public function findAll($application_id = false, $published = false, $user = null, $options = array()) {

		// get database
		$db = $this->database;

		// get date
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// set query options
		$conditions =
		     ($application_id !== false ? "application_id = ".(int) $application_id : "")
			." AND ".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND state = 1"
			." AND (publish_up = ".$null." OR publish_up <= ".$now.")"
			." AND (publish_down = ".$null." OR publish_down >= ".$now.")": "");

		return $this->all(array_merge(compact('conditions'), $options));
	}

	/*
		Function: getByCharacter
			Method to retrieve all items starting with a certain character.

		Parameters:
			$application_id - Application id
			$char - Character(s)
			$not_in - Use not in for matching multiple characters

		Returns:
			Array - Array of items
	*/
	public function getByIds($ids, $published = false, $user = null, $orderby = '', $ignore_order_priority = false){

		$ids = (array) $ids;

		if (empty($ids)) {
			return array();
		}

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT a.*"
			." FROM ".$this->name." AS a"
			.($join ? $join : "")
			." WHERE a.id IN (".implode(",", $ids).")"
			." AND a.".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND a.state = 1"
			." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
			." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
			.($order ? " ORDER BY " . $order : "");

		return $this->_queryObjectList($query);
	}

	/*
		Function: getByCharacter
			Method to retrieve all items starting with a certain character.

		Parameters:
			$application_id - Application id
			$char - Character(s)
			$not_in - Use not in for matching multiple characters
			$published
			$user
			$orderby
			$offset
			$limit

		Returns:
			Array - Array of items
	*/
	public function getByCharacter($application_id, $char, $not_in = false, $published = false, $user = null, $orderby = "", $offset = 0, $limit = 0, $ignore_order_priority = false){

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// escape and quote character array
		if (is_array($char)) {
			foreach ($char as $key => $val) {
				$char[$key] = "'".$db->escape($val)."'";
			}
		}

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT a.*"
			." FROM ".ZOO_TABLE_CATEGORY_ITEM." AS ci"
			." JOIN ".$this->name." AS a ON a.id = ci.item_id"
			.($join ? $join : "")
			." WHERE a.application_id = ".(int) $application_id
			." AND a.".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND a.state = 1"
			." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
			." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
			." AND BINARY LOWER(LEFT(a.name, 1)) ".(is_array($char) ? ($not_in ? "NOT" : null)." IN (".implode(",", $char).")" : " = '".$db->escape($char)."'")
			.($order ? " ORDER BY " . $order : "")
			.($limit ? " LIMIT ".(int) $offset.",".(int) $limit : "");

		return $this->_queryObjectList($query);
	}

	/*
		Function: getByTag
			Method to retrieve all items matching a certain tag.

		Parameters:
			$tag - Tag name
			$application_id - Application id

		Returns:
			Array - Array of items
	*/
	public function getByTag($application_id, $tag, $published = false, $user = null, $orderby = "", $offset = 0, $limit = 0, $ignore_order_priority = false){
		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT a.*"
				." FROM ".$this->name." AS a "
				." LEFT JOIN ".ZOO_TABLE_TAG." AS b ON a.id = b.item_id"
				.($join ? $join : "")
				." WHERE a.application_id = ".(int) $application_id
				." AND b.name = '".$db->escape($tag)."'"
				." AND a.".$this->app->user->getDBAccessString($user)
				.($published == true ? " AND a.state = 1"
				." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
				." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
				." GROUP BY a.id"
				.($order ? " ORDER BY " . $order : "")
				.($limit ? " LIMIT ".(int) $offset.",".(int) $limit : "");

		return $this->_queryObjectList($query);
	}

	/*
		Function: getByType
			Method to get types related items.

		Parameters:
			$type_id - Type

		Returns:
			Array - Items
	*/
	public function getByType($type_id, $application_id = false, $published = false, $user = null, $orderby = "", $offset = 0, $limit = 0, $ignore_order_priority = false){

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT a.*"
			." FROM ".$this->name." AS a"
			.($join ? $join : "")
			." WHERE a.type = ".$db->Quote($type_id)
			.($application_id !== false ? " AND a.application_id = ".(int) $application_id : "")
			." AND a.".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND a.state = 1"
			." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
			." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
			." GROUP BY a.id"
			.($order ? " ORDER BY " . $order : "")
			.($limit ? " LIMIT ".(int) $offset.",".(int) $limit : "");

		return $this->_queryObjectList($query);
	}

	/*
		Function: getByCategory
			Method to retrieve all items of a category.

		Parameters:
			$category_id - Category id(s)

		Returns:
			Array - Array of items
	*/
	public function getByCategory($application_id, $category_id, $published = false, $user = null, $orderby = "", $offset = 0, $limit = 0, $ignore_order_priority = false) {

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT a.*"
			." FROM ".$this->name." AS a"
			." LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS b ON a.id = b.item_id"
			.($join ? $join : "")
			." WHERE a.application_id = ".(int) $application_id
			." AND a.".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND a.state = 1"
			." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
			." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
            ." AND b.category_id ".(is_array($category_id) ? " IN (".implode(",", $category_id).")" : " = ".(int) $category_id)
			." GROUP BY a.id"
			.($order ? " ORDER BY " . $order : "")
			.($limit ? " LIMIT ".(int) $offset.",".(int) $limit : "");

		return $this->_queryObjectList($query);
	}

	/**
	 * Gets adjacent items
	 *
	 * @return array first value is previous item, second value is next item
	 */
	public function getPrevNext($application_id, $category_id, $item_id, $published = false, $user = null, $orderby = "", $ignore_order_priority = false) {

		// init vars
		$prev = null;
		$next = null;

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// get item ordering
		list($join, $order) = $this->_getItemOrder($orderby, $ignore_order_priority);

		$query = "SELECT ROWNUM"
				." FROM ("
					."SELECT @rownum:= @rownum+1 ROWNUM, id"
					." FROM"
					." (SELECT @rownum:=0) r,"
					." (SELECT id"
						." FROM ".$this->name." AS a"
						." LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS b ON a.id = b.item_id"
						.($join ? $join : "")
						." WHERE a.application_id = ".(int) $application_id
						." AND b.category_id ".(is_array($category_id) ? " IN (".implode(",", $category_id).")" : " = ".(int) $category_id)
						." AND a.".$this->app->user->getDBAccessString($user)
						.($published == true ? " AND a.state = 1"
						." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
						." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
						." GROUP BY a.id"
						.($order ? " ORDER BY " . $order : "")
					.") t1"
				.") t2"
				." WHERE id = ".(int) $item_id;

		if ($row = $this->_queryResult($query)) {

			$query = "SELECT ROWNUM, id"
					." FROM ("
						."SELECT @rownum:= @rownum+1 ROWNUM, id"
						." FROM"
						." (SELECT @rownum:=0) r,"
						." (SELECT id"
							." FROM ".$this->name." AS a"
							." LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS b ON a.id = b.item_id"
							.($join ? $join : "")
							." WHERE a.application_id = ".(int) $application_id
							." AND b.category_id ".(is_array($category_id) ? " IN (".implode(",", $category_id).")" : " = ".(int) $category_id)
							." AND a.".$this->app->user->getDBAccessString($user)
							.($published == true ? " AND a.state = 1"
							." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
							." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
							." GROUP BY a.id"
							.($order ? " ORDER BY " . $order : "")
						.") t1"
					.") t2"
					." WHERE ROWNUM = ".((int) $row-1) ." OR ROWNUM = ".((int) $row+1);

			if ($objects = $db->queryObjectList($query)) {
				foreach ($objects as $object) {
					if ($object->ROWNUM > $row) {
						$next = $this->get($object->id);
					} else {
						$prev = $this->get($object->id);
					}
				}
			}
		}

		return array($prev, $next);

	}

	public function getByUser($application_id, $user_id, $type = null, $search = null, $order = null, $offset = null, $limit = null, $published = false) {

		$options = $this->_getByUserOptions($application_id, $user_id, $type, $search, $published);

		// Order
		$orders = array(
			'date'   => 'a.created ASC',
			'rdate'  => 'a.created DESC',
			'alpha'  => 'a.name ASC',
			'ralpha' => 'a.name DESC',
			'hits'   => 'a.hits DESC',
			'rhits'  => 'a.hits ASC');

		$options['order'] = ($order && isset($orders[$order])) ? $orders[$order] : $orders['rdate'];

		$options = $limit ? array_merge($options, array('offset' => $offset, 'limit' => $limit)) : $options;

		return $this->all($options);
	}

	public function getItemCountByUser($application_id, $user_id, $type = null, $search = null, $published = false) {
		return $this->count(array_merge($this->_getByUserOptions($application_id, $user_id, $type, $search, $published), array('select' => 'a.id')));
	}

	protected function _getByUserOptions($application_id, $user_id, $type = null, $search = null, $published = false) {

		// select
		$select = 'a.*';

		// get from
		$from = $this->name.' AS a';

        // get data from the table
        $where = array();

        // application filter
        $where[] = 'a.application_id = ' . (int) $application_id;

        // author filter
        $where[] = 'a.created_by = ' . (int) $user_id;

        // user rights
        $where[] = 'a.'.$this->app->user->getDBAccessString($this->app->user->get((int) $user_id));

		// published
		if ($published) {

			$db = $this->database;

			// get dates
			$date = $this->app->date->create();
			$now  = $db->Quote($date->toSQL());
			$null = $db->Quote($db->getNullDate());

			// Add filters for publishing
			$where[] = 'a.state = 1';
			$where[] = "(a.publish_up = ".$null." OR a.publish_up <= ".$now.")";
			$where[] = "(a.publish_down = ".$null." OR a.publish_down >= ".$now.")";
		}

        // Type
        if ($type) {
        	if (is_array($type)) {
            	$where[] = 'a.type IN ("' . implode('", "', array_keys($type)) . '")';
        	} else {
            	$where[] = 'a.type = "' . (string) $type . '"';
        	}
		}

		// Search
		if ($search) {
			$from   .= ' LEFT JOIN '.ZOO_TABLE_TAG.' AS t ON a.id = t.item_id';
			$where[] = '(LOWER(a.name) LIKE '.$this->app->database->Quote('%'.$this->app->database->escape($search, true).'%', false)
				. ' OR LOWER(t.name) LIKE '.$this->app->database->Quote('%'.$this->app->database->escape($search, true).'%', false).')';
		}

		// conditions
		$conditions = array(implode(' AND ', $where));

		return compact('select', 'from', 'conditions');

	}

	/*
		@deprecated ZOO version 2.5 beta, use getByCategory instead
	*/
	public function getFromCategory($application_id, $category_id, $published = false, $user = null, $orderby = "", $offset = 0, $limit = 0) {
		return $this->getByCategory($application_id, $category_id, $published, $user, $orderby, $offset, $limit);
	}

	/*
		Function: getItemCountFromCategory
			Method to retrieve items count of a category.

		Parameters:
			$category_id - Category id(s)

		Returns:
			Array - Array of items
	*/
	public function getItemCountFromCategory($application_id, $category_id, $published = false, $user = null){

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		$query = "SELECT a.id"
			." FROM ".$this->name." AS a"
			." LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS b ON a.id = b.item_id"
			." WHERE a.application_id = ".(int) $application_id
			." AND b.category_id ".(is_array($category_id) ? " IN (".implode(",", $category_id).")" : " = ".(int) $category_id)
			." AND a.".$this->app->user->getDBAccessString($user)
			.($published == true ? " AND a.state = 1"
			." AND (a.publish_up = ".$null." OR a.publish_up <= ".$now.")"
			." AND (a.publish_down = ".$null." OR a.publish_down >= ".$now.")": "")
			." GROUP BY a.id";

		$db->query($query);

		return $db->getNumRows();

	}

	/*
		Function: search
			Method to retrieve all items matching search data.

		Parameters:
			$search_string - the search string
			$app_id - specify an application id to limit the search scope

		Returns:
			Array - Array of items
	*/
	public function search($search_string, $app_id = 0) {

		// get database
		$db = $this->database;
		$db_search = $db->Quote('%'.$db->escape( $search_string, true ).'%', false);

		$query = "SELECT a.*"
				." FROM ".$this->name." AS a"
				." LEFT JOIN ".ZOO_TABLE_SEARCH." AS b ON a.id = b.item_id"
				." WHERE (LOWER(b.value) LIKE LOWER(" . $db_search . ")"
				." OR LOWER(a.name) LIKE LOWER(" . $db_search . "))"
				. (empty($app_id) ? "" : " AND application_id = " . $app_id)
				." AND a.searchable=1"
				." GROUP BY a.id";

		return $this->_queryObjectList($query);
	}

	/*
		Function: searchElements
			Method to retrieve all items matching search data.

		Parameters:
			$elements_array - key = element_name, value = search string
			$app_id - specify an application id to limit the search scope

		Returns:
			Array - Array of items
	*/
	public function searchElements($elements_array, $app_id = 0) {

		// get database
		$db = $this->database;

		$i = 0;
		$join = array();
		$where = array();
		foreach ($elements_array as $name => $search_string) {
			$as = "table" . $i;
			$db_name = $db->Quote($db->escape( $name, true ), false);
			$db_search = $db->Quote('%'.$db->escape( $search_string, true ).'%', false);
			$join[] = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS " . $as . " ON a.id = " . $as . ".item_id";
			$where[] = $as.".element_id = ".$db_name." AND LOWER(".$as.".value) LIKE LOWER(".$db_search.")";
			$i++;
		}

		$query = "SELECT a.*"
				." FROM ".$this->name." AS a "
				. implode(" ", $join)
				." WHERE "
				. implode(" AND ", $where)
				. (empty($app_id) ? "" : " AND application_id = " . $app_id)
				." AND a.searchable=1"
				." GROUP BY a.id";

		return $this->_queryObjectList($query);
	}

	/*
		Function: getUsers
			Method to get users of items

		Parameters:
			$app_id - Application id

		Returns:
			Array - Array of items
	*/
	public function getUsers($app_id) {
		$query = 'SELECT DISTINCT u.id, u.name'
			    .' FROM '.$this->name.' AS i'
			    .' JOIN #__users AS u ON i.created_by = u.id'
			    . ((empty($app_id)) ? "" : " WHERE i.application_id = ".$app_id);

		return $this->database->queryObjectList($query, 'id');
	}

	/*
		@deprecated version 2.5.5, use parents has() function
	*/
	public function isInitialized($key) {
		return $this->has($key);
	}

	protected function _getItemOrder($order, $ignore_order_priority = false) {

		// if string, try to convert ordering
		if (is_string($order)) {
			$order = $this->app->itemorder->convert($order);
		}

		$result = array(null, null);
		$order = (array) $order;

		if (in_array('_ignore_priority', $order)) {
			$ignore_order_priority = true;
			unset($order['_ignore_priority']);
		}

		// trigger order event
		$this->app->event->dispatcher->notify($this->app->event->create($order, 'item:changeorder'));

		// remove empty and duplicate values
		$order = array_unique(array_filter($order));

		// if random return immediately
		if (in_array('_random', $order)) {
			$result[1] = 'RAND()';
			return $result;
		}

		// get order dir
		if (($index = array_search('_reversed', $order)) !== false) {
			$reversed = 'DESC';
			unset($order[$index]);
		} else {
			$reversed = 'ASC';
		}

		// get ordering type
		$alphanumeric = false;
		if (($index = array_search('_alphanumeric', $order)) !== false) {
			$alphanumeric = true;
			unset($order[$index]);
		}

		// set default ordering attribute
		if (empty($order)) {
			$order[] = '_itemname';
		}

		// if there is a none core element present, ordering will only take place for those elements
		if (count($order) > 1) {
			$order = array_filter($order, create_function('$a', 'return strpos($a, "_item") === false;'));
		}

		// order by core attribute
		foreach ($order as $element) {

			if (strpos($element, '_item') === 0) {
				$var = str_replace('_item', '', $element);
				if ($alphanumeric) {
					$result[1] = $reversed == 'ASC' ? "a.$var+0<>0 DESC, a.$var+0, a.$var" : "a.$var+0<>0, a.$var+0 DESC, a.$var DESC";
				} else {
					$result[1] = $reversed == 'ASC' ? "a.$var" : "a.$var DESC";
				}

			}
		}

		// else order by elements
		if (!isset($result[1])) {
			$result[0] = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS s ON a.id = s.item_id AND s.element_id IN ('".implode("', '", $order)."')";
			if ($alphanumeric) {
				$result[1] = $reversed == 'ASC' ? "ISNULL(s.value), s.value+0<>0 DESC, s.value+0, s.value" : "s.value+0<>0, s.value+0 DESC, s.value DESC";
			} else {
				$result[1] = $reversed == 'ASC' ? "s.value" : "s.value DESC";
			}
		}

		// If there wasn't _ignore_priority in the order array, prefix priority
		if (!$ignore_order_priority) {
			$result[1] = $result[1] ? 'a.priority DESC, ' . $result[1] : 'a.priority DESC';
		}

		// trigger init event
		$this->app->event->dispatcher->notify($this->app->event->create($order, 'item:orderquery', array('result' => &$result)));

		return $result;

	}

}

/*
	Class: ItemTableException
*/
class ItemTableException extends AppException {}