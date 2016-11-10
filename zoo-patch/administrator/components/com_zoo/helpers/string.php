<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/**
 * The general String Helper.
 *
 * @package Component.Helpers
 * @since 2.0
 */
class StringHelper extends AppHelper {

	/**
	 * wrapped class
	 * @var string
	 */
	protected $_class = 'JString';

	/**
	 * Map all functions to JRequest class
	 *
	 * @param string $method Method name
	 * @param array $args Method arguments
	 *
	 * @return mixed
	 */
	public function __call($method, $args) {
		return $this->_call(array($this->_class, $method), $args);
	}

	/**
	 * Truncates the input string.
	 *
	 * @param string $text input string
	 * @param int $length the length of the output string
	 * @param string $truncate_string the truncate string
	 *
	 * @return string The truncated string
	 * @since 2.0
	 */
	public function truncate($text, $length = 30, $truncate_string = '...') {

		if ($text == '') {
			return '';
		}

		if ($this->strlen($text) > $length) {
			$length -= min($length, strlen($truncate_string));
			$text = preg_replace('/\s+?(\S+)?$/', '', substr($text, 0, $length + 1));

			return $this->substr($text, 0, $length).$truncate_string;
		} else {
			return $text;
		}
	}

	/**
	 * Sluggifies the input string.
	 *
	 * @param string $string 		input string
	 * @param bool   $force_safe 	Do we have to enforce ASCII instead of UTF8 (default: false)
	 *
	 * @return string sluggified string
	 * @since 2.0
	 */
	public function sluggify($string, $force_safe = false) {

		$string = $this->strtolower((string) $string);
        $string = $this->str_ireplace(array('$',','), '', $string);

		if ($force_safe) {
			$string = JFilterOutput::stringURLSafe($string);
		} else {
			$string = JApplication::stringURLSafe($string);
		}

		return trim($string);
	}

    /**
     * Apply Joomla text filters based on the user's groups
     *
     * @param  string $string The string to clean
     *
     * @return string         The cleaned string
     */
    public function applyTextFilters($string) {

        // Apply the textfilters (let's reuse Joomla's ContentHelper class)
        if (!class_exists('ContentHelper')) {
            require_once JPATH_SITE . '/administrator/components/com_content/helpers/content.php';
        }

        return ContentHelper::filterText((string) $string);
    }

}