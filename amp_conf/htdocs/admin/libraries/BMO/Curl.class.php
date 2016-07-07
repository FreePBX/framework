<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Curl {

	public $pesthandles = array();
	private $caches = array();

	public function getProxySettings() {
		$conf = \FreePBX::Config();
		if ($conf->get('PROXY_ENABLED')) {
			$url = trim($conf->get('PROXY_ADDRESS'));
			if (!$url) {
				// It's blank? Whut?
				return array("enabled" => false);
			}

			$retarr = array("enabled" => true, "type" => "http", "url" => $url);

			// We don't want any prefix before the proxy host for the 'host' tag.
			if (preg_match("/\/\/(.+)/", $url, $out)) {
				$retarr['host'] = $out[1];
			}  else {
				$retarr['host'] = $url;
			}

			// Do we have a valid username and password?
			$user = $conf->get('PROXY_USERNAME');
			$pass = $conf->get('PROXY_PASSWORD');
			if ($user && $pass) {
				$retarr['username'] = $user;
				$retarr['password'] = $pass;
			}
			return $retarr;
		} else {
			return array("enabled" => false);
		}
	}

	/**
	 * Get Proxy based PEST object
	 * @param  string $url The URL to pass
	 * @return object     PEST object supporting proxy
	 */
	public function pest($url = false) {
		if (!$url) {
			throw new \Exception("Invalid URL");
		}

		if (!isset($this->pesthandles[$url])) {
			// Create the handle
			$handle = new \Pest($url);

			$proxy = $this->getProxySettings();

			if ($proxy['enabled']) {
				switch ($proxy['type']) {
				case "http":
					$handle->curl_opts[\CURLOPT_PROXYTYPE] = \CURLPROXY_HTTP;
					break;
				case "socks":
					$handle->curl_opts[\CURLOPT_PROXYTYPE] = \CURLPROXY_SOCKS5;
					break;
				default:
					throw new \Exception("Unknown proxy type ".$proxy['type']);
				}

				$handle->curl_opts[\CURLOPT_PROXY] = $proxy['url'];
				if (isset($proxy['username'])) {
					$handle->curl_opts[\CURLOPT_PROXYUSERPWD] = $proxy['username'].":".$proxy['password'];
				}
			}

			$this->pesthandles[$url] = $handle;
		}

		return $this->pesthandles[$url];
	}

	public function get($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::get($url, $headers, $options);
	}

	public function head($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::head($url, $headers, $options);
	}

	public function delete($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::delete($url, $headers, $options);
	}

	public function post($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::post($url, $headers, $options);
	}

	public function put($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::put($url, $headers, $options);
	}

	public function patch($url, $headers = array(), $options = array()) {
		$headers = $this->addHeaders($headers);
		$options = $this->addOptions($options);
		return \Requests::patch($url, $headers, $options);
	}

	public function setEnvVariables() {
		$p = $this->getProxySettings();
		if (!$p['enabled']) {
			return;
		}

		// We have a proxy. Set the required environment variables.
		if (isset($p['username'])) {
			$url = "http://".$p['username'].":".$p['password']."@".$p['host'];
		} else {
			$url = "http://".$p['host'];
		}
		putenv("http_proxy=$url");
		putenv("no_proxy=localhost,127.0.0.1,:1");
		return;
	}

	private function addOptions($options) {
		$proxy = $this->getProxySettings();
		if($proxy['enabled']) {
			if(!empty($proxy['username'])) {
				$options['proxy'] = array( $proxy['host'], $proxy['username'], $proxy['password'] );
			} else {
				$options['proxy'] = $proxy['host'];
			}
		}
		return $options;
	}

	private function addHeaders($headers) {
		return $headers;
	}
}
