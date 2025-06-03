<?php
/**
 * @author Evgeniy Pedan (e@pedan.su)
 */

require_once 'vendor/autoload.php';
require_once 'Database.php';
require_once 'ParseVendors.php';

//$db = new YdbBase();
$dateTime = new DateTime(date('d.m.Y'));
$parse = new ParseVendors($dateTime);
$db = new Database();
// Ежедневные
$paymentList = $parse->getPayments();
if (!empty($paymentList)) {
    $db->clearPayments($dateTime->format('Y-m-01'));
    foreach ($paymentList as $paymentItem) {
        $db->upsert('payments', $paymentItem);
    }
}

// Премиальные
$premiumPaymentList = $parse->getPremiumPayments();
if (!empty($premiumPaymentList)) {
    $db->clearPremiumPayments($dateTime->format('Y-m-01'));
    foreach ($premiumPaymentList as $premiumItem) {
        $db->upsert('payments_premium', $premiumItem);
    }
}