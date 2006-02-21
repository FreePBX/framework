CREATE TABLE IF NOT EXISTS paging_overview ( config VARCHAR(50) , detail VARCHAR(25)); 
CREATE TABLE IF NOT EXISTS paging_groups ( page_number VARCHAR(50) , ext VARCHAR(25)); 
CREATE TABLE IF NOT EXISTS paging_config ( xtn VARCHAR(50), phone_name VARCHAR(50));
CREATE TABLE IF NOT EXISTS paging_phones ( phone_name VARCHAR(50), priority INT, command VARCHAR(50));
