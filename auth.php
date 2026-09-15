<?php

session_start();

header('Content-Type: application/json');

require_once 'includes/mysqli_helper.php';

// ============================================================
// VALIDATE CLOUDFLARE TURNSTILE
// ============================================================
function validate_turnstile($token, $remote_ip = null)
{
    $secret_key = CF_SECRET_KEY; // Make sure CF_SECRET_KEY is defined in config
    $verify_url = CF_URL;        // Make sure CF_URL is defined in config

    if (empty($token)) {
        return false;
    }

    $data = [
        'secret'   => $secret_key,
        'response' => $token,
    ];

    if ($remote_ip !== null) {
        $data['remoteip'] = $remote_ip;
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5,
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($verify_url, false, $context);

    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    return !empty($result['success']);
}


// ============================================================
// ONLY ALLOW POST REQUEST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid request method.'
    ));

    exit;
}


// ============================================================
// GET LOGIN DETAILS
// ============================================================

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';


// ============================================================
// GET CLOUDFLARE TOKEN
// ============================================================

$turnstile_token = isset($_POST['cf-turnstile-response'])
    ? $_POST['cf-turnstile-response']
    : '';


// ============================================================
// VALIDATE INPUT
// ============================================================

if ($username === '' || $password === '') {

    echo json_encode(array(
        'success' => false,
        'message' => 'Please enter your username and password.'
    ));

    exit;
}


// ============================================================
// VALIDATE CLOUDFLARE TURNSTILE
// ============================================================

if (!validate_turnstile($turnstile_token)) {

    echo json_encode(array(
        'success' => false,
        'message' => 'Security verification failed. Please try again.'
    ));

    exit;
}


try {

    // ========================================================
    // AUTHENTICATE USER
    // ========================================================

    $user = fetch_one(
        "SELECT
            id,
            username,
            manpower_rid,
            branch_id
         FROM client_portal_users
         WHERE username = ?
           AND `password` = PASSWORD(?)
         LIMIT 1",
        array($username, $password),
        'ss'
    );


    // ========================================================
    // INVALID LOGIN
    // ========================================================

    if (!$user) {

        echo json_encode(array(
            'success' => false,
            'message' => 'Invalid username or password.'
        ));

        exit;
    }


    // ========================================================
    // LOGIN SUCCESSFUL
    // ========================================================

    session_regenerate_id(true);

    $_SESSION['iris-clients']['logged_in'] = true;
    $_SESSION['iris-clients']['user_id']   = $user['id'];
    $_SESSION['iris-clients']['username']  = $user['username'];
    $_SESSION['iris-clients']['manpower_rid']  = $user['manpower_rid'];
    $_SESSION['iris-clients']['branch']  = $user['branch_id'];

    // ========================================================
    // OPTIONAL: UPDATE LAST LOGIN
    // ========================================================

    update_data(
        "UPDATE client_portal_users
         SET last_login = NOW()
         WHERE id = ?",
        array($user['id']),
        'i'
    );


    // ========================================================
    // RETURN SUCCESS
    // ========================================================

    echo json_encode(array(
        'success'  => true,
        'message'  => 'Login successful.',
        'redirect' => 'index.php'
    ));

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode(array(
        'success' => false,
        'message' => 'Unable to process login.'
    ));

    // For debugging:
    // error_log($e->getMessage());
}