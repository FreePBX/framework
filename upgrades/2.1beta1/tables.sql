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

--
-- Change primary key on globals..it is nonsense to have the key as variable,value
-- as that would allow duplicates
--

ALTER TABLE `globals` DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `variable` );

--
-- People using SVN-HEAD could get very confused by the 'applications' module that
-- was nearly going in before the work on FeatureCodes was done
-- Following line will remove 'applications' from the modules, it's a bad fix but
-- might stops loads of ppl asking on #freepbx !!!!
--

DELETE FROM modules WHERE modulename = 'applications';
