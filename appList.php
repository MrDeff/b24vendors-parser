<?php
/**
 * @author Evgeniy Pedan (e@pedan.su)
 */

require_once 'vendor/autoload.php';
require_once 'Database.php';
require_once 'ParseVendors.php';

$db = new Database();
$parse = new ParseVendors();
$list = $parse->getAppList();
$dateTime = new DateTime(date('01.m.Y'));

foreach ($list as $app) {
    $db->upsertApp($app);
    if (!empty($app['id']) && $app['active']) {
        $db->clearAppClients($app['code'], $dateTime->format('Y-m-01'));
        $clientList = $parse->getClientList($app['id'], $app['code'], $dateTime);
        foreach ($clientList as $client) {
            $db->upsertAppClient($app['id'], $app['code'], $client);
        }
        sleep(1);
    }
}