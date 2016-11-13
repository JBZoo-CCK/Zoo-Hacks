<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/*
	Class: TagTable
		The table class for tags.
*/
class TagTable extends AppTable {

	public function __construct($app) {
		parent::__construct($app, ZOO_TABLE_TAG, 'name');

		$this->class = 'stdClass';
	}

	/*
		Function: getAll
			Retrieve all tags.

		Returns:
			Array
	*/
	public function getAll($application_id = false, $search = "", $tag = "", $orderby = "", $offset = 0, $limit = 0, $published = false, $category_id = false, $type_id = false) {

		// get database
		$db = $this->database;

		// get dates
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		$query = "SELECT a.name, COUNT(a.item_id) AS items"
			." FROM ".$this->name." AS a".($application_id !== false ? ", ".ZOO_TABLE_ITEM." AS b USE INDEX (ID_APPLICATION_INDEX)" : "")
			.(($category_id) ? " LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS c ON b.id = c.item_id" : "")
			." WHERE 1"
			.($application_id !== false ? " AND a.item_id = b.id AND b.application_id = ".(int) $application_id : "")
			.(!empty($search) ? " AND LOWER(a.name) LIKE ".$db->Quote('%'.$db->escape($search, true).'%', false) : "")
			.(!empty($tag) ? " AND a.name = ".$db->Quote($tag) : "")
			.($published == true ? " AND b.state = 1"
			.(($type_id) ? " AND b.type = ".$db->Quote($type_id) : "")
			.(($category_id) ? " AND c.category_id ".(is_array($category_id) ? " IN (".implode(",", $category_id).")" : " = ".(int) $category_id) : "")
			." AND (b.publish_up = ".$null." OR b.publish_up <= ".$now.")"
			." AND (b.publish_down = ".$null." OR b.publish_down >= ".$now.")": "")
			." GROUP BY a.name"
			.($orderby ? " ORDER BY ".$orderby : "")
			.($limit ? " LIMIT ".(int) $offset.",".(int) $limit : "");

		return $db->queryObjectList($query);
	}

	/*
		Function: count
			Returns number of tags of an application

		Returns:
			int
	*/
	public function count($application_id = 0, $search = "") {

		// get database
		$db = $this->database;

		$select  = 'DISTINCT a.name';
		$from = $this->name." AS a, ".ZOO_TABLE_ITEM." AS b USE INDEX (ID_APPLICATION_INDEX)";
		$conditions = array("a.item_id = b.id AND b.application_id = ".(int) $application_id.(!empty($search) ? " AND LOWER(a.name) LIKE ".$db->Quote('%'.$db->escape($search, true).'%', false) : ""));
		$options = compact('select', 'from', 'conditions');

		return parent::count($options);
	}

	/*
		Function: getItemTags
			Method to retrieve all tags of an item.

		Parameters:
			$item_id - Item id

		Returns:
			Array - Array of tags
	*/
	public function getItemTags($item_id){

		$query = "SELECT name"
			." FROM ".$this->name
			." WHERE item_id = ".(int) $item_id;

		return $this->database->queryResultArray($query);
	}

	/*
		Function: save
			Save tags.

	  		Parameters:
				$item - Item object
	*/
	public function save($item) {

		// get database
		$db = $this->database;

		// insert new item tags
		$tags = (array) $item->getTags();

		// delete old item tags
		$query = "DELETE FROM ".$this->name
				." WHERE item_id = ".(int) $item->id;
		$db->query($query);

		if (count($tags)) {

			// remove duplicates case insensitive
			$tags = array_intersect_key($tags, array_unique(array_map('strtolower',$tags)));

			foreach ($tags as $tag) {
				$tag = str_replace(array('.', '/'), '_', $tag);
				$values[] = sprintf("(%s, %s)", (int) $item->id, $db->Quote($tag));
			}

			$query = "INSERT INTO ".$this->name
					." VALUES ".implode(", ", $values);
			$db->query($query);
		}

		// trigger deleted event
		$this->app->event->dispatcher->notify($this->app->event->create($tags, 'tag:saved', compact('item')));

	}

	/*
		Function: update
			Update tags.
	*/
	public function update($application_id, $old, $new) {

		// replace .'s with _'s
		$new = str_replace('.', '_', $new);

		// get database
		$db = $this->database;

		// get item ids
		$query = "SELECT DISTINCT a.item_id FROM ".$this->name." AS a"
		        ." LEFT JOIN ".ZOO_TABLE_ITEM." AS b USE INDEX (ID_APPLICATION_INDEX) ON a.item_id = b.id"
			    ." WHERE (a.name = ".$db->Quote($new)
                ." OR a.name = ".$db->Quote($old).")"
			    ." AND b.application_id = ".(int) $application_id;
		$ids = $db->queryResultArray($query);

		if (count($ids)) {

            // remove all item tags which have the new and the old tag
            $this->delete(array($new, $old), $application_id);

			foreach ($ids as $id) {
				$values[] = sprintf("(%s, %s)", (int) $id, $db->Quote($new));
			}

			// insert new values
			$query = "INSERT ".$this->name
                    ." VALUES ".implode(", ", $values);
			$db->query($query);
		}

	}

	/*
		Function: delete
			Delete tags.

		Returns:
			Boolean.
	*/
	public function delete($tags, $application_id = 0) {

		if (empty($tags) || $application_id == 0) {
			return true;
		}

		$tags = (array) $tags;
		$quoted_tags = array();
		foreach ($tags as $tag) {
			$quoted_tags[] = $this->database->Quote($tag);
		}

		// delete item tags
		$query = "DELETE a"
			    ." FROM ".$this->name." AS a, ".ZOO_TABLE_ITEM." AS b USE INDEX (ID_APPLICATION_INDEX)"
				." WHERE a.item_id = b.id AND b.application_id = ".(int) $application_id
			    ." AND a.name IN (".implode(",", $quoted_tags).")";

		$result = $this->database->query($query);

		// trigger deleted event
		$this->app->event->dispatcher->notify($this->app->event->create($tags, 'tag:deleted', array('application' => $this->app->table->application->get($application_id))));

		return $result;
	}

}

/*
	Class: TagTableException
*/
class TagTableException extends AppException {}