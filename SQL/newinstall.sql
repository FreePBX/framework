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
INSERT INTO `admin` VALUES ('version','1.10.005');
-- --------------------------------------------------------

-- 
-- Table structure for table `extensions`
-- 

CREATE TABLE IF NOT EXISTS `extensions` (
  `context` varchar(20) NOT NULL default 'default',
  `extension` varchar(20) NOT NULL default '',
  `priority` int(2) NOT NULL default '1',
  `application` varchar(20) NOT NULL default '',
  `args` varchar(50) default NULL,
  `descr` text,
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`context`,`extension`,`priority`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `extensions`
-- 

;

-- --------------------------------------------------------

-- 
-- Table structure for table `globals`
-- 

CREATE TABLE IF NOT EXISTS `globals` (
  `variable` char(20) NOT NULL default '',
  `value` char(50) NOT NULL default '',
  PRIMARY KEY  (`variable`,`value`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `globals`
-- 

INSERT INTO `globals` VALUES ('CALLFILENAME', '""');
INSERT INTO `globals` VALUES ('DIAL_OPTIONS', 'tr');
INSERT INTO `globals` VALUES ('DIAL_OUT', '9');
INSERT INTO `globals` VALUES ('FAX', '');
INSERT INTO `globals` VALUES ('FAX_RX', 'system');
INSERT INTO `globals` VALUES ('FAX_RX_EMAIL', 'fax@mydomain.com');
INSERT INTO `globals` VALUES ('INCOMING', 'group-all');
INSERT INTO `globals` VALUES ('NULL', '""');
INSERT INTO `globals` VALUES ('OPERATOR', '');
INSERT INTO `globals` VALUES ('OUT', 'ZAP/g0');
INSERT INTO `globals` VALUES ('PARKNOTIFY', 'SIP/200');
INSERT INTO `globals` VALUES ('RECORDEXTEN', '""');
INSERT INTO `globals` VALUES ('RINGTIMER', '15');
INSERT INTO `globals` VALUES ('DIRECTORY', 'last');
INSERT INTO `globals` VALUES ('AFTER_INCOMING', '');
INSERT INTO `globals` VALUES ('IN_OVERRIDE', 'forcereghours');
INSERT INTO `globals` VALUES ('REGTIME', '7:55-17:05');
INSERT INTO `globals` VALUES ('REGDAYS', 'mon-fri');

-- --------------------------------------------------------

-- 
-- Table structure for table `sip`
-- 

CREATE TABLE IF NOT EXISTS `sip` (
  `id` bigint(11) NOT NULL default '-1',
  `keyword` varchar(20) NOT NULL default '',
  `data` varchar(150) NOT NULL default '',
  `flags` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`keyword`)
) TYPE=MyISAM;

-- 
-- Dumping data for table `sip`
-- 

;
