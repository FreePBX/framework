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
