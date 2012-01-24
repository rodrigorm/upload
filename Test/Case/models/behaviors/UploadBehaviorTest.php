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
		$this->assertEqual(array('isWritable'), current($this->TestUpload->validationErrors));

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
		$this->assertEqual(array('isValidDir'), current($this->TestUpload->validationErrors));

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

		$result = $this->TestUpload->Behaviors->Upload->_isImage('image/bmp');
		$this->assertTrue($result);

		$result = $this->TestUpload->Behaviors->Upload->_isImage('image/jpeg');
		$this->assertTrue($result);

		$result = $this->TestUpload->Behaviors->Upload->_isImage('application/zip');
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

		$this->assertTrue(is_string($result));
		$this->assertEqual(8, strlen($result));
		$this->assertTrue(is_dir(TMP . DS . $result));
	}

	function testReplacePath() {
		$result = $this->TestUpload->Behaviors->Upload->_path($this->TestUpload, 'photo', 'webroot{DS}files/{model}\\{field}{DS}');

		$this->assertTrue(is_string($result));
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

		$this->assertTrue(is_array($result));
		$this->assertEqual(1,count($result));
		$this->assertEqual(4, count($result['TestUpload']));
	}

	public function testSetupWithInvalidThumbnailMethod() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'thumbnailMethod' => 'invalid'
			)
		));
		$result = $this->TestUpload->Behaviors->Upload->settings['TestUpload']['photo']['thumbnailMethod'];
		$this->assertEqual($result, 'imagick');
	}

	public function testSetupWithInvalidPathMethod() {
		$this->TestUpload->Behaviors->detach('Upload.Upload');
		$this->TestUpload->Behaviors->attach('Upload.Upload', array(
			'photo' => array(
				'pathMethod' => 'invalid'
			)
		));
		$result = $this->TestUpload->Behaviors->Upload->settings['TestUpload']['photo']['pathMethod'];
		$this->assertEqual($result, 'primaryKey');
	}

	public function testBeforeSave() {
		$result = $this->TestUpload->save(array(
			'photo' => array(
				'name'  => 'Photo.png',
				'tmp_name'  => '/tmp/Photo.png',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_OK,
			)
		));
		$this->assertTrue(is_array($result));

		$result = $this->TestUpload->read();
		$this->assertEqual($result['TestUpload']['photo'], 'Photo.png');
		$this->assertEqual($result['TestUpload']['size'], 8192);
		$this->assertEqual($result['TestUpload']['type'], 'image/png');
	}

	public function testBeforeSaveWithRemoveFlag() {
		$result = $this->TestUpload->save(array(
			'id' => 1,
			'photo' => array(
				'remove' => true
			)
		));
		$this->assertTrue(is_array($result));

		$filesToRemove = $this->TestUpload->Behaviors->Upload->__filesToRemove;
		$this->assertEqual(count($filesToRemove), 1);

		$expected = ROOT . DS . APP_DIR . DS . 'webroot' . DS . 'files' . DS . 'test_upload' . DS . 'photo' . DS . '1' . DS . 'Photo.png';
		$this->assertEqual($filesToRemove['TestUpload'][0], $expected);
	}

	public function testBeforeDelete() {
		$result = $this->TestUpload->delete(1);
		$this->assertTrue($result);

		$filesToRemove = $this->TestUpload->Behaviors->Upload->__filesToRemove;
		$this->assertEqual(count($filesToRemove), 1);

		$expected = ROOT . DS . APP_DIR . DS . 'webroot' . DS . 'files' . DS . 'test_upload' . DS . 'photo' . DS . '1' . DS . 'Photo.png';
		$this->assertEqual($filesToRemove['TestUpload'][0], $expected);
	}
}