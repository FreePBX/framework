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
-- Table structure for table `Backup`
--

DROP TABLE IF EXISTS `Backup`;
CREATE TABLE `Backup` (
  `Name` varchar(50) default NULL,
  `Voicemail` varchar(50) default NULL,
  `Recordings` varchar(50) default NULL,
  `Configurations` varchar(50) default NULL,
  `CDR` varchar(55) default NULL,
  `FOP` varchar(50) default NULL,
  `Minutes` varchar(50) default NULL,
  `Hours` varchar(50) default NULL,
  `Days` varchar(50) default NULL,
  `Months` varchar(50) default NULL,
  `Weekdays` varchar(50) default NULL,
  `Command` varchar(200) default NULL,
  `Method` varchar(50) default NULL,
  `ID` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`ID`)
);

--
-- Dumping data for table `Backup`
--


/*!40000 ALTER TABLE `Backup` DISABLE KEYS */;
-- LOCK TABLES `Backup` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `Backup` ENABLE KEYS */;

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
  PRIMARY KEY  (`modulename`,`featurename`),
  KEY `enabled` (`enabled`)
);

--
-- Dumping data for table `featurecodes`
--


/*!40000 ALTER TABLE `featurecodes` DISABLE KEYS */;
-- LOCK TABLES `featurecodes` WRITE;
INSERT INTO `featurecodes` VALUES ('core','userlogon','User Logon','*11',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','userlogoff','User Logoff','*12',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','zapbarge','ZapBarge','888',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','simu_pstn','Simulate Incoming Call','7777',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','simu_fax','Simulate Incoming FAX Call','666',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','chanspy','ChanSpy','555',NULL,1);
INSERT INTO `featurecodes` VALUES ('core','pickup','Call Pickup (Can be used with GXP-2000)','**',NULL,1);
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `featurecodes` ENABLE KEYS */;

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
  `variable` char(20) NOT NULL default '',
  `value` char(50) NOT NULL default '',
  PRIMARY KEY  (`variable`)
);

--
-- Dumping data for table `globals`
--


/*!40000 ALTER TABLE `globals` DISABLE KEYS */;
-- LOCK TABLES `globals` WRITE;
INSERT INTO `globals` VALUES 
	 ('CALLFILENAME','\"\"')
	,('DIAL_OPTIONS','tr')
	,('TRUNK_OPTIONS','')
	,('DIAL_OUT','9')
	,('FAX','')
	,('FAX_RX','system')
	,('FAX_RX_EMAIL','fax@mydomain.com')
	,('FAX_RX_FROM','freepbx@gmail.com')
	,('INCOMING','group-all'),('NULL','\"\"')
	,('OPERATOR',''),('OPERATOR_XTN','')
	,('PARKNOTIFY','SIP/200')
	,('RECORDEXTEN','\"\"')
	,('RINGTIMER','15')
	,('DIRECTORY','last')
	,('AFTER_INCOMING','')
	,('IN_OVERRIDE','forcereghours')
	,('REGTIME','7:55-17:05')
	,('REGDAYS','mon-fri')
	,('DIRECTORY_OPTS','')
	,('DIALOUTIDS','1')
	,('OUT_1','ZAP/g0')
	,('VM_PREFIX','*')
	,('VM_OPTS','')
	,('VM_GAIN','')
	,('VM_DDTYPE','u')
	,('TIMEFORMAT','kM')
	,('TONEZONE','us')
	,('ALLOW_SIP_ANON','no')
	,('VMX_CONTEXT','from-internal')
	,('VMX_PRI','1')
	,('VMX_TIMEDEST_CONTEXT','')
	,('VMX_TIMEDEST_EXT','dovm')
	,('VMX_TIMEDEST_PRI','1')
	,('VMX_LOOPDEST_CONTEXT','')
	,('VMX_LOOPDEST_EXT','dovm')
	,('VMX_LOOPDEST_PRI','1')
	,('VMX_OPTS_TIMEOUT','')
	,('VMX_OPTS_LOOP','')
	,('VMX_OPTS_DOVM','')
	,('VMX_TIMEOUT','2')
	,('VMX_REPEAT','1')
	,('VMX_LOOPS','1')
	;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `globals` ENABLE KEYS */;

--
-- Table structure for table `iax`
--

DROP TABLE IF EXISTS `iax`;
CREATE TABLE `iax` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
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
  `extension` varchar(20) default NULL,
  `destination` varchar(50) default NULL,
  `faxexten` varchar(20) default NULL,
  `faxemail` varchar(50) default NULL,
  `answer` tinyint(1) default NULL,
  `wait` int(2) default NULL,
  `privacyman` tinyint(1) default NULL,
  `alertinfo` varchar(32) default NULL,
  `channel` varchar(20) default NULL,
  `ringing` varchar(20) default NULL,
  `mohclass` varchar(80) NOT NULL default 'default'
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


/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
-- LOCK TABLES `modules` WRITE;
INSERT INTO `modules` VALUES (1,'core','1.2',1);
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;

--
-- Table structure for table `queues`
--

DROP TABLE IF EXISTS `queues`;
CREATE TABLE `queues` (
  `id` bigint(11) NOT NULL default '-1',
  `keyword` varchar(20) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`,`data`)
);

--
-- Dumping data for table `queues`
--


/*!40000 ALTER TABLE `queues` DISABLE KEYS */;
-- LOCK TABLES `queues` WRITE;
-- UNLOCK TABLES;
/*!40000 ALTER TABLE `queues` ENABLE KEYS */;

--
-- Table structure for table `sip`
--

DROP TABLE IF EXISTS `sip`;
CREATE TABLE `sip` (
  `id` varchar(20) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
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
  `directdid` varchar(50) default NULL,
  `didalert` varchar(50) default NULL,
  `faxexten` varchar(20) default NULL,
  `faxemail` varchar(50) default NULL,
  `answer` tinyint(1) default NULL,
  `wait` int(2) default NULL,
  `privacyman` tinyint(1) default NULL,
  `mohclass` varchar(80) NOT NULL default 'default',
  `sipname` varchar(50) default NULL
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
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
);

--
-- Dumping data for table `zap`
--


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

