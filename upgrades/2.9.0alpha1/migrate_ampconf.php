<?php
//TODO: revisit migration once we have further refined the amportal.conf in a db
//      architecture. We need a class that allows modules to add their own values
//      or remove them much like feature codes, also need some concept of sections
//      which is not necessarily per module.
if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

global $db, $amp_conf;

outn(_("Preparing to migrate amportal.conf settings.."));
$sql = 'SELECT count(*) FROM freepbx_settings';
$result = $db->query($sql);
unset($sql);
if(DB::IsError($result)){
	$sql[] = "CREATE TABLE `freepbx_settings` (
	  `keyword` varchar(50) default NULL,
	  `value` varchar(255) default NULL,
	  `level` int(11) default '0',
	  `description` text default NULL,
	  `type` varchar(25) default NULL,
	  `options` blob default NULL,
	  `defaultval` varchar(255) default NULL,
	  `readonly` varchar(15) default NULL,
	  `hidden` varchar(15) default NULL,
	  UNIQUE KEY `key` (`key`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1";


  // TODO: this needs field names if we keep this, also needs newsql file entry
	$sql[] = "INSERT INTO `freepbx_settings` VALUES 
		('AMPADMINLOGO','logo.png',0,'Defines the logo that is to be displayed at the TOP RIGHT of the admin screen. This enables you to customize the look of the administration screen. NOTE: images need to be saved in the ..../admin/images directory of your AMP install. This image should be 55px in height<br>Default = logo.png','text',NULL,NULL,NULL,NULL),
		('AMPBADNUMBER','TRUE',0,'Generate the bad-number context which traps any bogus number or feature code and plays a message to the effect. If you use the Early Dial feature on some Grandstream phones, you will want to set this to false<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('AMPBIN','/var/lib/asterisk/bin',0,'Location of the FreePBX command line scripts<br>Default = /var/lib/asterisk/bin','text',NULL,'/var/lib/asterisk/bin',NULL,NULL),
		('AMPCGIBIN','/var/www/cgi-bin ',0,'The path to Apache cgi-bin dir (leave off trailing slash)<br>Default = var/www/cgi-bin','text',NULL,'/var/www/cgi-bin',NULL,NULL),
		('AMPDBENGINE','mysql',0,'Database engine used<br>Mostly used = mysql','select','mysql,sqlite3','mysql',NULL,NULL),
		('AMPDBHOST','localhost',0,'Hostname where the database asterisk is located<br>Default = localhost','text',NULL,'localhost',NULL,NULL),
		('AMPDBNAME','asterisk',0,'Name of the FreePBX database<br>Default = asterisk','text',NULL,'asterisk',NULL,NULL),
		('AMPDBPASS','amp109',0,'Password for accessing the database asterisk. Used in combination with AMPDBUSER<br>Default = amp109','text',NULL,NULL,NULL,NULL),
		('AMPDBUSER','asteriskuser',0,'Username for accessing the database asterisk<br>Default = asteriskuser','text',NULL,NULL,NULL,NULL),
		('AMPDISABLELOG','TRUE',0,'Whether or not to invoke the FreePBX log facility<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('AMPENABLEDEVELDEBUG','FALSE',0,'Whether or not to include log messages marked as <b>devel-debug</b> in the log system<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('AMPENGINE','asterisk',0,'The telephony backend engine to use<br>Default = asterisk','select','asterisk','asterisk',NULL,NULL),
		('AMPEXTENSIONS','extensions',0,'Sets the extension behavior in FreePBX.  If set to <b>extensions</b>, Devices and Users are administered together as a unified Extension, and appear on a single page. If set to <b>deviceanduser</b>, Devices and Users will be administered seperately. Devices (e.g. each individual line on a SIP phone) and Users (e.g. <b>101</b>) will be configured independent of each other, allowing association of one User to many Devices, or allowing Users to login and logout of Devices<br>Default = extensions','select','extensions,deviceanduser','extensions',NULL,NULL),
		('AMPMGRPASS','amp111',0,'Password for accessing the Asterisk Manager Interface (AMI)<br>Default = amp111','text',NULL,'amp111',NULL,NULL),
		('AMPMGRUSER','admin',0,'Username for accessing the Asterisk Manager Interface (AMI)<br>Default = admin','text',NULL,'admin',NULL,NULL),
		('AMPMPG123','TRUE',0,'When set to false, the old MoH behavior is adopted where MP3 files can be loaded and WAV files converted to MP3. The new default behavior assumes you have mpg123 loaded as well as sox and will convert MP3 files to WAV. This is highly recommended as MP3 files heavily tax the system and can cause instability on a busy phone system<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('AMPSBIN','/usr/sbin',0,'Where (root) command line scripts are located<br>Default = /usr/local/sbin','text',NULL,'/usr/sbin',NULL,NULL),
		('AMPSYSLOGLEVEL','',0,'Where to log if enabled, SQL, LOG_SQL logs to old MySQL table, others are passed to syslog system to determine where to log. Values are: LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG, LOG_SQL,SQL<br>Default = LOG_ERR','text',NULL,NULL,NULL,NULL),
		('AMPVMUMASK','007',0,'Defaults to 077 allowing only the asterisk user to have any permission on VM files. If set to something like 007, it would allow the group to have permissions. This can be used if setting apache to a different user then asterisk, so that the apache user (and thus ARI) can have access to read/write/delete the voicemail files. If changed, some of the voicemail directory structures may have to be manually changed<br>Default = 077','text',NULL,NULL,NULL,NULL),
		('AMPWEBADDRESS','',0,'The IP address or host name used to access the CDR<br>Default = not used','text',NULL,NULL,NULL,NULL),
		('AMPWEBROOT','/var/www/html',0,'The path to Apache webroot (leave off trailing slash)<br>Default = /var/www/html','text',NULL,'/var/www/html',NULL,NULL),
		('ARI_ADMIN_PASSWORD','ari_password',0,'This is the default admin password to allow an administrator to login to ARI bypassing all security. Change this to a secure password.Default = not set','text',NULL,'ari_password',NULL,NULL),
		('ARI_ADMIN_USERNAME','',0,'This is the default admin name used to allow an administrator to login to ARI bypassing all security. Change this to whatever you want, dont forget to change the ARI_ADMIN_PASSWORD as well.Default = not set','text',NULL,'admin',NULL,NULL),
		('ASTAGIDIR','/var/lib/asterisk/agi-bin',0,'This is the default directory for Asterisks agi files<br>Default = /var/lib/asterisk/agi-bin','text',NULL,'/var/lib/asterisk/agi-bin',NULL,NULL), 
		('ASTETCDIR','/etc/asterisk',0,'This is the default directory for Asterisks configuration files<br>Default = /etc/asterisk','text',NULL,'/etc/asterisk',NULL,NULL), 
		('ASTLOGDIR','/var/log/asterisk',0,'This is the default directory for Asterisks log files<br>Default = /var/log/asterisk','text',NULL,'/var/log/asterisk',NULL,NULL), 
		('ASTMODDIR','/usr/lib/asterisk/modules',0,'This is the default directory for Asterisks modules<br>Default = /usr/lib/asterisk/modules','text',NULL,'/usr/lib/asterisk/modules',NULL,NULL), 
		('ASTSPOOLDIR','/var/spool/asterisk',0,'This is the default directory for Asterisks spool directory<br>Default = /var/spool/asterisk','text',NULL,'/var/spool/asterisk',NULL,NULL), 
		('ASTRUNDIR','/var/run/asterisk',0,'This is the default directory for Asterisks run files<br>Default = /var/run/asterisk','text',NULL,'/var/run/asterisk',NULL,NULL), 
		('ASTVARLIBDIR','/var/lib/asterisk',0,'This is the default directory for Asterisks lib files<br>Default = /var/lib/asterisk','text',NULL,'/var/lib/asterisk',NULL,NULL), 
		('ASTMANAGERPORT','5038',0,'Port for the Asterisk Manager<br>Default = 5038','text',NULL,'5038',NULL,NULL), 
		('ASTMANAGERHOST','localhost',0,'Hostname for the Asterisk Manager<br>Default = 5038','text',NULL,'5038',NULL,NULL), 
		('AUTHTYPE','database',0,'Authentication type to use for web admin. If type set to <b>database</b>, the primary AMP admin credentials will be the AMPDBUSER/AMPDBPASS above. Valid settings are: none, database<br>Default = database','select','none,database','database',NULL,NULL),
		('BADDESTABORT','FALSE',0,'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('CDRDBHOST','',0,'Only used if you dont use the default values provided by FreePBX.<br>Hostname of db server if not the same as AMPDBHOST.','text',NULL,NULL,NULL,NULL),
		('CDRDBNAME','',0,'Only used if you dont use the default values provided by FreePBX.<br>Name of database used for cdr records','text',NULL,NULL,NULL,NULL),
		('CDRDBPASS','',0,'Only used if you dont use the default values provided by FreePBX.<br>Password for connecting to db if its not the same as AMPDBPASS','text',NULL,NULL,NULL,NULL),
		('CDRDBPORT','',0,'Only used if you dont use the default values provided by FreePBX.<br>Port number for db host','text',NULL,NULL,NULL,NULL),
		('CDRDBTABLENAME','',0,'Only used if you dont use the default values provided by FreePBX. Name of the table in the db where the cdr is stored. cdr is default','text',NULL,NULL,NULL,NULL),
		('CDRDBTYPE','',0,'Only used if you dont use the default values provided by FreePBX. mysql or postgres mysql is default','text',NULL,NULL,NULL,NULL),
		('CDRDBUSER','',0,'Only used if you dont use the default values provided by FreePBX. Username to connect to db with if its not the same as AMPDBUSER','text',NULL,NULL,NULL,NULL),
		('CHECKREFERER','TRUE',0,'When set to the default value of true, all requests into FreePBX that might possibly add/edit/delete settings will be validated to assure the request is coming from the server. This will protect the system from CSRF (cross site request forgery) attacks. It will have the effect of preventing legitimately entering URLs that could modify settings which can be allowed by changing this field to false<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL),
		('CUSTOMASERROR','TRUE',0,'If false, then the Destination Registry will not report unknown destinations as errors. This should be left to the default true and custom destinations should be moved into the new custom apps registry<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('CWINUSEBUSY','TRUE',0,'For extensions that have CW enabled, report unanswered CW calls as <b>busy</b> (resulting in busy voicemail greeting). If set to no, unanswered CW calls simply report as <b>no-answer</b><br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('DASHBOARD_INFO_UPDATE_TIME','',0,'These can be used to change the refresh rate of the System Status Panel. Most of the stats are updated based on the STATS interval but a few items are','text',NULL,NULL,NULL,NULL),
		('DASHBOARD_STATS_UPDATE_TIME','',0,'These can be used to change the refresh rate of the System Status Panel. Most of the stats are updated based on the STATS interval but a few items are','text',NULL,NULL,NULL,NULL),
		('DAYNIGHTTCHOOK','FALSE',0,'By default, the Day/Night module will not hook Time Conditions allowing one to associate a daynight manual override with a time condition since now time conditions have their own feature code. If there is already an associaiton configured (on an upgraded system), this will have no affect for the Time Conditions that # are effected. Setting this to true reverts the previous behavior by allowing for the continued use of a daynight toggle to be associated with a time conditon. This can be useful for two scenarios. First, to override a Time Condition without the automatic resetting that occurs with the built in Time Condition overrides. The second use is the ability to associate a single daynight toggle with multiple time conditions thus creating a <b>master switch</b> that can be used to override several possible call flows through different time conditions.<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL), 		
		('DEVEL','FALSE',0,'Needs to be documented<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('DEVELRELOAD','FALSE',0,'Needs to be documented<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('DISABLECUSTOMCONTEXTS','FALSE',0,'Normally FreePBX auto-generates a custom context that may be usable for adding custom dialplan to modify the normal behavior of FreePBX. It takes a good understanding of how Asterisk processes these includes to use this and in many of the cases, there is no useful application. All includes will result in a WARNING in the Asterisk log if there is no context found to include though it results in no errors. If you know that you want the includes, you can set this to true. If you comment it out FreePBX will revert to legacy behavior and include the contexts<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('DYNAMICHINTS','FALSE',0,'If true, Core will not statically generate hints, but instead make a call to the AMPBIN php script, and generate_hints.php through an Asterisk #exec call. This requires Asterisk.conf to be configured with <b>execincludes=yes<b> set in the [options] section<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL),
		('ENABLECW','TRUE',0,'Enable call waiting by default when an extension is created (Default is yes). Set to <b>no</b> to if you dont want phones to be commissioned with call waiting already enabled. The user would then be required to dial the CW feature code (*70 default) to enable their phone. Most installations should leave this alone. It allows multi-line phones to receive multiple calls on their line appearances<br>Default = yes','select','TRUE,FALSE','TRUE',NULL,NULL),
		('FCBEEPONLY','',0,'When set to true, a beep is played instead of confirmation message when activating/de-activating: CallForward, CallWaiting, DayNight, DoNotDisturb and FindMeFollow<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('FOPDISABLE','FALSE',0,'Set to true to disable FOP in interface and retrieve_conf.  Useful for sqlite3 or if you dont want FOP<br>Default = not set','select','TRUE,FALSE','TRUE',NULL,NULL),
		('FOPPASSWORD','passw0rd',0,'Password for performing transfers and hangups in the Flash Operator Panel (FOP)<br>Default = passw0rd','text',NULL,'passw0rd',NULL,NULL),
		('FOPRUN','TRUE',0,'Set to true if you want FOP started by freepbx_engine (amportal_start), false otherwise<br>Default = true','select','TRUE,FALSE','FALSE',NULL,NULL),
		('FOPSORT','extension',0,'How FOP sort extensions. By Last Name [lastname] or by Extension [extension]<br>Default = extension','select','extension,lastname','extension',NULL,NULL),
		('FOPWEBROOT','/var/www/html/panel',0,'Path to the Flash Operator Panel webroot (leave off trailing slash)<br>Default = /var/www/html/panel','text',NULL,'/var/www/html/panel',NULL,NULL),
		('FPBXDBUGFILE','/tmp/freepbx_debug.log',0,'Location and name of the FreePBX debug file. Used by developers<br>Default = /tmp/freepbx_debug.log','text',NULL,'/tmp/freepbx_debug.log',NULL,NULL),
		('MODULEADMINWGET','FALSE',0,'Module Admin normally tries to get its online information through direct file open type calls to URLs that go back to the freepbx.org server. If it fails, typically because of content filters in firewalls that dont like the way PHP formats the requests, the code will fall back and try a wget to pull the information. This will often solve the problem. However, in such environment there can be a significant timeout before the failed file open calls to the URLs return and there are often 2-3 of these that occur. Setting this value will force FreePBX to avoid the attempt to open the URL and go straight to the wget calls<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('MOHDIR','moh',0,'This is the subdirectory for the MoH files/directories which is located in ASTVARLIBDIR if not specified it will default to mohmp3 for backward compatibility<br>Default = not set','text',NULL,'moh',NULL,NULL),
		('POST_RELOAD','',0,'Automatically execute a script after applying changes in the AMP admin. Set POST_RELOAD to the script you wish to execute after applying changes. If POST_RELOAD_DEBUG=true, you will see the output of the script in the web page<br>Default = not set','text',NULL,NULL,NULL,NULL),
		('POST_RELOAD_DEBUG','',0,'Display debug output for script used if POST_RELOAD is used<br>Default = not set','select','TRUE,FALSE','FALSE',NULL,NULL),
		('RELOADCONFIRM','TRUE',0,'When set to false, will bypass the confirm on Reload Box<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('SERVERINTITLE','FALSE',0,'Precede browser title with the server name<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL), 
		('TCINTERVAL','60',0,'The polling interval used by the Time Conditions manintenace task, launched by an Asterisk call file used to update Time Conditions override states as well as keep custom device state hint values up-to-date when being used with BLF. This interval should be shorter than the shortest time condition true (open) or false (closed) interval to assure that an override is reset after the current time period has expired that it was set in.<br>Default value 60 seconds.','text',NULL,'60',NULL,NULL),
		('TCMAINT','TRUE',0,'If set to false, this will override the execution of the Time Conditons maintenace task launched by call files. If all the feature codes for time conditions are disabled, the maintenance task will not be launched so this is not needed for that condition. Setting this to false would be fairly un-common. On an extremely active system that has constant calls flowing throught all time conditions of interest that might be overriden one might set this to avoid the additional polling that would otherwise occur since calls traversing the time conditions contexts will also maintain all the necessary state information. You may also set this temporarily if debugging a system to avoid the periodic dialplan running through the CLI that the maintenance task otherwise creates.<br>Default = true','select','TRUE,FALSE','TRUE',NULL,NULL), 
		('USECATEGORIES','TRUE',0,'Controls if the menu items in the admin interface are sorted by category (true) or sorted alphebetically with no categories shown (false). Defaults = true','select','TRUE,FALSE','TRUE',NULL,NULL),
		('USEDEVSTATE','TRUE',0,'If this is set, it assumes that you are running Asterisk 1.4 or higher and want to take advantage of the func_devstate.c backport available from Asterisk 1.6. This allows custom hints to be created to support BLF for server side feature codes such as daynight, followme, etc<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL),
		('USEGOOGLEDNSFORENUM','',0,'Setting this flag will generate the required global variable so that enumlookup.agi will use Google DNS 8.8.8.8 when performing an ENUM lookup. Not all DNS deals with NAPTR record, but Google does. There is a drawback to this as Google tracks every lookup. If you are not comfortable with this, do not enable this setting. Please read Google FAQ about this: <b>http://code.google.com/speed/public-dns/faq.html#privacy</b><br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('USEQUEUESTATE','FALSE',0,'Setting this flag will generate the required dialplan to integrate with the following Asterisk patch: <b>https://issues.asterisk.org/view.php?id=15168</b>. This feature is planned for a future 1.6 release but given the existence of the patch can be used prior. Once the release version is known, code will be added to automatically enable this format in versions of Asterisk that support it<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('XTNCONFLICTABORT','FALSE',0,'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired<br>Default = false','select','TRUE,FALSE','FALSE',NULL,NULL),
		('ZAP2DAHDICOMPAT','FALSE',0,'If set to true, FreePBX will check if you have chan_dadhi installed. If so, it will automatically use all your ZAP configuration settings (devices and trunks) and silently convert them, under the covers, to DAHDI so no changes are needed. The GUI will continue to refer to these as ZAP but it will use the proper DAHDI channels. This will also keep Zap Channel DIDs working<br>Default = false','select','TRUE,FALSE','TRUE',NULL,NULL)";
		
	
	foreach ($sql as $q) {
		$result = $db->query($q);
		if(DB::IsError($result)){
			die_freepbx($result->getDebugInfo());
		}
	}
	
	//migrate legacy options
	foreach ($amp_conf as $key => $val) {
			switch ($val) {
				case 'true':
				case 'yes':
				case '1':
					$val = 'TRUE';
					break;
				case 'false':
				case 'no':
				case '0':
					$val = 'FALSE';
					break;
			}
			$settings[] = array('key' => $key, 'value' => $val);
	}

	$query = $db->prepare("UPDATE freepbx_settings set value = ? WHERE `keyword` = ?");
	$result = $db->executeMultiple($query,$settings);
	if(DB::IsError($result)){
		die_freepbx($result->getDebugInfo());
	}
  out(_("migrated"));
} else {
  out(_("previously migrated"));
}
//TODO: set crucial options to readonly
//build freepbx.conf if it doesnt already exists


outn(_("checking for freepbx.conf.."));

$freepbx_conf = getenv('FREEPBX_CONF');
if ($freepbx_conf && file_exists($freepbx_conf)) {
  out(sprintf(_("%s already exists"),$freepbx_conf));
} else if (file_exists('/etc/freepbx.conf')) {
  out(_("/etc/freepbx.conf already exists"));
} else if (file_exists('/etc/asterisk/freepbx.conf')) {
  out(_("/etc/asterisk/freepbx.conf already exists"));
} else {

  if ($freepbx_conf) {
    $filename = $freepbx_conf;
  } else {
	  $filename = is_writable('/etc') ? '/etc/freepbx.conf' : '/etc/asterisk/freepbx.conf';
  }
	
	$txt = '';
	$txt .= '<?php' . "\n";
	$txt .= '$amp_conf[\'AMPDBUSER\']	= "' . $amp_conf['AMPDBUSER'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBPASS\']	= "' . $amp_conf['AMPDBPASS'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBHOST\']	= "' . $amp_conf['AMPDBHOST'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBNAME\']	= "' . $amp_conf['AMPDBNAME'] . '";' . "\n";
	$txt .= '$amp_conf[\'AMPDBENGINE\']	= "' . $amp_conf['AMPDBENGINE'] . '";' . "\n";
	$txt .= '$amp_conf[\'datasource\']	= "' . $amp_conf['datasource'] . '";' . "\n";
	$txt .= 'require_once(\'' . $amp_conf['AMPWEBROOT'] . '/admin/bootstrap.php\');' . "\n";

  // don't use file_put_contents re php4 compatibility for framework/core
  $fh = fopen($filename,'w');
  if ($fh === false || (fwrite($fh,$txt) === false)) {
    out(sprintf(_("FATAL error writing  %s"),$filename));
    die_freepbx(_("You must have a proper freepbx.conf file to proceed"));
  }
  fclose($fh);
  out(sprintf(_("created %s"),$filename));
}
?>
