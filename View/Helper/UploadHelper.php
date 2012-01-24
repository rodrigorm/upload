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

		$Model = ClassRegistry::init($this->model());
		$filePath = ROOT . DS . APP_DIR . DS . str_replace('/', DS, "/{$path}{$dir}/{$filename}");
		if (!file_exists($filePath) && !empty($data[$Model->primaryKey])) {
			$Model->id = $data[$Model->primaryKey];
			$Model->createThumbnail($this->field(), $this->_style());
		}

		if (substr($path, 0, 8) == 'webroot/') {
			$path = substr($path, 8);
		}

		return parent::url($this->webroot("/{$path}{$dir}/{$filename}"), $full);
	}

	protected function _path() {
		$path = $this->_settings('path');
		return str_replace(DS, '/', $path);
	}

	protected function _dir($data) {
		$pathMethod = $this->_settings('pathMethod');
		if ($pathMethod == 'flat') {
			return '';
		}

		return str_replace(DS, '/', $data[$this->_dirField()]);
	}

	protected function _dirField() {
		return $this->_settings('fields.dir');
	}

	protected function _filename($data) {
		$filename = $data[$this->field()];

		// @TODO: Remove duplication from this and UploadBehavior
		$style = $this->_style();
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

	protected function _style() {
		$view =& ClassRegistry::getObject('view');
		return $view->fieldSuffix;
	}

	public function image($data, $fieldName, $options = array()) {
		return $this->Html->image($this->url($data, $fieldName), $options);
	}
}