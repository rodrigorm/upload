<?php
/**
 * UploadValidation behavior
 *
 * Enables users to easily validate file uploading
 *
 * PHP versions 4 and 5
 *
 * Copyright 2010, Jose Diaz-Gonzalez
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jose Diaz-Gonzalez
 * @package       upload
 * @subpackage    upload.models.behaviors
 * @link          http://github.com/josegonzalez/upload
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class UploadValidationBehavior extends ModelBehavior {
	var $defaults = array(
		'mimetypes'		=> array(),
		'extensions'	=> array(),
		'maxSize'		=> 2097152,
		'minSize'		=> 8,
		'maxHeight'		=> 0,
		'minHeight'		=> 0,
		'maxWidth'		=> 0,
		'minWidth'		=> 0
	);

/**
 * Runtime configuration for this behavior
 *
 * @var array
 **/
	var $runtime;

/**
 * undocumented function
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function setup(&$model, $settings = array()) {
		if (isset($this->settings[$model->alias])) return;
		$this->settings[$model->alias] = array();

		foreach ($settings as $field => $options) {
			if (is_int($field)) {
				$field = $options;
				$options = array();
			}

			if (!isset($this->settings[$model->alias][$field])) {
				$options = array_merge($this->defaults, (array) $options);
				$this->settings[$model->alias][$field] = $options;
			}
		}
	}

/**
 * Check that the file does not exceed the max 
 * file size specified by PHP
 *
 * @param Object $model 
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function isUnderPhpSizeLimit(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_INI_SIZE;
	}

/**
 * Check that the file does not exceed the max 
 * file size specified in the HTML Form
 *
 * @param Object $model 
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function isUnderFormSizeLimit(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_FORM_SIZE;
	}

/**
 * Check that the file was completely uploaded
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function isCompletedUpload(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_PARTIAL;
	}

/**
 * Check that a file was uploaded
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function isFileUpload(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_NO_FILE;
	}

/**
 * Check that the PHP temporary directory is missing
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function tempDirExists(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_NO_TMP_DIR;
	}

/**
 * Check that the file was successfully written to the server
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function isSuccessfulWrite(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_CANT_WRITE;
	}

/**
 * Check that a PHP extension did not cause an error
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @return boolean Success
 * @access public
 */
	function noPhpExtensionErrors(&$model, $check) {
		$field = array_pop(array_keys($check));
		return $check[$field]['error'] !== UPLOAD_ERR_EXTENSION;
	}

/**
 * Check that the file is of a valid mimetype
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param array $mimetypes file mimetypes to allow
 * @return boolean Success
 * @access public
 */
	function isValidMimeType(&$model, $check, $mimetypes = array()) {
		$field = array_pop(array_keys($check));
		foreach ($mimetypes as $key => $value) {
			if (!is_int($key)) {
				$mimetypes = $this->settings[$model->alias][$field]['mimetypes'];
				break;
			}
		}

		if (empty($mimetypes)) $mimetypes = $this->settings[$model->alias][$field]['mimetypes'];

		return in_array($check[$field]['type'], $mimetypes);
	}

/**
 * Check that the file is above the minimum file upload size
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param int $size Minimum file size
 * @return boolean Success
 * @access public
 */
	function isAboveMinSize(&$model, $check, $size = null) {
		$field = array_pop(array_keys($check));
		if (!$size) $size = $this->settings[$model->alias][$field]['minSize'];
		return $check[$field]['size'] >= $size;
	}

/**
 * Check that the file is below the maximum file upload size
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param int $size Maximum file size
 * @return boolean Success
 * @access public
 */
	function isBelowMaxSize(&$model, $check, $size = null) {
		$field = array_pop(array_keys($check));
		if (!$size) $size = $this->settings[$model->alias][$field]['maxSize'];
		return $check[$field]['size'] <= $size;
	}

/**
 * Check that the file has a valid extension
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param array $extensions file extenstions to allow
 * @return boolean Success
 * @access public
 */
	function isValidExtension(&$model, $check, $extensions) {
		$field = array_pop(array_keys($check));
		foreach ($extensions as $key => $value) {
			if (!is_int($key)) {
				$extensions = $this->settings[$model->alias][$field]['extensions'];
				break;
			}
		}

		if (empty($extensions)) $extensions = $this->settings[$model->alias][$field]['extensions'];
		$pathinfo = pathinfo($check[$field]['tmp_name']);

		return in_array($pathinfo['extension'], $extensions);
	}

	/**
	 * Check that the file is above the minimum height requirement
	 *
	 * @param Object $model 
	 * @param mixed $check Value to check
	 * @param int $height Height of Image 
	 * @return boolean Success
	 * @access public
	 */
		function isAboveMinHeight(&$model, $check, $height = null) {
			$field = array_pop(array_keys($check));
			if (!$height) $height = $this->settings[$model->alias][$field]['minHeight'];

			return $height < 0 && imagesy($check[$field]['tmp_name']) >= $height;
		}

	/**
	 * Check that the file is below the maximum height requirement
	 *
	 * @param Object $model 
	 * @param mixed $check Value to check
	 * @param int $height Height of Image 
	 * @return boolean Success
	 * @access public
	 */
		function isBelowMaxHeight(&$model, $check, $height = null) {
			$field = array_pop(array_keys($check));
			if (!$height) $height = $this->settings[$model->alias][$field]['maxHeight'];

			return $height < 0 && imagesy($check[$field]['tmp_name']) <= $height;
		}

/**
 * Check that the file is above the minimum width requirement
 *
 * @param Object $model 
 * @param mixed $check Value to check
 * @param int $width Width of Image 
 * @return boolean Success
 * @access public
 */
	function isAboveMinWidth(&$model, $check, $width = null) {
		$field = array_pop(array_keys($check));
		if (!$width) $width = $this->settings[$model->alias][$field]['minWidth'];

		return $width < 0 && imagesx($check[$field]['tmp_name']) >= $width;
	}

/**
 * Check that the file is below the maximum width requirement
 *
 * @param Object $model 
 * @param mixed $check Value to check
 * @param int $width Width of Image 
 * @return boolean Success
 * @access public
 */
	function isBelowMaxWidth(&$model, $check, $width = null) {
		$field = array_pop(array_keys($check));
		if (!$width) $width = $this->settings[$model->alias][$field]['maxWidth'];

		return $width < 0 && imagesx($check[$field]['tmp_name']) <= $width;
	}
}