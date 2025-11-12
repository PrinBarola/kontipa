<?php
// check-unique.php
// POST { type: 'email'|'phone', value: '...' }
header('Content-Type: application/json');
require_once 'includes/config.php';

$response = ['exists' => false, 'type' => null, 'value' => null, 'error' => ''];
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
    $type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : '');
    $value = isset($_POST['value']) ? trim($_POST['value']) : (isset($_GET['value']) ? trim($_GET['value']) : '');
    $response['type'] = $type; $response['value'] = $value;
    if (!$type || !$value) throw new Exception('Missing parameters');

    if (!isset($pdo) || $pdo === null) throw new Exception('Database connection not available');

    if ($type === 'email') {
        $stmt = $pdo->prepare('SELECT janitor_id FROM janitors WHERE email = ? LIMIT 1');
        $stmt->execute([$value]);
        $response['exists'] = $stmt->rowCount() > 0;
    } elseif ($type === 'phone') {
        $clean = preg_replace('/\D/', '', $value);
        $stmt = $pdo->prepare('SELECT janitor_id FROM janitors WHERE phone = ? LIMIT 1');
        $stmt->execute([$clean]);
        $response['exists'] = $stmt->rowCount() > 0;
    } else {
        throw new Exception('Unknown type');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
exit;
