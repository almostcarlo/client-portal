<?php

session_start();

// Remove only the IRIS clients session
unset($_SESSION['iris-clients']);

// Optional: regenerate session ID
session_regenerate_id(true);

// Redirect back to login
header('Location: login.php');
exit;