<?php
// includes/config.php
// FINAL FIXED VERSION — FULLY MATCHES YOUR DATABASE SCHEMA

declare(strict_types=1);

// ==========================
// ENVIRONMENT SETTINGS
// ==========================
$APP_ENV = getenv('APP_ENV') ?: 'development';

if ($APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

date_default_timezone_set('Asia/Manila');

// ==========================
// COMPOSER AUTOLOAD
// ==========================
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// ==========================
// DATABASE CREDENTIALS
// ==========================
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'trashbin_management';
$db_charset = 'utf8mb4';

// ==========================
// MYSQLi CONNECTION
// ==========================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = null;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset($db_charset);
} catch (mysqli_sql_exception $e) {
    error_log("[config] MySQLi error: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection error (MySQLi).");
}

// ==========================
// PDO CONNECTION (RECOMMENDED)
// ==========================
$pdo = null;

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("[config] PDO error: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection error (PDO).");
}

// ==========================
// SESSION
// ==========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================
// AUTH HELPERS (NO MORE user_id)
// ==========================
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) || isset($_SESSION['janitor_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

function isJanitor(): bool {
    return isset($_SESSION['janitor_id']);
}

function getCurrentUserId() {
    if (isset($_SESSION['admin_id'])) return $_SESSION['admin_id'];
    if (isset($_SESSION['janitor_id'])) return $_SESSION['janitor_id'];
    return null;
}

function getCurrentUserType() {
    if (isset($_SESSION['admin_id'])) return 'admin';
    if (isset($_SESSION['janitor_id'])) return 'janitor';
    return null;
}

// ==========================
// JANITOR EMPLOYEE ID GENERATOR
// ==========================
function generateEmployeeId(): string {
    global $conn;

    try {
        $conn->begin_transaction();

        $result = $conn->query(
            "SELECT MAX(CAST(SUBSTRING_INDEX(employee_id, '-', -1) AS UNSIGNED)) AS maxnum
             FROM janitors FOR UPDATE"
        );

        $row = $result->fetch_assoc();
        $max = $row['maxnum'] ?? 0;

        $next = $max + 1;
        $id = "JAN-" . str_pad((string)$next, 3, '0', STR_PAD_LEFT);

        $conn->commit();

        return $id;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("[config] generateEmployeeId error: " . $e->getMessage());
    }

    return "JAN-" . rand(1000, 9999);
}

// ==========================
// JSON OUTPUT
// ==========================
function sendJSON(array $data) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================
// DATA HELPERS (FIXED FOR NEW SCHEMA)
// ==========================

// ✅ Correct fields from your bins table (NO notes column)
function getAllBins(): array {
    global $pdo;

    $stmt = $pdo->query("
        SELECT 
            b.bin_id,
            b.bin_code,
            b.location,
            b.type,
            b.capacity,
            b.status,
            b.assigned_to,
            CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
            b.latitude,
            b.longitude,
            b.installation_date,
            b.created_at,
            b.updated_at
        FROM bins b
        LEFT JOIN janitors j ON j.janitor_id = b.assigned_to
        ORDER BY b.created_at DESC
    ");

    return $stmt->fetchAll();
}

function getBinById(int $id) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 
            b.bin_id,
            b.bin_code,
            b.location,
            b.type,
            b.capacity,
            b.status,
            b.assigned_to,
            CONCAT(j.first_name, ' ', j.last_name) AS janitor_name,
            b.latitude,
            b.longitude,
            b.installation_date,
            b.created_at,
            b.updated_at
        FROM bins b
        LEFT JOIN janitors j ON j.janitor_id = b.assigned_to
        WHERE b.bin_id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ✅ List of active janitors + assigned bins count
function getActiveJanitors(): array {
    global $pdo;

    $stmt = $pdo->query("
        SELECT 
            j.janitor_id,
            CONCAT(j.first_name, ' ', j.last_name) AS full_name,
            j.email,
            j.phone,
            j.employee_id,
            j.status,
            COUNT(b.bin_id) AS assigned_bins
        FROM janitors j
        LEFT JOIN bins b ON b.assigned_to = j.janitor_id
        WHERE j.status = 'active'
        GROUP BY j.janitor_id
        ORDER BY j.first_name ASC
    ");

    return $stmt->fetchAll();
}   