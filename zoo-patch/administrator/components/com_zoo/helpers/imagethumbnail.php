<?php
/**
 * @package   com_zoo
 * @author    YOOtheme http://www.yootheme.com
 * @copyright Copyright (C) YOOtheme GmbH
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

/**
 * Image thumbnail helper class.
 *
 * @package Component.Helpers
 * @since 2.0
 */
class ImageThumbnailHelper extends AppHelper {

	/**
	 * Creates an AppImageThumbnail instance
	 *
	 * @param string $file The filepath
	 *
	 * @return AppImageThumbnail
	 *
	 * @since 2.0
	 */
	public function create($file) {
		return $this->app->object->create('AppImageThumbnail', array($file));
	}

	/**
	 * Checks for the required php functions
	 *
	 * @return boolean
	 *
	 * @since 2.0
	 */
    public function check() {
		$gd_functions = array(
			'getimagesize',
			'imagecreatefromgif',
			'imagecreatefromjpeg',
			'imagecreatefrompng',
			'imagecreatetruecolor',
			'imagecopyresized',
			'imagecopy',
			'imagegif',
			'imagejpeg',
			'imagepng'
			);

		foreach ($gd_functions as $name) {
			if (!function_exists($name)) return false;
		}

		return true;
    }

}

/**
 * Image thumbnail class.
 *
 * @package Component.Helpers
 * @since 2.0
 */
class AppImageThumbnail {

	/**
	 * App instance
	 *
	 * @var App
	 * @since 2.0
	 */
	public $app;

	/**
	 * The image file path
	 * @var string
	 */
	public $img_file;

	/**
	 * The image format
	 * @var string
	 */
	public $img_format;

	/**
	 * The image source
	 * @var resource
	 */
	public $img_source;

	/**
	 * The image width
	 * @var string
	 */
	public $img_width;

	/**
	 * The image height
	 * @var string
	 */
	public $img_height;

	/**
	 * The thumb width
	 * @var string
	 */
	public $thumb_width;

	/**
	 * The thumb height
	 * @var string
	 */
	public $thumb_height;

	/**
	 * The thumb resize
	 * @var boolean
	 */
	public $thumb_resize;

	/**
	 * The thumb quality
	 * @var int
	 */
	public $thumb_quality;

	/**
	 * Class constructor
	 *
	 * @param string $file The file path.
	 * @since 2.0
	 */
    public function __construct($file) {

        $this->img_file      = $file;
        $this->thumb_resize  = true;
        $this->thumb_quality = 90;

		// get image info
		list($width, $height, $type, $attr) = @getimagesize($this->img_file, $info);

		// set image dimensions and type
		if (is_array($info)) {

	        $this->img_width    = $width;
	        $this->img_height   = $height;
	        $this->thumb_width  = $width;
	        $this->thumb_height = $height;

			switch ($type) {
	            case 1:
	                $this->img_format = 'gif';
	            	$this->img_source = imagecreatefromgif($this->img_file);
	                break;
	            case 2:
                	$this->img_format = 'jpeg';
	            	$this->img_source = imagecreatefromjpeg($this->img_file);
	                break;
	            case 3:
            		$this->img_format = 'png';
	            	$this->img_source = imagecreatefrompng($this->img_file);
	                break;
	            default:
	                $this->img_format = null;
	            	$this->img_source = null;
	                break;
	        }
		}
    }

	/**
	 * Set resize
	 *
	 * @param boolean $resize Resize value
	 *
	 * @return void
	 * @since 2.0
	 */
    public function setResize($resize) {
		$this->thumb_resize = $resize;
    }

	/**
	 * Set thumb dimensions
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return void
	 * @since 2.0
	 */
    public function setSize($width, $height) {
		$this->thumb_width  = $width;
		$this->thumb_height = $height;
    }

	/**
	 * Size thumb width
	 *
	 * @param int $width
	 *
	 * @return void
	 * @since 2.0
	 */
    public function sizeWidth($width) {
		$this->thumb_width  = $width;
		$this->thumb_height = @($width / $this->img_width) * $this->img_height;
    }

	/**
	 * Size thumb height
	 *
	 * @param int $height
	 *
	 * @return void
	 * @since 2.0
	 */
    public function sizeHeight($height) {
		$this->thumb_width  = @($height / $this->img_height) * $this->img_width;
		$this->thumb_height = $height;
    }

	/**
	 * Save file
	 *
	 * @param string $file the file to save
	 *
	 * @return boolean true on success
	 * @since 2.0
	 */
    public function save($file) {
		$return = false;

		if ($this->img_format) {

			$src   = $this->img_source;
			$src_x = 0;
			$src_y = 0;

	        // smart resize thumbnail image
			if ($this->thumb_resize) {
				$resized_width  = @($this->thumb_height / $this->img_height) * $this->img_width;
				$resized_height = @($this->thumb_width / $this->img_width) * $this->img_height;

				if ($this->thumb_width <= $resized_width) {
					$width  = $resized_width;
					$height = $this->thumb_height;
					$src_x  = intval(($resized_width - $this->thumb_width) / 2);
				} else {
					$width  = $this->thumb_width;
					$height = $resized_height;
					$src_y  = intval(($resized_height - $this->thumb_height) / 2);
				}

				$src = imagecreatetruecolor($width, $height);

				// save transparent colors
				if ($this->img_format == 'png') {
					imagecolortransparent($src, imagecolorallocate($src, 0, 0, 0));
					imagealphablending($src, false);
					imagesavealpha($src, true);
				}

				// get and reallocate transparency-color for gif
				if ($this->img_format == 'gif') {
					imagealphablending($src, false);
					$transindex = imagecolortransparent($this->img_source) <= imagecolorstotal($src) ? imagecolortransparent($this->img_source) : imagecolorstotal($src);
					if ($transindex >= 0) {
						$transcol = imagecolorsforindex($this->img_source, $transindex);
						$transindex = imagecolorallocatealpha($src, $transcol['red'], $transcol['green'], $transcol['blue'], 127);
						imagefill($src, 0, 0, $transindex);
					}
				}

				if (function_exists('imagecopyresampled')) {
					@imagecopyresampled($src, $this->img_source, 0, 0, 0, 0, $width, $height, $this->img_width, $this->img_height);
				} else {
					@imagecopyresized($src, $this->img_source, 0, 0, 0, 0, $width, $height, $this->img_width, $this->img_height);
				}

				// restore transparency for gif
				if ($this->img_format == 'gif') {
					if ($transindex >= 0) {
						imagecolortransparent($src, $transindex);
						for ($y=0; $y < imagesy($src); ++$y) {
							for ($x=0; $x < imagesx($src); ++$x) {
								if (((imagecolorat($src, $x, $y)>>24) & 0x7F) >= 100) {
									imagesetpixel($src, $x, $y, $transindex);
								}
							}
						}
					}
				}
			}

	        // create thumbnail image
			$thumbnail = imagecreatetruecolor($this->thumb_width, $this->thumb_height);

			// save transparent colors for png
			if ($this->img_format == 'png') {
				imagecolortransparent($thumbnail, imagecolorallocate($src, 0, 0, 0));
				imagealphablending($thumbnail, false);
				imagesavealpha($thumbnail, true);
			}

			// get and reallocate transparency-color for gif
			if ($this->img_format == 'gif') {
				imagealphablending($thumbnail, false);
				$transindex = imagecolortransparent($src);
				if ($transindex >= 0) {
					$transcol = imagecolorsforindex($src, $transindex);
					$transindex = imagecolorallocatealpha($thumbnail, $transcol['red'], $transcol['green'], $transcol['blue'], 127);
					imagefill($thumbnail, 0, 0, $transindex);
				}
			}

			@imagecopy($thumbnail, $src, 0, 0, $src_x, $src_y, $this->thumb_width, $this->thumb_height);

			// restore transparency for gif
			if ($this->img_format == 'gif') {
				if ($transindex >= 0) {
					imagecolortransparent($thumbnail, $transindex);
					for ($y=0; $y < imagesy($thumbnail); ++$y) {
						for ($x=0; $x < imagesx($thumbnail); ++$x) {
							if (((imagecolorat($thumbnail, $x, $y)>>24) & 0x7F) >= 100) {
								imagesetpixel($thumbnail, $x, $y, $transindex);
							}
						}
					}
				}
			}

			// save thumbnail to file
			ob_start();
			switch ($this->img_format) {
	            case 'gif':
	            	$return = imagegif($thumbnail);
	                break;
	            case 'jpeg':
	       			$return = imagejpeg($thumbnail, null, $this->thumb_quality);
	                break;
	            case 'png':
	    			$return = imagepng($thumbnail);
					break;
	        }
			$output = ob_get_contents();
			ob_end_clean();
			JFile::write($file, $output);

			// free memory resources
			imagedestroy($thumbnail);
			imagedestroy($src);
		}

		return $return;
    }

}