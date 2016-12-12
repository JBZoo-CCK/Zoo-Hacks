<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/*
   Class: ElementDisqus
       The Disqus element class (http://www.disqus.com)
*/
class ElementDisqus extends Element implements iSubmittable {

	/*
		Function: hasValue
			Checks if the element's value is set.

	   Parameters:
			$params - render parameter

		Returns:
			Boolean - true, on success
	*/
	public function hasValue($params = array()) {
		$value   = $this->get('value', $this->config->get('default'));
		$website = $this->config->get('website');
		return !empty($value) && !empty($website);
	}

	/*
		Function: render
			Override. Renders the element.

	   Parameters:
   			$params - render parameter

		Returns:
			String - html
	*/
	public function render($params = array()) {

		// init vars
		$developer = $this->config->get('developer');

		// render html
		if (($website = $this->config->get('website')) && $this->get('value', $this->config->get('default'))) {

			if ($layout = $this->getLayout()) {
				return $this->renderLayout($layout, compact('website', 'developer'));
			}

		}

		return null;
	}

	/*
	   Function: edit
	       Renders the edit form field.

	   Returns:
	       String - html
	*/
	public function edit() {
		return $this->app->html->_('select.booleanlist', $this->getControlName('value'), '', $this->get('value', $this->config->get('default')));
	}

	/*
		Function: renderSubmission
			Renders the element in submission.

	   Parameters:
            $params - AppData submission parameters

		Returns:
			String - html
	*/
	public function renderSubmission($params = array()) {
        return $this->edit();
	}

	/*
		Function: validateSubmission
			Validates the submitted element

	   Parameters:
            $value  - AppData value
            $params - AppData submission parameters

		Returns:
			Array - cleaned value
	*/
	public function validateSubmission($value, $params) {
		return array('value' => $value->get('value'));
	}

}