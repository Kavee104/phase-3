<?php
// Include database connection
require_once '../includes/db_connect.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect to home page
    header("Location: ../index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username/email and password
    $username_email = sanitize_input($conn, $_POST['username_email']);
    $password = $_POST['password']; // Don't sanitize password before verification
    
    // Validate inputs
    if (empty($username_email) || empty($password)) {
        $error_message = "Please enter username/email and password";
    } else {
        // Check if input is email or username
        $field = filter_var($username_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, email, password, first_name, last_name, user_type FROM users WHERE $field = ?");
        $stmt->bind_param("s", $username_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct
                // We need to re-start the session with write capability first
                session_write_close(); // Close the read-only session
                session_start(); // Start a new session with write capability
                
                // Now we can regenerate the ID safely
                session_regenerate_id(true);
                
                // Store data in session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
                
                // Redirect user
                $success_message = "Login successful! Redirecting...";
                header("refresh:1;url=../index.php");
                exit;
            } else {
                $error_message = "Invalid password";
            }
        } else {
            $error_message = "User not found";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LUMINAS HAIR & BEAUTY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <h1 class="salon-name">LUMINAS</h1>
                        <p class="salon-tagline">HAIR & BEAUTY</p>
                    </div>
                    
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="username_email" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username_email" name="username_email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>