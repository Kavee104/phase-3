<?php
// Start session with optimized parameters if not already started
if (session_status() == PHP_SESSION_NONE) {
    // Session performance optimization settings
    ini_set('session.gc_maxlifetime', 1440); // Session timeout in seconds (24 minutes)
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    
    // Start the session with optimized parameters
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'read_and_close' => true,   // Read session data and close immediately to prevent locking
        'cookie_httponly' => true,  // Protect cookie from XSS attacks
        'cookie_secure' => false,   // Set to true if using HTTPS only
        'use_strict_mode' => true   // Helps prevent session fixation attacks
    ]);
}

// Database connection parameters
$host = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$database = "luminas_db";

// Create connection with persistent connection option for better performance
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Enable caching if using a newer MySQL version (can significantly improve performance)
if (version_compare($conn->server_version, '5.7.0', '>=')) {
    $conn->query("SET SESSION query_cache_type=1");
}

// Function to sanitize user inputs
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

// Function to check if user is staff
function is_staff() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'staff';
}

// Function to check if user is customer
function is_customer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'customer';
}

// Optional: Add a prepared statement helper function for easier and safer database queries
function prepare_and_execute($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

// Create a closure function for database queries with caching capability
function cached_query($conn, $sql, $cache_time = 300) {
    static $cache = [];
    $cache_key = md5($sql);
    
    // Check if query is in cache and not expired
    if (isset($cache[$cache_key]) && $cache[$cache_key]['time'] > (time() - $cache_time)) {
        return $cache[$cache_key]['result'];
    }
    
    // Run the query and store in cache
    $result = $conn->query($sql);
    if ($result) {
        $cache[$cache_key] = [
            'time' => time(),
            'result' => $result
        ];
    }
    
    return $result;
}
?>