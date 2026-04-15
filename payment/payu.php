<?php
/**
 * PayU Payment Gateway Integration
 * Handles hash generation and payment processing
 */
require_once dirname(__DIR__) . '/config/config.php';

$key  = get_setting('payu_merchant_key','');
$salt = get_setting('payu_merchant_salt','');
$mode = get_setting('payu_mode','test');

// -------------------------------------------------------
// AJAX: Generate hash for payment initiation
// -------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'hash') {
    if (!is_logged_in() || get_user_role() !== 'student') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $fee_id  = sanitize_int($_POST['fee_id']  ?? 0);
    $amount  = sanitize_float($_POST['amount'] ?? 0);
    $invoice = sanitize($_POST['invoice']      ?? '');

    if (!$fee_id || !$amount || empty($key) || empty($salt)) {
        echo json_encode(['error' => 'Invalid parameters or PayU not configured']);
        exit;
    }

    // Verify fee belongs to this student
    $student = get_student_by_user_id((int)$_SESSION['user_id']);
    if (!$student) {
        echo json_encode(['error' => 'Student not found']);
        exit;
    }
    $fee_stmt = $pdo->prepare("SELECT * FROM fees WHERE id=? AND student_id=? AND status!='paid'");
    $fee_stmt->execute([$fee_id, $student['id']]);
    $fee = $fee_stmt->fetch();
    if (!$fee) {
        echo json_encode(['error' => 'Fee not found or already paid']);
        exit;
    }

    $txnid       = 'TXN' . time() . rand(100,999);
    $productinfo = 'School Fee: ' . $invoice;
    $firstname   = $student['name'];
    $email       = $student['email'] ?? '';
    $phone       = $student['phone'] ?? '0000000000';
    $surl        = get_setting('payu_surl', SITE_URL . '/payment/success.php');
    $furl        = get_setting('payu_furl', SITE_URL . '/payment/failure.php');

    // PayU hash: key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||salt
    $hash_str = "{$key}|{$txnid}|{$amount}|{$productinfo}|{$firstname}|{$email}|{$fee_id}||||||||||||{$salt}";
    $hash     = strtolower(hash('sha512', $hash_str));

    // Store pending transaction
    $pdo->prepare(
        "INSERT INTO transactions (fee_id,student_id,amount,txn_id,status)
         VALUES (?,?,?,'pending','pending')"
    )->execute([$fee_id, $student['id'], $amount]);
    // Temporarily store txnid in session
    $_SESSION['pending_txn'] = ['txnid'=>$txnid,'fee_id'=>$fee_id,'amount'=>$amount];

    header('Content-Type: application/json');
    echo json_encode(['hash'=>$hash,'txnid'=>$txnid]);
    exit;
}

// -------------------------------------------------------
// Verify hash on return (PayU callback verification)
// -------------------------------------------------------
function verify_payu_hash(array $response, string $salt): bool
{
    if (empty($response['hash'])) return false;

    $status      = $response['status'];
    $txnid       = $response['txnid']       ?? '';
    $amount      = $response['amount']      ?? '';
    $productinfo = $response['productinfo'] ?? '';
    $firstname   = $response['firstname']   ?? '';
    $email       = $response['email']       ?? '';
    $udf1        = $response['udf1']        ?? '';

    // Reverse hash: salt|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
    $hash_str = "{$salt}|{$status}|||||||||{$udf1}|{$email}|{$firstname}|{$productinfo}|{$amount}|{$txnid}|" . get_setting('payu_merchant_key','');
    $expected = strtolower(hash('sha512', $hash_str));
    return hash_equals($expected, strtolower($response['hash']));
}
