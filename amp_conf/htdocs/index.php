<?php
if (file_exists(dirname(__FILE__) . 'index_custom.php')) {
	header('Location: index_custom.php');
}
header('Location: /admin');
