<?

global $asterisk_conf;
outn("Cleaning up leftover backup scripts that were distributed in core...");
if (file_exists($asterisk_conf['astvarlibdir']."/bin/ampbackup.pl") && !is_link($asterisk_conf['astvarlibdir']."/bin/ampbackup.pl")) {
	unlink($asterisk_conf['astvarlibdir']."/bin/ampbackup.pl");
	out("Done");
} else {
	out("Not Required");
}
?>
