<?php

session_start();

if (
    !isset($_SESSION['iris-clients']['logged_in']) ||
    $_SESSION['iris-clients']['logged_in'] !== true
) {

    http_response_code(401);

    header('Content-Type: application/json');

    echo json_encode(array(
        'success' => false,
        'message' => 'Unauthorized access.'
    ));

    exit;
}