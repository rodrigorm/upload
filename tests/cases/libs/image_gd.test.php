<?php
App::import('Lib', 'Upload.ImageGd');

class ImageGdTestCase extends CakeTestCase {
	public $ImageGd;

	public $path;

	public function startTest($method) {
		parent::startTest($method);

		$this->path = App::pluginPath('upload') . 'tests' . DS . 'images' . DS;
		$source = $this->path . 'panda.jpg';
		$this->ImageGd = new ImageGd($source);
	}

	public function endTest($method) {
		parent::endTest($method);
		unset($this->ImageGd);
	}

	public function testCropThumbnailImage() {
		$destination = $this->path . 'best_panda.jpg';
		$this->ImageGd->cropThumbnailImage(120, 180);
		$this->assertTrue($this->ImageGd->writeImage($destination));
		$this->assertTrue(file_exists($destination));

		$expected = $this->path . 'reference' . DS . 'best_panda.jpg';
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}

	public function testThumbnailImage() {
		$destination = $this->path . 'band_panda.jpg';
		$this->ImageGd->thumbnailImage(150, 100);
		$this->assertTrue($this->ImageGd->writeImage($destination));
		$this->assertTrue(file_exists($destination));
	
		$expected = $this->path . 'band_panda.jpg';
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}

	public function testThumbnailImageWide() {
		$destination = $this->path . 'wide_panda.jpg';
		$this->ImageGd->thumbnailImage(150, 0);
		$this->assertTrue($this->ImageGd->writeImage($destination));
		$this->assertTrue(file_exists($destination));
	
		$expected = $this->path . 'wide_panda.jpg';
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}

	public function testThumbnailImageHigh() {
		$destination = $this->path . 'high_panda.jpg';
		$this->ImageGd->thumbnailImage(0, 100);
		$this->assertTrue($this->ImageGd->writeImage($destination));
		$this->assertTrue(file_exists($destination));
	
		$expected = $this->path . 'high_panda.jpg';
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}

	public function testThumbnailImageSide() {
		$destination = $this->path . 'side_panda.jpg';
		$this->ImageGd->thumbnailImage(0, 200);
		$this->assertTrue($this->ImageGd->writeImage($destination));
		$this->assertTrue(file_exists($destination));
	
		$expected = $this->path . 'side_panda.jpg';
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}
}