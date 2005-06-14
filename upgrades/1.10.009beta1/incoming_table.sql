CREATE TABLE IF NOT EXISTS `incoming` (
 `cidnum` VARCHAR( 20 ) ,
 `extension` VARCHAR( 20 ) ,
 `destination` VARCHAR( 50 ) ,
 `faxexten` VARCHAR( 20 ) ,
 `faxemail` VARCHAR( 50 ) ,
 `answer` TINYINT( 1 ) ,
 `wait` INT( 2 ) ,
 `privacyman` TINYINT( 1 ) 
);
