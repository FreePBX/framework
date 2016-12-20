<?php
global $db;

if (!$db->getAll('SHOW COLUMNS FROM incoming WHERE FIELD = "id"')) {
	$sql = "ALTER TABLE `incoming` ADD COlUMN `id` int(11) NOT NULL AUTO_INCREMENT";
	$result = $db->query($sql);
}
