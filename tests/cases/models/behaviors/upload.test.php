<?php
App::import('Behavior', 'Upload.Upload');

class TestUpload extends CakeTestModel {
	var $useTable = 'uploads';
	var $actsAs = array(
		'Upload.Upload' => array(
			'photo'
		)
	);
}


class UploadBehaviorTest extends CakeTestCase {

	var $fixtures = array('plugin.upload.upload');
	var $TestUpload = null;
	var $data = array();

	function startTest() {
		$this->TestUpload = ClassRegistry::init('TestUpload');
		$this->data['test_ok'] = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);
	}

	function endTest() {
		Classregistry::flush();
		unset($this->TestUpload);
	}

	function testIsWritable() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isWritable' => array(
					'rule' => 'isWritable',
					'message' => 'isWritable'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());

		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual('isWritable', current($this->TestUpload->validationErrors));

		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'path' => TMP
			)
		));

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.bmp',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/bmp',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);
		$this->TestUpload->set($data);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsValidDir() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidDir' => array(
					'rule' => 'isValidDir',
					'message' => 'isValidDir'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());

		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual('isValidDir', current($this->TestUpload->validationErrors));

		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'path' => TMP
			)
		));

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.bmp',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/bmp',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		);
		$this->TestUpload->set($data);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsImage() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'mimetypes' => array('image/bmp', 'image/jpeg')
			)
		));

		$result = $this->TestUpload->Behaviors->Upload->_isImage($this->TestUpload, 'image/bmp');
		$this->assertTrue($result);

		$result = $this->TestUpload->Behaviors->Upload->_isImage($this->TestUpload, 'image/jpeg');
		$this->assertTrue($result);

		$result = $this->TestUpload->Behaviors->Upload->_isImage($this->TestUpload, 'application/zip');
		$this->assertFalse($result);
	}

	function testGetPathRandom() {
		$this->TestUpload->data = array(
			'TestUpload' => array(
				'photo' => 'Photo.png'
			)
		);
		$this->TestUpload->Behaviors->Upload->settings['TestUpload']['photo']['path'] = 'tmp' . DS;
		$result = $this->TestUpload->Behaviors->Upload->_getPathRandom($this->TestUpload, 'photo');

		$this->assertIsA($result, 'String');
		$this->assertEqual(8, strlen($result));
		$this->assertTrue(is_dir(TMP . DS . $result));
	}

	function testReplacePath() {
		$result = $this->TestUpload->Behaviors->Upload->_path($this->TestUpload, 'photo', 'webroot{DS}files/{model}\\{field}{DS}');

		$this->assertIsA($result, 'String');
		$this->assertEqual('webroot/files/test_upload/photo/', $result);
	}

	function testPrepareFilesForDeletion() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'thumbsizes' => array(
					'xvga' => '1024x768',
					'vga' => '640x480',
					'thumb' => '80x80'
				),
				'fields' => array(
					'dir' => 'dir'
				)
			)
		));

		$result = $this->TestUpload->Behaviors->Upload->_prepareFilesForDeletion(
			$this->TestUpload, 'photo',
			array('TestUpload' => array('dir' => '1/', 'photo' => 'Photo.png')),
			$this->TestUpload->Behaviors->Upload->settings['TestUpload']['photo']
		);

		$this->assertIsA($result, 'Array');
		$this->assertEqual(1,count($result));
		$this->assertEqual(4, count($result['TestUpload']));
	}

	protected function _testResizePhp($style, $geometry) {
		$this->TestUpload->data = array(
			'TestUpload' => array(
				'photo' => 'panda.jpg'
			)
		);

		$path = App::pluginPath('upload') . 'tests' . DS . 'images' . DS;
		$result = $this->TestUpload->Behaviors->Upload->_resizePhp(
			$this->TestUpload,
			'photo',
			$path,
			$style,
			$geometry
		);
		$this->assertTrue($result);

		$result = $path . $style . '_panda.jpg';
		$expected = $path . 'reference' . DS . $style . '_panda.jpg';
		$this->assertTrue(file_exists($expected));
		$this->assertEqual(md5_file($result), md5_file($expected));
		@unlink($result);
	}

	public function testResizePhpBand() {
		$this->_testResizePhp('band', '[150x100]');
	}

	public function testResizePhpBest() {
		$this->_testResizePhp('best', '120x180');
	}

	public function testResizePhpWide() {
		$this->_testResizePhp('wide', '150w');
	}

	public function testResizePhpHigh() {
		$this->_testResizePhp('high', '100h');
	}

	public function testResizePhpSide() {
		$this->_testResizePhp('side', '200l');
	}
}