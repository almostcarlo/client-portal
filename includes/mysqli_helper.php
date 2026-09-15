<?php
/**
 * mysqli_helper.php
 *
 * PHP 5.6 compatible
 */

include_once("config.php");


// // ============================================================
// // DATABASE CONFIGURATION
// // ============================================================

// define('DB_HOST', '127.0.0.1');
// define('DB_USER', 'root');
// define('DB_PASS', 'timber3');
// define('DB_NAME', 'eastwestdb6_test');
// define('DB_PORT', 3306);


// ============================================================
// DATABASE CONNECTION
// ============================================================

function db_connect()
{
    static $conn = null;

    if ($conn === null) {

        $conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        if ($conn->connect_error) {
            throw new Exception(
                'Database connection failed: ' . $conn->connect_error
            );
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}


// ============================================================
// DETERMINE PARAMETER TYPES
// ============================================================

function get_param_types($params)
{
    $types = '';

    foreach ($params as $param) {

        if (is_int($param)) {
            $types .= 'i';

        } elseif (is_float($param)) {
            $types .= 'd';

        } else {
            $types .= 's';
        }
    }

    return $types;
}


// ============================================================
// BIND PARAMETERS
// ============================================================
//
// mysqli_stmt::bind_param() requires parameters to be passed
// by reference. call_user_func_array() is used here because
// PHP 5 does not support the modern:
//     $stmt->bind_param($types, ...$params)
// syntax.
// ============================================================

function bind_params($stmt, $types, $params)
{
    if (empty($params)) {
        return true;
    }

    $bind_params = array();

    $bind_params[] = $types;

    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }

    return call_user_func_array(
        array($stmt, 'bind_param'),
        $bind_params
    );
}


// ============================================================
// FETCH DATA
// ============================================================
//
// Returns an array of associative arrays.
//
// Example:
//
// $data = fetch_data(
//     "SELECT * FROM users WHERE status = ?",
//     array('ACTIVE'),
//     's'
// );
//
// ============================================================


function fetch_data($sql, $params = array(), $types = '', $debug = false, $key_column = null)
{
    $conn = db_connect();

    if (!empty($params) && $types === '') {
        $types = get_param_types($params);
    }

    // --------------------------------------------------------
    // DEBUG
    // --------------------------------------------------------

    if ($debug) {
        echo '<pre style="
            background:#f5f5f5;
            border:1px solid #ccc;
            padding:15px;
            font-family:monospace;
            white-space:pre-wrap;">';

        echo "================ DATABASE DEBUG ================\n\n";

        echo "SQL:\n";
        echo htmlspecialchars($sql);
        echo "\n\n";

        echo "PARAMETER TYPES:\n";
        echo htmlspecialchars($types);
        echo "\n\n";

        echo "PARAMETERS:\n";
        print_r($params);

        echo "\n";
    }

    // --------------------------------------------------------
    // PREPARE
    // --------------------------------------------------------

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        if ($debug) {
            echo "PREPARE ERROR:\n";
            echo htmlspecialchars($conn->error);
            echo "\n\n";
            echo "=================================================\n";
            echo '</pre>';
            exit;
        }

        throw new Exception(
            'Prepare failed: ' . $conn->error
        );
    }

    // --------------------------------------------------------
    // BIND PARAMETERS
    // --------------------------------------------------------

    if (!empty($params)) {

        if (!bind_params($stmt, $types, $params)) {

            $error = $stmt->error;

            if ($debug) {
                echo "BIND ERROR:\n";
                echo htmlspecialchars($error);
                echo "\n\n";
                echo "=================================================\n";
                echo '</pre>';
                exit;
            }

            $stmt->close();

            throw new Exception(
                'Parameter binding failed: ' . $error
            );
        }
    }

    // --------------------------------------------------------
    // EXECUTE
    // --------------------------------------------------------

    if (!$stmt->execute()) {

        $error = $stmt->error;

        if ($debug) {
            echo "EXECUTE ERROR:\n";
            echo htmlspecialchars($error);
            echo "\n\n";
            echo "=================================================\n";
            echo '</pre>';
            exit;
        }

        $stmt->close();

        throw new Exception(
            'Execute failed: ' . $error
        );
    }

    // --------------------------------------------------------
    // RESULT METADATA
    // --------------------------------------------------------

    $metadata = $stmt->result_metadata();

    // Query executed but has no result set
    // Example: INSERT, UPDATE, DELETE
    if (!$metadata) {

        if ($debug) {
            echo "RESULT:\n";
            echo "Query executed but returned no result set.\n";
            echo "=================================================\n";
            echo '</pre>';
            exit;
        }

        $stmt->close();

        return array();
    }

    // --------------------------------------------------------
    // PREPARE RESULT BINDING
    // --------------------------------------------------------

    $fields = $metadata->fetch_fields();

    $row = array();
    $bind_result = array();

    foreach ($fields as $field) {

        $field_name = $field->name;

        $row[$field_name] = null;
        $bind_result[] = &$row[$field_name];
    }

    call_user_func_array(
        array($stmt, 'bind_result'),
        $bind_result
    );

    // --------------------------------------------------------
    // VALIDATE KEY COLUMN
    // --------------------------------------------------------

    if ($key_column !== null) {

        $key_exists = false;

        foreach ($fields as $field) {
            if ($field->name === $key_column) {
                $key_exists = true;
                break;
            }
        }

        if (!$key_exists) {

            if ($debug) {
                echo "KEY COLUMN ERROR:\n";
                echo "Column '" . htmlspecialchars($key_column) . "' "
                   . "does not exist in the result set.\n\n";
                echo "=================================================\n";
                echo '</pre>';
                exit;
            }

            $metadata->free();
            $stmt->close();

            throw new Exception(
                "Key column '{$key_column}' does not exist in the result set."
            );
        }
    }

    // --------------------------------------------------------
    // FETCH ROWS
    // --------------------------------------------------------

    $data = array();

    while ($stmt->fetch()) {

        $record = array();

        foreach ($fields as $field) {

            $field_name = $field->name;

            $record[$field_name] = $row[$field_name];
        }

        // Use specified column as array key
        if ($key_column !== null) {

            $key = $record[$key_column];

            $data[$key] = $record;

        } else {

            $data[] = $record;
        }
    }

    // --------------------------------------------------------
    // DEBUG RESULT
    // --------------------------------------------------------

    if ($debug) {

        echo "KEY COLUMN:\n";
        echo ($key_column !== null)
            ? htmlspecialchars($key_column)
            : 'None - numeric array';
        echo "\n\n";

        echo "ROWS FOUND: " . count($data) . "\n\n";

        if (empty($data)) {

            echo "RESULT:\n";
            echo "Query executed successfully, "
               . "but no matching records were found.\n\n";

        } else {

            echo "RESULT:\n";
            print_r($data);
            echo "\n";
        }

        echo "=================================================\n";
        echo '</pre>';

        $metadata->free();
        $stmt->close();

        exit;
    }

    // --------------------------------------------------------
    // CLEANUP
    // --------------------------------------------------------

    $metadata->free();
    $stmt->close();

    return $data;
}


// ============================================================
// FETCH ONE
// ============================================================
//
// Returns one associative array or null.
//
// Example:
//
// $user = fetch_one(
//     "SELECT * FROM users WHERE username = ? LIMIT 1",
//     array($username),
//     's'
// );
//
// ============================================================

function fetch_one($sql, $params = array(), $types = '')
{
    $data = fetch_data($sql, $params, $types);

    if (!empty($data)) {
        return $data[0];
    }

    return null;
}


// ============================================================
// UPDATE / DELETE
// ============================================================
//
// Returns affected rows.
//
// Example:
//
// update_data(
//     "UPDATE users SET last_login = NOW() WHERE user_id = ?",
//     array($user_id),
//     'i'
// );
//
// ============================================================

function update_data($sql, $params = array(), $types = '')
{
    $conn = db_connect();

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            'Prepare failed: ' . $conn->error
        );
    }


    // Automatically determine parameter types
    if (!empty($params) && $types === '') {
        $types = get_param_types($params);
    }


    // Bind parameters
    if (!empty($params)) {

        if (!bind_params($stmt, $types, $params)) {

            $stmt->close();

            throw new Exception(
                'Parameter binding failed: ' . $stmt->error
            );
        }
    }


    // Execute
    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            'Execute failed: ' . $error
        );
    }


    $affected_rows = $stmt->affected_rows;

    $stmt->close();

    return $affected_rows;
}


// ============================================================
// INSERT DATA
// ============================================================
//
// Returns the newly inserted ID.
//
// Example:
//
// $user_id = insert_data(
//     "INSERT INTO users (username, password)
//      VALUES (?, PASSWORD(?))",
//     array($username, $password),
//     'ss'
// );
//
// ============================================================

function insert_data($sql, $params = array(), $types = '')
{
    $conn = db_connect();

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            'Prepare failed: ' . $conn->error
        );
    }


    // Automatically determine parameter types
    if (!empty($params) && $types === '') {
        $types = get_param_types($params);
    }


    // Bind parameters
    if (!empty($params)) {

        if (!bind_params($stmt, $types, $params)) {

            $stmt->close();

            throw new Exception(
                'Parameter binding failed: ' . $stmt->error
            );
        }
    }


    // Execute
    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            'Execute failed: ' . $error
        );
    }


    $insert_id = $stmt->insert_id;

    $stmt->close();

    return $insert_id;
}


// ============================================================
// DELETE DATA
// ============================================================
//
// Alias for update_data() to make the intent clearer.
//
// Example:
//
// delete_data(
//     "DELETE FROM users WHERE user_id = ?",
//     array($user_id),
//     'i'
// );
//
// ============================================================

function delete_data($sql, $params = array(), $types = '')
{
    return update_data($sql, $params, $types);
}