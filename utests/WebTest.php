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
		self::$f = FreePBX::create();
	}
	private function getHttpPort($name = "acp") {
		// This is only usable if the machine the tests are run on
		// is a FreePBX Distro machine, and that the ports have been
		// configured by sysadmin.  If the ports are not discoverable,
		// it will return false.
		$conffile = false;
		if (file_exists("/etc/httpd/conf.d/schmoozecom.conf")) {
			$conffile = "/etc/httpd/conf.d/schmoozecom.conf";
		} elseif (file_exists("/etc/httpd/conf.d/sangoma.conf")) {
			$conffile = "/etc/httpd/conf.d/sangoma.conf";
		} else {
			// Unable to detect, return false.
			return false;
		}
		$filearr = file($conffile, FILE_IGNORE_NEW_LINES);
		// Try to json_decode the second line, which should
		// contain the configuration of the system
		if (!isset($filearr[1])) {
			return false;
		}
		$confarr = @json_decode(substr($filearr[1], 2), true);
		if (isset($confarr[$name])) {
			return $confarr[$name]['port'];
		} else {
			return false;
		}
	}

	public function testLogin() {
		$port = $this->getHttpPort("acp");
		if (!$port) {
			// Not a FreePBX Distro machine. Assume port 80
			$port = 80;
		}

		$cookiePlugin = new CookiePlugin(new ArrayCookieJar());
		$client = new Client("http://127.0.0.1:$port/admin/");
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
