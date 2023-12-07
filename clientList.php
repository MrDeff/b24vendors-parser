<?php
/**
 * @author Evgeniy Pedan (e@pedan.su)
 */

require_once 'vendor/autoload.php';
require_once 'Database.php';
require_once 'ParseVendors.php';

$db = new Database();
$parse = new ParseVendors();
$list = $parse->getClientList(APP_ID);

echo "<pre>";
print_r($list);
echo "</pre>";