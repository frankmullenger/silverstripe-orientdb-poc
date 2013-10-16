<?php

require_once BASE_PATH . '/vendor/orientdb-php/orientdb-php/OrientDB/OrientDB.php';

class PerformanceController extends AppController {

	private $dbType;
	
	private static $allowed_actions = array(
		'index',
		'build',
		'populate',
		'getone',
		'hasone',
		'hasmany',
		'manymany',
		'inheritance'
	);

	public function init() {
		parent::init();

		global $databaseConfig;
		SS_Log::log(new Exception(print_r($databaseConfig, true)), SS_Log::NOTICE);
		$this->dbType = $databaseConfig['type'];

		$_REQUEST['showqueries'] = 1;
	}
	
	public function index() {

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType"
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function getone() {

		$i = rand(1,999);

		$testObject = TestObject::get()->first();

		$content .= 'TestObject::get()->first() <br>';

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType get one",
			'Content' => $content
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function hasone() {

		$post = Post::get()->first();
		$author = $post->Author();

		$content .= '$post = Post::get()->first(); <br>';
		$content .= '$author = $post->Author(); <br>';

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType has one",
			'Content' => $content
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function hasmany() {

		$author = Person::get()->first();
		$posts = $author->Posts();

		$posts->toArray();

		$content .= '$author = Person::get()->first(); <br>';
		$content .= '$posts = $author->Posts(); <br>';
		$content .= '$posts->toArray(); <br>';

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType has many",
			'Content' => $content
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function manymany() {

		$article = Article::get()->first();
		$tags = $article->Tags();

		foreach ($tags as $tag) {
			$articles = $tag->Articles();
			$articles->toArray();
		}

		$content .= '$article = Article::get()->first(); <br>';
		$content .= '$tags = $article->Tags(); <br>';
		$content .= 'foreach ($tags as $tag) { <br>';
		$content .= '&nbsp;&nbsp;&nbsp; $articles = $tag->Articles(); <br>';
		$content .= '&nbsp;&nbsp;&nbsp; $articles->toArray();<br>';

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType many many",
			'Content' => $content
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function inheritance() {

		$child = Family_Child::get()->first();

		$content .= '$child = Family_Child::get()->first(); <br>';

		return $this->customise(new ArrayData(array(
			'Title' => "Performance: $this->dbType inheritance",
			'Content' => $content
		)))->renderWith(array(
			'PerformanceController',
			'AppController'
		));
	}

	public function build($request) {

		set_time_limit(3600);
		$_REQUEST['showqueries'] = 0;

		//Only build the classes do not populate the records
		$_REQUEST['dont_populate'] = 1;

		$renderer = DebugView::create();
		$renderer->writeHeader();
		$renderer->writeInfo("Orient Environment Builder: Do not run while logged in as a member", Director::absoluteBaseURL());
		echo "<div class=\"build\">";

		$da = DatabaseAdmin::create();
		$da->clearAllData();
		echo "<p>Cleared all data</p>";

		return $da->handleRequest($request, $this->model);

		echo "</div>";
		$renderer->writeFooter();
	}

	public function populate() {

		$_REQUEST['showqueries'] = 0;

		set_time_limit(3600);
		$renderer = DebugView::create();
		$renderer->writeHeader();
		$renderer->writeInfo("Orient Environment Builder: Do not run while logged in as a member", Director::absoluteBaseURL());
		echo "<div class=\"build\">";
		
		$numTestObjects = 100;
		$content .= "<br /><br />Creating $numTestObjects test objects.. <br />";

		for ($i = 0; $i < $numTestObjects; $i++) {
			$rand = rand(1, 10);
			$testObj = new TestObject();
			$testObj->Code = 'orient-test-' . $rand;
			$testObj->Title = 'OrientDB Test ' . $rand;
			$testObj->Sort = 1;
			$testObj->write(false, true, true);
			// $content .= "{$testObj->Title} <br />";
		}


		$content .= "Creating inherited object <br />";

		$child = Family_Child::create();
		$child->update(array(
			'Name' => "Name First",
			'Title' => 'Child'
		));
		$result = $child->write();


		$numPosts = 100;
		$content .= "Creating $numPosts posts and authors <br />";

		for ($i = 0; $i < $numPosts; $i++) {
			$rand = rand(1,9);

			$author = new Person();
			$author->Name = "Person $rand";
			$id = $author->write();

			$post = new Post();
			$post->Title = "Post Title $rand";
			$post->AuthorID = $id;
			$post->write();
		}


		$numArticles = 100;
		$content .= "Creating $numArticles articles and at least as many tags <br />";

		for ($i = 0; $i < $numArticles; $i++) {
			$rand = rand(1, 999);

			$article = new Article();
			$article->Name = "Article $rand";
			$id = $article->write();

			$range = array(1,2);

			foreach ($range as $val) {
				$tag = new Tag();
				$tag->Name = "Tag $val";
				$tag->write();

				$article->Tags()->add($tag);
			}
		}

		echo $content;

		echo "</div>";
		$renderer->writeFooter();
	}

	public function Link() {
		//To match the route for this controller
		return 'performance/';
	}
}