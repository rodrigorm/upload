<?php
App::import('Lib', 'Upload.Resize');

class ResizeImagick extends Resize {
	protected function _getImage($source) {
		return new imagick($ource);
	}
}