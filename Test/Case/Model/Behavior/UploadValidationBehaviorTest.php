<?php
App::import('Behavior', 'Upload.UploadValidation');

class TestUploadValidation extends CakeTestModel {
	var $useTable = 'uploads';
	var $actsAs = array(
		'Upload.UploadValidation' => array(
			'photo'
		)
	);
}


class UploadValidationBehaviorTest extends CakeTestCase {
	var $fixtures = array('plugin.upload.upload');
	var $TestUpload = null;
	var $data = array();

	function startTest() {
		$this->TestUpload = ClassRegistry::init('TestUploadValidation');
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

	function testIsUnderPhpSizeLimit() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isUnderPhpSizeLimit' => array(
					'rule' => 'isUnderPhpSizeLimit',
					'message' => 'isUnderPhpSizeLimit'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_INI_SIZE,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isUnderPhpSizeLimit'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsUnderFormSizeLimit() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isUnderFormSizeLimit' => array(
					'rule' => 'isUnderFormSizeLimit',
					'message' => 'isUnderFormSizeLimit'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_FORM_SIZE,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isUnderFormSizeLimit'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsCompletedUpload() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isCompletedUpload' => array(
					'rule' => 'isCompletedUpload',
					'message' => 'isCompletedUpload'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_PARTIAL,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isCompletedUpload'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsFileUpload() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isFileUpload' => array(
					'rule' => 'isFileUpload',
					'message' => 'isFileUpload'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_NO_FILE,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isFileUpload'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testTempDirExists() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'tempDirExists' => array(
					'rule' => 'tempDirExists',
					'message' => 'tempDirExists'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_NO_TMP_DIR,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('tempDirExists'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsSuccessfulWrite() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'isSuccessfulWrite' => array(
					'rule' => 'isSuccessfulWrite',
					'message' => 'isSuccessfulWrite'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_CANT_WRITE,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isSuccessfulWrite'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testNoPhpExtensionErrors() {
		$this->TestUpload->validate = array(
			'photo' => array(
				'noPhpExtensionErrors' => array(
					'rule' => 'noPhpExtensionErrors',
					'message' => 'noPhpExtensionErrors'
				),
			)
		);

		$data = array(
			'photo' => array(
				'tmp_name'  => 'Photo.png',
				'dir'   => '/tmp/php/file.tmp',
				'type'  => 'image/png',
				'size'  => 8192,
				'error' => UPLOAD_ERR_EXTENSION,
			)
		);
		$this->TestUpload->set($data);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('noPhpExtensionErrors'), current($this->TestUpload->validationErrors));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsValidMimeType() {
		$this->TestUpload->Behaviors->detach('Upload.UploadValidation');
		$this->TestUpload->Behaviors->attach('Upload.UploadValidation', array(
			'photo' => array(
				'mimetypes' => array('image/bmp', 'image/jpeg')
			)
		));

		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidMimeType' => array(
					'rule' => 'isValidMimeType',
					'message' => 'isValidMimeType'
				),
			)
		);

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertFalse($this->TestUpload->validates());
		$this->assertEqual(1, count($this->TestUpload->validationErrors));
		$this->assertEqual(array('isValidMimeType'), current($this->TestUpload->validationErrors));

		$this->TestUpload->Behaviors->detach('Upload.UploadValidation');
		$this->TestUpload->Behaviors->attach('Upload.UploadValidation', array(
			'photo' => array(
				'mimetypes' => array('image/png', 'image/jpeg')
			)
		));

		$this->TestUpload->set($this->data['test_ok']);
		$this->assertTrue($this->TestUpload->validates());
		$this->assertEqual(0, count($this->TestUpload->validationErrors));
	}

	function testIsAboveMinSize() {
		$check = array(
			'photo' => array(
				'size' => 100
			)
		);
		$minSize = 50;
		$result = $this->TestUpload->isAboveMinSize($check, $minSize);
		$this->assertTrue($result);
	}

	function testIsValidExtension() {
		$this->TestUpload->Behaviors->detach('Upload.UploadValidation');
		$this->TestUpload->Behaviors->attach('Upload.UploadValidation', array(
			'photo' => array(
				'extensions' => array('jpeg', 'bmp')
			)
		));

		$this->TestUpload->validate = array(
			'photo' => array(
				'isValidExtension' => array(
					'rule' => 'isValidExtension',
					'message' => 'isValidExtension'
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
		$this->assertEqual(array('isValidExtension'), current($this->TestUpload->validationErrors));

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

	function testIsBelowMaxSize() {
		$check = array(
			'photo' => array(
				'size' => 100
			)
		);
		$maxSize = 200;
		$result = $this->TestUpload->isBelowMaxSize($check, $maxSize);
		$this->assertTrue($result);
	}
}