<?php
App::import('Lib', 'Upload.Resize' . DS . 'Php');

class ResizePhpTestCase extends CakeTestCase {
	public $ResizePhp;

	public function startTest($method) {
		parent::startTest($method);
		$this->ResizePhp = new ResizePhp();
	}

	public function endTest($method) {
		parent::endTest($method);
		unset($this->ResizePhp);
	}

	protected function _testResize($style, $geometry) {
		$path = App::pluginPath('upload') . 'tests' . DS . 'images' . DS;

		$source = $path . 'panda.jpg';
		$destination = $path . $style . '_panda.jpg';

		$result = $this->ResizePhp->resize(
			$source,
			$destination,
			$geometry
		);
		$this->assertTrue($result);

		$expected = $path . 'reference' . DS . $style . '_panda.jpg';
		$this->assertTrue(file_exists($destination));
		$this->assertEqual(md5_file($destination), md5_file($expected));
		@unlink($destination);
	}

	public function testResizeBand() {
		$this->_testResize('band', '[150x100]');
	}

	public function testResizeBest() {
		$this->_testResize('best', '120x180');
	}

	public function testResizeWide() {
		$this->_testResize('wide', '150w');
	}

	public function testResizeHigh() {
		$this->_testResize('high', '100h');
	}

	public function testResizeSide() {
		$this->_testResize('side', '200l');
	}
}