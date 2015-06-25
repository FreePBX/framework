<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
use Guzzle\Http\Client;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Symfony\Component\DomCrawler\Crawler;
class WebTest extends PHPUnit_Framework_TestCase {

	protected static $f;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public function testLogin() {
		$cookiePlugin = new CookiePlugin(new ArrayCookieJar());
		$client = new Client('http://127.0.0.1/admin/');
		$client->addSubscriber($cookiePlugin);
		$request = $client->get('config.php');
		$response = $request->send();
		$crawler = new Crawler($response->getBody(true));
		$this->assertGreaterThan(
			0,
			$crawler->filter('html:contains("FreePBX Administration")')->count(),
			"The Login Page Seems Incorrect. Missing FreePBX Administration"
		);

		$this->assertGreaterThan(
			0,
			$crawler->filter('#key')->count(),
			"Could not find login token"
		);

		$key = $crawler->filter('#key')->text();
		$key = trim($key);
		exec('fwconsole unlock '.$key);

		$response = $request->send();
		$crawler = new Crawler($response->getBody(true));
		$this->assertEquals(
			0,
			$crawler->filter('#key')->count(),
			"The Session unlock process did not work"
		);
	}
}
