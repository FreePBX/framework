-- MySQL dump 10.13  Distrib 5.1.69, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: asterisk
-- ------------------------------------------------------
-- Server version	5.1.69

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
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES ('need_reload','true'),('version','2.11.0rc1'),('default_directory','1'),('directory28_migrated','1');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ampusers`
--

DROP TABLE IF EXISTS `ampusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ampusers` (
  `username` varchar(255) NOT NULL,
  `password_sha1` varchar(40) NOT NULL,
  `extension_low` varchar(20) NOT NULL DEFAULT '',
  `extension_high` varchar(20) NOT NULL DEFAULT '',
  `deptname` varchar(20) NOT NULL DEFAULT '',
  `sections` blob NOT NULL,
  PRIMARY KEY (`username`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ampusers`
--

LOCK TABLES `ampusers` WRITE;
/*!40000 ALTER TABLE `ampusers` DISABLE KEYS */;
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
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cronmanager`
--

LOCK TABLES `cronmanager` WRITE;
/*!40000 ALTER TABLE `cronmanager` DISABLE KEYS */;
INSERT INTO `cronmanager` VALUES ('module_admin','UPDATES','22',24,0,'/var/lib/asterisk/bin/module_admin listonline > /dev/null 2>&1');
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
);
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
  `dial` varchar(255) NOT NULL DEFAULT '',
  `devicetype` varchar(5) NOT NULL DEFAULT '',
  `user` varchar(50) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `emergency_cid` varchar(100) DEFAULT NULL,
  KEY `id` (`id`),
  KEY `tech` (`tech`)
);
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
);
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
-- Table structure for table `fax_details`
--

DROP TABLE IF EXISTS `featurecodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `featurecodes` (
  `modulename` varchar(50) NOT NULL DEFAULT '',
  `featurename` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(200) NOT NULL DEFAULT '',
  `helptext` varchar(250) NOT NULL DEFAULT '',
  `defaultcode` varchar(20) DEFAULT NULL,
  `customcode` varchar(20) DEFAULT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `providedest` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`modulename`,`featurename`),
  KEY `enabled` (`enabled`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `featurecodes`
--

LOCK TABLES `featurecodes` WRITE;
/*!40000 ALTER TABLE `featurecodes` DISABLE KEYS */;
INSERT INTO `featurecodes` VALUES ('core','userlogon','User Logon','','*11',NULL,1,0),('core','userlogoff','User Logoff','','*12',NULL,1,0),('core','zapbarge','ZapBarge','','888',NULL,1,1),('core','chanspy','ChanSpy','','555',NULL,1,1),('core','simu_pstn','Simulate Incoming Call','','7777',NULL,1,1),('core','pickup','Directed Call Pickup','','**',NULL,1,0),('core','pickupexten','Asterisk General Call Pickup','','*8',NULL,1,0),('core','blindxfer','In-Call Asterisk Blind Transfer','','##',NULL,1,0),('core','atxfer','In-Call Asterisk Attended Transfer','','*2',NULL,1,0),('core','automon','In-Call Asterisk Toggle Call Recording','','*1',NULL,1,0),('core','disconnect','In-Call Asterisk Disconnect Code','','**',NULL,1,0),('pbdirectory','app-pbdirectory','Phonebook dial-by-name directory','','411',NULL,1,1),('donotdisturb','dnd_on','DND Activate','','*78',NULL,1,0),('donotdisturb','dnd_off','DND Deactivate','','*79',NULL,1,0),('donotdisturb','dnd_toggle','DND Toggle','','*76',NULL,1,0),('recordings','record_save','Save Recording','','*77',NULL,1,0),('recordings','record_check','Check Recording','','*99',NULL,1,0),('callwaiting','cwon','Call Waiting - Activate','','*70',NULL,1,0),('callwaiting','cwoff','Call Waiting - Deactivate','','*71',NULL,1,0),('voicemail','myvoicemail','My Voicemail','','*97',NULL,1,0),('voicemail','dialvoicemail','Dial Voicemail','','*98',NULL,1,1),('voicemail','directdialvoicemail','Direct Dial Prefix','','*',NULL,1,0),('paging','intercom-prefix','Intercom prefix','','*80',NULL,1,0),('paging','intercom-on','User Intercom Allow','','*54',NULL,1,0),('paging','intercom-off','User Intercom Disallow','','*55',NULL,1,0),('blacklist','blacklist_add','Blacklist a number','','*30',NULL,1,1),('blacklist','blacklist_remove','Remove a number from the blacklist','','*31',NULL,1,1),('blacklist','blacklist_last','Blacklist the last caller','','*32',NULL,1,0),('fax','simu_fax','Dial System FAX','','666',NULL,1,1),('dictate','dodictate','Perform dictation','','*34',NULL,1,0),('dictate','senddictate','Email completed dictation','','*35',NULL,1,0),('findmefollow','fmf_toggle','Findme Follow Toggle','','*21',NULL,1,0),('campon','request','Camp-On Request','','*82',NULL,1,0),('campon','cancel','Camp-On Cancel','','*83',NULL,1,0),('campon','toggle','Camp-On Toggle','','*84',NULL,1,0),('parking','parkedcall','Pickup ParkedCall Prefix','','*85',NULL,1,1),('infoservices','calltrace','Call Trace','','*69',NULL,1,0),('infoservices','echotest','Echo Test','','*43',NULL,1,1),('infoservices','speakingclock','Speaking Clock','','*60',NULL,1,1),('infoservices','speakextennum','Speak Your Exten Number','','*65',NULL,1,0),('callforward','cfon','Call Forward All Activate','','*72',NULL,1,0),('callforward','cfoff','Call Forward All Deactivate','','*73',NULL,1,0),('callforward','cfoff_any','Call Forward All Prompting Deactivate','','*74',NULL,1,0),('callforward','cfbon','Call Forward Busy Activate','','*90',NULL,1,0),('callforward','cfboff','Call Forward Busy Deactivate','','*91',NULL,1,0),('callforward','cfboff_any','Call Forward Busy Prompting Deactivate','','*92',NULL,1,0),('callforward','cfuon','Call Forward No Answer/Unavailable Activate','','*52',NULL,1,0),('callforward','cfuoff','Call Forward No Answer/Unavailable Deactivate','','*53',NULL,1,0),('callforward','cf_toggle','Call Forward Toggle','','*740',NULL,1,0),('queues','que_toggle','Queue Toggle','','*45',NULL,1,0),('queues','que_pause_toggle','Queue Pause Toggle','','*46',NULL,1,0),('speeddial','callspeeddial','Speeddial prefix','','*0',NULL,1,0),('speeddial','setspeeddial','Set user speed dial','','*75',NULL,1,0),('hotelwakeup','hotelwakeup','Wake Up Calls','','*68',NULL,1,0);
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
) AUTO_INCREMENT=3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `freepbx_log`
--

LOCK TABLES `freepbx_log` WRITE;
/*!40000 ALTER TABLE `freepbx_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `freepbx_log` ENABLE KEYS */;
UNLOCK TABLES;

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
);
/*!40101 SET character_set_client = @saved_cs_client */;

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
);
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `globals` WRITE;
/*!40000 ALTER TABLE `globals` DISABLE KEYS */;
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
);
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
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incoming`
--

LOCK TABLES `incoming` WRITE;
/*!40000 ALTER TABLE `incoming` DISABLE KEYS */;
/*!40000 ALTER TABLE `incoming` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_xml`
--

DROP TABLE IF EXISTS `module_xml`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_xml` (
  `id` varchar(20) NOT NULL DEFAULT 'xml',
  `time` int(11) NOT NULL DEFAULT '0',
  `data` longblob NOT NULL,
  PRIMARY KEY (`id`)
);
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
  `signature` blob,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1;
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
);
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
);
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
);
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
);
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
  `dest` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`route_id`)
);
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
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sip`
--

LOCK TABLES `sip` WRITE;
/*!40000 ALTER TABLE `sip` DISABLE KEYS */;
/*!40000 ALTER TABLE `sip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sip`
--

DROP TABLE IF EXISTS `pjsip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pjsip` (
  `id` varchar(20) NOT NULL DEFAULT '-1',
  `keyword` varchar(30) NOT NULL DEFAULT '',
  `data` varchar(255) NOT NULL,
  `flags` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`keyword`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pjsip`
--

LOCK TABLES `pjsip` WRITE;
/*!40000 ALTER TABLE `pjsip` DISABLE KEYS */;
/*!40000 ALTER TABLE `pjsip` ENABLE KEYS */;
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
);
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
  `continue` varchar(4) DEFAULT 'off',
  PRIMARY KEY (`trunkid`,`tech`,`channelid`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trunks`
--

LOCK TABLES `trunks` WRITE;
/*!40000 ALTER TABLE `trunks` DISABLE KEYS */;
INSERT INTO `trunks` VALUES (1,'','dahdi','','','','','','g0','',NULL,'off','off');
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
  `mohclass` varchar(80) DEFAULT 'default',
  KEY `extension` (`extension`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dahdichandids`
--

DROP TABLE IF EXISTS `dahdichandids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dahdichandids` (
  `channel` int(11) NOT NULL DEFAULT '0',
  `description` varchar(40) NOT NULL DEFAULT '',
  `did` varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`channel`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dahdichandids`
--

LOCK TABLES `dahdichandids` WRITE;
/*!40000 ALTER TABLE `dahdichandids` DISABLE KEYS */;
/*!40000 ALTER TABLE `dahdichandids` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-04  1:30:59
