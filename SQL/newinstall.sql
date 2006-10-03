-- phpMyAdmin SQL Dump
-- version 2.6.0-alpha1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: May 20, 2004 at 04:00 PM
-- Server version: 3.23.58
-- PHP Version: 4.3.2
-- 
-- Database : `asterisk`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `admin`
-- 

CREATE TABLE IF NOT EXISTS `admin` (
  `variable` varchar(20) NOT NULL default '',
  `value` varchar(80) NOT NULL default '',
  PRIMARY KEY  (`variable`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `admin`
-- 

INSERT INTO `admin` VALUES ('need_reload', 'false');
INSERT INTO `admin` VALUES ('version','2.0.0');
-- --------------------------------------------------------

-- 
-- Table structure for table `extensions`
-- 

CREATE TABLE IF NOT EXISTS `extensions` (
  `context` varchar(45) NOT NULL default 'default',
  `extension` varchar(45) NOT NULL default '',
  `priority` VARCHAR(5) NOT NULL default '1',
  `application` varchar(45) NOT NULL default '',
  `args` varchar(255) default NULL,
  `descr` text,
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`context`,`extension`,`priority`)
) TYPE=MyISAM;


-- 
-- Create a default route (9 to get out)
-- 

INSERT INTO extensions (context, extension, priority, application, args) VALUES 
 ('outrt-001-9_outside','_9.','1','Macro','dialout-trunk,1,${EXTEN:1}');

INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES 
 ('outrt-001-9_outside','_9.','2','Macro','outisbusy','No available circuits');

INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES 
 ('outbound-allroutes','include','1','outrt-001-9_outside','','','2');
 
-- --------------------------------------------------------

-- 
-- Table structure for table `globals`
-- 

CREATE TABLE IF NOT EXISTS `globals` (
  `variable` char(20) NOT NULL default '',
  `value` char(50) NOT NULL default '',
  PRIMARY KEY  (`variable`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `globals`
-- 

INSERT INTO `globals` VALUES ('CALLFILENAME', '""');
INSERT INTO `globals` VALUES ('DIAL_OPTIONS', 'tr');
INSERT INTO `globals` VALUES ('TRUNK_OPTIONS', 'r');
INSERT INTO `globals` VALUES ('DIAL_OUT', '9');
INSERT INTO `globals` VALUES ('FAX', '');
INSERT INTO `globals` VALUES ('FAX_RX', 'system');
INSERT INTO `globals` VALUES ('FAX_RX_EMAIL', 'fax@mydomain.com');
INSERT INTO `globals` VALUES ('INCOMING', 'group-all');
INSERT INTO `globals` VALUES ('NULL', '""');
INSERT INTO `globals` VALUES ('OPERATOR', '');
INSERT INTO `globals` VALUES ('PARKNOTIFY', 'SIP/200');
INSERT INTO `globals` VALUES ('RECORDEXTEN', '""');
INSERT INTO `globals` VALUES ('RINGTIMER', '15');
INSERT INTO `globals` VALUES ('DIRECTORY', 'last');
INSERT INTO `globals` VALUES ('AFTER_INCOMING', '');
INSERT INTO `globals` VALUES ('IN_OVERRIDE', 'forcereghours');
INSERT INTO `globals` VALUES ('REGTIME', '7:55-17:05');
INSERT INTO `globals` VALUES ('REGDAYS', 'mon-fri');
INSERT INTO `globals` VALUES ('DIRECTORY_OPTS', '');
INSERT INTO `globals` VALUES ('DIALOUTIDS', '1');
INSERT INTO `globals` VALUES ('OUT_1', 'ZAP/g0');
INSERT INTO `globals` VALUES ('VM_PREFIX', '*');
INSERT INTO `globals` VALUES ('VM_OPTS', '');

-- --------------------------------------------------------

-- 
-- Table structure for table `sip`
-- 

CREATE TABLE IF NOT EXISTS `sip` (
  `id` VARCHAR( 20 ) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `sip`
-- 

-- ----------------------------------------------------------


-- 
-- Table structure for table `ampusers`
-- 

CREATE TABLE IF NOT EXISTS `ampusers` (
  `username` varchar(20) NOT NULL default '',
  `password` varchar(20) NOT NULL default '',
  `extension_low` varchar(20) NOT NULL default '',
  `extension_high` varchar(20) NOT NULL default '',
  `deptname` varchar(20) NOT NULL default '',
  `sections` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`username`)
) TYPE=MyISAM;

INSERT INTO ampusers (username, password, extension_low, extension_high, deptname, sections) VALUES 
 ('admin','admin','','','','*');
 
-- 
-- Table structure for table `iax`
-- 
	         
CREATE TABLE IF NOT EXISTS `iax` (
  `id` VARCHAR( 20 ) NOT NULL default '-1',
  `keyword` varchar(30) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
 ) TYPE=MyISAM;

 
-- 
-- Table structure for table `zap`
-- 

CREATE TABLE IF NOT EXISTS `zap` (
  `id` VARCHAR( 20 ) NOT NULL default '-1',
  `keyword`varchar(30) NOT NULL default '',
  `data`varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY (`id`,`keyword`)
  ) TYPE=MyISAM;
  
-- 
-- Table structure for table `queues`
-- 

CREATE TABLE IF NOT EXISTS `queues` (
  `id` bigint(11) NOT NULL default '-1',
  `keyword` varchar(20) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`,`data`)
) TYPE=MyISAM;;

CREATE TABLE IF NOT EXISTS `devices` (
`id` VARCHAR( 20 ) NOT NULL ,
`tech` VARCHAR( 10 ) NOT NULL , 
`dial` VARCHAR( 50 ) NOT NULL , 
`devicetype` VARCHAR( 5 ) NOT NULL , 
`user` VARCHAR( 50 ) , 
`description` VARCHAR( 50 ) ,
`emergency_cid` VARCHAR( 100 ) NULL
) TYPE = MYISAM ;

CREATE TABLE IF NOT EXISTS users (extension VARCHAR( 20 ) NOT NULL , password VARCHAR( 20 ) , name VARCHAR( 50 ) , voicemail VARCHAR( 50 ) , ringtimer INT(3) , noanswer VARCHAR( 100 ) , recording VARCHAR( 50 ) ,  outboundcid VARCHAR( 50 ) , directdid VARCHAR( 50 ) NULL, didalert VARCHAR ( 50 ) NULL,  faxexten VARCHAR( 20 ) , faxemail VARCHAR( 50 ) , answer TINYINT( 1 ) , wait INT( 2 ) , privacyman TINYINT( 1 ) );

--
-- Needs to be changed to their new structure, comment out for now
--
CREATE TABLE IF NOT EXISTS `incoming` ( `cidnum` VARCHAR( 20 ) , `extension` VARCHAR( 20 ) , `destination` VARCHAR( 50 ) , `faxexten` VARCHAR( 20 ) , `faxemail` VARCHAR( 50 ) , `answer` TINYINT( 1 ) , `wait` INT( 2 ) , `privacyman` TINYINT( 1 ) , alertinfo VARCHAR( 32 ) , `channel` VARCHAR( 20 ) );
--
--

CREATE TABLE IF NOT EXISTS `Backup` (`Name` varchar(50) default NULL,`Voicemail` varchar(50) default NULL,`Recordings` varchar(50) default NULL,`Configurations` varchar(50) default NULL,`CDR` varchar(55) default NULL,`FOP` varchar(50) default NULL,`Minutes` varchar(50) default NULL,`Hours` varchar(50) default NULL,`Days` varchar(50) default NULL,`Months` varchar(50) default NULL,`Weekdays` varchar(50) default NULL,`Command` varchar(200) default NULL,`Method` varchar(50) default NULL,`ID` int(11) NOT NULL auto_increment,PRIMARY KEY  (ID));

-- 
-- Create module tables
-- 

CREATE TABLE IF NOT EXISTS `modules` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`modulename` VARCHAR( 50 ) NOT NULL ,
`version` VARCHAR( 20 ) NOT NULL ,
`enabled` TINYINT NOT NULL
) TYPE = MYISAM ;

--
-- Table structure for table `featurecodes`
--

CREATE TABLE IF NOT EXISTS `featurecodes` (
  `modulename` varchar(50) NOT NULL,
  `featurename` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `defaultcode` varchar(20) default NULL,
  `customcode` varchar(20) default NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`modulename`,`featurename`),
  KEY `enabled` (`enabled`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `freepbx_log` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `time` DATETIME NOT NULL ,
  `section` VARCHAR( 50 ) NULL ,
  `level` ENUM( 'error', 'warning', 'debug' ) NOT NULL ,
  `message` TEXT NOT NULL ,
  INDEX ( `time` , `level` )
) ENGINE = MYISAM ;
