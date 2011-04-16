<?php
/**
 * Upload behavior
 *
 * Enables users to easily add file uploading and necessary validation rules
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

class UploadBehavior extends ModelBehavior {

	var $defaults = array(
		'pathMethod'		=> 'primaryKey',
		'path'				=> 'webroot{DS}files{DS}{model}{DS}{field}{DS}',
		'fields'			=> array('dir' => 'dir', 'type' => 'type', 'size' => 'size'),
		'prefixStyle'		=> true,
		'thumbnails'		=> true,
		'thumbsizes'		=> array(),
		'thumbnailQuality'	=> 75,
		'thumbnailMethod'	=> 'imagick',
	);

	var $_imageMimetypes = array(
		'image/bmp',
		'image/gif',
		'image/jpeg',
		'image/pjpeg',
		'image/png',
		'image/vnd.microsoft.icon',
		'image/x-icon',
	);

	var $_pathMethods = array('flat', 'primaryKey', 'random');

	var $_resizeMethods = array('imagick', 'php');

	var $__filesToRemove = array();

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
				$options['fields'] += $this->defaults['fields'];
				$options['path'] = $this->_path($model, $field, $options['path']);
				if (!in_array($options['thumbnailMethod'], $this->_resizeMethods)) {
					$options['thumbnailMethod'] = 'imagick';
				}
				if (!in_array($options['pathMethod'], $this->_pathMethods)) {
					$options['pathMethod'] = 'primaryKey';
				}
				$options['thumbnailMethod'] = '_resize' . Inflector::camelize($options['thumbnailMethod']);
				$this->settings[$model->alias][$field] = $options;
			}
		}

		if (!$model->Behaviors->enabled('UploadValidation')) {
			$model->Behaviors->attach('Upload.UploadValidation', $this->settings[$model->alias]);
		}
	}

/**
 * undocumented function
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function beforeSave(&$model) {
		foreach ($this->settings[$model->alias] as $field => $options) {
		    if (!is_array($model->data[$model->alias][$field])) continue;
			if (!empty($model->data[$model->alias][$field]['remove'])) {
				//if the record is already saved in the database, set the existing file to be removed after the save is sucessfull
				if (!empty($model->data[$model->alias][$model->primaryKey])) {
					$data = $model->find('first', array(
						'conditions' => array("{$model->alias}.{$model->primaryKey}" => $model->id),
						'contain' => false,
						'recursive' => -1,
					));
					$this->_prepareFilesForDeletion($model, $field, $data, $options);
				}
				$model->data[$model->alias][$field] = null;
			} elseif (empty($model->data[$model->alias][$field]['name'])) {
				// if field is empty, don't delete/nullify existing file
				unset($model->data[$model->alias][$field]);
				continue;
			}
				
			$this->runtime[$model->alias][$field] = $model->data[$model->alias][$field];
			$model->data[$model->alias] = array_merge($model->data[$model->alias], array(
				$field => $this->runtime[$model->alias][$field]['name'],
				$options['fields']['type'] => $this->runtime[$model->alias][$field]['type'],
				$options['fields']['size'] => $this->runtime[$model->alias][$field]['size']
			));
		}
		return true;
	}

	function afterSave(&$model, $created) {
		$temp = array($model->alias => array());
		foreach ($this->settings[$model->alias] as $field => $options) {
			if (!in_array($field, array_keys($model->data[$model->alias]))) continue;
			if (empty($this->runtime[$model->alias][$field])) continue;

			$tempPath = $this->_getPath($model, $field);
			$path = ROOT . DS . APP_DIR . DS . $this->settings[$model->alias][$field]['path'];
			$path .= $tempPath . DS;
			$tmp = $this->runtime[$model->alias][$field]['tmp_name'];
			$filePath = $path . $model->data[$model->alias][$field];
			if (!@move_uploaded_file($tmp, $filePath)) {
				$model->invalidate($field, 'moveUploadedFile');
			}
			$this->_createThumbnails($model, $field, $path);
			$temp[$model->alias][$options['fields']['dir']] = "\"{$tempPath}\"";
		}

		if (!empty($temp[$model->alias])) {
			$model->updateAll($temp[$model->alias], array(
				$model->alias.'.'.$model->primaryKey => $model->id
			));
		}
		
		if(empty($this->__filesToRemove[$model->alias])) return true;
		foreach ($this->__filesToRemove[$model->alias] as $file) {
			$result[] = @unlink($file);
		}
		return $result;
	}

	function beforeDelete(&$model, $cascade) {
		$data = $model->find('first', array(
			'conditions' => array("{$model->alias}.{$model->primaryKey}" => $model->id),
			'contain' => false,
			'recursive' => -1,
		));

		foreach ($this->settings[$model->alias] as $field => $options) {
			$this->_prepareFilesForDeletion($model, $field, $data, $options);
		}
		return true;
	}

	function afterDelete(&$model) {
		$result = array();
		foreach ($this->__filesToRemove[$model->alias] as $file) {
			$result[] = @unlink($file);
		}
		return $result;
	}

/**
 * Check that the upload directory is writable
 *
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param string $path Path relative to ROOT . DS . APP_DIR . DS
 * @return boolean Success
 * @access public
 */
	function isWritable(&$model, $check) {
		$field = array_pop(array_keys($check));

		return is_writable($this->settings[$model->alias][$field]['path']);
	}

/**
 * Check that the upload directory exists
 *
 * @param Object $model
 * @param mixed $check Value to check
 * @param string $path Path relative to ROOT . DS . APP_DIR . DS
 * @return boolean Success
 * @access public
 */
	function isValidDir(&$model, $check) {
		$field = array_pop(array_keys($check));

		return is_dir($this->settings[$model->alias][$field]['path']);
	}

	function _resizeImagick(&$model, $field, $path, $style, $geometry) {
		$srcFile  = $path . $model->data[$model->alias][$field];
		$destFile = $path . $style . '_' . $model->data[$model->alias][$field];

		if (!$this->settings[$model->alias][$field]['prefixStyle']) {
			$pathInfo = $this->_pathinfo($path . $model->data[$model->alias][$field]);
			$destFile = $path . $pathInfo['filename'] . '_' . $style . '.' . $pathInfo['extension'];
		}

		App::import('Lib', 'Upload.Resize/Imagick');
		$ResizeImagick = new ResizeImagick();
		return $ResizeImagick->resize($srcFile, $destFile, $geometry, $this->settings[$model->alias][$field]['thumbnailQuality']);
	}

	function _resizePhp(&$model, $field, $path, $style, $geometry) {
		$srcFile  = $path . $model->data[$model->alias][$field];
		$destFile = $path . $style . '_' . $model->data[$model->alias][$field];

		if (!$this->settings[$model->alias][$field]['prefixStyle']) {
			$pathInfo = $this->_pathinfo($path . $model->data[$model->alias][$field]);
			$destFile = $path . $pathInfo['filename'] . '_' . $style . '.' . $pathInfo['extension'];
		}

		App::import('Lib', 'Upload.Resize/Php');
		$ResizePhp = new ResizePhp();
		return $ResizePhp->resize($srcFile, $destFile, $geometry);
	}

	function _getPath(&$model, $field) {
		$pathMethod = $this->settings[$model->alias][$field]['pathMethod'];

		$pathMethod = '_getPath' . Inflector::camelize($pathMethod);

		if (PHP5) {
			return $this->{$pathMethod}($model, $field);
		} else {
			return $this->{$pathMethod}(&$model, $field);
		}
	}

	function _getPathFlat(&$model, $field) {
		$path = $this->settings[$model->alias][$field]['path'];
		$destDir = ROOT . DS . APP_DIR . DS . $path;
		if (!file_exists($destDir)) {
			@mkdir($destDir, 0777, true);
			@chmod($destDir, 0777);
		}
		return '';
	}

	function _getPathPrimaryKey(&$model, $field) {
		$path = $this->settings[$model->alias][$field]['path'];
		$destDir = ROOT . DS . APP_DIR . DS . $path . $model->id . DIRECTORY_SEPARATOR;
		if (!file_exists($destDir)) {
			@mkdir($destDir, 0777, true);
			@chmod($destDir, 0777);
		}
		return $model->id;
	}

	function _getPathRandom(&$model, $field) {
		$path = $this->settings[$model->alias][$field]['path'];
		$string = $model->data[$model->alias][$field];

		$endPath = null;
		$decrement = 0;
		$string = crc32($string . time());

		for ($i = 0; $i < 3; $i++) {
			$decrement = $decrement - 2;
			$endPath .= sprintf("%02d" . DIRECTORY_SEPARATOR, substr('000000' . $string, $decrement, 2));
		}

		$destDir = ROOT . DS . APP_DIR . DS . $path . $endPath;
		if (!file_exists($destDir)) {
			@mkdir($destDir, 0777, true);
			@chmod($destDir, 0777);
		}

		return substr($endPath, 0, -1);
	}

/**
 * Returns a path based on settings configuration
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
	function _path(&$model, $fieldName, $path) {
		$replacements = array(
			'{model}'	=> Inflector::underscore($model->alias),
			'{field}'	=> $fieldName,
			'{DS}'		=> DIRECTORY_SEPARATOR,
			'/'			=> DIRECTORY_SEPARATOR,
			'\\'		=> DIRECTORY_SEPARATOR,
		);
		return str_replace(
			array_keys($replacements),
			array_values($replacements),
			$path
		);
	}

	function _createThumbnails(&$model, $field, $path) {
		if ($this->_isImage($model, $this->runtime[$model->alias][$field]['type'])
		&& $this->settings[$model->alias][$field]['thumbnails']
		&& !empty($this->settings[$model->alias][$field]['thumbsizes'])) {
			// Create thumbnails
			$method = $this->settings[$model->alias][$field]['thumbnailMethod'];

			foreach ($this->settings[$model->alias][$field]['thumbsizes'] as $style => $geometry) {
				if (!$this->$method($model, $field, $path, $style, $geometry)) {
					$model->invalidate($field, 'resizeFail');
				}
			}
		}
	}

	function _isImage(&$model, $mimetype) {
		return in_array($mimetype, $this->_imageMimetypes);
	}

	function _prepareFilesForDeletion(&$model, $field, $data, $options) {
		$this->__filesToRemove[$model->alias] = array();
		$this->__filesToRemove[$model->alias][] = ROOT . DS . APP_DIR . DS . $this->settings[$model->alias][$field]['path'] . $data[$model->alias][$options['fields']['dir']] . DS . $data[$model->alias][$field];
		foreach ($options['thumbsizes'] as $style => $geometry) {
			$this->__filesToRemove[$model->alias][] = ROOT . DS . APP_DIR . DS . $this->settings[$model->alias][$field]['path'] . $data[$model->alias][$options['fields']['dir']] . DS . $style . '_' . $data[$model->alias][$field];
		}
		return $this->__filesToRemove;
	}

	function _pathinfo($filename) {
		$pathinfo = pathinfo($filename);
		// PHP < 5.2.0 doesn't include 'filename' key in pathinfo. Let's try to fix this.
		if (empty($pathinfo['filename'])) {
			$suffix = !empty($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '';
			$pathinfo['filename'] = basename($pathinfo['basename'], $suffix);
		}
		return $pathinfo;
	}
}