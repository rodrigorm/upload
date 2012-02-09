<?php
App::import('Lib', 'Upload.Resize');

class ResizeTestCase extends CakeTestCase {
	public $Resize;

	public function startTest($method) {
		parent::startTest($method);

		$this->path = App::pluginPath('Upload') . 'Test' . DS . 'images' . DS;
		$this->source = $this->path . 'panda.jpg';
		$this->Resize = new Resize('php', $this->source);
	}

	public function endTest($method) {
		parent::endTest($method);
		unset($this->Resize);
	}

	protected function _testResize($style, $geometry) {
		$destination = $this->path . $style . '_panda.jpg';

		$result = $this->Resize->process(
			$destination,
			$geometry
		);
		$this->assertTrue($result);

		$expected = $this->path . 'reference' . DS . $style . '_panda.jpg';
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