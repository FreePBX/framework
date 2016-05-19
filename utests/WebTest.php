<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
* @backupStaticAttributes disabled
*/
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
class WebTest extends PHPUnit_Framework_TestCase {

	protected static $f;

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
	}

	public function testLogin() {
		$jar = new CookieJar();
		$client = new Client();
		$res = $client->request('GET', 'http://127.0.0.1/admin/', ['cookies' => $jar]);
		$body = $res->getBody();
		$body = (string)$body;

		$crawler = new Crawler($body);
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

                $res = $client->request('GET', 'http://127.0.0.1/admin/', ['cookies' => $jar]);
                $body = $res->getBody();
		$body = (string)$body;

		$crawler = new Crawler($body);
		$this->assertEquals(
			0,
			$crawler->filter('#key')->count(),
			"The Session unlock process did not work"
		);
	}
}
