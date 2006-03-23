CREATE TABLE IF NOT EXISTS ivr ( ivr_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, descrname VARCHAR(50), deptname VARCHAR(50), enable_directory VARCHAR(1), enable_directdial VARCHAR(1));
CREATE TABLE IF NOT EXISTS ivr_dests ( ivr_id INT NOT NULL, selection VARCHAR(10), dest_type VARCHAR(50), dest_id VARCHAR(50));
