<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;
class WebTest extends PHPUnit_Framework_TestCase {

	protected static $f;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public function testLogin() {
		$client = new Client('http://127.0.0.1/admin/');
		$request = $client->get('config.php');
		$response = $request->send();
		$crawler = new Crawler($response->getBody(true));
		$this->assertGreaterThan(
			0,
			$crawler->filter('html:contains("FreePBX Administration")')->count(),
			"The Login Page Seems Incorrect. Missing FreePBX Administration"
		);
	}
}
