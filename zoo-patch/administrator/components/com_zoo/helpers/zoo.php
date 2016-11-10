<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/**
 * ZOOs generals helper class.
 *
 * @package Component.Helpers
 * @since 2.0
 */
class ZooHelper extends AppHelper {

	/**
	 * The current application
	 * @var Application
	 */
	protected $_application;

	/**
	 * The current version
	 * @var string
	 */
	protected $_version;

	/**
	 * user groups / view levels
	 * @var array
	 */
	protected $_groups;

	/**
	 * Returns a reference to the currently active Application object.
	 *
	 * @return Application
	 * @since 2.0
	 */
	public function getApplication() {

		// check if application object already exists
		if (isset($this->_application)) {
			return $this->_application;
		}

		// get joomla and application table
		$joomla = $this->app->system->application;
		$table = $this->app->table->application;

		// handle admin
		if ($joomla->isAdmin()) {

			// create application from user state, or search for default
			$id = $joomla->getUserState('com_zooapplication');
			$apps = $table->all(array('order' => 'name'));

			if (isset($apps[$id])) {
				$this->_application = $apps[$id];
			} else if (!empty($apps)) {
				$this->_application = array_shift($apps);
			}

			return $this->_application;
		}

		// handle site
		if ($joomla->isSite()) {

			// get component params
			$params = $joomla->getParams();

			// create application from menu item params / request
			if ($item_id = $this->app->request->getInt('item_id') and $item = $this->app->table->item->get($item_id)) {
				$this->_application = $item->getApplication();
			} else if ($category_id = $this->app->request->getInt('category_id') and $category = $this->app->table->category->get($category_id)) {
				$this->_application = $category->getApplication();
			} else if ($submission_id = $this->app->request->getInt('submission_id') and $submission = $this->app->table->submission->get($submission_id)) {
				$this->_application = $submission->getApplication();
			} else if ($id = $this->app->request->getInt('app_id')) {
				$this->_application = $table->get($id);
			} else if ($id = $params->get('application') and $application = $table->get((int) $id)) {
				$this->_application = $application;
			} else {
				// try to get application from default menu item
				$menu = $this->app->system->application->getMenu('site');
				$default = $menu->getDefault();
				if (isset($default->component) && $default->component == 'com_zoo') {
					if ($app_id = $menu->getParams($default->id)->get('application')) {
						$this->_application = $table->get((int) $app_id);
					}
				}
			}

			// trigger event
			$this->app->event->dispatcher->notify($this->app->event->create(null, 'zoo:initApp', array('application' => &$this->_application)));

			return $this->_application;
		}

		return null;
	}

	/**
	 * Add help button to current toolbar to show help url in popup window.
	 *
	 * @param string $ref Help url
	 * @since 2.0
	 */
	public function toolbarHelp($ref = 'http://yootheme.com/zoo/documentation') {
		JToolBar::getInstance('toolbar')->appendButton('Link', 'help', 'Help', $ref);
	}

	/**
	 * Resize and cache image file.
	 *
	 * @param string $file
	 * @param int $width
	 * @param int $height
	 *
	 * @return string image path
	 * @since 2.0
	 */
	public function resizeImage($file, $width, $height) {

		// init vars
		$width = (int) $width;
		$height = (int) $height;
		$file_info = pathinfo($file);
		$thumbfile = $this->app->path->path('media:zoo').'/images/'.$file_info['filename'].'_'.md5($file.$width.$height).'.'.$file_info['extension'];

		// check thumbnail directory
		if (!JFolder::exists(dirname($thumbfile))) {
			JFolder::create(dirname($thumbfile));
		}

		// create or re-cache thumbnail
		if ($this->app->imagethumbnail->check() && (!is_file($thumbfile) || filemtime($file) > filemtime($thumbfile))) {
			$thumbnail = $this->app->imagethumbnail->create($file);

			if ($width > 0 && $height > 0) {
				$thumbnail->setSize($width, $height);
				$thumbnail->save($thumbfile);
			} else if ($width > 0 && $height == 0) {
				$thumbnail->sizeWidth($width);
				$thumbnail->save($thumbfile);
			} else if ($width == 0 && $height > 0) {
				$thumbnail->sizeHeight($height);
				$thumbnail->save($thumbfile);
			} else {
				if (JFile::exists($file)) {
					JFile::copy($file, $thumbfile);
				}
			}
		}

		if (is_file($thumbfile)) {
			return $thumbfile;
		}

		return $file;
	}

	/**
	 * Trigger joomla content plugins on given text.
	 *
	 * @param string $text
	 * @param array $params
	 * @param string $context
	 *
	 * @return string The text after the plugins are applied to it
	 * @since 2.0
	 */
	public function triggerContentPlugins($text, $params = array(), $context = 'com_zoo') {
		// disable loadmodule plugin on feed view
		if ($this->app->document->getType() == 'feed' && $this->app->parameter->create(JPluginHelper::getPlugin('content', 'loadmodule')->params)->get('enabled', 1)) {
			$text = preg_replace('/{loadposition\s*.*?}/i', '', $text);
		}

		return JHtml::_('content.prepare', $text, $params, $context);
	}

	/**
	 * Returns user group objects.
	 *
	 * @return array
	 * @since 2.0
	 */
	public function getGroups() {
		if (!isset($this->_groups)) {
			$this->_groups = $this->app->database->queryObjectList("SELECT id, title AS name FROM #__viewlevels", "id");
		}
		return $this->_groups;
	}

	/**
	 * Return user group object.
	 *
	 * @param string $id
	 *
	 * @return object group
	 * @since 2.0
	 */
	public function getGroup($id) {
		$groups = $this->getGroups();
		return isset($groups[$id]) ? $groups[$id] : (object) array('id' => '', 'name' => '');
	}

	/**
	 * Returns current ZOO version.
	 *
	 * @return string version
	 * @since 2.0
	 */
	public function version() {
		if (empty($this->_version) and $xml = @simplexml_load_file($this->app->path->path('component.admin:zoo.xml')) and ((string) $xml->name == 'ZOO' || (string) $xml->name == 'com_zoo')) {
			$this->_version = (string) current($xml->xpath('//version'));
		}

		return $this->_version;
	}

	/**
	 * Build page title from Joomla configuration.
	 *
	 * @param string $title
	 *
	 * @return string title
	 * @since 2.0
	 */
	public function buildPageTitle($title) {
		$dir = $this->app->system->config->get('sitename_pagetitles', 0);
		if ($dir == 1) {
			return JText::sprintf('JPAGETITLE', $this->app->system->config->get('sitename'), $title);
		} else if ($dir == 2) {
			return JText::sprintf('JPAGETITLE', $title, $this->app->system->config->get('sitename'));
		}
		return $title;
	}

	/**
	 * Puts an index.html into given directory
	 *
	 * @param string $dir
	 * @since 2.0
	 *
	 * @deprecated 3.3.13
	 */
	public function putIndexFile($dir) {
		$dir = rtrim($dir, "\\/");
		if (!JFile::exists($dir.'/index.html')) {
			$buffer = '<!DOCTYPE html><title></title>';
			JFile::write($dir.'/index.html', $buffer);
		}
	}

	/**
	 * Gets the application groups
	 *
	 * @return array groups
	 * @since 2.0
	 *
	 * @deprecated 2.5.11 use ApplicationHelper::groups()
	 */
	public function getApplicationGroups() {
		return $this->app->application->groups();
	}

	/**
	 * Gets the application layouts
	 *
	 * @param Application $application The application object
	 * @param string      $type_id     The id of the type
	 * @param string      $layout_type The type of the layout to fetch. Default: all
	 *
	 * @return array layouts
	 * @since 2.0
	 *
	 * @deprecated 2.5.11 use TypeHelper::layouts()
	 */
	public function getLayouts($application, $type_id, $layout_type = '') {
		return $this->app->type->layouts($application->getType($type_id), $layout_type);
	}

}
