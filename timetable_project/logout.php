<?php
/**
 * Logout confirmation page.
 * Clears session state before redirecting the user back to login.
 */
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <script>
        // Ask for confirmation before logging out
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                // If user clicks OK, destroy the session via PHP
                window.location.href = "logout_action.php";
            } else {
                // If user clicks Cancel, go back to the dashboard
                window.location.href = "dashboard.php";
            }
        }

        // Run confirmation as soon as the page loads
        window.onload = confirmLogout;
    </script>
</head>
<body>
</body>
</html>
