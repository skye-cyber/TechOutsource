<?php
require('../db/server.php');
//require('logout.php');

$response = [
    "status" => "error",
"message" => "",
"data" => [],
"redirect" => null
];

function sendJson($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_POST['login'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    try {
        $username = test_input($_POST['username']);
        $password = test_input($_POST['password']);
        $usertype = test_input($_POST['usertype']);
        $table = ($usertype === 'freelancer') ? 'freelancer' : 'employer';

        if (!isset($conn)) {
            throw new Exception("Database connection is not established.");
        }

        $stmt = $conn->prepare("SELECT * FROM $table WHERE username = ? AND password = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $_SESSION['Username'] = $username;
            $_SESSION['Usertype'] = ($usertype === 'freelancer') ? 1 : 2;
            $response['status'] = "success";
            $response['message'] = "Login successful.";
            $response['redirect'] = '../' . $table . "Profile.php";
        } else {
            $response['message'] = "Username/password is incorrect.";
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    sendJson($response);
} else {
    $response['message'] = "Invalid request.";
    sendJson($response);
}

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
