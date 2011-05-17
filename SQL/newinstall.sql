-- MySQL dump 10.9
--
-- Host: localhost    Database: asterisk
-- ------------------------------------------------------
-- Server version	4.1.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `freepbx_settings`
--

DROP TABLE IF EXISTS `freepbx_settings`;
CREATE TABLE `freepbx_settings` (
  `keyword` varchar(50) default NULL,
  `value` varchar(255) default NULL,
  `name` varchar(80) default NULL,
  `level` tinyint(1) default 0,
  `description` text default NULL,
  `type` varchar(25) default NULL,
  `options` text default NULL,
  `defaultval` varchar(255) default NULL,
  `readonly` tinyint(1) default 0,
  `hidden` tinyint(1) default 0,
  `category` varchar(50) default NULL,
  `module` varchar(25) default NULL,
  `emptyok` tinyint(1) default 1,
	`sortorder` int(11) default 0,
  PRIMARY KEY  (`keyword`)
 );

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `variable` varchar(20) NOT NULL default '',
  `value` varchar(80) NOT NULL default '',
  PRIMARY KEY  (`variable`)
);

--
-- Dumping data for table `admin`
--


/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
-- LOCK TABLES `admin` WRITE;
INSERT INTO `admin` VALUES ('need_reload','true');
INSERT INTO `admin` VALUES ('version','2.2.0beta3');
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;

--
-- Table structure for table `ampusers`
--

DROP TABLE IF EXISTS `ampusers`;
CREATE TABLE `ampusers` (
  `username` varchar(20) NOT NULL default '',
  `password` varchar(20) NOT NULL default '',
  `extension_low` varchar(20) NOT NULL default '',
  `extension_high` varchar(20) NOT NULL default '',
  `deptname` varchar(20) NOT NULL default '',
  `sections` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`username`)
);

--
-- Dumping data for table `ampusers`
--


/*!40000 ALTER TABLE `ampusers` DISABLE KEYS */;
-- LOCK TABLES `ampusers` WRITE;
INSERT INTO `ampusers` VALUES ('admin','admin','','','','*');
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `ampusers` ENABLE KEYS */;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` varchar(20) NOT NULL default '',
  `tech` varchar(10) NOT NULL default '',
  `dial` varchar(50) NOT NULL default '',
  `devicetype` varchar(5) NOT NULL default '',
  `user` varchar(50) default NULL,
  `description` varchar(50) default NULL,
  `emergency_cid` varchar(100) default NULL
);

--
-- Dumping data for table `devices`
--


/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
-- LOCK TABLES `devices` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;

--
-- Table structure for table `extensions`
--

DROP TABLE IF EXISTS `extensions`;
CREATE TABLE `extensions` (
  `context` varchar(45) NOT NULL default 'default',
  `extension` varchar(45) NOT NULL default '',
  `priority` varchar(5) NOT NULL default '1',
  `application` varchar(45) NOT NULL default '',
  `args` varchar(255) default NULL,
  `descr` text,
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`context`,`extension`,`priority`)
);

--
-- Dumping data for table `extensions`
--


/*!40000 ALTER TABLE `extensions` DISABLE KEYS */;
-- LOCK TABLES `extensions` WRITE;
INSERT INTO `extensions` VALUES ('outrt-001-9_outside','_9.','1','Macro','dialout-trunk,1,${EXTEN:1}',NULL,0);
INSERT INTO `extensions` VALUES ('outrt-001-9_outside','_9.','2','Macro','outisbusy','No available circuits',0);
INSERT INTO `extensions` VALUES ('outbound-allroutes','include','1','outrt-001-9_outside','','',2);
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `extensions` ENABLE KEYS */;

--
-- Table structure for table `featurecodes`
--

DROP TABLE IF EXISTS `featurecodes`;
CREATE TABLE `featurecodes` (
  `modulename` varchar(50) NOT NULL default '',
  `featurename` varchar(50) NOT NULL default '',
  `description` varchar(200) NOT NULL default '',
  `defaultcode` varchar(20) default NULL,
  `customcode` varchar(20) default NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  `providedest` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`modulename`,`featurename`),
  KEY `enabled` (`enabled`)
);

--
-- Table structure for table `freepbx_log`
--

DROP TABLE IF EXISTS `freepbx_log`;
CREATE TABLE `freepbx_log` (
  `id` int(11) NOT NULL auto_increment,
  `time` datetime NOT NULL default '0000-00-00 00:00:00',
  `section` varchar(50) default NULL,
  `level` enum('error','warning','debug','devel-debug') NOT NULL default 'error',
  `status` int(11) NOT NULL default '0',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `time` (`time`,`level`)
);

--
-- Dumping data for table `freepbx_log`
--


/*!40000 ALTER TABLE `freepbx_log` DISABLE KEYS */;
-- LOCK TABLES `freepbx_log` WRITE;
INSERT INTO `freepbx_log` VALUES (1,'2006-11-06 01:55:36','retrieve_conf','devel-debug',0,'Started retrieve_conf, DB Connection OK');
INSERT INTO `freepbx_log` VALUES (2,'2006-11-06 01:55:36','retrieve_conf','devel-debug',0,'Writing extensions_additional.conf');
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `freepbx_log` ENABLE KEYS */;

--
-- Table structure for table `globals`
--

DROP TABLE IF EXISTS `globals`;
CREATE TABLE `globals` (
  `variable` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`variable`)
);

--
-- Dumping data for table `globals`
--


/*!40000 ALTER TABLE `globals` DISABLE KEYS */;
-- LOCK TABLES `globals` WRITE;
INSERT INTO `globals` VALUES ('CALLFILENAME','\"\"');
INSERT INTO `globals` VALUES ('DIAL_OPTIONS','tr');
INSERT INTO `globals` VALUES ('TRUNK_OPTIONS','');
INSERT INTO `globals` VALUES ('DIAL_OUT','9');
INSERT INTO `globals` VALUES ('FAX','');
INSERT INTO `globals` VALUES ('FAX_RX','system');
INSERT INTO `globals` VALUES ('FAX_RX_EMAIL','noreply@mydomain.tld');
INSERT INTO `globals` VALUES ('FAX_RX_FROM','noreply@mydomain.tld');
INSERT INTO `globals` VALUES ('INCOMING','group-all');
INSERT INTO `globals` VALUES ('NULL','\"\"');
INSERT INTO `globals` VALUES ('OPERATOR','');
INSERT INTO `globals` VALUES ('OPERATOR_XTN','');
INSERT INTO `globals` VALUES ('PARKNOTIFY','SIP/200');
INSERT INTO `globals` VALUES ('RECORDEXTEN','\"\"');
INSERT INTO `globals` VALUES ('RINGTIMER','15');
INSERT INTO `globals` VALUES ('DIRECTORY','last');
INSERT INTO `globals` VALUES ('AFTER_INCOMING','');
INSERT INTO `globals` VALUES ('IN_OVERRIDE','forcereghours');
INSERT INTO `globals` VALUES ('REGTIME','7:55-17:05');
INSERT INTO `globals` VALUES ('REGDAYS','mon-fri');
INSERT INTO `globals` VALUES ('DIRECTORY_OPTS','');
INSERT INTO `globals` VALUES ('DIALOUTIDS','1');
INSERT INTO `globals` VALUES ('OUT_1','ZAP/g0');
INSERT INTO `globals` VALUES ('VM_PREFIX','*');
INSERT INTO `globals` VALUES ('VM_OPTS','');
INSERT INTO `globals` VALUES ('VM_GAIN','');
INSERT INTO `globals` VALUES ('VM_DDTYPE','u');
INSERT INTO `globals` VALUES ('TIMEFORMAT','kM');
INSERT INTO `globals` VALUES ('TONEZONE','us');
INSERT INTO `globals` VALUES ('ALLOW_SIP_ANON','no');
INSERT INTO `globals` VALUES ('VMX_CONTEXT','from-internal');
INSERT INTO `globals` VALUES ('VMX_PRI','1');
INSERT INTO `globals` VALUES ('VMX_TIMEDEST_CONTEXT','');
INSERT INTO `globals` VALUES ('VMX_TIMEDEST_EXT','dovm');
INSERT INTO `globals` VALUES ('VMX_TIMEDEST_PRI','1');
INSERT INTO `globals` VALUES ('VMX_LOOPDEST_CONTEXT','');
INSERT INTO `globals` VALUES ('VMX_LOOPDEST_EXT','dovm');
INSERT INTO `globals` VALUES ('VMX_LOOPDEST_PRI','1');
INSERT INTO `globals` VALUES ('VMX_OPTS_TIMEOUT','');
INSERT INTO `globals` VALUES ('VMX_OPTS_LOOP','');
INSERT INTO `globals` VALUES ('VMX_OPTS_DOVM','');
INSERT INTO `globals` VALUES ('VMX_TIMEOUT','2');
INSERT INTO `globals` VALUES ('VMX_REPEAT','1');
INSERT INTO `globals` VALUES ('VMX_LOOPS','1');
INSERT INTO `globals` VALUES ('TRANSFER_CONTEXT','from-internal-xfer');
INSERT INTO `globals` VALUES ('MIXMON_FORMAT','wav');
INSERT INTO `globals` VALUES ('MIXMON_DIR','');
INSERT INTO `globals` VALUES ('MIXMON_POST','');


-- UNLOCK TABLES;
/*!40000 ALTER TABLE `globals` ENABLE KEYS */;

--
-- Table structure for table `iax`
--

DROP TABLE IF EXISTS `iax`;
CREATE TABLE `iax` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);

--
-- Dumping data for table `iax`
--


/*!40000 ALTER TABLE `iax` DISABLE KEYS */;
-- LOCK TABLES `iax` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `iax` ENABLE KEYS */;

--
-- Table structure for table `incoming`
--

DROP TABLE IF EXISTS `incoming`;
CREATE TABLE `incoming` (
  `cidnum` varchar(20) default NULL,
  `extension` varchar(50) default NULL,
  `destination` varchar(50) default NULL,
  `faxexten` varchar(20) default NULL,
  `faxemail` varchar(50) default NULL,
  `answer` tinyint(1) default NULL,
  `wait` int(2) default NULL,
  `privacyman` tinyint(1) default NULL,
  `alertinfo` varchar(255) default NULL,
  `ringing` varchar(20) default NULL,
  `mohclass` varchar(80) NOT NULL default 'default',
  `description` varchar(80) default NULL,
	`grppre` varchar (80) default NULL ,
	`delay_answer` int (2) default NULL
);

--
-- Table structure for table `zapchandids`
--

DROP TABLE IF EXISTS `zapchandids`;
CREATE TABLE `zapchandids` (
	`channel` int(11) NOT NULL default '0',
	`description` varchar(40) NOT NULL default '',
	`did` varchar(60) NOT NULL default '',
	PRIMARY KEY  (channel)
);

--
-- Dumping data for table `incoming`
--


/*!40000 ALTER TABLE `incoming` DISABLE KEYS */;
-- LOCK TABLES `incoming` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `incoming` ENABLE KEYS */;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL auto_increment,
  `modulename` varchar(50) NOT NULL default '',
  `version` varchar(20) NOT NULL default '',
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);

--
-- Dumping data for table `modules`
--

DROP TABLE IF EXISTS `module_xml`;
CREATE TABLE `module_xml` (
	`id` varchar(20) NOT NULL default 'xml',
	`time` int(11) NOT NULL default '0',
	`data` mediumblob NOT NULL,
  PRIMARY KEY  (`id`)
);

--
-- Table structure for table `sip`
--

DROP TABLE IF EXISTS `sip`;
CREATE TABLE `sip` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);

--
-- Dumping data for table `sip`
--


/*!40000 ALTER TABLE `sip` DISABLE KEYS */;
-- LOCK TABLES `sip` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `sip` ENABLE KEYS */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `extension` varchar(20) NOT NULL default '',
  `password` varchar(20) default NULL,
  `name` varchar(50) default NULL,
  `voicemail` varchar(50) default NULL,
  `ringtimer` int(3) default NULL,
  `noanswer` varchar(100) default NULL,
  `recording` varchar(50) default NULL,
  `outboundcid` varchar(50) default NULL,
  `sipname` varchar(50) default NULL,
  `noanswer_cid` varchar(20) NOT NULL default '',
  `busy_cid` varchar(20) NOT NULL default '',
  `chanunavail_cid` varchar(20) NOT NULL default '',
  `noanswer_dest` varchar(255) NOT NULL default '',
  `busy_dest` varchar(255) NOT NULL default '',
  `chanunavail_dest` varchar(255) NOT NULL default ''
);

--
-- Dumping data for table `users`
--


/*!40000 ALTER TABLE `users` DISABLE KEYS */;
-- LOCK TABLES `users` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;

--
-- Table structure for table `zap`
--

DROP TABLE IF EXISTS `zap`;
CREATE TABLE `zap` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);

--
-- Dumping data for table `zap`
--

--
-- Table structure for table `dahdi`
--

DROP TABLE IF EXISTS `dahdi`;
CREATE TABLE `dahdi` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);

--
-- Dumping data for table `dahdi`
--

DROP  TABLE IF EXISTS `outbound_routes`;
CREATE TABLE `outbound_routes` (
	`route_id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`name` VARCHAR( 40 ),
	`outcid` VARCHAR( 40 ),
	`outcid_mode` VARCHAR( 20 ),
	`password` VARCHAR( 30 ),
	`emergency_route` VARCHAR( 4 ),
	`intracompany_route` VARCHAR( 4 ),
	`mohclass` VARCHAR( 80 ),
	`time_group_id` INTEGER DEFAULT NULL
);

DROP TABLE IF EXISTS `outbound_route_patterns`;
CREATE TABLE `outbound_route_patterns` (
	`route_id` INTEGER NOT NULL,
	`match_pattern_prefix` VARCHAR( 60 ),
	`match_pattern_pass` VARCHAR( 60 ),
	`match_cid` VARCHAR( 60 ),
	`prepend_digits` VARCHAR( 100 ),
  PRIMARY KEY (`route_id`, `match_pattern_prefix`, `match_pattern_pass`,`match_cid`,`prepend_digits`)
);

DROP TABLE IF EXISTS `outbound_route_trunks`;
CREATE TABLE `outbound_route_trunks` (
	`route_id` INTEGER NOT NULL,
	`trunk_id` INTEGER NOT NULL,
	`seq` INTEGER NOT NULL,
  PRIMARY KEY  (`route_id`, `trunk_id`, `seq`) 
);

DROP TABLE IF EXISTS `outbound_route_sequence`;
CREATE TABLE `outbound_route_sequence` (
	`route_id` INTEGER NOT NULL,
	`seq` INTEGER NOT NULL,
  PRIMARY KEY  (`route_id`, `seq`) 
);

-- 
-- Table structure for table `notifications`
-- 

DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
  module varchar(24) NOT NULL default '',
  id varchar(24) NOT NULL default '',
  `level` int(11) NOT NULL default '0',
  display_text varchar(255) NOT NULL default '',
  extended_text blob NOT NULL,
  link varchar(255) NOT NULL default '',
  `reset` tinyint(4) NOT NULL default '0',
	candelete tinyint(4) NOT NULL default '0',
  `timestamp` int(11) NOT NULL default '0',
  PRIMARY KEY  (`module`,`id`)
);

CREATE TABLE IF NOT EXISTS `cronmanager` (
  `module` varchar(24) NOT NULL default '',
  `id` varchar(24) NOT NULL default '',
  `time` varchar(5) default NULL,
  `freq` int(11) NOT NULL default '0',
  `lasttime` int(11) NOT NULL default '0',
  `command` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`module`,`id`)
);


/*!40000 ALTER TABLE `zap` DISABLE KEYS */;
-- LOCK TABLES `zap` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `zap` ENABLE KEYS */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

