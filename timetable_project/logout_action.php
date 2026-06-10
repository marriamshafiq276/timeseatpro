<?php
/**
 * Direct logout endpoint.
 * Destroys the active session and redirects to the login page.
 */
session_start();
session_destroy();
header("Location: login.php");
exit();
?>
