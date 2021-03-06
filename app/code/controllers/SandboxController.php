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
		'scratch',
		'getone',
		'relations',
		'manymany',
		'inheritance'
	);

	public function init() {
		parent::init();
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

		global $databaseConfig;

		$server = $databaseConfig['server'];
		$port = $databaseConfig['port'];
		$serverUsername = $databaseConfig['serverusername'];
		$serverPassword = $databaseConfig['serverpassword'];
		$username = $databaseConfig['username'];
		$password = $databaseConfig['password'];
		$dbName = $databaseConfig['database'];
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
		try {

			SS_Log::log(new Exception(print_r($clusters, true)), SS_Log::NOTICE);
			
			$results = $db->command(OrientDB::COMMAND_QUERY, 'desc TestObject');

			SS_Log::log(new Exception(print_r($results, true)), SS_Log::NOTICE);

		}
		catch (OrientDBException $e) {
			SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);
		}

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

	public function getone() {

		$i = rand(1,999);

		$post = new Post();
		$post->Title = "Post Title $i";
		$post->write();

		$post = Post::get()
			->filter(array('ID' => $post->ID))
			->first();

		SS_Log::log(new Exception(print_r($post->toMap(), true)), SS_Log::NOTICE);

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox get one'
		)))->renderWith(array(
			'SandboxController',
			'AppController'
		));
	}

	public function haseone() {

	}

	public function hasmany() {

	}

	public function relations() {

		//Create a post and relate it to an author
		$i = rand(1,999);

		// $author = new Person();
		// $author->Name = "Person $i";
		// $id = $author->write();

		// $post = new Post();
		// $post->Title = "Post Title $i";
		// $post->AuthorID = $id;
		// $post->write();

		$post = Post::get()->first();
		SS_Log::log(new Exception(print_r($post->toMap(), true)), SS_Log::NOTICE);

		$author = $post->Author();
		SS_Log::log(new Exception(print_r($author->toMap(), true)), SS_Log::NOTICE);

		$posts = $author->Posts();
		SS_Log::log(new Exception(print_r($posts->map()->toArray(), true)), SS_Log::NOTICE);

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox'
		)))->renderWith(array(
			'SandboxController',
			'AppController'
		));
	}

	public function manymany() {

		//Create a post and relate it to an author
		$i = rand(1, 999);

		$article = new Article();
		$article->Name = "Article $i";
		$id = $article->write();

		$range = array(1,2);

		foreach ($range as $val) {
			$tag = new Tag();
			$tag->Name = "Tag $val";
			$tag->write();

			$article->Tags()->add($tag);
		}

		foreach ($article->Tags() as $tag) {
			SS_Log::log(new Exception(print_r($tag->toMap(), true)), SS_Log::NOTICE);
		}
		SS_Log::log(new Exception(print_r($article->Tags()->count(), true)), SS_Log::NOTICE);

		$firstTag = $article->Tags()->first();
		SS_Log::log(new Exception(print_r($tag->toMap(), true)), SS_Log::NOTICE);


		foreach ($tag->Articles() as $article) {
			SS_Log::log(new Exception(print_r($article->toMap(), true)), SS_Log::NOTICE);
		}

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox Many_Many'
		)))->renderWith(array(
			'SandboxController',
			'AppController'
		));
	}

	public function inheritance() {

		//Create a child class and compare the queries
		$rand = rand(1, 999);
		$child = Family_Child::create();
		$child->update(array(
			'Name' => "Name $rand",
			'Title' => 'Child'
		));
		$result = $child->write();

		SS_Log::log(new Exception(print_r($result, true)), SS_Log::NOTICE);

		$child = Family_Child::get()
			->first();

		SS_Log::log(new Exception(print_r($child->toMap(), true)), SS_Log::NOTICE);

		return $this->customise(new ArrayData(array(
			'Title' => 'Orient DB Sandbox inheritance'
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