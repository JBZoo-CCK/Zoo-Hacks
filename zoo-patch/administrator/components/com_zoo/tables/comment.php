<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/*
   Class: CommentTable
      The Table Class for comments.
*/
class CommentTable extends AppTable {

	public function __construct($app) {
		parent::__construct($app, ZOO_TABLE_COMMENT);
	}

	protected function _initObject($object) {

		parent::_initObject($object);

		// add to cache
		$key_name = $this->key;

		if ($object->$key_name && !key_exists($object->$key_name, $this->_objects)) {
			$this->_objects[$object->$key_name] = $object;
		}

		// trigger init event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'comment:init'));

		return $object;
	}

	/*
		Function: save
			Override. Save object to database table.

		Returns:
			Boolean.
	*/
	public function save($object) {

		// auto update all comments of a joomla user, if name/email changed
		if ($object->user_id
			&& ($row = $this->first(array('conditions' => array('user_id = ?', $object->user_id))))
			&& ($row->author != $object->author || $row->email != $object->email)) {

			// get database
			$db = $this->database;

			$query = "UPDATE ".$this->name
				." SET author = ".$db->quote($object->author).", email = ".$db->quote($object->email)
				." WHERE user_id = ".$object->user_id;
			$db->query($query);
		}

		$old_state = '';
		if ($object->id) {
			$old_state = $this->get($object->id, true)->state;
		}
		$new = !(bool) $object->id;

		$result = parent::save($object);

		// trigger save event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'comment:saved', compact('new', 'old_state')));

		return $result;
	}

	/*
		Function: getCommentsForItem
			Retrieve comments by item id

		Parameters:
			$item_id - Item id

		Returns:
			Array
	*/
	public function getCommentsForItem($item_id, $order = 'ASC', CommentAuthor $author = null, $state = Comment::STATE_APPROVED) {

		// set query options
		$order = 'created '.($order == 'ASC' ? 'ASC' : 'DESC');

		if ($author) {
			$conditions = array("item_id = ? AND (state = ? OR (author = '?' AND email = '?' AND user_id = '?' AND user_type = '?'))", $item_id, $state, $author->name, $author->email, $author->user_id, $author->getUserType());
		} else {
			$conditions = array("item_id = ? AND state = ?", $item_id, $state);
		}

		return $this->all(compact('conditions', 'order'));
	}

	/*
		Function: getLastComment
			Retrieve last comment by ip address and author (optional)

		Parameters:
			$ip - IP address
			$author - The author

		Returns:
			Comment
	*/
	public function getLastComment($ip, CommentAuthor $author = null) {

		// set query options
		$order = 'created DESC';

		if ($author && !$author->isGuest()) {
			$conditions = array("ip = '?' AND user_id = '?' AND user_type = '?'", $ip, $author->user_id, $author->getUserType());
		} else {
			$conditions = array("ip = '?'", $ip);
		}

		return $this->first(compact('conditions', 'order'));
	}

	public function getLatestComments($application, $categories, $limit) {

		// get database
		$db = $this->database;

		// get date
		$date = $this->app->date->create();
		$now  = $db->Quote($date->toSQL());
		$null = $db->Quote($db->getNullDate());

		// build where condition
		$where   = array('i.application_id = '.(int) $application->id);
		$where[] = 'c.state = ' . Comment::STATE_APPROVED;
		$where[] = " i.state = 1";
		$where[] = " (i.publish_up = ".$null." OR i.publish_up <= ".$now.")";
		$where[] = " (i.publish_down = ".$null." OR i.publish_down >= ".$now.")";
		$where[] = is_array($categories) ? "ci.category_id IN (".implode(",", $categories).")" : "ci.category_id = " . $categories;

		// build query options
		$options = array(
			'select'     => 'c.*, i.application_id',
			'from'       => $this->name.' AS c'
							. ' JOIN '.ZOO_TABLE_ITEM.' AS i ON c.item_id = i.id'
							. ' LEFT JOIN '.ZOO_TABLE_CATEGORY_ITEM.' AS ci ON i.id = ci.item_id',
			'conditions' => array(implode(' AND ', $where)),
			'order'      => 'c.created DESC',
			'group'		 => 'c.id',
			'offset' 	 => 0,
			'limit'		 => $limit);

		// query comment table
		return $this->all($options);
	}

	/*
		Function: getApprovedCommentCount
			Retrieve approved comments by author

		Parameters:
			$author - Author

		Returns:
			Int
	*/
	public function getApprovedCommentCount(CommentAuthor $author) {

		// set query options
		if ($author && !$author->isGuest()) {
			$conditions = array("state = ? AND user_id = '?' AND user_type = '?'", Comment::STATE_APPROVED, $author->user_id, $author->getUserType());
		} else {
			$conditions = array("state = ? AND user_id = '0' AND author = '?' AND email = '?'", Comment::STATE_APPROVED, $author->name, $author->email);
		}

		return $this->count(compact('conditions'));
	}

	/*
		Function: delete
			delete comment with id <$comment_id>

		Parameters:
			$object - comment object

		Returns:
			true, if comment is deleted
			false, otherwise
	*/
	public function delete($object) {

		// get database
		$db = $this->database;

		$old_parent = $object->id;
		$new_parent = $object->parent_id;

		parent::delete($object);

		$query = "UPDATE ".$this->name
			." SET parent_id = ".$new_parent
			." WHERE parent_id = ".$old_parent;
		$result = $db->query($query);

		// trigger deleted event
		$this->app->event->dispatcher->notify($this->app->event->create($object, 'comment:deleted'));

		return $result;
	}

}

/*
	Class: CommentTableException
*/
class CommentTableException extends AppException {}