<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/*
	Class: CategoryTable
		The table class for categories.
*/
class CategoryTable extends AppTable {

	public function __construct($app) {
		parent::__construct($app, ZOO_TABLE_CATEGORY);
	}

	protected function _initObject($object) {

		parent::_initObject($object);

		// workaround for php bug, which calls constructor before filling values
		if (is_string($object->params) || is_null($object->params)) {

			// decorate data as object
			$object->params = $this->app->parameter->create($object->params);
		}
		if (is_string($object->item_ids) || is_null($object->item_ids)) {
			$object->item_ids = isset($object->item_ids) ? explode(',', $object->item_ids) : array();
			if (!empty($object->item_ids)) {
				$object->item_ids = array_combine($object->item_ids, $object->item_ids);
			}

		}

		// add to cache
		$key_name = $this->key;

		if ($object->$key_name && !key_exists($object->$key_name, $this->_objects)) {
			$this->_objects[$object->$key_name] = $object;
		}

		// trigger init event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'category:init'));

		return $object;
	}

	/*
		Function: save
			Override. Save object to database table.

		Returns:
			Boolean.
	*/
	public function save($object) {

		if ($object->name == '') {
			throw new CategoryTableException('Invalid name');
		}

		if ($object->alias == '' || $object->alias != $this->app->string->sluggify($object->alias)) {
			throw new CategoryTableException('Invalid slug');
		}

		if ($this->app->alias->category->checkAliasExists($object->alias, $object->id)) {
			throw new CategoryTableException('Slug already exists, please choose a unique slug');
		}

		if (!is_numeric($object->parent)) {
			throw new CategoryTableException('Invalid parent id');
		}

		$new = !(bool) $object->id;

		$result = parent::save($object);

		// trigger save event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'category:saved', compact('new')));

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

		// update childrens parent category
		$query = "UPDATE ".$this->name
		    	." SET parent=".$object->parent
			    ." WHERE parent=".$object->id;
		$db->query($query);

		// delete category to item relations
		$this->app->category->deleteCategoryItemRelations($object->id);

		$result = parent::delete($object);

		// trigger deleted event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'category:deleted'));

		return $result;
	}

	/*
		Function: getbyId
			Method to retrieve categories by id.

		Parameters:
			$category_id - Categoryid(s)

		Returns:
			Array - Array of categories
	*/
	public function getById($ids, $published = false){

		$ids = array_filter((array) $ids);
		if (empty($ids)) {
			return array();
		}
		$ids = array_combine($ids, $ids);
		$objects = array_intersect_key($this->_objects, $ids);
		$ids = array_diff_key($ids, $objects);

		if (!empty($ids)) {
			$where = "id IN (".implode(",", $ids).")" . ($published == true ? " AND published = 1" : "");
			$objects += $this->all(array('conditions' => $where));
		}

		usort($objects, create_function('$a, $b', 'if ($a->ordering == $b->ordering) { return 0; } return ($a->ordering < $b->ordering) ? -1 : 1;'));

		return $objects;
	}

	/*
		Function: getByName
			Method to retrieve categories by name.

		Parameters:
			$application_id - application id
			$name - Category name

		Returns:
			Array - Array of categories
	*/
	public function getByName($application_id, $name){

		if (empty($name) || empty($application_id)) {
			return array();
		}
		$conditions = "application_id=" . (int) $application_id . " AND name=" . $this->app->database->Quote($name);
		return $this->all(compact('conditions'));

	}

	/*
		Function: getAll
			Method to retrieve all categories of an application.

		Parameters:
			$application_id - Application Id
			$published - Only published categories

		Returns:
			Array - Array of categories
	*/
	public function getAll($application_id, $published = false, $item_count = false, $user = null){

		$application_id = (int) $application_id;

		if ($item_count) {

			$db = $this->database;
			$db->query('SET SESSION group_concat_max_len = 1048576');

			$select = 'c.*, GROUP_CONCAT(DISTINCT ci.item_id) as item_ids';
			$from	= $this->name . ' as c  USE INDEX (APPLICATIONID_ID_INDEX2) LEFT JOIN '.ZOO_TABLE_CATEGORY_ITEM.' as ci ON ci.category_id = c.id';

			if ($published) {

				// get dates
				$date = $this->app->date->create();
				$now  = $db->Quote($date->toSQL());
				$null = $db->Quote($db->getNullDate());

				$select = 'c.*, GROUP_CONCAT(DISTINCT i.id) as item_ids';

				$from  = $this->name . ' as c  USE INDEX (APPLICATIONID_ID_INDEX) LEFT JOIN '.ZOO_TABLE_CATEGORY_ITEM.' as ci ON ci.category_id = c.id'
                        .' LEFT JOIN '.ZOO_TABLE_ITEM.' AS i USE INDEX (MULTI_INDEX2) ON ci.item_id = i.id'
						.' AND i.'.$this->app->user->getDBAccessString($user)
						.' AND i.state = 1'
						.' AND (i.publish_up = '.$null.' OR i.publish_up <= '.$now.')'
						.' AND (i.publish_down = '.$null.' OR i.publish_down >= '.$now.')';
			}

			$where  = 'c.application_id = ?' . ($published == true ? " AND c.published = 1" : "");
			$conditions = array($where, $application_id);
			$group = 'c.id';

			$categories = $this->all(compact('select', 'from', 'conditions', 'group'));

			// sort categories
			uasort($categories, create_function('$a, $b', '
				if ($a->ordering == $b->ordering) {
					return 0;
				}
				return ($a->ordering < $b->ordering) ? -1 : 1;'
			));

		} else {
			$where = "application_id = ?" . ($published == true ? " AND published = 1" : "");

			$categories = $this->all(array('conditions' => array($where, $application_id), 'order' => 'ordering'));
		}

		return $categories;
	}

	/*
		Function: getByItemId
			Method to retrieve item's related categories.

		Parameters:
			$item_id - Item id
			$published - Only published categories

		Returns:
			Array - Related categories
	*/
	public function getByItemId($item_id, $published = false) {
		$query = 'SELECT b.*'
	            .' FROM '.ZOO_TABLE_CATEGORY_ITEM.' AS a'
	            .' JOIN '.$this->name.' AS b ON b.id = a.category_id'
			    .' WHERE a.item_id='.(int) $item_id
			    .($published == true ? " AND b.published = 1" : "");

		return $this->_queryObjectList($query, $this->key);
	}

	/*
		Function: updateorder
			Method to check/fix category ordering.

		Parameters:
			$application_id - Application id
			$parents - Parent category id(s)

		Returns:
			Boolean. True on success
	*/
	public function updateorder($application_id, $parents = array(), $order_by = 'ordering') {

		if (!is_array($parents)) {
			$parents = array($parents);
		}

		// execute update order for each parent categories
		$parents = array_unique($parents);
		foreach ($parents as $parent) {
			if (!$this->reorder('application_id = '.(int) $application_id.' AND parent = '.(int) $parent, $order_by)) {
				return false;
			}
		}

		return true;
	}

	/*
		Function: reorder
			Compacts the ordering sequence of the selected records.

		Parameters:
			$where - SQL where condition
			$order_by - SQL ORDER BY condition

		Returns:
			Boolean. True on success
	*/
	public function reorder($where = '', $order_by = 'ordering') {

		// get database
		$db = $this->database;

		// get rows
		$query = 'SELECT '.$this->key.', ordering'
		        .' FROM '.$this->name
		        .($where ? ' WHERE '.$where : '')
		        .' ORDER BY ' . $db->quoteName($order_by);
		$rows =	$db->queryObjectList($query, $this->key);

		// init vars
		$i      = 1;
		$update = array();

		// collect rows which ordering need to be updated
		foreach ($rows as $id => $row) {

			if ($row->ordering != $i) {
				$update[$i - $row->ordering][] = $id;
			}

			$i++;
		}

		// do the ordering update
		foreach ($update as $diff => $ids) {

			// build ordering update query
			$query = 'UPDATE '.$this->name
				    .sprintf(' SET ordering = (ordering'.($diff >= 0 ? '+' : '').'%s)', $diff)
				    .sprintf(' WHERE '.$this->key.(count($ids) == 1 ? ' = %s' : ' IN (%s)'), implode(',', $ids));

			// set and execute query
			$db->query($query);
		}

		return true;
	}

}

/*
	Class: CategoryTableException
*/
class CategoryTableException extends AppException {}