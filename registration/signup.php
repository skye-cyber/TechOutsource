<?php
require('../db/server.php');

// Initialize variables
$username = $name = $email = $password = $contactNo = $country = "";

// Prepare an array to hold response data
$response = [
    "status" => "error", // Default status
"message" => "",
"data" => []
];

if (isset($_POST["register"])) {
    // Assign POST data to variables
    $username = test_input($_POST["username"]);
    $email = test_input($_POST["email"]);
    $password = test_input($_POST["password1"]);
    $repassword = test_input($_POST["password2"]);
    $usertype = test_input($_POST["userType"]);
    $country = test_input($_POST["country"]);
    $contactNo = test_input($_POST["contactNo"]);
    $name = test_input($_POST["name"]); // <--- You missed assigning name before

    // Validate inputs
    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate password
    if (empty($password) || empty($repassword)) {
        $errors[] = "Both password fields are required.";
    } elseif ($password !== $repassword) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Validate usertype
    $allowedUsertypes = ["freelancer", "employer"];
    if (empty($usertype)) {
        $errors[] = "User type is required.";
    } elseif (!in_array($usertype, $allowedUsertypes)) {
        $errors[] = "Invalid user type selected.";
    }

    // Return validation errors if any
    if (!empty($errors)) {
        $response['message'] = "Validation error:";
        $response['errors'] = $errors;
        echo json_encode($response);
        exit();
    }

    try {
        // Check if username exists in either freelancer or employer
        $stmt = $conn->prepare("
        SELECT username FROM freelancer WHERE username = ?
        UNION
        SELECT username FROM employer WHERE username = ?
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $response['message'] = "The username is already taken.";
            echo json_encode($response);
            exit();
        }

        // Insert into correct table
        if ($usertype === "freelancer") {
            $insert = $conn->prepare("
            INSERT INTO freelancer (username, password, name, email, contactNo, country)
            VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->bind_param("ssssss", $username, $password, $name, $email, $contactNo, $country);
        } else {
            $insert = $conn->prepare("
            INSERT INTO employer (username, password, name, email, contactNo, country)
            VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->bind_param("ssssss", $username, $password, $name, $email, $contactNo, $country);
        }

        if ($insert->execute()) {
            $_SESSION["Username"] = $username;
            $_SESSION["Usertype"] = ($usertype === "freelancer") ? 1 : 2;

            $response['status'] = "success";
            $response['message'] = "Registration successful.";
            $response['redirect'] = ($usertype === "freelancer") ? "../freelancerProfile.php" : "../employerProfile.php";
            echo json_encode($response);
            exit();
        } else {
            throw new Exception("Failed to insert user.");
        }
    } catch (mysqli_sql_exception $e) {
        echo json_encode(['success' => false, 'errorMsg' => "Database query failed: " . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'errorMsg' => 'An unexpected error occurred: ' . $e->getMessage()]);
    }
}

// Function to sanitize inputs
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
