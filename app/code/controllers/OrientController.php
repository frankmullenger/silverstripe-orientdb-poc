<?php

require_once BASE_PATH . '/vendor/orientdb-php/orientdb-php/OrientDB/OrientDB.php';

/**
 * @package {@packagename}
 * @subpackage controllers
 */
class OrientController extends AppController {
	
	private static $allowed_actions = array(
		'index',
		'build',
		'populate',
		'creat',
		'read',
		'update',
		'delete',
		'WriteForm',
		'write'
	);

	public function init() {
		parent::init();

		//Using the Orient DB
		global $databaseConfigOrient;
		DB::connect($databaseConfigOrient);
		Object::useCustomClass('DataList', 'OrientDataList');
	}
	
	public function index() {

		$member = Member::get()->first();
		SS_Log::log(new Exception(print_r($member->toMap(), true)), SS_Log::NOTICE);

		// $members = Member::get();
		// SS_Log::log(new Exception(print_r($members->sql(), true)), SS_Log::NOTICE);
		// SS_Log::log(new Exception(print_r($members->first(), true)), SS_Log::NOTICE);

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Demo'
		)))->renderWith(array(
			'OrientController',
			'AppController'
		));
	}

	public function creat(SS_HTTPRequest $request) {

		$rid = $request->getVar('RID');
		$record = TestObject::get()->filter(array('ID' => $rid))->first();

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Demo',
			'SubTitle' => "Creating Record $rid",
			'Content' => '',
			'Form' => $this->WriteForm()
		)))->renderWith(array(
			'OrientController',
			'AppController'
		));
	}

	public function read(SS_HTTPRequest $request) {

		$rid = $request->getVar('RID');
		$record = TestObject::get()->filter(array('ID' => $rid))->first();

		$content = $record->renderWith(array(
			'TestObject'
		));

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Demo',
			'SubTitle' => "Reading Record $rid",
			'Content' => $content
		)))->renderWith(array(
			'OrientController',
			'AppController'
		));
	}

	public function update(SS_HTTPRequest $request) {

		$rid = $request->getVar('RID');
		$record = TestObject::get()->filter(array('ID' => $rid))->first();

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Demo',
			'SubTitle' => "Updating Record $rid",
			'Content' => '',
			'Form' => $this->WriteForm()
		)))->renderWith(array(
			'OrientController',
			'AppController'
		));
	}

	public function WriteForm() {

		$request = $this->getRequest();
		$rid = $request->getVar('RID');

		if ($rid) {
			$record = TestObject::get()->filter(array('ID' => $rid))->first();
		}
		else {
			$record = TestObject::create();
		}

		$fields = FieldList::create(
			HiddenField::create('ID'),
			TextField::create('Title'),
			TextField::create('Code')
		);

		$actions = FieldList::create(
			FormAction::create('write', 'Update')
		);

		$form = new Form($this, 'WriteForm', $fields, $actions);
		$form->loadDataFrom($record);
		return $form;
	}

	public function write($data, $form) {

		$rid = $data['ID'];

		if ($rid) {
			$record = TestObject::get()->filter(array('ID' => $rid))->first();
		}
		else {
			$record = TestObject::create();
		}

		$form->saveInto($record);
		$record->write();

		$this->redirectBack();
	}

	public function delete(SS_HTTPRequest $request) {

		$rid = $request->getVar('RID');
		$record = TestObject::get()->filter(array('ID' => $rid))->first();
		$record->delete();

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Demo',
			'SubTitle' => "Deleted Record $rid",
			'Content' => $content
		)))->renderWith(array(
			'OrientController',
			'AppController'
		));
	}

	public function build($request) {

		//Only build the classes do not populate the records
		$_REQUEST['dont_populate'] = 1;

		$renderer = DebugView::create();
		$renderer->writeHeader();
		$renderer->writeInfo("Orient Environment Builder: Do not run while logged in as a member", Director::absoluteBaseURL());
		echo "<div class=\"build\">";
		
		$da = DatabaseAdmin::create();
		return $da->handleRequest($request, $this->model);

		echo "</div>";
		$renderer->writeFooter();
	}

	public function populate() {

		$renderer = DebugView::create();
		$renderer->writeHeader();
		$renderer->writeInfo("Orient Environment Builder: Do not run while logged in as a member", Director::absoluteBaseURL());
		echo "<div class=\"build\">";
		
		$content = 'Creating some test objects.. <br /><br />';

		for ($i = 0; $i < 50; $i++) {
			$rand = rand(1, 10);
			$testObj = new TestObject();
			$testObj->Code = 'orient-test-' . $rand;
			$testObj->Title = 'OrientDB Test ' . $rand;
			$testObj->Sort = 1;
			$testObj->write(false, true, true);
			$content .= "{$testObj->Title} <br />";
		}

		echo $content;

		echo "</div>";
		$renderer->writeFooter();
	}

	public function TestObjects() {
		return TestObject::get()->sort('@RID', 'DESC');
	}

	public function Link() {
		//To match the route for this controller
		return 'orient/';
	}
}