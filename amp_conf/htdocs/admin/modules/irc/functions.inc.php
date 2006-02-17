<?
// Try to determine the distro and kernel of the current machine
function getversioninfo() {
  // Various places that may have the Distro Name..
  $version = "";
  $locations = array('/etc/redhat-release', '/etc/fedora-release', 
  '/etc/debian_version', '/etc/SuSE-release', '/etc/gentoo-release');
  foreach ($locations as $loc) {
	if (is_readable($loc)) {
		$fh = fopen($loc, "r");
		if ($version != "") {
			$version .= " OR ".fgets($fh, 80);
		} else {
			$version = fgets($fh, 80);
		}
	}
  }
  if ($version == "") { 
	return "Unknown Version";
  } else {
	$lastchar = substr("$version", strlen("$version") - 1, 1);
               if ($lastchar == "\n")
               {
                       $version = substr("$version", 0, -1);
               } 
  	return $version; 
  }
}
?>
