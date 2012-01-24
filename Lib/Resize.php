<?php
class Resize extends Object {
	private $method;

	private $source;

	private $quality = 100;
	
	public function __construct($method, $source, $quality = 100) {
		$this->method = $method;
		$this->source = $source;
		$this->quality = $quality;
	}
	
	public function process($destFile, $geometry, $quality = 100) {
		$image  = $this->_getImage($this->source);
		$height = $image->getImageHeight();
		$width  = $image->getImageWidth();

		if (preg_match('/^\\[[\\d]+x[\\d]+\\]$/', $geometry)) {
			// resize with banding
			list($destW, $destH) = explode('x', substr($geometry, 1, strlen($geometry)-2));
			$image->thumbnailImage($destW, $destH);
		} elseif (preg_match('/^[\\d]+x[\\d]+$/', $geometry)) {
			// cropped resize (best fit)
			list($destW, $destH) = explode('x', $geometry);
			$image->cropThumbnailImage($destW, $destH);
		} elseif (preg_match('/^[\\d]+w$/', $geometry)) {
			// calculate heigh according to aspect ratio
			$image->thumbnailImage((int)$geometry-1, 0);
		} elseif (preg_match('/^[\\d]+h$/', $geometry)) {
			// calculate width according to aspect ratio
			$image->thumbnailImage(0, (int)$geometry-1);
		} elseif (preg_match('/^[\\d]+l$/', $geometry)) {
			// calculate shortest side according to aspect ratio
			$destW = 0;
			$destH = 0;
			$destW = ($width > $height) ? (int)$geometry-1 : 0;
			$destH = ($width > $height) ? 0 : (int)$geometry-1;

			$image->thumbnailImage($destW, $destH, true);
		}

		$image->setImageCompressionQuality($quality);
		if (!$image->writeImage($destFile)) return false;

		$image->clear();
		$image->destroy();
		return true;
	}

	protected function _getImage($source) {
		if ($this->method == 'imagick') {
			return new imagick($ource);
		}

		App::import('Lib', 'Upload.ImageGd');
		return new ImageGd($source);
	}
}