<?php
// vim: set ai ts=4 sw=4 ft=php:

class PJSip {

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	// Return an array consisting of all SIP devices, Trunks, or both.
	private function getAllOld($type = null) {
		$allkeys = $this->db->query("SELECT DISTINCT(`id`) FROM `sip`");
		$out = $allkeys->fetchAll(PDO::FETCH_ASSOC);
		foreach ($out as $res) {
			if (strpos($res['id'], "tr-") === false) {
				// This isn't a trunk. 
				// Do we want stuff that's not a trunk?
				if (!$type || $type == "devices") {
					$retarr['device'][] = $res['id'];
				}
			} else {
				// This IS a trunk
				if (preg_match("/^tr-.+-(\d+)/", $res['id'], $output)) {
					if (!$type || $type == "trunks") {
						$retarr['trunk'][] = $output[1];
					}
				} else {
					throw new Exception("I have no idea what ".$res['id']." is.");
				}
			}
		}
		if (isset($retarr)) {
			return $retarr;
		} else {
			return array();
		}
	}

	// Grab an Old Extension from the existing database
	private function getExtOld($ext = null) {

		// Careful - 0 is, sorta kinda, a valid device.
		if ($ext === null)
			throw new Exception("No Device given to getExtOld");

		// Have we already prepared our query?
		if (!isset($this->getExtOldQuery)) {
			$this->getExtOldQuery = $this->db->prepare("SELECT * FROM `sip` WHERE `id` = :id");
		}

		$this->getExtOldQuery->execute(array(":id" => $ext));
		$output = $this->getExtOldQuery->fetchAll(PDO::FETCH_ASSOC);

		// Tidy up the return
		foreach ($output as $entry) {
			$retarr[$entry['keyword']] = $entry['data'];
		}

		if (isset($retarr)) {
			return $retarr;
		} else {
			// return array();
			throw new Exception("Old SIP Device $ext not found");
		}
	}


	// Generate Transports
	// This is a quick POC to validate 'stuff'.
	private function getTransportConfigs() {
		// Need to configure these things somewhere.
		$conf = array(
			array(
				"name" => "transport-default",
				"protocol" => "udp",
				"port" => "5062",
			),
			array(
				"name" => "transport-tls",
				"protocol" => "tls",
				"port" => "5061",
				"cert_file" => "/tmp/cert.crt",
				"privkey_file" => "/tmp/privkey.key",
				"cypher" => "ALL",    // Need more info on these two.
				"method" => "tlsv1",  // Need more info on these two.
			)
		);
		return $conf;
	}

	private function generateTransports() {
		$retstr="; PJSip Transport Section\n";
		$transports = $this->getTransportConfigs();
		foreach($transports as $trans) {
			$retstr .= ";\n[".$trans['name']."]\n";
			$retstr .= "type=transport\n";
			foreach ($trans as $key => $val)
				$retstr .= "$key=$val\n";
		}

		return $retstr;
	}

	private function discoverTransport($type) {
		if (isset($this->transportTypes[$type]))
			return $this->transportTypes[$type];

		// Figure out which transport name matches our transport.
		//
		// Note: This is wrong.
		$transports = $this->getTransportConfigs();
		foreach ($transports as $arr) {
			if ($arr['protocol'] == $type) {
				$this->transportTypes[$type] = $arr['name'];
				return $arr['name'];
			}
		}

		// If we made it here, we don't have a transport for this protocol.
		throw new Exception("Yeah, if you could go ahead and make a transport for $type, that'd be great");
	}

	public function generateEndpoints() {
		// Only old stuff for the moment.
		$allEndpoints = $this->getAllOld("devices");

		foreach ($allEndpoints['device'] as $dev)
			$this->generateEndpoint($this->getExtOld($dev), $retarr);

		return $retarr;
	}

	private function generateEndpoint($config, &$retarr) {
		
		// Validate $config array
		$this->validateEndpoint($config);

		// With pjsip, we need three sections. 
		$endpointname = "99".$config['account'];
		$endpoint[] = "type=endpoint";
		$authname = "$endpointname-auth";
		$auth[] = "type=auth";
		$aorname = "$endpointname-aor";
		$aor[] = "type=aor";

		// Endpoint
		$endpoint[] = "aors=$aorname";
		$endpoint[] = "auth=$authname";
		// Note that blank codec lines are no longer allowed
		if (!empty($config['allow']))
			$endpoint[] = "allow=".$config['allow'];

		if (!empty($config['disallow']))
			$endpoint[] = "disallow=".$config['disallow'];

		$endpoint[] = "context=".$config['context'];
		$endpoint[] = "callerid=".$config['callerid'];
		$endpoint[] = "dtmf_mode=".$config['dtmfmode'];
		$endpoint[] = "mailboxes=".$config['mailbox'];
		$endpoint[] = "transport=".$config['transport'];

		// Auth
		$auth[] = "auth_type=userpass";
		$auth[] = "password=".$config['secret'];
		$auth[] = "username=".$config['username'];

		// AOR
		$aor[] = "max_contacts=1";

		if (isset($retarr[$endpointname]))
			throw new Exception("Endpoint $endpointname already exists.");
		$retarr[$endpointname] = $endpoint;

		if (isset($retarr[$authname]))
			throw new Exception("Endpoint $authname already exists.");
		$retarr[$authname] = $auth;

		if (isset($retarr[$aorname]))
			throw new Exception("Endpoint $aorname already exists.");
		$retarr[$aorname] = $aor; 
	}

	private function validateEndpoint(&$config) {

		// Currently unported: 
		//   accountcode, callgroup, 
		
		// DTMF Mode has changed.
		if ($config['dtmfmode'] == "rfc2833")
			$config['dtmfmode'] = "rfc4733";

		$config['transport'] = $this->discoverTransport($config['transport']);

		// 'username' is for when username != exten.
		if (!isset($config['username']))
			$config['username'] = "99".$config['account'];

	}

	public function writePJSipConf($conf) {
		$output = "; PJSip Configuration\n";
		$output = "; Don't edit this. You'll be sad\n";
		$output .= $this->generateTransports();
		foreach ($conf as $endpoint => $val) {
			$output .= "[$endpoint]\n";
			$output .= implode("\n", $val);
			$output .= "\n;\n";
		}
		file_put_contents("/etc/asterisk/pjsip.conf", $output); // TODO: Generate Config File Class Needed.
	}
}
