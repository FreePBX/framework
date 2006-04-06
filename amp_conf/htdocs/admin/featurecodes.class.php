<?php
class featurecode
{
	var $_modulename;	// Module name
	var $_featurename;	// Feature name
	var $_description;	// Description (i.e. what the user will see)
	var $_defaultcode;	// Default code if user doesn't pick one
	var $_customcode;	// Custom code
	var $_enabled;		// Enabled/Disabled (0-disabled; 1-enabled)
	
	// CONSTRUCTOR
	function featurecode($modulename, $featurename) {
		if ($modulename == '' || $featurename == '')
			die('feature code class must be called with ModuleName and FeatureName');

		$this->_modulename = $modulename;
		$this->_featurename = $featurename;
		$this->_enabled = -1;  // -1 means not initialised
	}

	// HAS BEEN INIT'D ????
	function isReady() {
		return (!($this->_enabled == -1));
	}
	
	// INIT FUNCTION -- READS FROM DATABASE IF THERE BASICALLY
	function init() {
		if ($this->isReady())
			die('FeatureCode: init already called!');
			
		$s = "SELECT description, defaultcode, customcode, enabled ";
		$s .= "FROM featurecodes ";
		$s .= "WHERE modulename = '$this->_modulename' AND featurename = '$this->_featurename'";
		
		$res = sql($s, "getRow");
		if (is_array($res)) { // found something, read it
			$this->_description = $res[0];
			$this->_defaultcode = $res[1];
			$this->_customcode = $res[2];
			$this->_enabled = $res[3];
			
			return true;
		} else {
			// didn't find, but mark as 'enabled' by default ???
			$this->_enabled = 1;
			
			return false;
		}
	}
	
	// UPDATE FUNCTION -- WRITES CURRENT STUFF BACK TO DATABASE
	function update() {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');

		$s = "REPLACE INTO featurecodes (modulename, featurename, description, defaultcode, customcode, enabled) ";
		$s .= "VALUES ('$this->_modulename', '$this->_featurename', '$this->_description', '$this->_defaultcode', '$this->_customcode', $this->_enabled) ";
		sql($s, "query");
		
		return true;
	}
	
	// SET DESCRIPTION
	function setDescription($description) {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');

		if ($description == '') {
			unset($this->_description);
		} else {
			$this->_description = $description;
		}
	}
	
	// GET DESCRIPTION
	function getDescription() {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');
		
		$desc = (isset($this->_description) ? $this->_description : '');
		
		return ($desc != '' ? $desc : $this->_featurename);
	}
	
	// SET DEFAULT CODE
	function setDefault($deafultcode) {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');
			
		if ($deafultcode == '') {
			unset($this->_defaultcode);
		} else {
			$this->_defaultcode = $deafultcode;			
		}
	}
	
	// SET CUSTOM CODE
	function setCode($customcode) {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');

		if ($customcode == '') {
			unset($this->_customcode);
		} else {
			$this->_customcode = $customcode;
		}
	}
	
	// GET FEATURE CODE -- DEFAULT OR CUSTOM IF SET
	//                     RETURN '' IF NOT AVAILABLE
	function getCode() {
		if (!$this->isReady())
			die('FeatureCode: you must call init function before using');

		$curcode = (isset($this->_customcode) ? $this->_customcode : '');
		$defcode = (isset($this->_defaultcode) ? $this->_defaultcode : '');
		
		return ($curcode == '' ? $defcode : $curcode);
	}
	
	// GET FEATURE CODE ONLY IF ENABLED
	function getCodeActive() {
		if ($this->isEnabled()) {
			return $this->getCode();
		} else {
			return '';
		}
	}
	
	// SET ENABLED
	function setEnabled($b = true) {
		$this->_enabled = ($b ? 1 : 0);
	}
	
	// GET ENABLED
	function isEnabled() {
		return ($this->_enabled == 1);
	}
}

// Helpers for eleswhere

// Return Array() of 'enabled' features for a specific module
function featurecodes_getModuleFeatures($modulename) {
	$s = "SELECT featurename, description ";
	$s .= "FROM featurecodes ";
	$s .= "WHERE modulename = '$modulename' AND enabled = 1 ";

	$results = sql($s,"getAll",DB_FETCHMODE_ASSOC);

	if (is_array($results)) {
		return $results;
	} else {
		return null;
		
	}
}
?>
