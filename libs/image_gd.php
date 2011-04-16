<?php
class ImageGd extends Object {
	private $resource;

	private $outputHandler;

	public function __construct($file) {
		$pathinfo = pathinfo($file);
		$createHandler = null;
		switch (strtolower($pathinfo['extension'])) {
			case 'gif':
				$createHandler = 'imagecreatefromgif';
				$this->outputHandler = 'imagegif';
				break;
			case 'jpg':
			case 'jpeg':
				$createHandler = 'imagecreatefromjpeg';
				$this->outputHandler = 'imagejpeg';
				break;
			case 'png':
				$createHandler = 'imagecreatefrompng';
				$this->outputHandler = 'imagepng';
				break;
			default:
				return false;
		}

		$this->resource = $createHandler($file);
	}

	public function writeImage($destFile) {
		$outputHandler = $this->outputHandler;
		$outputHandler($this->resource, $destFile);
		return true;
	}

	public function cropThumbnailImage($destW, $destH) {
		$ratioH = $this->getImageHeight() / $destH;
		$ratioW = $this->getImageWidth() / $destW;

		if ($ratioH > $ratioW) {
			$ratio = $destW / $this->getImageWidth();
		} else {
			$ratio = $destH / $this->getImageHeight();
		}

		return $this->_resize($destW, $destH, $ratio);
	}

	public function thumbnailImage($destW, $destH) {
		$ratioH = $destH / $this->getImageHeight();
		$ratioW = $destW / $this->getImageWidth();
		
		if ($destW == 0) {
			$destW = $ratioH * $this->getImageWidth();
		}
		if ($destH == 0) {
			$destH = $ratioW * $this->getImageHeight();
		}

		if ($this->getImageWidth() > $this->getImageHeight()) {
			$ratio = $destW / $this->getImageWidth();
		} else {
			$ratio = $destH / $this->getImageHeight();
		}

		return $this->_resize($destW, $destH, $ratio);
	}

	protected function _resize($destW, $destH, $ratio) {
		$resizeW = $this->getImageWidth() * $ratio;
		$resizeH = $this->getImageHeight() * $ratio;

		$img = imagecreatetruecolor($destW, $destH);
		imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));

		$destX = ($destW - $resizeW) / 2;
		$destY = ($destH - $resizeH) / 2;

		imagecopyresampled($img, $this->resource, $destX, $destY, 0, 0, $resizeW, $resizeH, $this->getImageWidth(), $this->getImageHeight());

		$this->resource = $img;
	}

	public function getImageWidth() {
		return imagesx($this->resource);
	}

	public function getImageHeight() {
		return imagesy($this->resource);
	}

	public function setImageCompressionQuality() {
		return null;
	}
}