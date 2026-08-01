<?php

// Session එක ආරම්භ කිරීම
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. සියලුම Session Variables මුදා හැරීම (Free all session variables)
session_unset();

// 2. සියලුම Session Arrays හිස් කිරීම
$_SESSION = array();

// 3. Session Cookie එක බ්‍රවුසර් එකෙන් සම්පූර්ණයෙන්ම ඉවත් කිරීම
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Session එක සම්පූර්ණයෙන්ම විනාශ කිරීම
session_destroy();

// 5. පරිශීලකයා සාර්ථකව Login පිටුවට රීඩිරෙක්ට් කිරීම
header("Location: login.php");
exit();
?>