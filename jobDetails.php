<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection

// --- Input Validation and Initialization ---

$username = "";
$linkPro = "index.php"; // Default link if not logged in
$linkEditPro = "index.php"; // Default link
$linkBtn = "index.php"; // Default link
$textBtn = "Login to Apply"; // Default text

// Ensure the user is logged in and set user-specific links
if (isset($_SESSION["Username"])) {
    $username = htmlspecialchars($_SESSION["Username"]); // Sanitize username for display
    if (isset($_SESSION["Usertype"])) { // Check if Usertype is set
        if ($_SESSION["Usertype"] == 1) { // Freelancer
            $linkPro = "freelancerProfile.php";
            $linkEditPro = "editFreelancer.php";
            $linkBtn = "applyJob.php";
            $textBtn = "Apply for this job";
        } else { // Assuming Usertype 0 for Employer
            $linkPro = "employerProfile.php";
            $linkEditPro = "editEmployer.php";
            $linkBtn = "editJob.php"; // Link to edit job for employer
            $textBtn = "Edit the job offer";
        }
    } else {
         // Handle case where Usertype is not set but Username is (shouldn't happen with proper login)
         error_log("Usertype not set for user: " . $username);
         // Optionally redirect or handle differently
         // header("location: registration/logout.php"); exit();
    }
} else {
    // User is not logged in, links will go to index.php
    // header("location: index.php"); exit(); // Uncomment to enforce login
}

$job_id = "";
if (isset($_SESSION["job_id"])) {
    $job_id = $_SESSION["job_id"];
} else {
    // Redirect or show error if job_id is not in session
    // header("location: index.php"); exit();
    echo "Error: Job ID not specified.";
    exit(); // Stop execution if job_id is missing
}

// --- Form Submissions Handling ---

// Handle viewing freelancer profile
if (isset($_POST["f_user"])) {
    $_SESSION["f_user"] = htmlspecialchars($_POST["f_user"]); // Sanitize for session storage
    header("location: viewFreelancer.php");
    exit(); // Always exit after header redirect
}

// Handle viewing cover letter
if (isset($_POST["c_letter_content"])) { // Changed input name for clarity
    $_SESSION["c_letter"] = htmlspecialchars($_POST["c_letter_content"]); // Sanitize for session storage
    header("location: coverLetter.php");
    exit(); // Always exit after header redirect
}

// Handle hiring a freelancer (Employer action)
// Added CSRF protection basic check (assuming a token is stored in $_SESSION['csrf_token'])
if (isset($_POST["f_hire"]) && isset($_POST["f_price"]) && $_SESSION["Usertype"] != 1) {
     // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    //      die('CSRF token validation failed.'); // Basic protection
    // }

    $f_hire = htmlspecialchars($_POST["f_hire"]); // Sanitize input
    $f_price = htmlspecialchars($_POST["f_price"]); // Sanitize input

    // Validate price is a number (basic check)
    if (!is_numeric($f_price) || $f_price < 0) {
        // Log error or handle gracefully
         $_SESSION['error_message'] = "Invalid price submitted."; // Use session for messages
         header("location: jobDetails.php"); // Redirect back
         exit();
    } else {
        // Use database transaction for atomic operations
        $conn->begin_transaction();

        try {
            // Insert into selected table using prepared statement
            $sql_insert = "INSERT INTO selected (f_username, job_id, e_username, price, valid) VALUES (?, ?, ?, ?, 1)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                 throw new Exception("Prepare failed: " . $conn->error);
            }
            // 'sssd' -> s=string, d=double (for price, assuming price is decimal/float)
            // Adjust 'd' based on your database schema for price (i=integer if applicable)
            $stmt_insert->bind_param("sssd", $f_hire, $job_id, $username, $f_price);
            if (!$stmt_insert->execute()) {
                throw new Exception("Execute insert failed: " . $stmt_insert->error);
            }
            $stmt_insert->close();

            // Delete from apply table using prepared statement
            $sql_delete = "DELETE FROM apply WHERE job_id=?";
            $stmt_delete = $conn->prepare($sql_delete);
             if ($stmt_delete === false) {
                 throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt_delete->bind_param("s", $job_id); // 's' for string job_id
             if (!$stmt_delete->execute()) {
                throw new Exception("Execute delete failed: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            // Update job_offer validity using prepared statement
            $sql_update = "UPDATE job_offer SET valid=0 WHERE job_id=?";
            $stmt_update = $conn->prepare($sql_update);
             if ($stmt_update === false) {
                 throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt_update->bind_param("s", $job_id); // 's' for string job_id
            if (!$stmt_update->execute()) {
                throw new Exception("Execute update failed: " . $stmt_update->error);
            }
            $stmt_update->close();

            // Commit transaction
            $conn->commit();
             $_SESSION['success_message'] = "Freelancer hired successfully!";
            header("location: jobDetails.php"); // Redirect after successful transaction
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Hiring failed: " . $e->getMessage()); // Log the error
            $_SESSION['error_message'] = "An error occurred during the hiring process. Please try again.";
            header("location: jobDetails.php"); // Redirect back with error
            exit();
        }
    }
}

// Handle marking job as done (Employer action)
if (isset($_POST["f_done"]) && $_SESSION["Usertype"] != 1) { // Ensure only employers can mark done
     // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    //      die('CSRF token validation failed.'); // Basic protection
    // }
    // $f_done = $_POST["f_done"]; // Not used in the update query, job_id is sufficient

    // Use prepared statement for update
    $sql_update_done = "UPDATE selected SET valid=0 WHERE job_id=?";
    $stmt_update_done = $conn->prepare($sql_update_done);
    if ($stmt_update_done === false) {
        error_log("Prepare failed for f_done: " . $conn->error);
         $_SESSION['error_message'] = "An internal error occurred.";
    } else {
        $stmt_update_done->bind_param("s", $job_id); // 's' for string job_id
        if ($stmt_update_done->execute()) {
             $_SESSION['success_message'] = "Job marked as done.";
            header("location: jobDetails.php"); // Redirect after successful update
            exit();
        } else {
            error_log("Execute failed for f_done: " . $stmt_update_done->error);
             $_SESSION['error_message'] = "Failed to mark job as done. Please try again.";
        }
        $stmt_update_done->close();
    }
     header("location: jobDetails.php"); // Redirect back
     exit();
}

// --- Fetch Job Details ---

$e_username = $title = $type = $description = $budget = $skills = $special_skill = $timestamp = $deadline = "";
$jv = 1; // Default valid status

$sql_job = "SELECT e_username, title, type, description, budget, skills, special_skill, timestamp, valid, deadline FROM job_offer WHERE job_id=?";
$stmt_job = $conn->prepare($sql_job);
if ($stmt_job === false) {
    error_log("Prepare failed for job details: " . $conn->error);
    echo "Error fetching job details.";
    exit();
}
$stmt_job->bind_param("s", $job_id); // 's' for string job_id
$stmt_job->execute();
$result_job = $stmt_job->get_result();

if ($result_job->num_rows > 0) {
    $row_job = $result_job->fetch_assoc();
    // Sanitize fetched data before assigning to variables
    $e_username = htmlspecialchars($row_job["e_username"]);
    $title = htmlspecialchars($row_job["title"]);
    $type = htmlspecialchars($row_job["type"]);
    $description = htmlspecialchars($row_job["description"]);
    $budget = htmlspecialchars($row_job["budget"]);
    $skills = htmlspecialchars($row_job["skills"]);
    $special_skill = htmlspecialchars($row_job["special_skill"]);
    $timestamp = htmlspecialchars($row_job["timestamp"]);
    $jv = $row_job["valid"]; // valid is likely an integer/boolean
    $deadline = htmlspecialchars($row_job["deadline"]);
} else {
    echo "Job not found.";
    exit(); // Stop execution if job is not found
}
$stmt_job->close();

// Set message recipient session variable (sanitize)
$_SESSION["msgRcv"] = $e_username;

// --- Fetch Employer Details ---

$e_Name = $email = $contact_no = $address = "";
$sql_employer = "SELECT name, email, contactNo, country FROM employer WHERE username=?";
$stmt_employer = $conn->prepare($sql_employer);
if ($stmt_employer === false) {
     error_log("Prepare failed for employer details: " . $conn->error);
     // Continue execution, but employer details will be missing
} else {
    $stmt_employer->bind_param("s", $e_username); // 's' for string username
    $stmt_employer->execute();
    $result_employer = $stmt_employer->get_result();

    if ($result_employer->num_rows > 0) {
        $row_employer = $result_employer->fetch_assoc();
        // Sanitize fetched data before assigning to variables
        $e_Name = htmlspecialchars($row_employer["name"]);
        $email = htmlspecialchars($row_employer["email"]);
        $contact_no = htmlspecialchars($row_employer["contactNo"]);
        $address = htmlspecialchars($row_employer["country"]); // Assuming 'country' is used for address
    } else {
        // Employer details not found, handle this case (e.g., display default or error)
        // echo "Employer details not found."; // Avoid outputting this directly, handle visually
    }
    $stmt_employer->close();
}

// Generate a new CSRF token for the form (basic implementation)
// if (empty($_SESSION['csrf_token'])) {
//     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// }
// $csrf_token = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - <?php echo $title; ?></title>
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/packed_animator.js'></script>
    <link rel="stylesheet" href="../src/css/styles.css">
    <style>
        /* Custom gradients for body background */
        .bg-gradient-vibrant-light {
            background: linear-gradient(to bottom right, #a78bfa, #f87171); /* Example vibrant light gradient (purple-400 to red-400) */
        }
        .dark .bg-gradient-vibrant-dark {
             background: linear-gradient(to bottom right, #4f46e5, #e879f9); /* Example vibrant dark gradient (indigo-600 to fuchsia-500) */
        }

        /* Base body styling with transitions */
        body {
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
        }

        /* Custom switch for dark mode toggle - keep if not handled by styles.css */
         .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
         }

         .switch input {
            opacity: 0;
            width: 0;
            height: 0;
         }

         .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
         }

         .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
         }

         input:checked + .slider {
            background-color: #2196F3;
         }

         input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
         }

         input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
         }

         .slider.round {
            border-radius: 34px;
         }

         .slider.round:before {
            border-radius: 50%;
         }

        /* Style for form buttons within table cells to look like links */
         .btn-link-form {
             background: none;
             border: none;
             padding: 0;
             color: #3b82f6; /* Tailwind blue-500 */
             cursor: pointer;
             text-decoration: underline;
         }
         .dark .btn-link-form {
             color: #60a5fa; /* Tailwind blue-400 */
         }
         .btn-link-form:hover {
             text-decoration: none;
         }
         .dark .btn-link-form:hover {
             color: #90cdf4; /* Tailwind blue-300 */
         }
    </style>
</head>
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-gray-900 dark:text-gray-100 min-h-screen pt-16 transition duration-300 ease-in-out">

    <div class="fixed top-4 right-3 z-50 ml-2">
        <label class="switch">
            <input type="checkbox" id="theme-toggle">
            <span class="slider round"></span>
        </label>
    </div>

    <nav class="fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-md z-40 transition duration-300 ease-in-out">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a class="text-xl font-bold text-gray-800 dark:text-white" href="index.php">Freelance Marketplace</a>
            <div class="block lg:hidden">
                 <button id="nav-toggle" class="flex items-center px-3 py-2 border rounded text-gray-500 border-gray-600 hover:text-gray-800 hover:border-teal-500 appearance:none focus:outline-none">
                     <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
                 </button>
            </div>
            <div class="w-full flex-grow lg:flex lg:items-center lg:w-auto hidden lg:block" id="nav-content">
                <ul class="pt-4 lg:pt-0 list-reset lg:flex justify-end flex-1 items-center">
                    <li class="lg:mr-3">
                        <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="allJob.php">Browse all jobs</a>
                    </li>
                    <li class="lg:mr-3">
                        <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="allFreelancer.php">Browse Freelancers</a>
                    </li>
                    <li class="lg:mr-3">
                        <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="allEmployer.php">Browse Employers</a>
                    </li>
                     <?php if ($username): // Only show dropdown if logged in ?>
                     <li class="dropdown relative group"> <button id="profile-dropdown-toggle" class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline focus:outline-none transition duration-300">
                            <i class="fas fa-user mr-1"></i> <?php echo $username; ?> <i class="fas fa-angle-down ml-1"></i>
                        </button>
                         <div id="profile-dropdown-menu" class="dropdown-menu absolute hidden bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded shadow-md mt-1 py-2 z-50 right-0 group-hover:block lg:focus-within:block">
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="<?php echo $linkPro; ?>"><i class="fas fa-home mr-2"></i> View profile</a>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="<?php echo $linkEditPro; ?>"><i class="fas fa-inbox mr-2"></i> Edit Profile</a>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="message.php"><i class="fas fa-envelope mr-2"></i> Messages</a>
                             <div class="border-b border-gray-200 dark:border-gray-600 my-2"></div>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="registration/logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                        </div>
                     </li>
                     <?php else: // Show login/register if not logged in ?>
                        <li class="lg:mr-3">
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="login.html">Login</a>
                        </li>
                        <li class="lg:mr-3">
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="signup.html">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mx-auto px-4 py-8">
         <?php
            // Display potential success or error messages
            if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['error_message']); endif; ?>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-right">
                    <h3 class="text-2xl font-semibold mb-4 text-indigo-700 dark:text-purple-400">Job Offer Details</h3>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Job Title</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $title; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Job Type</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $type; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Job Description</h4>
                        <p class="text-gray-800 dark:text-gray-200 leading-relaxed"><?php echo nl2br($description); ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Budget</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $budget; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Required Skills</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $skills; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Special Requirement</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $special_skill; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-teal-700 dark:text-cyan-400">Deadline</h4>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $deadline; ?></p>
                    </div>
                     <?php if ($username && (!isset($_SESSION["Usertype"]) || $_SESSION["Usertype"] == 1)): // Only show apply button for logged-in freelancers ?>
                         <?php if ($jv == 1): // Only show if job is open ?>
                             <a href="<?php echo $linkBtn; ?>" class="mt-4 inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105" id="applybtn">
                                 <?php echo $textBtn; ?>
                             </a>
                         <?php else: ?>
                              <span class="mt-4 inline-block bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-lg">Job Closed</span>
                         <?php endif; ?>
                     <?php elseif ($username && isset($_SESSION["Usertype"]) && $_SESSION["Usertype"] != 1 && $username == $e_username): // Show edit button for the employer who posted ?>
                          <a href="<?php echo $linkBtn; ?>" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                             <?php echo $textBtn; ?>
                         </a>
                     <?php endif; ?>
                </div>

                <?php if ($username == $e_username && isset($_SESSION["Usertype"]) && $_SESSION["Usertype"] != 1): ?>
                <div id="applicant" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-left">
                    <h3 class="text-2xl font-semibold mb-4 text-indigo-700 dark:text-purple-400">Applicants for this job</h3>
                    <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse table-auto">
                        <thead>
                            <tr class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                                <th class="px-4 py-3 text-left border-b border-gray-300 dark:border-gray-600">Applicant</th>
                                <th class="px-4 py-3 text-left border-b border-gray-300 dark:border-gray-600">Bid</th>
                                <th class="px-4 py-3 text-left border-b border-gray-300 dark:border-gray-600">CV</th>
                                <th class="px-4 py-3 text-left border-b border-gray-300 dark:border-gray-600">Cover Letter</th>
                                <th class="px-4 py-3 text-left border-b border-gray-300 dark:border-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch applications if the job is still open (valid=1)
                            if ($jv == 1) {
                                $sql_apply = "SELECT f_username, bid, cv, cover_letter FROM apply WHERE job_id=? ORDER BY bid ASC";
                                $stmt_apply = $conn->prepare($sql_apply);
                                if ($stmt_apply === false) {
                                    error_log("Prepare failed for apply listing: " . $conn->error);
                                    echo "<tr><td colspan='5' class='px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-center text-red-500 dark:text-red-400'>Error loading applicants.</td></tr>";
                                } else {
                                    $stmt_apply->bind_param("s", $job_id);
                                    $stmt_apply->execute();
                                    $result_apply = $stmt_apply->get_result();

                                    if ($result_apply->num_rows > 0) {
                                        while ($row_apply = $result_apply->fetch_assoc()) {
                                            // Sanitize fetched data
                                            $applicant_username = htmlspecialchars($row_apply["f_username"]);
                                            $bid = htmlspecialchars($row_apply["bid"]);
                                            $cv_filename = htmlspecialchars($row_apply["cv"]);
                                            $cover_letter_content = htmlspecialchars($row_apply["cover_letter"]); // Fetch the content

                                            echo '<tr>';
                                            // View Freelancer Profile Button (Vibrant link color)
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">';
                                            echo '<form action="jobDetails.php" method="post" class="inline-block">';
                                            // echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                                            echo '<input type="hidden" name="f_user" value="' . $applicant_username . '">';
                                            echo '<button type="submit" class="btn-link-form text-purple-600 hover:text-purple-800 dark:text-pink-400 dark:hover:text-pink-300 transition duration-300">' . $applicant_username . '</button>';
                                            echo '</form>';
                                            echo '</td>';

                                            // Bid
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">' . $bid . '</td>';

                                            // CV Link (ensure proper file path and security) (Vibrant link color)
                                            $cv_path = 'files/' . $cv_filename; // Adjust path as needed
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
                                            if (!empty($cv_filename) && file_exists($cv_path)) { // Check if file exists
                                                echo '<a title="click to download" aria-label="click to download" href="' . $cv_path . '" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline dark:text-blue-400 transition duration-300">' . $cv_filename . '</a>';
                                            } else {
                                                 echo '<span class="text-gray-500 dark:text-gray-400">N/A</span>'; // Indicate no CV or file not found
                                            }
                                            echo '</td>';

                                            // Cover Letter Button (Vibrant link color)
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
                                            if (!empty($cover_letter_content) || $cv_path) {
                                                echo '<form action="jobDetails.php" method="post" title="click to view" aria-label="click to view" class="inline-block">';
                                                // Pass cover letter content in a hidden input (careful with large content/special chars)
                                                echo '<input type="hidden" name="c_letter_content" value="' . htmlspecialchars($cv_path) . '">'; // HTML entity encode content for value attribute
                                                echo '<button type="submit" class="btn-link-form text-purple-600 hover:text-purple-800 dark:text-pink-400 dark:hover:text-pink-300 transition duration-300">View Cover Letter</button>';
                                                echo '</form>';
                                            } else {
                                                echo '<span class="text-gray-500 dark:text-gray-400">N/A</span>'; // Indicate no cover letter
                                            }
                                            echo '</td>';

                                            // Action (Hire Button)
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
                                            echo '<form action="jobDetails.php" method="post" onsubmit="return confirm(\'Are you sure you want to hire ' . addslashes($applicant_username) . ' for this job?\');">';
                                            // echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; // Add CSRF token
                                            echo '<input type="hidden" name="f_hire" value="' . $applicant_username . '">';
                                            echo '<input type="hidden" name="f_price" value="' . $bid . '">'; // Pass bid as price
                                            echo '<button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-sm font-bold py-1 px-3 rounded transition duration-300 ease-in-out transform hover:scale-105">Hire</button>';
                                            echo '</form>';
                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-center text-gray-600 dark:text-gray-400'>No applicants yet.</td></tr>";
                                    }
                                    $stmt_apply->close();
                                }
                            } else {
                                // If job is not valid (already completed/closed), show the selected freelancer
                                $sql_selected = "SELECT f_username, price, valid FROM selected WHERE job_id=?";
                                $stmt_selected = $conn->prepare($sql_selected);
                                if ($stmt_selected === false) {
                                     error_log("Prepare failed for selected listing: " . $conn->error);
                                     echo "<tr><td colspan='5' class='px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-center text-red-500 dark:text-red-400'>Error loading selected freelancer.</td></tr>";
                                } else {
                                    $stmt_selected->bind_param("s", $job_id);
                                    $stmt_selected->execute();
                                    $result_selected = $stmt_selected->get_result();

                                    if ($result_selected->num_rows > 0) {
                                        while ($row_selected = $result_selected->fetch_assoc()) {
                                            // Sanitize fetched data
                                            $selected_username = htmlspecialchars($row_selected["f_username"]);
                                            $selected_price = htmlspecialchars($row_selected["price"]);
                                            $selected_valid = $row_selected["valid"];

                                            $tc = ($selected_valid == 0) ? "Job Ended" : "End Job";

                                             echo '<tr>';
                                            // View Freelancer Profile Button (Vibrant link color)
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">';
                                            echo '<form action="jobDetails.php" method="post" class="inline-block">';
                                            // echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                                            echo '<input type="hidden" name="f_user" value="' . $selected_username . '">';
                                            echo '<button type="submit" class="btn-link-form text-purple-600 hover:text-purple-800 dark:text-pink-400 dark:hover:text-pink-300 transition duration-300">' . $selected_username . '</button>';
                                            echo '</form>';
                                            echo '</td>';

                                            // Price
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200">' . $selected_price . '</td>';

                                            // CV and Cover Letter columns - N/A for selected view
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700"><span class="text-gray-500 dark:text-gray-400">N/A</span></td>';
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700"><span class="text-gray-500 dark:text-gray-400">N/A</span></td>';

                                            // Action (End Job Button)
                                            echo '<td class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">';
                                             if ($selected_valid != 0) { // Only show "End Job" if not already ended
                                                echo '<form action="jobDetails.php" method="post" onsubmit="return confirm(\'Are you sure you want to mark this job as done?\');">';
                                                // echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; // Add CSRF token
                                                echo '<input type="hidden" name="f_done" value="' . $selected_username . '">'; // Pass username if needed, job_id is used in query
                                                echo '<button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm font-bold py-1 px-3 rounded transition duration-300 ease-in-out transform hover:scale-105">' . $tc . '</button>';
                                                echo '</form>';
                                            } else {
                                                echo '<span class="text-gray-600 dark:text-gray-400">' . $tc . '</span>'; // Display "Job Ended" as text
                                            }
                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                    } else {
                                         echo "<tr><td colspan='5' class='px-4 py-3 border-b border-gray-200 dark:border-gray-700 text-center text-gray-600 dark:text-gray-400'>No freelancer selected for this job.</td></tr>";
                                    }
                                    $stmt_selected->close();
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                 <?php endif; // End employer specific applicant section ?>
            </div>
            <div class="md:col-span-1">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6 text-center" data-aos="fade-left">
                    <img src="image/img04.jpg" alt="Profile Picture" class="rounded-full mx-auto mb-4 w-32 h-32 object-cover border-4 border-purple-500 dark:border-pink-500">
                    <h2 class="text-xl font-semibold mb-2 text-indigo-700 dark:text-purple-400"><?php echo $e_Name; ?></h2>
                    <p class="mb-2 text-gray-600 dark:text-gray-400"><i class="fas fa-user mr-2"></i> <?php echo $e_username; ?></p>
                     <?php if ($username && $username != $e_username): // Only show message button if logged in and not viewing own profile ?>
                    <a href="sendMessage.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out mt-4 transform hover:scale-105">
                         <i class="fas fa-envelope mr-2"></i> Send Message
                     </a>
                     <?php endif; ?>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-up">
                    <h4 class="text-lg font-medium mb-4 text-indigo-700 dark:text-purple-400">Contact Information</h4>
                    <div class="mb-3">
                        <h5 class="font-medium text-teal-700 dark:text-cyan-400">Email</h5>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $email; ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="font-medium text-teal-700 dark:text-cyan-400">Mobile</h5>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $contact_no; ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="font-medium text-teal-700 dark:text-cyan-400">Address</h5>
                        <p class="text-gray-800 dark:text-gray-200"><?php echo $address; ?></p>
                    </div>
                </div>

                 <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="text-xl font-semibold mb-4 text-indigo-700 dark:text-purple-400">Related Jobs</h3>
                    <p class="text-gray-700 dark:text-gray-300">Related jobs will be listed here...</p>
                    </div>

            </div>
            </div>
    </div>
    <footer class="bg-gray-200 dark:bg-gray-900 text-gray-800 dark:text-gray-200 py-8 mt-8 shadow-inner">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center md:text-left">
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Quick Links</h3>
                    <ul>
                        <li><a href="index.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition duration-300">Home</a></li>
                        <li><a href="allJob.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition duration-300">Browse all jobs</a></li>
                        <li><a href="allFreelancer.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition duration-300">Browse Freelancers</a></li>
                        <li><a href="allEmployer.php" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 transition duration-300">Browse Employers</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">About Us</h3>
                    <p>Freelance Marketplace</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Connecting talent with opportunity.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Contact Us</h3>
                    <p><i class="fas fa-phone-alt mr-2"></i> +254 725 146 071</p>
                    <p><i class="fas fa-map-marker-alt mr-2"></i> Nairobi, Kenya</p>
                     <p><i class="fas fa-envelope mr-2"></i> info@yourmarketplace.com</p> </div>
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Follow Us</h3>
                    <div class="flex justify-center md:justify-start space-x-4 text-2xl">
                         <a href="#" class="text-blue-700 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200 transition duration-300"><i class="fab fa-facebook-square"></i></a>
                         <a href="#" class="text-red-700 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 transition duration-300"><i class="fab fa-google-plus-square"></i></a>
                         <a href="#" class="text-cyan-600 hover:text-cyan-800 dark:text-cyan-400 dark:hover:text-cyan-200 transition duration-300"><i class="fab fa-twitter-square"></i></a>
                         <a href="#" class="text-blue-800 hover:text-blue-900 dark:text-blue-500 dark:hover:text-blue-300 transition duration-300"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
             <div class="mt-8 text-sm text-center text-gray-600 dark:text-gray-400">
                 <p>&copy; <?php echo date("Y"); ?> Freelance Marketplace. All rights reserved.</p>
             </div>
        </div>
    </footer>
    <script>

        // Basic Navbar Toggle for mobile (requires JS)
        const navToggle = document.getElementById('nav-toggle');
        const navContent = document.getElementById('nav-content');
        if (navToggle && navContent) {
            navToggle.addEventListener('click', function () {
                navContent.classList.toggle('hidden');
            });
        }

         // Profile Dropdown Toggle (requires JS)
         const profileDropdownToggle = document.getElementById('profile-dropdown-toggle');
         const profileDropdownMenu = document.getElementById('profile-dropdown-menu');
         if (profileDropdownToggle && profileDropdownMenu) {
             profileDropdownToggle.addEventListener('click', function() {
                 profileDropdownMenu.classList.toggle('hidden');
             });

             // Close dropdown if clicked outside
             document.addEventListener('click', function(event) {
                 if (!profileDropdownToggle.contains(event.target) && !profileDropdownMenu.contains(event.target)) {
                     profileDropdownMenu.classList.add('hidden');
                 }
             });
             // Optional: Close dropdown on hover for larger screens
             profileDropdownToggle.parentElement.addEventListener('mouseenter', function() {
                 if (window.innerWidth >= 1024) { // Tailwind 'lg' breakpoint
                     profileDropdownMenu.classList.remove('hidden');
                 }
             });
              profileDropdownToggle.parentElement.addEventListener('mouseleave', function() {
                  if (window.innerWidth >= 1024) { // Tailwind 'lg' breakpoint
                      // Add a slight delay or check relatedTarget if needed to prevent flickering
                      profileDropdownMenu.classList.add('hidden');
                  }
             });
         }


         // Close alert messages (using onclick in HTML directly for simplicity)
         document.querySelectorAll('[role="alert"]').forEach(function(alert) {
             const closeButton = alert.querySelector('svg');
             if (closeButton) {
                 closeButton.parentElement.addEventListener('click', function() {
                     alert.remove(); // or style.display = 'none';
                 });
             }
         });

    </script>
</body>
</html>
