CREATE TABLE IF NOT EXISTS ivr ( ivr_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, displayname VARCHAR(50), deptname VARCHAR(50), enable_directory VARCHAR(8), enable_directdial VARCHAR(8), timeout INT, announcement VARCHAR(10), dircontext VARCHAR ( 50 ) DEFAULT "default" );
CREATE TABLE IF NOT EXISTS ivr_dests ( ivr_id INT NOT NULL, selection VARCHAR(10), dest VARCHAR(50));
