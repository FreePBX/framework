
CREATE TABLE IF NOT EXISTS `applications` ( `exten` VARCHAR( 50 ) NOT NULL , `app` VARCHAR( 25 ) , `var` VARCHAR( 50 ));
INSERT INTO applications (exten, app, var) VALUES ('#', 'Directory', 'APP-DIRECTORY');
INSERT INTO applications (exten, app, var) VALUES ('*411', 'Directory Whole', 'APP-DIRECTORY-TOTAL');
INSERT INTO applications (exten, app, var) VALUES ('*78', 'DND Activate', 'APP-DND-ON');
INSERT INTO applications (exten, app, var) VALUES ('*79', 'DND Deactivate', 'APP-DND-OFF');
INSERT INTO applications (exten, app, var) VALUES ('*98', 'Message Center', 'APP-MESSAGECENTER');
INSERT INTO applications (exten, app, var) VALUES ('*97', 'Your Messages', 'APP-MESSAGECENTER-DIRECT');
INSERT INTO applications (exten, app, var) VALUES ('*70', 'CallWaiting Activate', 'APP-CALLWAITING-ON');
INSERT INTO applications (exten, app, var) VALUES ('*71', 'CallWaiting Deactivate', 'APP-CALLWAITING-OFF');
INSERT INTO applications (exten, app, var) VALUES ('*72', 'Call Forward Activate', 'APP-CALLFORWARD-ON');
INSERT INTO applications (exten, app, var) VALUES ('*73', 'Call Forward Deactivate', 'APP-CALLFORWARD-OFF');
INSERT INTO applications (exten, app, var) VALUES ('*90', 'Call Forward on Busy Activate', 'APP-CALLFORWARD-BUSY-ON');
INSERT INTO applications (exten, app, var) VALUES ('*91', 'Call Forward on Busy Deactivate', 'APP-CALLFORWARD-BUSY-OFF');
INSERT INTO applications (exten, app, var) VALUES ('*69', 'Call Trace', 'APP-CALLTRACE');
INSERT INTO applications (exten, app, var) VALUES ('*11', 'User Logon', 'APP-USERLOGON');
INSERT INTO applications (exten, app, var) VALUES ('*12', 'User Logoff', 'APP-USERLOGOFF');
