<?php
// Include session management functions
require_once('../includes/session.php');

// Start the session securely
start_secure_session();

// Destroy all session data to log the user out
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");
exit(); // Always exit after a header redirect
?>
