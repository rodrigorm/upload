<?php
class UploadHelper extends AppHelper {
	public $helpers = array(
		'Html'
	);

	public function url($data, $fieldName, $full = false) {
		$this->setEntity($fieldName);

		if (isset($data[$this->model()])) {
			$data = $data[$this->model()];
		}

		$path = $this->_path();
		$dir = $this->_dir($data);
		$filename = $this->_filename($data);

		return parent::url($this->webroot("/{$path}{$dir}/{$filename}"), $full);
	}

	protected function _path() {
		$path = $this->_settings('path');

		if (substr($path, 0, 8) == 'webroot' . DS) {
			$path = substr($path, 8);
		}

		return str_replace(DS, '/', $path);
	}

	protected function _dir($data) {
		$pathMethod = $this->_settings('pathMethod');
		if ($pathMethod == 'flat') {
			return '';
		}

		return $data[$this->_dirField()];
	}

	protected function _dirField() {
		return $this->_settings('fields.dir');
	}

	protected function _filename($data) {
		$filename = $data[$this->field()];

		// @TODO: Remove duplication from this and UploadBehavior
		$view =& ClassRegistry::getObject('view');
		$style = $view->fieldSuffix;
		if (!empty($style)) {
			$prefixStyle = $this->_settings('prefixStyle');
			if ($prefixStyle) {
				$filename = $style . '_' . $filename;
			} else {
				$pathinfo = pathinfo($filename);
				if (empty($pathinfo['filename'])) {
					$suffix = !empty($pathinfo['extension']) ? '.'.$pathinfo['extension'] : '';
					$pathinfo['filename'] = basename($pathinfo['basename'], $suffix);
				}
				$filename = $pathinfo['filename'] . '_' . $style . '.' . $pathinfo['extension'];
			}
		}

		return $filename;
	}

	protected function _settings($name) {
		$Model = ClassRegistry::init($this->model());
		$settings = $Model->Behaviors->Upload->settings[$Model->alias][$this->field()];

		return Set::extract($settings, $name);
	}

	public function image($data, $fieldName, $options = array()) {
		return $this->Html->image($this->url($data, $fieldName), $options);
	}
}