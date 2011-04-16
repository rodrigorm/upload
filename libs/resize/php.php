<?php
App::import('Lib', 'Upload.Resize');
App::import('Lib', 'Upload.ImageGd');

class ResizePhp extends Resize {
	protected function _getImage($source) {
		return new ImageGd($source);
	}
}