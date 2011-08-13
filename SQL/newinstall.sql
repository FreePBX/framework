-- MySQL dump 10.13  Distrib 5.1.52, for unknown-linux-gnu (x86_64)
--
-- Host: localhost    Database: hipbx
-- ------------------------------------------------------
-- Server version	5.1.52

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `variable` varchar(20) NOT NULL DEFAULT '',
  `value` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`variable`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES ('need_reload','true'),('version','2.9.0');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ampusers`
--

DROP TABLE IF EXISTS `ampusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ampusers` (
  `username` varchar(20) NOT NULL DEFAULT '',
  `password_sha1` varchar(40) NOT NULL,
  `extension_low` varchar(20) NOT NULL DEFAULT '',
  `extension_high` varchar(20) NOT NULL DEFAULT '',
  `deptname` varchar(20) NOT NULL DEFAULT '',
  `sections` blob NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ampusers`
--

LOCK TABLES `ampusers` WRITE;
/*!40000 ALTER TABLE `ampusers` DISABLE KEYS */;
INSERT INTO `ampusers` VALUES ('admin','d033e22ae348aeb5660fc2140aec35850c4da997','','','','*');
/*!40000 ALTER TABLE `ampusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cronmanager`
--

DROP TABLE IF EXISTS `cronmanager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cronmanager` (
  `module` varchar(24) NOT NULL DEFAULT '',
  `id` varchar(24) NOT NULL DEFAULT '',
  `time` varchar(5) DEFAULT NULL,
  `freq` int(11) NOT NULL DEFAULT '0',
  `lasttime` int(11) NOT NULL DEFAULT '0',
  `command` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`module`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cronmanager`
--

LOCK TABLES `cronmanager` WRITE;
/*!40000 ALTER TABLE `cronmanager` DISABLE KEYS */;
INSERT INTO `cronmanager` VALUES ('module_admin','UPDATES','22',24,0,'/var/lib/asterisk/bin/module_admin listonline');
/*!40000 ALTER TABLE `cronmanager` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dahdi`
--

DROP TABLE IF EXISTS `dahdi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dahdi` (
  `id` varchar(20) NOT NULL DEFAULT '-1',
  `keyword` varchar(30) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL DEFAULT '',
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dahdi`
--

LOCK TABLES `dahdi` WRITE;
/*!40000 ALTER TABLE `dahdi` DISABLE KEYS */;
/*!40000 ALTER TABLE `dahdi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `id` varchar(20) NOT NULL DEFAULT '',
  `tech` varchar(10) NOT NULL DEFAULT '',
  `dial` varchar(50) NOT NULL DEFAULT '',
  `devicetype` varchar(5) NOT NULL DEFAULT '',
  `user` varchar(50) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `emergency_cid` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `extensions`
--

DROP TABLE IF EXISTS `extensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extensions` (
  `context` varchar(45) NOT NULL DEFAULT 'default',
  `extension` varchar(45) NOT NULL DEFAULT '',
  `priority` varchar(5) NOT NULL DEFAULT '1',
  `application` varchar(45) NOT NULL DEFAULT '',
  `args` varchar(255) DEFAULT NULL,
  `descr` text,
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`context`,`extension`,`priority`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `extensions`
--

LOCK TABLES `extensions` WRITE;
/*!40000 ALTER TABLE `extensions` DISABLE KEYS */;
INSERT INTO `extensions` VALUES ('outrt-001-9_outside','_9.','1','Macro','dialout-trunk,1,${EXTEN:1}',NULL,0),('outrt-001-9_outside','_9.','2','Macro','outisbusy','No available circuits',0),('outbound-allroutes','include','1','outrt-001-9_outside','','',2);
/*!40000 ALTER TABLE `extensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `featurecodes`
--

DROP TABLE IF EXISTS `featurecodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `featurecodes` (
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `featurename` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(200) NOT NULL DEFAULT '',
  `defaultcode` varchar(20) DEFAULT NULL,
  `customcode` varchar(20) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `providedest` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`modulename`,`featurename`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `featurecodes`
--

LOCK TABLES `featurecodes` WRITE;
/*!40000 ALTER TABLE `featurecodes` DISABLE KEYS */;
INSERT INTO `featurecodes` VALUES ('core','userlogon','User Logon','*11',NULL,1,0),('core','userlogoff','User Logoff','*12',NULL,1,0),('core','zapbarge','ZapBarge','888',NULL,1,1),('core','chanspy','ChanSpy','555',NULL,1,1),('core','simu_pstn','Simulate Incoming Call','7777',NULL,1,1),('core','pickup','Directed Call Pickup','**',NULL,1,0),('core','pickupexten','Asterisk General Call Pickup','*8',NULL,1,0),('core','blindxfer','In-Call Asterisk Blind Transfer','##',NULL,1,0),('core','atxfer','In-Call Asterisk Attended Transfer','*2',NULL,1,0),('core','automon','In-Call Asterisk Toggle Call Recording','*1',NULL,1,0),('core','disconnect','In-Call Asterisk Disconnect Code','**',NULL,1,0);
/*!40000 ALTER TABLE `featurecodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `freepbx_log`
--

DROP TABLE IF EXISTS `freepbx_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `freepbx_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `section` varchar(50) DEFAULT NULL,
  `level` enum('error','warning','debug','devel-debug') NOT NULL DEFAULT 'error',
  `status` int(11) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`time`,`level`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `freepbx_settings`
--

DROP TABLE IF EXISTS `freepbx_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `freepbx_settings` (
  `keyword` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(255) DEFAULT NULL,
  `name` varchar(80) DEFAULT NULL,
  `level` tinyint(1) DEFAULT '0',
  `description` text,
  `type` varchar(25) DEFAULT NULL,
  `options` text,
  `defaultval` varchar(255) DEFAULT NULL,
  `readonly` tinyint(1) DEFAULT '0',
  `hidden` tinyint(1) DEFAULT '0',
  `category` varchar(50) DEFAULT NULL,
  `module` varchar(25) DEFAULT NULL,
  `emptyok` tinyint(1) DEFAULT '1',
  `sortorder` int(11) DEFAULT '0',
  PRIMARY KEY (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freepbx_settings`
--

LOCK TABLES `freepbx_settings` WRITE;
/*!40000 ALTER TABLE `freepbx_settings` DISABLE KEYS */;
INSERT INTO `freepbx_settings` VALUES 
('AS_DISPLAY_HIDDEN_SETTINGS','0','Display Hidden Settings',0,'This will display settings that are normally hidden by the system. These settings are often internally used settings that are not of interest to most users.','bool','','0',0,0,'Advanced Settings Details','',0,0),
('AS_DISPLAY_READONLY_SETTINGS','0','Display Readonly Settings',0,'This will display settings that are readonly. These settings are often internally used settings that are not of interest to most users. Since they are readonly they can only be viewed.','bool','','0',0,0,'Advanced Settings Details','',0,0),
('AS_OVERRIDE_READONLY','0','Override Readonly Settings',0,'Setting this to true will allow you to override un-hidden readonly setting to change them. Settings that are readonly may be extremely volatile and have a high chance of breaking your system if you change them. Take extreme caution when electing to make such changes.','bool','','0',0,0,'Advanced Settings Details','',0,0),
('AS_DISPLAY_FRIENDLY_NAME','1','Display Friendly Name',0,'Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false..','bool','','1',0,0,'Advanced Settings Details','',0,0),
('AMPSYSLOGLEVEL','FILE','FreePBX Log Routing',0,'Determine where to send log information if the log is enabled (\'Disable FreePBX Log\' (AMPDISABLELOG) false. There are two places to route the log messages. \'FILE\' will send all log messages to the defined \'FreePBX Log File\' (FPBX_LOG_FILE). All the other settings will route the log messages to your System Logging subsystem (syslog) using the specified log level. Syslog can be configured to route different levels to different locations. See \'syslog\' documentation (man syslog) on your system for more details.','select','FILE,LOG_EMERG,LOG_ALERT,LOG_CRIT,LOG_ERR,LOG_WARNING,LOG_NOTICE,LOG_INFO,LOG_DEBUG','FILE',0,0,'System Setup','',1,-190),
('AMPDISABLELOG','0','Disable FreePBX Log',0,'Whether or not to invoke the FreePBX log facility.','bool','','0',0,0,'System Setup','',0,-180),
('LOG_OUT_MESSAGES','1','Log Verbose Messages',0,'FreePBX has many verbose and useful messages displayed to users during module installation, system installations, loading configurations and other places. In order to accumulate these messages in the log files as well as the on screen display, set this to true.','bool','','1',0,0,'System Setup','',0,-170),
('LOG_NOTIFICATIONS','1','Send Dashboard Notifications to Log',0,'When enabled all notification updates to the Dashboard notification panel will also be logged into the specified log file when enabled.','bool','','1',0,0,'System Setup','',0,-160),
('FPBX_LOG_FILE','/var/log/asterisk/freepbx.log','FreePBX Log File',0,'Full path and name of the FreePBX Log File used in conjunction with the Syslog Level (AMPSYSLOGLEVEL) being set to FILE, not used otherwise. Initial installs may have some early logging sent to /tmp/freepbx_pre_install.log when it is first bootstrapping the installer.','text','','/var/log/asterisk/freepbx.log',0,0,'System Setup','',0,-150),
('PHP_ERROR_HANDLER_OUTPUT','dbug','PHP Error Log Output',0,'Where to send PHP errors, warnings and notices by the FreePBX PHP error handler. Set to \'dbug\', they will go to the Debug File regardless of whether dbug Loggin is disabled or not. Set to \'freepbxlog\' will send them to the FreePBX Log. Set to \'off\' and they will be ignored.','select','dbug,freepbxlog,off','dbug',0,0,'System Setup','',0,-140),
('AMPEXTENSIONS','extensions','User & Devices Mode',0,'Sets the extension behavior in FreePBX.  If set to <b>extensions</b>, Devices and Users are administered together as a unified Extension, and appear on a single page. If set to <b>deviceanduser</b>, Devices and Users will be administered separately. Devices (e.g. each individual line on a SIP phone) and Users (e.g. <b>101</b>) will be configured independent of each other, allowing association of one User to many Devices, or allowing Users to login and logout of Devices.','select','extensions,deviceanduser','extensions',0,0,'System Setup','',0,-135),
('AUTHTYPE','database','Authorization Type',3,'Authentication type to use for web admin. If type set to <b>database</b>, the primary AMP admin credentials will be the AMPDBUSER/AMPDBPASS above. When using database you can create users that are restricted to only certain module pages. When set to none, you should make sure you have provided security at the apache level. When set to webserver, FreePBX will expect authentication to happen at the apache level, but will take the user credentials and apply any restrictions as if it were in database mode.','select','database,none,webserver','database',1,0,'System Setup','',0,-130),
('AMP_ACCESS_DB_CREDS','0','Allow Login With DB Credentials',0,'When Set to True, admin access to the FreePBX GUI will be allowed using the FreePBX configured AMPDBUSER and AMPDBPASS credentials. This only applies when Authorization Type is \'database\' mode.','bool','','0',0,0,'System Setup','',0,-126),
('ARI_ADMIN_USERNAME','','User Portal Admin Username',0,'This is the default admin name used to allow an administrator to login to ARI bypassing all security. Change this to whatever you want, do not forget to change the User Portal Admin Password as well. Default = not set','text','','',0,0,'System Setup','',1,-120),
('ARI_ADMIN_PASSWORD','ari_password','User Portal Admin Password',0,'This is the default admin password to allow an administrator to login to ARI bypassing all security. Change this to a secure password. Default = not set','text','','ari_password',0,0,'System Setup','',0,-110),
('FORCED_ASTVERSION','','Force Asterisk Version',4,'Normally FreePBX gets the current Asterisk version directly from Asterisk. This is required to generate proper dialplan for a given version. When using some custom Asterisk builds, the version may not be properly parsed and improper dialplan generated. Setting this to an equivalent Asterisk version will override what is read from Asterisk. This SHOULD be left blank unless you know what you are doing.','text','','',1,0,'System Setup','',1,-100),
('AMPENGINE','asterisk','Telephony Engine',3,'The telephony backend engine being used, asterisk is the only option currently.','select','asterisk','asterisk',1,0,'System Setup','',0,-100),('AMPVMUMASK','007','Asterisk VMU Mask',4,'Defaults to 077 allowing only the asterisk user to have any permission on VM files. If set to something like 007, it would allow the group to have permissions. This can be used if setting apache to a different user then asterisk, so that the apache user (and thus ARI) can have access to read/write/delete the voicemail files. If changed, some of the voicemail directory structures may have to be manually changed.','text','','007',0,0,'System Setup','',0,-100),
('AMPWEBADDRESS','192.168.1.1','FreePBX Web Address',4,'This is the address of your Web Server. It is mostly obsolete and derived when not supplied and will be phased out, but there are still some areas expecting a variable to be set and if you are using it this will migrate your value.','text','','',0,0,'System Setup','',1,-100),
('AMPASTERISKUSER','apache','System Asterisk User',4,'The user Asterisk should be running as, used by freepbx_engine. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPASTERISKGROUP','apache','System Asterisk Group',4,'The user group Asterisk should be running as, used by freepbx_engine. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPASTERISKWEBUSER','apache','System Web User',4,'The user your httpd should be running as, used by freepbx_engine. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPASTERISKWEBGROUP','apache','System Web Group',4,'The user group your httpd should be running as, used by freepbx_engine. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPDEVUSER','apache','System Device User',4,'The user that various device directories should be set to, used by freepbx_engine. Examples include /dev/zap, /dev/dahdi, /dev/misdn, /dev/mISDN and /dev/dsp. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPDEVGROUP','apache','System Device Group',4,'The user group that various device directories should be set to, used by freepbx_engine. Examples include /dev/zap, /dev/dahdi, /dev/misdn, /dev/mISDN and /dev/dsp. Most systems should not change this.','text','','asterisk',1,0,'System Setup','',0,-100),
('AMPBADNUMBER','1','Use bad-number Context',2,'Generate the bad-number context which traps any bogus number or feature code and plays a message to the effect. If you use the Early Dial feature on some Grandstream phones, you will want to set this to false.','bool','','1',0,0,'Dialplan and Operational','',0,-100),
('CWINUSEBUSY','1','Occupied Lines CW Busy',0,'For extensions that have CW enabled, report unanswered CW calls as <b>busy</b> (resulting in busy voicemail greeting). If set to no, unanswered CW calls simply report as <b>no-answer</b>.','bool','','1',0,0,'Dialplan and Operational','',0,-100),
('ZAP2DAHDICOMPAT','0','Convert ZAP Settings to DAHDi',0,'If set to true, FreePBX will check if you have chan_dahdi installed. If so, it will automatically use all your ZAP configuration settings (devices and trunks) and silently convert them, under the covers, to DAHDi so no changes are needed. The GUI will continue to refer to these as ZAP but it will use the proper DAHDi channels. This will also keep Zap Channel DIDs working.','bool','','0',1,0,'Dialplan and Operational','',0,-100),
('DYNAMICHINTS','0','Dynamically Generate Hints',0,'If true, Core will not statically generate hints, but instead make a call to the AMPBIN php script, and generate_hints.php through an Asterisk #exec call. This requires asterisk.conf to be configured with <b>execincludes=yes<b> set in the [options] section.','bool','','0',1,0,'Dialplan and Operational','',0,-100),
('ENABLECW','1','CW Enabled by Default',0,'Enable call waiting by default when an extension is created (Default is yes). Set to <b>no</b> to if you do not want phones to be commissioned with call waiting already enabled. The user would then be required to dial the CW feature code (*70 default) to enable their phone. Most installations should leave this alone. It allows multi-line phones to receive multiple calls on their line appearances.','bool','','1',0,0,'Dialplan and Operational','',0,-100),
('FCBEEPONLY','0','Feature Codes Beep Only',0,'When set to true, a beep is played instead of confirmation message when activating/de-activating: CallForward, CallWaiting, DayNight, DoNotDisturb and FindMeFollow.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('USEDEVSTATE','0','Enable Custom Device States',0,'If this is set, it assumes that you are running Asterisk 1.4 or higher and want to take advantage of the func_devstate.c backport available from Asterisk 1.6. This allows custom hints to be created to support BLF for server side feature codes such as daynight, followme, etc','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('USEGOOGLEDNSFORENUM','0','Use Google DNS for Enum',2,'Setting this flag will generate the required global variable so that enumlookup.agi will use Google DNS 8.8.8.8 when performing an ENUM lookup. Not all DNS deals with NAPTR record, but Google does. There is a drawback to this as Google tracks every lookup. If you are not comfortable with this, do not enable this setting. Please read Google FAQ about this: <b>http://code.google.com/speed/public-dns/faq.html#privacy</b>.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('DISABLECUSTOMCONTEXTS','0','Disable -custom Context Includes',2,'Normally FreePBX auto-generates a custom context that may be usable for adding custom dialplan to modify the normal behavior of FreePBX. It takes a good understanding of how Asterisk processes these includes to use this and in many of the cases, there is no useful application. All includes will result in a WARNING in the Asterisk log if there is no context found to include though it results in no errors. If you know that you want the includes, you can set this to true. If you comment it out FreePBX will revert to legacy behavior and include the contexts.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('NOOPTRACE','0','NoOp Traces in Dialplan',0,'Some modules will generate lots of NoOp() commands proceeded by a [TRACE](trace_level) that can be used during development or while trying to trace call flows. These NoOp() commands serve no other purpose so if you do not want to see excessive NoOp()s in your dialplan you can set this to 0. The higher the number the more detailed level of trace NoOp()s will be generated','select','0,1,2,3,4,5,6,7,8,9,10','0',0,0,'Dialplan and Operational','',0,-100),
('DIVERSIONHEADER','0','Generate Diversion Headers',0,'If this value is set to true, then calls going out your outbound routes that originate from outside your PBX and were subsequently forwarded through a call forward, ring group, follow-me or other means, will have a SIP diversion header added to the call with the original incoming DID assuming there is a DID available. This is useful with some carriers that may require this under certain circumstances.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('CFRINGTIMERDEFAULT','0','Call Forward Ringtimer Default',0,'This is the default time in seconds to try and connect a call that has been call forwarded by the server side CF, CFU and CFB options. (If your phones use client side CF such as SIP redirects, this will not have any affect) If set to the default of 0, it will use the standard ring timer. If set to -1 it will ring the forwarded number with no limit which is consistent with the behavior of some existing PBX systems. If set to any other value, it will ring for that duration before diverting the call to the users voicemail if they have one. This can be overridden for each extension.','select','-1,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120','0',0,0,'Dialplan and Operational','',0,-100),
('DEFAULT_INTERNAL_AUTO_ANSWER','disabled','Internal Auto Answer Default',0,'Default setting for new extensions. When set to Intercom, calls to new extensions/users from other internal users act as if they were intercom calls meaning they will be auto-answered if the endpoint supports this feature and the system is configured to operate in this mode. All the normal white list and black list settings will be honored if they are set. External calls will still ring as normal, as will certain other circumstances such as blind transfers and when a Follow Me is configured and enabled. If Disabled, the phone rings as a normal phone.','select','disabled,intercom','disabled',0,0,'Dialplan and Operational','',0,-100),
('FORCE_INTERNAL_AUTO_ANSWER_ALL','0','Force All Internal Auto Answer',0,'Force all extensions to operate in the Internal Auto Answer mode regardless of their individual settings. See \'Internal Auto Answer Default\' for more information.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('CONCURRENCYLIMITDEFAULT','0','Extension Concurrency Limit',0,'Default maximum number of outbound simultaneous calls that an extension can make. This is also very useful as a Security Protection against a system that has been compromised. It will limit the number of simultaneous calls that can be made on the compromised extension. This default is used when an extension is created. A default of 0 means no limit.','select','0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120','0',0,0,'Dialplan and Operational','',0,-100),
('BLOCK_OUTBOUND_TRUNK_CNAM','0','Block CNAM on External Trunks',0,'Some carriers will reject a call if a CallerID Name (CNAM) is presented. This occurs in several areas when configuring CID on the PBX using the format of \"CNAM\" <CNUM>. To remove the CNAM part of CID on all external trunks, set this value to true. This WILL NOT remove CNAM when a trunk is called from an Intra-Company route. This can be done on each individual trunk in addition to globally if there are trunks where it is desirable to keep CNAM information, though most carriers ignore CNAM.','bool','','0',0,0,'Dialplan and Operational','',0,-100),
('ASTSTOPTIMEOUT','120','Waiting Period to Stop Asterisk',0,'When Asterisk is stopped or restarted with the \'amportal stop/restart\' commands, it does a graceful stop waiting for active channels to hangup. This sets the maximum time in seconds to wait prior to force stopping Asterisk','select','0,5,10,30,60,120,300,600,1800,3600,7200,10800','120',0,0,'Dialplan and Operational','',0,-100),
('ASTSTOPPOLLINT','2','Polling Interval for Stopping Asterisk',0,'When Asterisk is stopped or restarted with the \'amportal stop/restart\' commands, it does a graceful stop waiting for active channels to hangup. This sets the polling interval to check if Asterisk is shutdown and update the countdown timer.','select','1,2,3,5,10','2',0,0,'Dialplan and Operational','',0,-100),
('CID_PREPEND_REPLACE','1','Only Use Last CID Prepend',0,'Some modules allow the CNAM to be prepended. If a previous prepend was done, the default behavior is to remove the previous prepend and only use the most recent one. Setting this to false will turn that off allowing all prepends to be \'starcked\' in front of one another.','bool','','1',0,0,'Dialplan and Operational','',0,-100),
('DITECH_VQA_INBOUND','7','Ditech VQA Inbound Setting',0,'If Ditech\'s VQA, Voice Quality application is installed, this setting will be used for all inbound calls. For more information \'core show application VQA\' at the Asterisk CLI will show the different settings.','select','0,1,2,3,4,5,6,7','7',0,0,'Dialplan and Operational','',0,-100),
('DITECH_VQA_OUTBOUND','7','Ditech VQA Outbound Setting',0,'If Ditech\'s VQA, Voice Quality application is installed, this setting will be used for all outbound calls. For more information \'core show application VQA\' at the Asterisk CLI will show the different settings.','select','0,1,2,3,4,5,6,7','7',0,0,'Dialplan and Operational','',0,-100),
('AMPBIN','/var/lib/asterisk/bin','FreePBX bin Dir',4,'Location of the FreePBX command line scripts.','dir','','/var/lib/asterisk/bin',1,0,'Directory Layout','',0,-100),
('AMPSBIN','/usr/local/sbin','FreePBX sbin Dir',4,'Where (root) command line scripts are located.','dir','','/usr/sbin',1,0,'Directory Layout','',0,-100),
('AMPWEBROOT','/var/www/html','FreePBX Web Root Dir',4,'The path to Apache webroot (leave off trailing slash).','dir','','/var/www/html',1,0,'Directory Layout','',0,-100),
('ASTAGIDIR','/usr/share/asterisk/agi-bin','Asterisk AGI Dir',4,'This is the default directory for Asterisks agi files.','dir','','/var/lib/asterisk/agi-bin',1,0,'Directory Layout','',0,-100),
('ASTETCDIR','/etc/asterisk','Asterisk etc Dir',4,'This is the default directory for Asterisks configuration files.','dir','','/etc/asterisk',1,0,'Directory Layout','',0,-100),
('ASTLOGDIR','/var/log/asterisk','Asterisk Log Dir',4,'This is the default directory for Asterisks log files.','dir','','/var/log/asterisk',1,0,'Directory Layout','',0,-100),
('ASTMODDIR','/usr/lib64/asterisk/modules','Asterisk Modules Dir',4,'This is the default directory for Asterisks modules.','dir','','/usr/lib/asterisk/modules',1,0,'Directory Layout','',0,-100),
('ASTSPOOLDIR','/var/spool/asterisk','Asterisk Spool Dir',4,'This is the default directory for Asterisks spool directory.','dir','','/var/spool/asterisk',1,0,'Directory Layout','',0,-100),
('ASTRUNDIR','/var/run/asterisk','Asterisk Run Dir',4,'This is the default directory for Asterisks run files.','dir','','/var/run/asterisk',1,0,'Directory Layout','',0,-100),
('ASTVARLIBDIR','/usr/share/asterisk','Asterisk bin Dir',4,'This is the default directory for Asterisks lib files.','dir','','/var/lib/asterisk',1,0,'Directory Layout','',0,-100),
('AMPCGIBIN','/var/www/cgi-bin ','CGI Dir',4,'The path to Apache cgi-bin dir (leave off trailing slash).','dir','','/var/www/cgi-bin ',1,0,'Directory Layout','',0,-100),
('MOHDIR','mohmp3','MoH Subdirectory',4,'This is the subdirectory for the MoH files/directories which is located in ASTVARLIBDIR. Older installation may be using mohmp3 which was the old Asterisk default and should be set to that value if the music files are located there relative to the ASTVARLIBDIR.','select','moh,mohmp3','moh',1,0,'Directory Layout','',0,-100),
('CHECKREFERER','1','Check Server Referrer',0,'When set to the default value of true, all requests into FreePBX that might possibly add/edit/delete settings will be validated to assure the request is coming from the server. This will protect the system from CSRF (cross site request forgery) attacks. It will have the effect of preventing legitimately entering URLs that could modify settings which can be allowed by changing this field to false.','bool','','1',0,0,'GUI Behavior','',0,-100),
('MODULEADMINWGET','0','Use wget For Module Admin',0,'Module Admin normally tries to get its online information through direct file open type calls to URLs that go back to the freepbx.org server. If it fails, typically because of content filters in firewalls that do not like the way PHP formats the requests, the code will fall back and try a wget to pull the information. This will often solve the problem. However, in such environment there can be a significant timeout before the failed file open calls to the URLs return and there are often 2-3 of these that occur. Setting this value will force FreePBX to avoid the attempt to open the URL and go straight to the wget calls.','bool','','0',0,0,'GUI Behavior','',0,-100),
('USECATEGORIES','1','Show Categories in Nav Menu',0,'Controls if the menu items in the admin interface are sorted by category (true) or sorted alphabetically with no categories shown (false). Defaults = true','bool','','1',0,0,'GUI Behavior','',0,-100),
('SERVERINTITLE','0','Include Server Name in Browser',0,'Precede browser title with the server name.','bool','','0',0,0,'GUI Behavior','',0,-100),
('RELOADCONFIRM','1','Require Confirm with Apply Changes',0,'When set to false, will bypass the confirm on Reload Box.','bool','','1',0,0,'GUI Behavior','',0,-100),
('BADDESTABORT','0','Abort Config Gen on Bad Dest',3,'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired.','bool','','0',0,0,'GUI Behavior','',0,-100),
('XTNCONFLICTABORT','0','Abort Config Gen on Exten Conflict',3,'Setting either of these to true will result in retrieve_conf aborting during a reload if an extension conflict is detected or a destination is detected. It is usually better to allow the reload to go through and then correct the problem but these can be set if a more strict behavior is desired.','bool','','0',0,0,'GUI Behavior','',0,-100),
('CUSTOMASERROR','1','Report Unknown Dest as Error',2,'If false, then the Destination Registry will not report unknown destinations as errors. This should be left to the default true and custom destinations should be moved into the new custom apps registry.','bool','','1',0,0,'GUI Behavior','',0,-100),
('ALWAYS_SHOW_DEVICE_DETAILS','0','Show all Device Setting on Add',0,'When adding a new extension/device, setting this to true will show most available device settings that are displayed when you edit the same extension/device. Otherwise, just a few basic settings are displayed.','bool','','0',0,0,'Device Settings','',0,10),
('AMPMGRPASS','unset','Asterisk Manager Password',2,'Password for accessing the Asterisk Manager Interface (AMI), you must change this in manager.conf if changed here.','text','','amp111',1,0,'Asterisk Manager','',0,-100),
('AMPMGRUSER','admin','Asterisk Manager User',2,'Username for accessing the Asterisk Manager Interface (AMI), you must change this in manager.conf if changed here.','text','','admin',1,0,'Asterisk Manager','',0,-100),
('ASTMANAGERHOST','localhost','Asterisk Manager Host',2,'Hostname for the Asterisk Manager','text','','localhost',1,0,'Asterisk Manager','',0,-100),
('ASTMANAGERPORT','5038','Asterisk Manager Port',2,'Port for the Asterisk Manager','int','1024,65535','5038',1,0,'Asterisk Manager','',0,-100),
('ASTMANAGERPROXYPORT','','Asterisk Manager Proxy Port',2,'Optional port for an Asterisk Manager Proxy','int','1024,65535','',1,0,'Asterisk Manager','',1,-100),
('FPBXDBUGFILE','/tmp/freepbx_debug.log','Debug File',2,'Full path and name of FreePBX debug file. Used by the dbug() function by developers.','text','','/tmp/freepbx_debug.log',0,0,'Developer and Customization','',0,-100),
('FPBXDBUGDISABLE','1','Disable FreePBX dbug Logging',2,'Set to true to stop all dbug() calls from writing to the Debug File (FPBXDBUGFILE)','bool','','1',0,0,'Developer and Customization','',0,-100),
('DIE_FREEPBX_VERBOSE','0','Provide Verbose Tracebacks',2,'Provides a very verbose traceback when die_freepbx() is called including extensive object details if present in the traceback.','bool','','0',0,0,'Developer and Customization','',0,-100),
('DEVEL','0','Developer Mode',2,'This enables several debug features geared towards developers, including some page load timing information, some debug information in Module Admin, use of original CSS files and other future capabilities will be enabled.','bool','','0',0,0,'Developer and Customization','',0,-100),
('USE_PACKAGED_JS','1','Use Packaged Javascript Library ',2,'FreePBX packages several javascript libraries and components into a compressed file called libfreepbx.javascript.js. By default this will be loaded instead of the individual uncompressed libraries. Setting this to false will force FreePBX to load all the libraries as individual uncompressed files. This is useful during development and debugging.','bool','','1',0,0,'Developer and Customization','',0,-100),
('FORCE_JS_CSS_IMG_DOWNLOAD','0','Always Download Web Assets',2,'FreePBX appends versioning tags on the CSS and javascript files and some of the main logo images. The versioning will help force browsers to load new versions of the files when module versions are upgraded. Setting this value to true will try to force these to be loaded to the browser every page load by appending an additional timestamp in the version information. This is useful during development and debugging where changes are being made to javascript and CSS files.','bool','','0',0,0,'Developer and Customization','',0,-100),
('DEVELRELOAD','0','Leave Reload Bar Up',2,'Forces the \"Apply Configuration Changes\" reload bar to always be present even when not necessary.','bool','','0',0,0,'Developer and Customization','',0,-100),
('PRE_RELOAD','','PRE_RELOAD Script',2,'Optional script to run just prior to doing an extension reload to Asterisk through the manager after pressing Apply Configuration Changes in the GUI.','text','','',1,0,'Developer and Customization','',1,-100),
('POST_RELOAD','','POST_RELOAD Script',2,'Automatically execute a script after applying changes in the AMP admin. Set POST_RELOAD to the script you wish to execute after applying changes. If POST_RELOAD_DEBUG=true, you will see the output of the script in the web page.','text','','',1,0,'Developer and Customization','',1,-100),
('POST_RELOAD_DEBUG','0','POST_RELOAD Debug Mode',2,'Display debug output for script used if POST_RELOAD is used.','bool','','0',0,0,'Developer and Customization','',0,-100),
('AMPLOCALBIN','','AMPLOCALBIN Dir for retrieve_conf',2,'If this directory is defined, retrieve_conf will check for a file called <i>retrieve_conf_post_custom</i> and if that file exists, it will be included after other processing thus having full access to the current environment for additional customization.','dir','','',1,0,'Developer and Customization','',1,-100),
('DISABLE_CSS_AUTOGEN','0','Disable Mainstyle CSS Compression',2,'Stops the automatic generation of a stripped CSS file that replaces the primary sheet, usually mainstyle.css.','bool','','0',0,0,'Developer and Customization','',0,-100),
('MODULEADMIN_SKIP_CACHE','0','Disable Module Admin Caching',2,'Module Admin caches a copy of the online XML document that describes what is available on the server. Subsequent online update checks will use the cached information if it is less than 5 minutes old. To bypass the cache and force it to go to the server each time, set this to True. This should normally be false but can be helpful during testing.','bool','','0',1,0,'Developer and Customization','',0,-100),
('FOPSORT','extension','FOP Sort Mode',0,'How FOP sort extensions. By Last Name [lastname] or by Extension [extension].','select','extension,lastname','extension',0,0,'Flash Operator Panel','',0,-100),
('FOPWEBROOT','/var/www/html/panel','FOP Web Root Dir',4,'Path to the Flash Operator Panel webroot (leave off trailing slash).','dir','','/var/www/html/panel',1,0,'Flash Operator Panel','',0,-100),
('FOPPASSWORD','passw0rd','FOP Password',0,'Password for performing transfers and hangups in the Flash Operator Panel (FOP).','text','','passw0rd',0,0,'Flash Operator Panel','',0,-100),
('FOPDISABLE','0','Disable FOP',0,'Set to true to disable FOP in interface and retrieve_conf.  Useful for sqlite3 or if you do not want FOP.','bool','','0',0,0,'Flash Operator Panel','',0,-100),
('FOPRUN','1','Start FOP with amportal',0,'Set to true if you want FOP started by freepbx_engine (amportal_start), false otherwise.','bool','','1',0,0,'Flash Operator Panel','',0,-100),
('CDRDBHOST','','Remote CDR DB Host',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX.<br>Hostname of db server if not the same as AMPDBHOST.','text','','',1,0,'Remote CDR Database','',1,-100),
('CDRDBNAME','','Remote CDR DB Name',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX.<br>Name of database used for cdr records.','text','','',1,0,'Remote CDR Database','',1,-100),
('CDRDBPASS','','Remote CDR DB Password',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX.<br>Password for connecting to db if its not the same as AMPDBPASS.','text','','',1,0,'Remote CDR Database','',1,-100),
('CDRDBPORT','','Remote CDR DB Port',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX.<br>Port number for db host.','int','1024,65536','',1,0,'Remote CDR Database','',1,-100),
('CDRDBTABLENAME','','Remote CDR DB Table',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX. Name of the table in the db where the cdr is stored. cdr is default.','text','','',1,0,'Remote CDR Database','',1,-100),
('CDRDBTYPE','','Remote CDR DB Type',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX. Defaults to your configured AMDBENGINE.','select',',mysql,postgres','',1,0,'Remote CDR Database','',1,-100),
('CDRDBUSER','','Remote CDR DB User',3,'DO NOT set this unless you know what you are doing. Only used if you do not use the default values provided by FreePBX. Username to connect to db with if it is not the same as AMPDBUSER.','text','','',1,0,'Remote CDR Database','',1,-100),
('AMPADMINLOGO','','Legacy Right Logo',1,'Legacy setting, use BRAND_IMAGE_FREEPBX_RIGHT in the future. If set, this will override BRAND_IMAGE_FREEPBX_RIGHT. The setting is the name of the image file and is always assumed to be present in the admin/images directory. Overrides the standard logo that is to be displayed at the TOP RIGHT of the admin screen. This enables you to customize the look of the administration screen. NOTE: images need to be saved in the ..../admin/images directory of your AMP install. This image should be 55px in height.','text','','',1,0,'Styling and Logos','',1,0),
('BRAND_IMAGE_HIDE_NAV_BACKGROUND','0','Hide Nav Background',1,'Hide the configured left navigation bar background.','bool','','0',0,0,'Styling and Logos','',0,10),
('BRAND_IMAGE_SHADOW_SIDE_BACKGROUND','images/shadow-side-background.png','Image: shadow-side-background.png',1,'Styling image.','text','','images/shadow-side-background.png',1,0,'Styling and Logos','',1,20),
('BRAND_IMAGE_FREEPBX_RIGHT','images/logo.png','Image: Right Upper',1,'Right upper logo. Use this setting instead of AMPADMINLOGO. Path is relative to admin.','text','','images/logo.png',1,0,'Styling and Logos','',0,30),
('BRAND_IMAGE_FREEPBX_LEFT','images/freepbx_large.png','Image: Left Upper',1,'Left upper logo.  Path is relative to admin.','text','','images/freepbx_large.png',1,0,'Styling and Logos','',0,40),
('BRAND_IMAGE_FREEPBX_FOOT','images/freepbx_small.png','Image: Footer',1,'Logo in footer.  Path is relative to admin.','text','','images/freepbx_small.png',1,0,'Styling and Logos','',1,50),
('BRAND_IMAGE_RELOAD_LOADING','images/loading.gif','Image: Reload Screen',1,'Image used during a reload, default is animated GIF eating the * (asterisk).  Path is relative to admin.','text','','images/loading.gif',1,0,'Styling and Logos','',1,60),
('BRAND_FREEPBX_ALT_LEFT','','Alt for Left Logo',1,'alt attribute to use in place of image and title hover value. Defaults to FreePBX','text','','',1,0,'Styling and Logos','',1,70),
('BRAND_FREEPBX_ALT_RIGHT','','Alt for Right Logo',1,'alt attribute to use in place of image and title hover value. Defaults to FreePBX','text','','',1,0,'Styling and Logos','',1,80),
('BRAND_FREEPBX_ALT_FOOT','','Alt for Footer Logo',1,'alt attribute to use in place of image and title hover value. Defaults to FreePBX','text','','',1,0,'Styling and Logos','',1,90),
('BRAND_IMAGE_FREEPBX_LINK_LEFT','','Link for Left Logo',1,'link to follow when clicking on logo, defaults to http://www.freepbx.org','text','','',1,0,'Styling and Logos','',1,100),
('BRAND_IMAGE_FREEPBX_LINK_RIGHT','','Link for Right Logo',1,'link to follow when clicking on logo, defaults to http://www.freepbx.org','text','','',1,0,'Styling and Logos','',1,110),
('BRAND_IMAGE_FREEPBX_LINK_FOOT','','Link for Footer Logo',1,'link to follow when clicking on logo, defaults to http://www.freepbx.org','text','','',1,0,'Styling and Logos','',1,120),
('BRAND_HIDE_LOGO_RIGHT','0','Hide Right Logo',1,'Setting to true will hide the upper right logo.','bool','','0',0,0,'Styling and Logos','',0,130),
('BRAND_HIDE_HEADER_VERSION','0','Hide Left FreePBX Version',1,'Setting to true will hide the FreePBX version information below the left upper header.','bool','','0',0,0,'Styling and Logos','',0,140),
('BRAND_HIDE_HEADER_MENUS','0','Hide Header Menus',1,'Setting to true will hide the complete horizontal menu bar in the header.','bool','','0',0,0,'Styling and Logos','',0,150),
('BRAND_CSS_ALT_MAINSTYLE','','Primary CSS Stylesheet',1,'Set this to replace the default mainstyle.css style sheet with your own, relative to admin.','text','','',1,0,'Styling and Logos','',1,160),
('BRAND_CSS_CUSTOM','','Optional Additional CSS Stylesheet',1,'Optional custom CSS style sheet included after the primary one and any module specific ones are loaded, relative to admin.','text','','',1,0,'Styling and Logos','',1,170),
('VIEW_FREEPBX_ADMIN','views/freepbx_admin.php','View: freepbx_admin.php',1,'freepbx_admin.php view. This should never be changed except for very advanced layout changes.','text','','views/freepbx_admin.php',1,1,'Styling and Logos','',0,180),
('VIEW_FREEPBX','views/freepbx.php','View: freepbx.php',1,'freepbx.php view. This should never be changed except for very advanced layout changes.','text','','views/freepbx.php',1,1,'Styling and Logos','',0,190),
('VIEW_FREEPBX_RELOAD','views/freepbx_reload.php','View: freepbx_reload.php',1,'freepbx_reload.php view. This should never be changed except for very advanced layout changes.','text','','views/freepbx_reload.php',1,1,'Styling and Logos','',0,200),
('VIEW_FREEPBX_RELOADBAR','views/freepbx_reloadbar.php','View: freepbx_reloadbar.php',1,'freepbx_reloadbar.php view. This should never be changed except for very advanced layout changes.','text','','views/freepbx_reloadbar.php',1,1,'Styling and Logos','',0,210),
('VIEW_WELCOME','views/welcome.php','View: welcome.php',1,'welcome.php view. This should never be changed except for very advanced layout changes.','text','','views/welcome.php',1,1,'Styling and Logos','',0,220),
('VIEW_WELCOME_NONMANAGER','views/welcome_nomanager.php','View: welcome_nomanager.php',1,'welcome_nomanager.php view. This should never be changed except for very advanced layout changes.','text','','views/welcome_nomanager.php',1,1,'Styling and Logos','',0,230),
('VIEW_MENUITEM_DISABLED','views/menuitem_disabled.php','View: menuitem_disabled.php',1,'menuitem_disabled.php view. This should never be changed except for very advanced layout changes.','text','','views/menuitem_disabled.php',1,1,'Styling and Logos','',0,240),
('VIEW_NOACCESS','views/noaccess.php','View: noaccess.php',1,'noaccess.php view. This should never be changed except for very advanced layout changes.','text','','views/noaccess.php',1,1,'Styling and Logos','',0,250),
('VIEW_UNAUTHORIZED','views/unauthorized.php','View: unauthorized.php',1,'unauthorized.php view. This should never be changed except for very advanced layout changes.','text','','views/unauthorized.php',1,1,'Styling and Logos','',0,260),
('VIEW_BAD_REFFERER','views/bad_refferer.php','View: bad_refferer.php',1,'bad_refferer.php view. This should never be changed except for very advanced layout changes.','text','','views/bad_refferer.php',1,1,'Styling and Logos','',0,270),
('VIEW_LOGGEDOUT','views/loggedout.php','View: loggedout.php',1,'loggedout.php view. This should never be changed except for very advanced layout changes.','text','','views/loggedout.php',1,1,'Styling and Logos','',0,280),
('VIEW_PANEL','views/panel.php','View: panel.php',1,'panel.php view. This should never be changed except for very advanced layout changes.','text','','views/panel.php',1,1,'Styling and Logos','',0,290),
('VIEW_REPORTS','views/reports.php','View: reports.php',1,'reports.php view. This should never be changed except for very advanced layout changes.','text','','views/reports.php',1,1,'Styling and Logos','',0,300),
('DEVICE_STRONG_SECRETS','1','Require Strong Secrets',0,'Requires a strong secret on SIP and IAX devices requiring at least two numeric and non-numeric characters and 6 or more characters. This can be disabled if using devices that can not meet these needs, or you prefer to put other constraints including more rigid constraints that this rule actually considers weak when it may not be.','bool','','1',0,0,'Device Settings','',0,12),
('DEVICE_SIP_CANREINVITE','no','SIP canrenivite (directmedia)',0,'Default setting for SIP canreinvite (same as directmedia). See Asterisk documentation for details.','select','no,yes,nonat,update','no',0,0,'Device Settings','',0,20),
('DEVICE_SIP_TRUSTRPID','yes','SIP trustrpid',0,'Default setting for SIP trustrpid. See Asterisk documentation for details.','select','no,yes','yes',0,0,'Device Settings','',0,30),
('DEVICE_SIP_SENDRPID','no','SIP sendrpid',0,'Default setting for SIP sendrpid. A value of \'yes\' is equivalent to \'rpid\' and will send the \'Remote-Party-ID\' header. A value of \'pai\' is only valid starting with Asterisk 1.8 and will send the \'P-Asserted-Identity\' header. See Asterisk documentation for details.','select','no,yes,pai','no',0,0,'Device Settings','',0,40),
('DEVICE_SIP_NAT','no','SIP nat',0,'Default setting for SIP nat. A \'yes\' will attempt to handle nat, also works for local (uses the network ports and address instead of the reported ports), \'no\' follows the protocol, \'never\' tries to block it, no RFC3581, \'route\' ignores the rport information. See Asterisk documentation for details.','select','no,yes,never,route','no',0,0,'Device Settings','',0,50),
('DEVICE_SIP_ENCRYPTION','no','SIP encryption',0,'Default setting for SIP encryption. Whether to offer SRTP encrypted media (and only SRTP encrypted media) on outgoing calls to a peer. Calls will fail with HANGUPCAUSE=58 if the peer does not support SRTP. See Asterisk documentation for details.','select','no,yes','no',0,0,'Device Settings','',0,60),
('DEVICE_SIP_QUALIFYFREQ','60','SIP qualifyfreq',0,'Default setting for SIP qualifyfreq. Only valid for Asterisk 1.6 and above. Frequency that \'qualify\' OPTIONS messages will be sent to the device. Can help to keep NAT holes open but not dependable for remote client firewalls. See Asterisk documentation for details.','int','15,86400','60',0,0,'Device Settings','',0,70),
('DEVICE_QUALIFY','yes','SIP and IAX qualify',0,'Default setting for SIP and IAX qualify. Whether to send periodic OPTIONS messages (for SIP) or otherwise monitor the channel, and at what point to consider the channel unavailable. A value of \'yes\' is equivalent to 2000, time in msec. Can help to keep NAT holes open with SIP but not dependable for remote client firewalls. See Asterisk documentation for details.','text','','yes',0,0,'Device Settings','',0,80),
('DEVICE_DISALLOW','','SIP and IAX disallow',0,'Default setting for SIP and IAX disallow (for codecs). Codecs to disallow, can help to reset from the general settings by setting a value of \'all\' and then specifically including allowed codecs with the \'allow\' directive. Values van be separated with \'&\' e.g. \'g729&g722\'. See Asterisk documentation for details.','text','','',0,0,'Device Settings','',1,90),
('DEVICE_ALLOW','','SIP and IAX allow',0,'Default setting for SIP and IAX allow (for codecs). Codecs to allow in addition to those set in general settings unless explicitly \'disallowed\' for the device. Values van be separated with \'&\' e.g. \'ulaw&g729&g729\' where the preference order is preserved. See Asterisk documentation for details.','text','','',0,0,'Device Settings','',1,90),
('DEVICE_CALLGROUP','','SIP and DAHDi callgroup',0,'Default setting for SIP, DAHDi (and Zap) callgroup. Callgroup(s) that the device is part of, can be one or more callgroups, e.g. \'1,3-5\' would be in groups 1,3,4,5. See Asterisk documentation for details.','text','','',0,0,'Device Settings','',1,100),
('DEVICE_PICKUPGROUP','','SIP and DAHDi pickupgroup',0,'Default setting for SIP, DAHDi (and Zap) pickupgroup. Pickupgroups(s) that the device can pickup calls from, can be one or more groups, e.g. \'1,3-5\' would be in groups 1,3,4,5. Device does not have to be in a group to be able to pickup calls from that group. See Asterisk documentation for details.','text','','',0,0,'Device Settings','',1,110),
('ASTVERSION','1.8.5.0','Asterisk Version',10,'Last Asterisk Version detected (or forced)','text','','',1,1,'Internal Use','',1,0),
('AST_FUNC_DEVICE_STATE','DEVICE_STATE','Asterisk Function DEVICE_STATE',10,'Set to the function name if the function is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AST_FUNC_EXTENSION_STATE','EXTENSION_STATE','Asterisk Function EXTENSION_STATE',10,'Set to the function name if the function is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AST_FUNC_SHARED','SHARED','Asterisk Function SHARED',10,'Set to the function name if the function is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AST_FUNC_CONNECTEDLINE','CONNECTEDLINE','Asterisk Function CONNECTEDLINE',10,'Set to the function name if the function is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AST_FUNC_MASTER_CHANNEL','MASTER_CHANNEL','Asterisk Function MASTER_CHANNEL',10,'Set to the function name if the function is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AST_APP_VQA','','Asterisk Application VQA',10,'Set to the application name if the application is present in this Asterisk install','text','','',1,1,'Internal Use','',1,0),
('AUTOMIXMON','0','Use Automixmon for One-Touch Recording',0,'Starting with Asterisk 1.6, one-touch-recording can be toggled on and off during a call if the dial options had \'x\' and/or \'X\' options set. When this is set to true, the \'In-Call Asterisk Toggle Call Recording\' will use the asterisk \'automixmon\' option instead of the \'automon\' option to set this. Only one or the other can be set from the GUI. You need to set the proper options of \'x\' and/or \'X\' when using this, or \'w\' and/or \'W\' if using the older \'automon\' version. Setting this to true will have no effect on systems running Asterisk 1.4 or earlier.','bool','','0',0,0,'Dialplan and Operational','',0,0),
('VMX_CONTEXT','from-internal','VMX Default Context',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this.','text','','from-internal',1,0,'VmX Locater','',0,0),
('VMX_PRI','1','VMX Default Priority',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this.','int','1,1000','1',1,0,'VmX Locater','',0,0),
('VMX_TIMEDEST_CONTEXT','','VMX Default Timeout Context',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don\'t press any key (timeout) or press # which is interpreted as a timeout. Set this to \'dovm\' to go to voicemail (default).','text','','',1,0,'VmX Locater','',1,0),
('VMX_TIMEDEST_EXT','dovm','VMX Default Timeout Extension',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don\'t press any key (timeout) or press # which is interpreted as a timeout. Set this to \'dovm\' to go to voicemail (default).','text','','dovm',1,0,'VmX Locater','',0,0),
('VMX_TIMEDEST_PRI','1','VMX Default Timeout Priority',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they don\'t press any key (timeout) or press # which is interpreted as a timeout. Set this to \'dovm\' to go to voicemail (default).','int','1,1000','1',1,0,'VmX Locater','',0,0),
('VMX_LOOPDEST_CONTEXT','','VMX Default Loop Exceed Context',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.','text','','',1,0,'VmX Locater','',1,0),
('VMX_LOOPDEST_EXT','dovm','VMX Default Loop Exceed Extension',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.','text','','dovm',1,0,'VmX Locater','',0,0),
('VMX_LOOPDEST_PRI','1','VMX Default Loop Exceed Priority',9,'Used to do extremely advanced and customized changes to the macro-vm VmX locater. Check the dialplan for a thorough understanding of how to use this. The default location that a caller will be sent if they press an invalid options too many times, as defined by the Maximum Loops count.','int','1,1000','1',1,0,'VmX Locater','',0,0),
('MIXMON_DIR','','Override Call Recording Location',9,'Override the default location where asterisk will store call recordings. Be sure to set proper permissions on the directory for the asterisk user.','dir','','',1,0,'Directory Layout','',1,0),
('MIXMON_POST','','Post Call Recording Script',9,'An optional script to be run after the call is hangup. You can include channel and MixMon variables like ${CALLFILENAME}, ${MIXMON_FORMAT} and ${MIXMON_DIR}. To ensure that you variables are properly escaped, use the following notation: ^{MY_VAR}','text','','',1,0,'Developer and Customization','',1,0),
('DASHBOARD_STATS_UPDATE_TIME','6','Dashboard Stats Update Frequency',0,'Update rate in seconds of all sections of the System Status panel except the Info box.','select','6,10,20,30,45,60,120,300,600','6',0,0,'GUI Behavior','dashboard',0,0),
('DASHBOARD_INFO_UPDATE_TIME','30','Dashboard Info Update Frequency',0,'Update rate in seconds of the Info section of the System Status panel.','select','15,30,60,120,300,600','30',0,0,'GUI Behavior','dashboard',0,0),
('SSHPORT','','Dashboard Non-Std SSH Port',2,'SSH port number configured on your system if not using the default port 22, this allows the dashboard monitoring to watch the poper port.','int','1,65535','',0,0,'System Setup','dashboard',1,0),
('MAXCALLS','','Dashboard Max Calls Initial Scale',2,'Use this to pre-set the scale for maximum calls on the Dashboard display. If not set, the the scale is dynamically sized based on the active calls on the system.','int','0,3000','',0,0,'GUI Behavior','dashboard',1,0);
/*!40000 ALTER TABLE `freepbx_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `globals`
--

DROP TABLE IF EXISTS `globals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `globals` (
  `variable` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`variable`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `globals`
--

LOCK TABLES `globals` WRITE;
/*!40000 ALTER TABLE `globals` DISABLE KEYS */;
INSERT INTO `globals` VALUES ('CALLFILENAME','\"\"'),('DIAL_OPTIONS','tr'),('TRUNK_OPTIONS',''),('DIAL_OUT','9'),('FAX',''),('FAX_RX','system'),('FAX_RX_EMAIL','noreply@mydomain.tld'),('FAX_RX_FROM','noreply@mydomain.tld'),('INCOMING','group-all'),('NULL','\"\"'),('OPERATOR',''),('OPERATOR_XTN',''),('PARKNOTIFY','SIP/200'),('RECORDEXTEN','\"\"'),('RINGTIMER','15'),('DIRECTORY','last'),('AFTER_INCOMING',''),('IN_OVERRIDE','forcereghours'),('REGTIME','7:55-17:05'),('REGDAYS','mon-fri'),('DIRECTORY_OPTS',''),('DIALOUTIDS','1'),('RECORDING_STATE','ENABLED'),('VM_PREFIX','*'),('VM_OPTS',''),('VM_GAIN',''),('VM_DDTYPE','u'),('TIMEFORMAT','kM'),('TONEZONE','us'),('ALLOW_SIP_ANON','no'),('VMX_OPTS_TIMEOUT',''),('VMX_OPTS_LOOP',''),('VMX_OPTS_DOVM',''),('VMX_TIMEOUT','2'),('VMX_REPEAT','1'),('VMX_LOOPS','1'),('TRANSFER_CONTEXT','from-internal-xfer'),('MIXMON_FORMAT','wav');
/*!40000 ALTER TABLE `globals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iax`
--

DROP TABLE IF EXISTS `iax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iax` (
  `id` varchar(20) NOT NULL DEFAULT '-1',
  `keyword` varchar(30) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL,
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iax`
--

LOCK TABLES `iax` WRITE;
/*!40000 ALTER TABLE `iax` DISABLE KEYS */;
/*!40000 ALTER TABLE `iax` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incoming`
--

DROP TABLE IF EXISTS `incoming`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incoming` (
  `cidnum` varchar(20) DEFAULT NULL,
  `extension` varchar(50) NOT NULL,
  `destination` varchar(50) DEFAULT NULL,
  `faxexten` varchar(20) DEFAULT NULL,
  `faxemail` varchar(50) DEFAULT NULL,
  `answer` tinyint(1) DEFAULT NULL,
  `wait` int(2) DEFAULT NULL,
  `privacyman` tinyint(1) DEFAULT NULL,
  `alertinfo` varchar(255) DEFAULT NULL,
  `ringing` varchar(20) DEFAULT NULL,
  `mohclass` varchar(80) NOT NULL DEFAULT 'default',
  `description` varchar(80) DEFAULT NULL,
  `grppre` varchar(80) DEFAULT NULL,
  `delay_answer` int(2) DEFAULT NULL,
  `pricid` varchar(20) DEFAULT NULL,
  `pmmaxretries` varchar(2) DEFAULT NULL,
  `pmminlength` varchar(2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_xml`
--

DROP TABLE IF EXISTS `module_xml`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_xml` (
  `id` varchar(20) NOT NULL DEFAULT 'xml',
  `time` int(11) NOT NULL DEFAULT '0',
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '',
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `module` varchar(24) NOT NULL DEFAULT '',
  `id` varchar(24) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT '0',
  `display_text` varchar(255) NOT NULL DEFAULT '',
  `extended_text` blob NOT NULL,
  `link` varchar(255) NOT NULL DEFAULT '',
  `reset` tinyint(4) NOT NULL DEFAULT '0',
  `candelete` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outbound_route_patterns`
--

DROP TABLE IF EXISTS `outbound_route_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outbound_route_patterns` (
  `route_id` int(11) NOT NULL,
  `match_pattern_prefix` varchar(60) NOT NULL DEFAULT '',
  `match_pattern_pass` varchar(60) NOT NULL DEFAULT '',
  `match_cid` varchar(60) NOT NULL DEFAULT '',
  `prepend_digits` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`route_id`,`match_pattern_prefix`,`match_pattern_pass`,`match_cid`,`prepend_digits`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound_route_patterns`
--

LOCK TABLES `outbound_route_patterns` WRITE;
/*!40000 ALTER TABLE `outbound_route_patterns` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbound_route_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outbound_route_sequence`
--

DROP TABLE IF EXISTS `outbound_route_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outbound_route_sequence` (
  `route_id` int(11) NOT NULL,
  `seq` int(11) NOT NULL,
  PRIMARY KEY (`route_id`,`seq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound_route_sequence`
--

LOCK TABLES `outbound_route_sequence` WRITE;
/*!40000 ALTER TABLE `outbound_route_sequence` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbound_route_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outbound_route_trunks`
--

DROP TABLE IF EXISTS `outbound_route_trunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outbound_route_trunks` (
  `route_id` int(11) NOT NULL,
  `trunk_id` int(11) NOT NULL,
  `seq` int(11) NOT NULL,
  PRIMARY KEY (`route_id`,`trunk_id`,`seq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound_route_trunks`
--

LOCK TABLES `outbound_route_trunks` WRITE;
/*!40000 ALTER TABLE `outbound_route_trunks` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbound_route_trunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outbound_routes`
--

DROP TABLE IF EXISTS `outbound_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outbound_routes` (
  `route_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(40) DEFAULT NULL,
  `outcid` varchar(40) DEFAULT NULL,
  `outcid_mode` varchar(20) DEFAULT NULL,
  `password` varchar(30) DEFAULT NULL,
  `emergency_route` varchar(4) DEFAULT NULL,
  `intracompany_route` varchar(4) DEFAULT NULL,
  `mohclass` varchar(80) DEFAULT NULL,
  `time_group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`route_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound_routes`
--

LOCK TABLES `outbound_routes` WRITE;
/*!40000 ALTER TABLE `outbound_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `outbound_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sip`
--

DROP TABLE IF EXISTS `sip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sip` (
  `id` varchar(20) NOT NULL DEFAULT '-1',
  `keyword` varchar(30) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL,
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sip`
--

LOCK TABLES `sip` WRITE;
/*!40000 ALTER TABLE `sip` DISABLE KEYS */;
/*!40000 ALTER TABLE `sip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trunk_dialpatterns`
--

DROP TABLE IF EXISTS `trunk_dialpatterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trunk_dialpatterns` (
  `trunkid` int(11) NOT NULL DEFAULT '0',
  `match_pattern_prefix` varchar(50) NOT NULL DEFAULT '',
  `match_pattern_pass` varchar(50) NOT NULL DEFAULT '',
  `prepend_digits` varchar(50) NOT NULL DEFAULT '',
  `seq` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trunkid`,`match_pattern_prefix`,`match_pattern_pass`,`prepend_digits`,`seq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trunk_dialpatterns`
--

LOCK TABLES `trunk_dialpatterns` WRITE;
/*!40000 ALTER TABLE `trunk_dialpatterns` DISABLE KEYS */;
/*!40000 ALTER TABLE `trunk_dialpatterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trunks`
--

DROP TABLE IF EXISTS `trunks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trunks` (
  `trunkid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `tech` varchar(20) NOT NULL,
  `outcid` varchar(40) NOT NULL DEFAULT '',
  `keepcid` varchar(4) DEFAULT 'off',
  `maxchans` varchar(6) DEFAULT '',
  `failscript` varchar(255) NOT NULL DEFAULT '',
  `dialoutprefix` varchar(255) NOT NULL DEFAULT '',
  `channelid` varchar(255) NOT NULL DEFAULT '',
  `usercontext` varchar(255) DEFAULT NULL,
  `provider` varchar(40) DEFAULT NULL,
  `disabled` varchar(4) DEFAULT 'off',
  PRIMARY KEY (`trunkid`,`tech`,`channelid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trunks`
--

LOCK TABLES `trunks` WRITE;
/*!40000 ALTER TABLE `trunks` DISABLE KEYS */;
INSERT INTO `trunks` VALUES (1,'','zap','','','','','','g0','',NULL,'off');
/*!40000 ALTER TABLE `trunks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `extension` varchar(20) NOT NULL DEFAULT '',
  `password` varchar(20) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `voicemail` varchar(50) DEFAULT NULL,
  `ringtimer` int(3) DEFAULT NULL,
  `noanswer` varchar(100) DEFAULT NULL,
  `recording` varchar(50) DEFAULT NULL,
  `outboundcid` varchar(50) DEFAULT NULL,
  `sipname` varchar(50) DEFAULT NULL,
  `noanswer_cid` varchar(20) NOT NULL DEFAULT '',
  `busy_cid` varchar(20) NOT NULL DEFAULT '',
  `chanunavail_cid` varchar(20) NOT NULL DEFAULT '',
  `noanswer_dest` varchar(255) NOT NULL DEFAULT '',
  `busy_dest` varchar(255) NOT NULL DEFAULT '',
  `chanunavail_dest` varchar(255) NOT NULL DEFAULT '',
  `mohclass` varchar(80) DEFAULT 'default'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zap`
--

DROP TABLE IF EXISTS `zap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zap` (
  `id` varchar(20) NOT NULL DEFAULT '-1',
  `keyword` varchar(30) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL,
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zap`
--

LOCK TABLES `zap` WRITE;
/*!40000 ALTER TABLE `zap` DISABLE KEYS */;
/*!40000 ALTER TABLE `zap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zapchandids`
--

DROP TABLE IF EXISTS `zapchandids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zapchandids` (
  `channel` int(11) NOT NULL DEFAULT '0',
  `description` varchar(40) NOT NULL DEFAULT '',
  `did` varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`channel`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `zapchandids`
--

LOCK TABLES `zapchandids` WRITE;
/*!40000 ALTER TABLE `zapchandids` DISABLE KEYS */;
/*!40000 ALTER TABLE `zapchandids` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-08-13 11:44:20
