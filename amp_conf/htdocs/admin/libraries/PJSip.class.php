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
			"transport-default" => array( "protocol" => "udp", "bind" => "0.0.0.0:5060", "type" => "transport"),
			"transport-tls" => array( 
				"protocol" => "tls",
				"bind" => "0.0.0.0:5061",
				"cert_file" => "/tmp/cert.crt",
				"priv_key_file" => "/tmp/privkey.key",
				"cypher" => "ALL",    // Need more info on these two.
				"method" => "tlsv1",  // Need more info on these two.
				"type" => "transport",
			)
		);
		return $conf;
	}

	private function discoverTransport($type) {
		if (isset($this->transportTypes[$type]))
			return $this->transportTypes[$type];

		// Figure out which transport name matches our transport.
		//
		// Note: This is wrong.
		$transports = $this->getTransportConfigs();
		foreach ($transports as $name => $arr) {
			if ($arr['protocol'] == $type) {
				$this->transportTypes[$type] = $name;
				return $name;
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
		$endpointname = $config['account'];
		$endpoint[] = "type=endpoint";
		$authname = "$endpointname-auth";
		$auth[] = "type=auth";
		$aorname = "$endpointname";
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

		if (isset($retarr["pjsip.endpoint.conf"][$endpointname]))
			throw new Exception("Endpoint $endpointname already exists.");
		$retarr["pjsip.endpoint.conf"][$endpointname] = $endpoint;

		if (isset($retarr["pjsip.auth.conf"][$authname]))
			throw new Exception("Auth $authname already exists.");
		$retarr["pjsip.auth.conf"][$authname] = $auth;

		if (isset($retarr["pjsip.aor.conf"][$aorname]))
			throw new Exception("AOR $aorname already exists.");
		$retarr["pjsip.aor.conf"][$aorname] = $aor; 
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
			$config['username'] = $config['account'];

	}

	public function writePJSipConf($conf) {
		// Generate includes
		$pjsip = "#include pjsip.transports.conf\n#include pjsip.endpoint.conf\n#include pjsip.aor.conf\n#include pjsip.auth.conf\n";

		// Transports are a multi-dimensional array, because
		// we use it earlier to match extens with transports
		$transports = $this->getTransportConfigs();
		foreach ($transports as $transport => $entries) {
			$tmparr = array();
			foreach ($entries as $key => $val) {
				$tmparr[] = "$key=$val";
			}
			$conf['pjsip.transports.conf'][$transport] = $tmparr;
		}

		$conf['pjsip.conf'] = $pjsip;
		$this->FreePBX->WriteConfig($conf);
	}
}
