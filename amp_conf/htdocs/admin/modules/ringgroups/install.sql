
CREATE TABLE IF NOT EXISTS `ringgroups` ( `grpnum` INT NOT NULL , `strategy` VARCHAR( 50 ) NOT NULL , `grptime` SMALLINT NOT NULL , `grppre` VARCHAR( 100 ) NULL , `grplist` VARCHAR( 255 ) NOT NULL , `annmsg` VARCHAR( 255 ) NULL , `postdest` VARCHAR( 255 ) NULL , PRIMARY KEY  (`grpnum`) ) TYPE = MYISAM ; 

