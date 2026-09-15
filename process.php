<?php

session_start();

header('Content-Type: application/json');

require_once 'includes/mysqli_helper.php';


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

switch ($_POST['action']) {
    case 'add-remarks':

        $q_insert = "insert into applicant_remarks (applicant_id, remark_type, remarks, added_by, add_date)
                                            values (?, 'CL', ?, '{$_SESSION['iris-clients']['username']}', NOW())";
        $r_insert = insert_data($q_insert, [$_POST['applicant_id'], $_POST['remarks']], 'ss');

        if (!$r_insert) {

            echo json_encode(array(
                'success' => false,
                'message' => 'Unable to save remarks. Please try again later.'
            ));

            exit;
        }else{

            echo json_encode(array(
                'success'  => true,
                'message'  => 'Remarks has been saved.',
            ));

            exit;

        }
        break;
    
    // default:
    //     # code...
    //     break;
}