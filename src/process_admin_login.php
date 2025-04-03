<?php

$valid_username = 'admin';
$valid_password = 'password'; 

session_start();

$redirect_url = 'admin_login.php?error=invalid_credentials'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    
    $submitted_username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $submitted_password = isset($_POST['password']) ? $_POST['password'] : ''; 

    
    if ($submitted_username === $valid_username && $submitted_password === $valid_password) {

        session_regenerate_id(true);

        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $submitted_username; 
        $_SESSION['is_admin'] = true; 

        $redirect_url = 'admin_dashboard.php';

    } else {
        
        $redirect_message = 'Invalid username or password.'; 
        $redirect_url = 'admin_login.php?error=' . urlencode($redirect_message);
    }

} else {
    
    $redirect_url = 'admin_login.php?error=invalid_request';
}

// Perform the redirect
header('Location: ' . $redirect_url);
exit; 
?>