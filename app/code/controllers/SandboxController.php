<?php

require_once BASE_PATH . '/vendor/orientdb-php/orientdb-php/OrientDB/OrientDB.php';

/**
 * @package {@packagename}
 * @subpackage controllers
 */
class SandboxController extends AppController {
	
	private static $allowed_actions = array(
		'index',
		'build',
		'populate',
		'scratch'
	);

	public function init() {
		parent::init();

		//Using the Orient DB
		global $databaseConfigOrient;
		DB::connect($databaseConfigOrient);
		Object::useCustomClass('DataList', 'OrientDataList');
	}
	
	public function index() {
		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox'
		)))->renderWith(array(
			'SandboxController',
			'AppController'
		));
	}

	public function scratch() {

		global $databaseConfigOrient;

		$server = $databaseConfigOrient['server'];
		$port = $databaseConfigOrient['port'];
		$serverUsername = $databaseConfigOrient['serverusername'];
		$serverPassword = $databaseConfigOrient['serverpassword'];
		$username = $databaseConfigOrient['username'];
		$password = $databaseConfigOrient['password'];
		$dbName = $databaseConfigOrient['database'];
		$clusterName = 'default';

		$log = '';

		try {
			$db = new OrientDB('localhost', 2424);
			$log .= 'Connected to localhost on port 2424 <br/>';
		}
		catch (Exception $e) {
			SS_Log::log(new Exception(print_r('Failed to connect: ' . $e->getMessage(), true)), SS_Log::NOTICE);
		}

		try {
			$connect = $db->connect($serverUsername, $serverPassword);
			$log .= 'Connected to DB as root <br />';
		}
		catch (OrientDBException $e) {
			SS_Log::log(new Exception(print_r('Failed to connect(): ' . $e->getMessage(), true)), SS_Log::NOTICE);
		}

		$exists = $db->DBExists($dbName);
		if ($exists) {
			$log .= "$dbName exists <br />";
		}
		else {
			SS_Log::log(new Exception(print_r('DB doesnt exist', true)), SS_Log::NOTICE);
		}

		$clusters = $db->DBOpen($dbName, $username, $password);
		$log .= "opened $dbName as admin user";

		
		//Get properties of a table


		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox',
			'SubTitle' => '',
			'Content' => $log,
			'Form' => ''
		)))->renderWith(array(
			'SandboxController',
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
		return 'sandbox/';
	}
}