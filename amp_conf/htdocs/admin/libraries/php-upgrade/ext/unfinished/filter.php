<?php
/**
 * @group 5_2_FILTER
 * @since 5.2
 *
 * filter_*() functions can probably be reimplemented for PHP5.0 and PHP4.x
 *
 *
 */




/**
 * Gets numeric filter index, from its textual name.
 *
 * @param  string
 * @return integer
 */
function filter_id($name) {
   $map = array(
      "int" => FILTER_VALIDATE_INT,
      "boolean" => FILTER_VALIDATE_BOOLEAN,
      "float" => FILTER_VALIDATE_FLOAT,
      "validate_regexp" => FILTER_VALIDATE_REGEXP,
      "validate_url" => FILTER_VALIDATE_URL,
      "validate_email" => FILTER_VALIDATE_EMAIL,
      "validate_ip" => FILTER_VALIDATE_IP,
      "string" => FILTER_SANITIZE_STRING,
      "stripped" => FILTER_SANITIZE_STRIPPED,
      "encoded" => FILTER_SANITIZE_ENCODED,
      "special_chars" => FILTER_SANITIZE_SPECIAL_CHARS,
      "unsafe_raw" => FILTER_UNSAFE_RAW,
      "email" => FILTER_SANITIZE_EMAIL,
      "url" => FILTER_SANITIZE_URL,
      "number_int" => FILTER_SANITIZE_NUMBER_INT,
      "number_float" => FILTER_SANITIZE_NUMBER_FLOAT,
      "magic_quotes" => FILTER_SANITIZE_MAGIC_QUOTES,
      "callback" => FILTER_CALLBACK,
   );
   if (isset($map[$name])) {
      return($map[$name]);
   }
   else {
      trigger_error(E_USER_ERROR, "no filter by name '".htmlentities($name)."'");
   }
}



/**
 * Returns list of filter names.
 *
 * @return array
 */
function filter_list() {
   return array(
      "int",
      "boolean",
      "float",
      "validate_regexp",
      "validate_url",
      "validate_email",
      "validate_ip",
      "string",
      "stripped",
      "encoded",
      "special_chars",
      "unsafe_raw",
      "email",
      "url",
      "number_int",
      "number_float",
      "magic_quotes",
      "callback",
   );
}



/**
 * filter id and flag constants,
 * may not match real values yet, just for testing
 *
 */
define("INPUT_POST", 1);
define("INPUT_GET", 2);
define("INPUT_COOKIE", 4);
define("INPUT_ENV", 8);
define("INPUT_SERVER", 16);
define("INPUT_SESSION", 32);
define("INPUT_REQUEST", 64);
define("FILTER_FLAG_NONE", 		0x0000000);
define("FILTER_REQUIRE_SCALAR",		0x0000001);
define("FILTER_REQUIRE_ARRAY",		0x0000002);
define("FILTER_FORCE_ARRAY",		0x0000006);
define("FILTER_NULL_ON_FAILURE",	0x0000080);
define("FILTER_VALIDATE_INT", 1083);
define("FILTER_VALIDATE_BOOLEAN", 1084);
define("FILTER_VALIDATE_FLOAT", 1085);
define("FILTER_VALIDATE_REGEXP", 1086);
define("FILTER_VALIDATE_URL", 1087);
define("FILTER_VALIDATE_EMAIL", 1088);
define("FILTER_VALIDATE_IP", 1089);
define("FILTER_DEFAULT", 1090);
define("FILTER_UNSAFE_RAW", 1091);
define("FILTER_SANITIZE_STRING", 1092);
define("FILTER_SANITIZE_STRIPPED", 1093);
define("FILTER_SANITIZE_ENCODED", 1094);
define("FILTER_SANITIZE_SPECIAL_CHARS", 1095);
define("FILTER_SANITIZE_EMAIL", 1096);
define("FILTER_SANITIZE_URL", 1097);
define("FILTER_SANITIZE_NUMBER_INT", 1098);
define("FILTER_SANITIZE_NUMBER_FLOAT", 1099);
define("FILTER_SANITIZE_MAGIC_QUOTES", 1100);
define("FILTER_CALLBACK", 1101);
define("FILTER_FLAG_ALLOW_OCTAL", 	0x0000100);
define("FILTER_FLAG_ALLOW_HEX", 	0x0000200);
define("FILTER_FLAG_STRIP_LOW", 	0x0000400);
define("FILTER_FLAG_STRIP_HIGH", 	0x0000800);
define("FILTER_FLAG_ENCODE_LOW", 	0x0001000);
define("FILTER_FLAG_ENCODE_HIGH",	0x0002000);
define("FILTER_FLAG_ENCODE_AMP",	0x0004000);
define("FILTER_FLAG_NO_ENCODE_QUOTES",	0x0008000);
define("FILTER_FLAG_EMPTY_STRING_NULL", 0x0010000);
define("FILTER_FLAG_ALLOW_FRACTION", 	0x0020000);
define("FILTER_FLAG_ALLOW_THOUSAND",	0x0040000);
define("FILTER_FLAG_ALLOW_SCIENTIFIC",	0x0080000);
define("FILTER_FLAG_SCHEME_REQUIRED",	0x0100000);
define("FILTER_FLAG_HOST_REQUIRED",	0x0200000);
define("FILTER_FLAG_PATH_REQUIRED",	0x0400000);
define("FILTER_FLAG_QUERY_REQUIRED",	0x0800000);
define("FILTER_FLAG_IPV4", 		0x1000000);
define("FILTER_FLAG_IPV6", 		0x2000000);
define("FILTER_FLAG_NO_RES_RANGE",	0x4000000);
define("FILTER_FLAG_NO_PRIV_RANGE", 	0x8000000);


/**
 * Get list of variables from _REQUEST, _GET, _POST, _COOKIE, _SERVER, _ENV
 * or _SESSION array, piped through some of the filters.
 *
 * @param integer  input array flag ids
 * @param mixed    mapping varname => filter_id or filter/flags hash
 * @global $_REQUEST,$_GET,$_POST,$_SERVER,$_ENV,$_SESSION,$_COOKIE
 */
function filter_input_array($GPCSESR, $def=array()) {
   $r = array();
   
   if ($GPCSESR & INPUT_REQUEST) {
      $r = array_merge($r, filter_var_array($_REQUEST, $def));
   }
   if ($GPCSESR & INPUT_POST) {
      $r = array_merge($r, filter_var_array($_POST, $def));
   }
   if ($GPCSESR & INPUT_GET) {
      $r = array_merge($r, filter_var_array($_GET, $def));
   }
   if ($GPCSESR & INPUT_COOKIE) {
      $r = array_merge($r, filter_var_array($_COOKIE, $def));
   }
   if ($GPCSESR & INPUT_SERVER) {
      $r = array_merge($r, filter_var_array($_SERVER, $def));
   }
   if ($GPCSESR & INPUT_ENV) {
      $r = array_merge($r, filter_var_array($_ENV, $def));
   }
   
   return($r);
}



/**
 * Get a single variable filtered from input arrays.
 *
 * @param bitmask
 * @param string
 * @param bitmask
 * @param bitmask
 * @return string
 */
function filter_input($_INPUT, $name, $filter=0, $flags=0) {
   return filter_input_array($_INPUT, array($name => array("filter"=>$filter, "flags"=>$flags)));
}




/**
 * Extract a list of variables from an array. Apply filters.
 *
 * @param array   source data
 * @param array   variable list
 * @return array  filtered vars
 */
function filter_var_array($hash, $def=array()) {
   $r = array();

   #-- variable names from list
   foreach($def as $varname=>$filt) {
   
      #-- do we have it?
      if (!isset($hash[$varname])) {
      }
      #-- filter id and flags
      elseif (is_array($filt)) {
         $r[$varname] = filter_var($hash[$name], $filt["filter"], $filt["flags"]);
      }
      #-- filter without args
      else {
         $r[$varname] = filter_var($hash[$name], $filt);
      }
   }
   
   return($r);
}


/**
 * isset() test in _GET,_POST,... arrays
 *
 */
function filter_has_var($_INPUT, $varname) {
   return false
   or ($_INPUT & INPUT_REQUEST) and isset($_REQUEST[$varname])
   or ($_INPUT & INPUT_SERVER) and isset($_SERVER[$varname])
   or ($_INPUT & INPUT_COOKIE) and isset($_COOKIE[$varname])
   or ($_INPUT & INPUT_POST) and isset($_POST[$varname])
   or ($_INPUT & INPUT_GET) and isset($_GET[$varname])
   or ($_INPUT & INPUT_ENV) and isset($_ENV[$varname]);
}



/**
 * Input filtering main function.
 *
 * @param mixed $var
 */
function filter_var($var, $filter=FILTER_SANITIZE_STRING, $options=array()) {

   #-- recursively call ourselves for array input
   if (is_array($var)) {
     array_walk_recursive($var, "filter_var__walk", array($filter, $options));
     return $var;
   }
   
   #-- extract options
   $flags = 0x0000000;
   $regexp = "//";
   $callback = "";
   $min_range = INT_MIN;
   $max_range = INT_MAX;
   if (is_array($options)) {
      extract($options);   // regexp, callback, min_range, max_range, flags
   }
   elseif (is_int($options)) {
      $flags = $options;
   }
   
   #-- clean up flags
   $FAILURE = $flags & FILTER_NULL_ON_FAILURE ? NULL : false;
   $flags = $flags & ~FILTER_NULL_ON_FAILURE;
   $flags = $flags & ~FILTER_REQUIRE_SCALAR;
   $flags = $flags & ~FILTER_REQUIRE_ARRAY;
   $flags = $flags & ~FILTER_FORCE_ARRAY;

   
   #-- regular expressions for simple filters
   $rx = array(
       "int" => "/^[-+]?(\d{1,9})$/",
       "oct" => "/^0([0-7]{1,12})$/",
       "hex" => => "/^(0x)?([0-0a-fA-F]{1,12})$/",
       "bool_true" => => "/^(true|yes|on|1)$/",
       "bool_false" => => "/^(false|no|off|0|)$/",
       "float" => => "/^([+-]?\d+(\.\d+)([eE]\d+))$/",
       "char_low" => => "[\000-\037]",
       "char_high" => => "[\177-\377]",
   );


   #-- generic string code
   if (1) {
      #-- rm ASCII <32
      if ($flags & FILTER_FLAG_STRIP_LOW) {
         $var = preg_replace("/$rx[char_low]+/", "", $var);
      }
      #-- rm ASCII >127
      if ($flags & FILTER_FLAG_STRIP_HIGH) {
         $var = preg_replace("/$rx[char_high]+/", "", $var);
      }
   }


   #-- integer
   if ($filter == FILTER_VALIDATE_INT) {

      #-- octal
      if ($flags & FILTER_FLAG_ALLOW_OCTAL) {
         if (preg_match($rx["oct"], $var)) {
            $var = octdec($var);
         }
         else {
            return $FAILURE;
         }
      }

      #-- hex
      elseif ($flags & FILTER_FLAG_ALLOW_HEX) {
         if (preg_match($rx["hex"], $var, $uu)) {
            $var = hexdec($uu[2]);
         }
         else {
            return $FAILURE;
         }
      }

      #-- plain int
      elseif (preg_match($rx["int"])) {
         $var = (int) $var;
      }
      else {
         return $FAILURE;
      }
      
      #-- range
      if (($var < $min_range) or ($var > $max_range)) {
         return $FAILURE;
      }
      else {
         return $var;
      }
   }

   
   #-- boolean
   elseif ($filter == FILTER_VALIDATE_BOOLEAN) {
      if (preg_match($rx["bool_true"], $var) {
         return TRUE;
      }
      elseif (preg_match($rx["bool_false"], $var) {
         return FALSE;
      }
      else {
         return $FAILURE;
      }
   }


   #-- float
   elseif ($filter = FILTER_VALIDATE_FLOAT) {
      return preg_match($rx["float"], $var) ? (float)$var : $FAILURE;
   }


   #-- regexp
   elseif ($filter = FILTER_VALIDATE_REGEXP) {
      if ($regexp) {
         return preg_match($regexp, $var) ? $var : $FAILURE;
      }
      else {
         trigger_error(E_USER_ERROR, "no regexp supplied for FILTER_VALIDATE_REGEXP");
      }
   }



// FILTER_VALIDATE_URL


// FILTER_VALIDATE_EMAIL


// FILTER_VALIDATE_IP






   #-- strip_tags
   elseif ($filter == FILTER_SANITIZE_STRING) {

      #-- remove angle brackets and everything in between
      $var = strip_tags($var);

      #-- quotes
      if ($flags & FILTER_FLAG_NO_ENCODE_QUOTES) {
         // nothing
      }
      else {
         $var = strtr(array("'", '"'), array("&#39;", "&#34;"), $var);
      }

      #-- encode ASCII <32
      if ($flags & FILTER_FLAG_ENCODE_LOW) {
         $var = preg_replace("/($rx[char_low]+)/e", "'&#'.ord('$1').';'", $var);
      }
      #-- encode ASCII >127
      if ($flags & FILTER_FLAG_ENCODE_HIGH) {
         $var = preg_replace("/($rx[char_high]+)/e", "'&#'.ord('$1').';'", $var);
      }


      #-- AMPERSAND
      if ($flags & FILTER_FLAG_ENCODE_AMP) {
         $var = str_replace("&", "&#38;", $var);
      }

      return $var;
   }


   #-- urlencoded
   elseif  ($filter == FILTER_SANITIZE_ENCODED) {
   
      $var = urlencode($var);

      #-- encode ASCII <32
      if ($flags & FILTER_FLAG_ENCODE_LOW) {
         $var = preg_replace("/($rx[char_low]+)/e", "rawurlencode('$1')", $var);
      }
      #-- encode ASCII >127
      if ($flags & FILTER_FLAG_ENCODE_HIGH) {
         $var = preg_replace("/($rx[char_high]+)/e", "rawurlencode('$1')", $var);
      }
       
      return $var;
   }


   #-- htmlspecialchars()
   elseif ($filter == FILTER_SANITIZE_SPECIAL_CHARS) {

      #-- encode rest
      $var = htmlspecialchars($var);

      #-- always encode ASCII<32
      if (1) {
         $var = preg_replace("/($rx[char_low]+)/e", "'&#'.ord('$1').';'", $var);
      }
      #-- encode ASCII >127
      if ($flags & FILTER_FLAG_ENCODE_HIGH) {
         $var = preg_replace("/($rx[char_high]+)/e", "'&#'.ord('$1').';'", $var);
      }

      return $var;
   }


   #-- binary
   elseif ($filter == FILTER_UNSAFE_RAW)) {
   
      #-- "raw": encode AMPERSAND
      if ($flags & FILTER_FLAG_ENCODE_AMP) {
         $var = str_replace("&", "%", $var);           // ???????????????
      }
   
      #-- rm ASCII <32
      if ($flags & FILTER_FLAG_STRIP_LOW) {
         $var = preg_replace("/$rx[char_low]+/", "", $var);
      }
      #-- rm ASCII >127
      if ($flags & FILTER_FLAG_STRIP_HIGH) {
         $var = preg_replace("/$rx[char_high]+/", "", $var);
      }
      #-- encode ASCII <32
      if ($flags & FILTER_FLAG_ENCODE_LOW) {
         $var = preg_replace("/($rx[char_low]+)/e", "rawurlencode('$1')", $var);
      }
      #-- encode ASCII >127
      if ($flags & FILTER_FLAG_ENCODE_HIGH) {
         $var = preg_replace("/($rx[char_high]+)/e", "rawurlencode('$1')", $var);
      }


      return $var;
   }


   #-- email
   elseif ($filter == FILTER_SANITIZE_EMAIL) {
      return preg_replace("/[^-\w\d!#\$%&'*+\/=?\^`{\|}~@\.\[\]]+/", "", $var);
   }


   #-- url
   elseif ($filter == FILTER_SANITIZE_URL) {
      return preg_replace("/[^-\w\d\$.+!*'(),{}\|\\~\^\[\]\`<>#%\";\/?:@&=]+/", "", $var);
   }


   #-- number_int
   elseif ($filter == FILTER_SANITIZE_NUMBER_INT) {
      return preg_replace("/(^[^-+\d]|[^\d]+)/", "", $var);
   }


   #-- number_float
   elseif ($filter == FILTER_SANITIZE_NUMBER_INT) {
      // FILTER_FLAG_ALLOW_FRACTION
      // FILTER_FLAG_ALLOW_THOUSAND
      // FILTER_FLAG_ALLOW_SCIENTIFIC
      return preg_replace("/(^[^-+\d]|[^\d.eE]+)/", "", $var);
   }


   #-- magic_quotes
   elseif ($filter == FILTER_SANITIZE_MAGIC_QUOTES) {
      return addslashes($var);
   }


   #-- callback function
   elseif ($filter == FILTER_CALLBACK) {
      return call_user_func($callback, $var);
   }

         
   #-- filter unknown         
   else {
      trigger_error(E_USER_ERROR, "Uh, oh. Unknown filter id #".$filter);
      return $FAILURE;
   }
   
}


/**
 * Callback wrapper; needed for array_walk_recursive().
 *
 */
function filter_var__walk(&$var, $key, $filter, $options) {
   $var = filter_var($var, $filter, $options);
}



/**
 * @tests
 
123 === filter_var("123", FILTER_VALIDATE_INT)
FALSE === filter_var("--123", FILTER_VALIDATE_INT)
123 == filter_var("+123", FILTER_VALIDATE_INT)
15 === filter_var("0xF", FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX)
FALSE === filter_var("-0xFFFF", FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX)
"12&#38;3" === filter_var("12&3", FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_AMP)
"1&#9;2'&5" === ("1\t2'3&<4>5", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES|FILTER_FLAG_ENCODE_LOW)
 
 */

?>