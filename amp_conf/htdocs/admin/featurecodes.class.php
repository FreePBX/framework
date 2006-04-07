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
	// $opt = 0 -- called by user code (i.e. outside this class)
	// $opt = 1 -- called automatically by this class
	// $opt = 2 -- called by user code, run even if called once already
	function init($opt = 0) {
		if ($this->isReady()) {
			if ($opt < 2)
				die('FeatureCode: init already called!');
		}
			
		$s = "SELECT description, defaultcode, customcode, enabled ";
		$s .= "FROM featurecodes ";
		$s .= "WHERE modulename = ".sql_formattext($this->_modulename)." AND featurename = ".sql_formattext($this->_featurename)." ";
		
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
			die('FeatureCode: class function init never called...will not update');

		$s = "REPLACE INTO featurecodes (modulename, featurename, description, defaultcode, customcode, enabled) ";
		$s .= "VALUES (".sql_formattext($this->_modulename).", ".sql_formattext($this->_featurename).", ".sql_formattext($this->_description).", ".sql_formattext($this->_defaultcode).", ".sql_formattext($this->_customcode).", ".sql_formattext($this->_enabled).") ";
		sql($s, "query");
		
		return true;
	}
	
	// SET DESCRIPTION
	function setDescription($description) {
		if (!$this->isReady())
			$this->init(1);

		if ($description == '') {
			unset($this->_description);
		} else {
			$this->_description = $description;
		}
	}
	
	// GET DESCRIPTION
	function getDescription() {
		if (!$this->isReady())
			$this->init(1);
		
		$desc = (isset($this->_description) ? $this->_description : '');
		
		return ($desc != '' ? $desc : $this->_featurename);
	}
	
	// SET DEFAULT CODE
	function setDefault($deafultcode) {
		if (!$this->isReady())
			$this->init(1);
			
		if ($deafultcode == '') {
			unset($this->_defaultcode);
		} else {
			$this->_defaultcode = $deafultcode;			
		}
	}
	
	// SET CUSTOM CODE
	function setCode($customcode) {
		if (!$this->isReady())
			$this->init(1);

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
			$this->init(1);

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
		if (!$this->isReady())
			$this->init(1);

		$this->_enabled = ($b ? 1 : 0);
	}
	
	// GET ENABLED
	function isEnabled() {
		if (!$this->isReady())
			$this->init(1);

		return ($this->_enabled == 1);
	}
}

// Helpers for eleswhere

// Return Array() of 'enabled' features for a specific module
function featurecodes_getModuleFeatures($modulename) {
	$s = "SELECT featurename, description ";
	$s .= "FROM featurecodes ";
	$s .= "WHERE modulename = ".sql_formattext($modulename)." AND enabled = 1 ";

	$results = sql($s,"getAll",DB_FETCHMODE_ASSOC);

	if (is_array($results)) {
		return $results;
	} else {
		return null;
		
	}
}
?>
