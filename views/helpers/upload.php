<?php
class UploadHelper extends AppHelper {
	public function url($data, $fieldName, $full = false) {
		$this->setEntity($fieldName);

		if (isset($data[$this->model()])) {
			$data = $data[$this->model()];
		}

		$path = $this->_path();
		$dir = $this->_dir($data);
		$file = $data[$this->field()];

		return parent::url($this->webroot("/{$path}{$dir}/{$file}"), $full);
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

	protected function _settings($name) {
		$Model = ClassRegistry::init($this->model());
		$settings = $Model->Behaviors->Upload->settings[$Model->alias][$this->field()];

		return Set::extract($settings, $name);
	}
}