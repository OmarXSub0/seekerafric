<?php
/**
 * Moolre Payment Webhook / Callback POSTs here when a payment status changes.
 */

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log for debugging (remove in production)
//file_put_contents('moolre_webhook.log', date('Y-m-d H:i:s') . ' ' . $raw . "\n", FILE_APPEND);

if (
    isset($data['status']) && $data['status'] == 1 &&
    isset($data['code']) && $data['code'] === 'P01'
) {
    $tx = $data['data'] ?? [];
    $ref = $tx['externalref'] ?? '';
    $amount = $tx['amount'] ?? '';
    $tx_id = $tx['transactionid'] ?? '';
    $payer = $tx['payer'] ?? '';

    // TODO: update your database here
    // e.g. mark order as paid using $ref
    require_once 'firebase.php';
    fs_set('payments', $ref, [
        'status' => 'paid',
        'amount' => $amount,
        'tx_id' => $tx_id,
        'payer' => $payer,
        'paid_at' => date('c'),
    ]);

    http_response_code(200);
    echo json_encode(['status' => 'received']);
} else {
    http_response_code(200); // always return 200 to Moolre
    echo json_encode(['status' => 'ignored']);
}
