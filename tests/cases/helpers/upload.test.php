<?php
App::import('Helper', array('Upload.Upload', 'Html'));

class TestUpload extends CakeTestModel {
	var $alias = 'Upload';
	var $useTable = 'uploads';
	var $actsAs = array(
		'Upload.Upload' => array(
			'photo'
		)
	);
}

class TestUser extends CakeTestModel {
	var $alias = 'User';
	var $useTable = 'users';
	var $actsAs = array(
		'Upload.Upload' => array(
			'photo' => array(
				'thumbsizes' => array(
					'thumb' => '50x50',
					'galeria' => '180x180'
				)
			)
		)
	);
}

class TestUploadController extends Controller {
}

class UploadHelperTestCase extends CakeTestCase {
	var $fixtures = array(
		'plugin.upload.upload',
		'core.user'
	);

	public function startTest($method) {
		parent::startTest($method);
		$this->Upload = new UploadHelper();
		$this->Upload->Html = new HtmlHelper();
		$this->Controller =& new TestUploadController();
		$this->View =& new View($this->Controller);
		$this->TestUpload = ClassRegistry::init('TestUpload');
		$this->TestUser = ClassRegistry::init('TestUser');

		ClassRegistry::addObject('view', $this->View);
		ClassRegistry::addObject('Upload', new TestUpload());
		ClassRegistry::addObject('User', new TestUser());

		$this->record = array(
			'Upload' => array(
				'id' => 1,
				'photo' => 'Photo.png',
				'dir' => '1',
				'type' => 'image/png',
				'size' => 8192
			)
		);
	}

	public function endTest($method) {
		parent::endTest($method);
		unset($this->Upload);
		ClassRegistry::flush();
	}

	public function testInstance() {
		$this->assertIsA($this->Upload, 'UploadHelper');
		$this->assertIsA($this->Upload, 'AppHelper');
	}

	public function testUrl() {
		$result = $this->Upload->url($this->record, 'Upload.photo');
		$expected = '/files/upload/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithRandomDir() {
		$data = $this->record;
		$data['Upload']['dir'] = '03/04/05';
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/files/upload/photo/03/04/05/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithUserModel() {
		$data = array(
			'User' => array(
				'id' => 1,
				'photo' => 'Photo.png',
				'dir' => '1',
				'type' => 'image/png',
				'size' => 8192
			)
		);
		$result = $this->Upload->url($data, 'User.photo');
		$expected = '/files/user/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithRelatedData() {
		$data = $this->record['Upload'];
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/files/upload/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithDiferentFilename() {
		$data = $this->record;
		$data['Upload']['photo'] = 'image.jpg';
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/files/upload/photo/1/image.jpg';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithDiferentPathBehaviorSettings() {
		$this->TestUpload->Behaviors->detach('Upload');
		$this->TestUpload->Behaviors->attach('Upload', array(
			'photo' => array(
				'path' => 'webroot{DS}img{DS}{field}{DS}',
			)
		));
		$data = $this->record;
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/img/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithDiferentDirBehaviorSettings() {
		$this->TestUpload->Behaviors->detach('Upload');
		$this->TestUpload->Behaviors->attach('Upload', array(
			'photo' => array(
				'fields' => array(
					'dir' => 'photo_dir'
				)
			)
		));
		$data = $this->record;
		$data['Upload']['photo_dir'] = '150';
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/files/upload/photo/150/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithPathOutsideWebroot() {
		$this->TestUpload->Behaviors->detach('Upload');
		$this->TestUpload->Behaviors->attach('Upload', array(
			'photo' => array(
				'path' => 'uploads{DS}{model}{DS}{field}{DS}',
			)
		));
		$data = $this->record;
		$result = $this->Upload->url($data, 'Upload.photo');
		$expected = '/uploads/upload/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithFull() {
		if (!defined('FULL_BASE_URL')) {
			define('FULL_BASE_URL', 'http://example.com');
		}
		$result = $this->Upload->url($this->record, 'Upload.photo', true);
		$expected = FULL_BASE_URL . '/files/upload/photo/1/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWithFlatPathMethod() {
		$this->TestUpload->Behaviors->detach('Upload');
		$this->TestUpload->Behaviors->attach('Upload', array(
			'photo' => array(
				'pathMethod' => 'flat'
			)
		));
		$result = $this->Upload->url($this->record, 'Upload.photo');
		$expected = '/files/upload/photo/Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWhithThumbStyle() {
		$result = $this->Upload->url($this->record, 'Upload.photo.thumb');
		$expected = '/files/upload/photo/1/thumb_Photo.png';
		$this->assertEqual($result, $expected);
	}

	public function testUrlWhithPrefixStyleDisabled() {
		$this->TestUpload->Behaviors->detach('Upload');
		$this->TestUpload->Behaviors->attach('Upload', array(
			'photo' => array(
				'prefixStyle' => false
			)
		));
		$result = $this->Upload->url($this->record, 'Upload.photo.thumb');
		$expected = '/files/upload/photo/1/Photo_thumb.png';
		$this->assertEqual($result, $expected);
	}

	public function testImage() {
		$result = $this->Upload->image($this->record, 'Upload.photo');
		$this->assertTags($result, array(
			'img' => array(
				'src' => '/files/upload/photo/1/Photo.png', 'alt' => ''
			)
		));
	}
}