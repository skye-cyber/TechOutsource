<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection and contains test_input()

// --- Input Validation and Initialization ---
// Ensure the user is logged in AND is a freelancer to apply for a job
if (!isset($_SESSION["Username"]) || !isset($_SESSION["Usertype"]) || $_SESSION["Usertype"] != 1) {
    $_SESSION['error_message'] = "You must be logged in as a Freelancer to apply for jobs.";
    header("location: index.php"); // Redirect to login/home if not authorized
    exit();
}
$username = htmlspecialchars($_SESSION["Username"]); // Sanitize logged-in username for display
$user_type = 1; // We know they are freelancer based on the check above

// Determine navbar links for the logged-in freelancer
$linkPro = "freelancerProfile.php";
$linkEditPro = "editFreelancer.php";
$job_id = null; // Initialize job_id

// Get the job_id from session (if set) or preferably from GET
if (isset($_GET['job_id'])) {
    $job_id = htmlspecialchars($_GET['job_id']); // Sanitize immediately from GET
    $_SESSION['job_id'] = $job_id; // Optionally store in session
} elseif (isset($_SESSION["job_id"])) {
    $job_id = htmlspecialchars($_SESSION["job_id"]); // Sanitize from Session
}

// If no job_id is specified, redirect or show error
if (empty($job_id)) {
    $_SESSION['error_message'] = "Job not specified.";
    header("location: allJob.php"); // Redirect to browse jobs
    exit();
}

// --- Check if user has already applied (Securely) ---
$already_applied = false;
$sql_check_apply = "SELECT bid FROM apply WHERE job_id=? AND f_username=? LIMIT 1";
$stmt_check_apply = $conn->prepare($sql_check_apply);
if ($stmt_check_apply === false) {
    error_log("Prepare failed for checking application: " . $conn->error);
    $_SESSION['error_message'] = "Error checking previous applications.";
    // Proceed, but maybe disable the form or show error
} else {
    $stmt_check_apply->bind_param("is", $job_id, $username); // i=integer (job_id), s=string (username)
    if ($stmt_check_apply->execute()) {
        $result_check_apply = $stmt_check_apply->get_result();
        if ($result_check_apply->num_rows > 0) {
            $already_applied = true;
            $_SESSION['info_message'] = "You have already applied for this job."; // Use info message
        }
    } else {
        error_log("Execute failed for checking application: " . $stmt_check_apply->error);
        $_SESSION['error_message'] = "Error checking previous applications.";
    }
    $stmt_check_apply->close();
}

// --- Handle Job Application Submission ---
if (isset($_POST["apply"]) && !$already_applied) { // Only process if form submitted AND not already applied
    $cover = test_input($_POST["cover"] ?? ''); // Use ?? '' and test_input
    $bid = test_input($_POST["bid"] ?? '');

    // --- Secure File Upload Handling ---
    $cv_file_name = $_FILES['cv_file']['name'] ?? '';
    $cv_file_tmp = $_FILES['cv_file']['tmp_name'] ?? '';
    $cv_file_error = $_FILES['cv_file']['error'] ?? UPLOAD_ERR_NO_FILE; // Default to no file error
    $cv_file_size = $_FILES['cv_file']['size'] ?? 0;
    $cv_file_type = $_FILES['cv_file']['type'] ?? '';
    $uploaded_cv_filename = null; // To store the final, safe filename

    // Validate inputs
    if (empty($cover) || empty($bid) || $cv_file_error === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Please fill in cover letter, bid, and attach your CV.";
    } elseif (!is_numeric($bid) || $bid < 0) {
        $_SESSION['error_message'] = "Please enter a valid positive number for your bid.";
    } elseif ($cv_file_error !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "File upload error: " . $cv_file_error; // Could map error codes to messages
    } elseif ($cv_file_size > 5 * 1024 * 1024) { // Example: 5MB limit
        $_SESSION['error_message'] = "CV file is too large (max 5MB).";
    } else {
        // Validate file type (basic check)
        $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // PDF, DOCX
        $allowed_extensions = ['pdf', 'docx', 'doc'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($file_info, $cv_file_tmp);
        finfo_close($file_info);
        $file_extension = strtolower(pathinfo($cv_file_name, PATHINFO_EXTENSION));

        if (!in_array($detected_type, $allowed_types) || !in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file type. Only PDF and DOCX are allowed.";
        } else {
            // --- Generate Unique Filename and Move File Safely ---
            $upload_dir = __DIR__ . "/files/"; // Use __DIR__ for absolute path, adjust 'files/' as needed
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
            }
            $unique_filename = uniqid('', true) . '.' . $file_extension; // Generate unique name
            $destination_path = $upload_dir . $unique_filename;
            if (move_uploaded_file($cv_file_tmp, $destination_path)) {
                $uploaded_cv_filename = $unique_filename; // Store the safe filename for database

                // --- Insert Application (Securely) ---
                $sql_insert_apply = "INSERT INTO apply (f_username, job_id, bid, cv, cover_letter) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_apply = $conn->prepare($sql_insert_apply);
                if ($stmt_insert_apply === false) {
                    error_log("Prepare failed for inserting application: " . $conn->error);
                    $_SESSION['error_message'] = "An internal error occurred during application preparation.";
                    // Optional: Delete the uploaded file if DB insert fails
                    if ($uploaded_cv_filename && file_exists($destination_path)) {
                        unlink($destination_path);
                    }
                } else {
                    $bid_float = (float)$bid; // Ensure bid is treated as float/decimal for binding
                    $stmt_insert_apply->bind_param("sidss", $username, $job_id, $bid_float, $uploaded_cv_filename, $cover);
                    if ($stmt_insert_apply->execute()) {
                        $_SESSION['success_message'] = "Application submitted successfully!";
                        // Redirect to the job details page or all jobs page
                        header("location: jobDetails.php?job_id=" . urlencode($job_id)); // Redirect back to job details
                        exit();
                    } else {
                        error_log("Execute failed for inserting application: " . $stmt_insert_apply->error);
                        $_SESSION['error_message'] = "Failed to submit application. Please try again.";
                        // Optional: Delete the uploaded file if DB insert fails
                        if ($uploaded_cv_filename && file_exists($destination_path)) {
                            unlink($destination_path);
                        }
                    }
                    $stmt_insert_apply->close();
                }
            } else {
                $_SESSION['error_message'] = "Error moving uploaded file.";
            }
        }
    }
    // Redirect after POST request (even on error) to prevent form resubmission on refresh
    // Redirect back to the apply page for the same job if there was an error before DB insert
    //header("location: applyJob.php" . (!empty($job_id) ? "?job_id=" . urlencode($job_id) : ""));
    exit();
}

// Function to sanitize inputs (assuming it's not in db/server.php)
// If test_input is already defined in db/server.php, remove this function
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data); // Sanitize for display
        return $data;
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job</title>
    <!-- Tailwind CSS Link -->
    <link rel="stylesheet" href="src/css/styles.css">
    <script src='../src/js/packed_animator.js'></script>
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/smoothScroll.js'></script>
    <style>
        /* Custom gradients for body background - Slightly different / Glowy */
        .bg-gradient-vibrant-light {
            background: linear-gradient(to bottom right, #99f6e4, #c4b5fd, #fbcfe8); /* Teal, Purple, Pink */
        }
        .dark .bg-gradient-vibrant-dark {
             /* Deep blues/purples with intense highlights */
             background: linear-gradient(to bottom right, #172554, #4c1d95, #831843); /* Dark Blue, Deep Purple, Dark Pink/Maroon */
        }

         /* Text colors that 'glow' or stand out against dark background */
        .dark .text-heading-dark {
             color: #67e8f9; /* Cyan */
         }
         .dark .text-label-dark {
             color: #34d399; /* Emerald */
         }

        /* Base body styling with transitions */
        body {
            transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out;
        }

        /* Custom switch for dark mode toggle */
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

        /* Vibrant text colors for light mode */
        .text-heading-light {
             color: #4338ca; /* indigo-700 */
        }
         .text-label-light {
             color: #0d9488; /* teal-700 */
         }
         .text-body-light {
             color: #374151; /* gray-700 */
         }


         /* Combining light and dark text colors */
         .text-heading {
             @apply text-heading-light dark:text-heading-dark;
         }
         .text-label {
             @apply text-label-light dark:text-label-dark;
         }
          .text-body {
             @apply text-body-light dark:text-gray-300; /* Using gray-300 for general body in dark */
         }


    </style>
</head>
<!-- Apply base gradient class to the body -->
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-body min-h-screen pt-16 transition duration-300 ease-in-out">

    <!-- Dark mode toggle -->
    <div class="fixed top-4 right-3 z-50">
        <label class="switch">
            <input type="checkbox" id="theme-toggle">
            <span class="slider round"></span>
        </label>
    </div>

    <!-- Navbar menu -->
    <nav class="fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-md z-40 transition duration-300 ease-in-out">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a class="text-xl font-bold text-gray-800 dark:text-white" href="index.php">Freelance Marketplace</a>
            <div class="block lg:hidden">
                 <!-- Mobile menu button -->
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
                     <li class="dropdown relative group"> <!-- Added group class for hover -->
                        <button id="profile-dropdown-toggle" class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline focus:outline-none transition duration-300">
                            <i class="fas fa-user mr-1"></i> <?php echo $username; ?> <i class="fas fa-angle-down ml-1"></i>
                        </button>
                         <!-- Use group-hover:block to show on hover for larger screens -->
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
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="login.php">Login</a>
                        </li>
                        <li class="lg:mr-3">
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar menu -->

    <div class="flex items-center justify-center py-12 pt-20 min-h-screen"> <!-- Added pt-20 for spacing below fixed navbar, min-h-screen to push footer down -->
         <?php
            // Display potential success or error messages
            if (isset($_SESSION['success_message'])): ?>
                <div class="fixed top-24 left-1/2 transform -translate-x-1/2 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative z-50 w-auto max-w-sm" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['success_message'];?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                 <div class="fixed top-24 left-1/2 transform -translate-x-1/2 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative z-50 w-auto max-w-sm" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['error_message']); endif; ?>

             <?php if (isset($_SESSION['info_message'])): ?>
                 <div class="fixed top-24 left-1/2 transform -translate-x-1/2 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative z-50 w-auto max-w-sm" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['info_message']; ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-blue-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['info_message']); endif; ?>


        <div class="w-full max-w-2xl bg-white dark:bg-gray-800/70 backdrop-blur-md rounded-2xl shadow-xl p-8 transition-transform transform hover:scale-[1.005] duration-500" data-aos="zoom-in"> <!-- Adjusted max-w for a slightly wider form -->
            <h2 class="text-3xl font-extrabold text-heading mb-2 text-center tracking-tight">Apply for Job #<?php echo htmlspecialchars($job_id); ?></h2> <!-- Used text-heading -->

             <?php if ($already_applied): ?>
                 <p class="text-red-600 dark:text-red-400 text-center text-lg font-semibold mb-6" data-aos="fade-up">
                     You have already applied for this job. You cannot apply again.
                 </p>
             <?php else: ?>

            <form id="applyJobForm" method="post" class="space-y-3" enctype="multipart/form-data"> <!-- Added ID for JS validation -->
                 <!-- Optional: Add CSRF token field -->
                 <?php // if(isset($csrf_token)): echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; endif; ?>

                <div data-aos="fade-up" data-aos-delay="100">
                    <label for="cover" class="block text-lg font-semibold text-label">Cover Letter <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <textarea id="cover" name="cover" rows="12" class="mt-2 block w-full px-4 py-3 border border-blue-300 dark:border-blue-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required></textarea>
                </div>

                <div class="grid grid-cols-1 gap-3"> <!-- Two columns for CV and Bid -->
                    <div data-aos="fade-up" data-aos-delay="200">
                        <label for="cv_file" class="block text-lg font-semibold text-label">Attach a CV <span class="text-red-500">*</span></label> <!-- Used text-label -->
                         <input type="file" id="cv_file" name="cv_file" class="mt-2 block w-full text-body-light dark:text-gray-300
                             file:mr-4 file:py-2 file:px-4
                             file:rounded-full file:border-0
                             file:text-sm file:font-semibold
                             file:bg-purple-50 file:text-purple-700
                             hover:file:bg-purple-100
                             dark:file:bg-purple-900 dark:file:text-purple-200
                             dark:hover:file:bg-purple-800
                             transition-colors duration-300 ease-in-out cursor-pointer" required />
                         <p class="mt-1 text-sm text-muted">Allowed: PDF, DOCX. Max size: 5MB.</p>
                    </div>

                    <div data-aos="fade-up" data-aos-delay="300">
                        <label for="bid" class="block text-lg font-semibold text-label">Your Bid (in currency) <span class="text-red-500">*</span></label> <!-- Used text-label -->
                        <input type="number" id="bid" name="bid" step="0.01" class="mt-2 block w-full px-4 py-3 border border-green-300 dark:border-green-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required min="0" />
                         <!-- Added step="0.01" for currency -->
                    </div>
                </div>

                <div class="text-center" data-aos="fade-up" data-aos-delay="400">
                    <button type="submit" name="apply" class="px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold rounded-full shadow-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:shadow-lg dark:shadow-indigo-800/50">Submit Application</button>
                </div>
            </form>
            <?php endif; ?>

             <!-- Removed the jobs applied for card, as this page is only for applying for *one* job -->
             <!-- <div class="card">
                <div class="card-title">Jobs Applied for</div>
                <div class="card-body">

                </div>
            </div> -->
        </div>
    </div>


    <!-- Footer -->
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
                     <p><i class="fas fa-envelope mr-2"></i> TechOutsource.com</p> <!-- Example email -->
                </div>
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
    <!-- End Footer-->


    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
    // Initialize AOS for animations
    AOS.init({
        duration: 800, // animation duration
        once: false, // whether animation should happen only once - outside the viewport
    });

    // Basic Navbar Toggle for mobile
    const navToggle = document.getElementById('nav-toggle');
    const navContent = document.getElementById('nav-content');
    if (navToggle && navContent) {
        navToggle.addEventListener('click', function () {
            navContent.classList.toggle('hidden');
        });
    }

    // Profile Dropdown Toggle
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
                profileDropdownMenu.classList.add('hidden');
            }
        });
    }


    // Basic client-side form validation
    const applyJobForm = document.getElementById('applyJobForm');
    if (applyJobForm) {
        applyJobForm.addEventListener('submit', function(event) {
            const coverInput = document.getElementById('cover');
            const cvFileInput = document.getElementById('cv_file');
            const bidInput = document.getElementById('bid');
            let isValid = true;
            let errorMessage = '';

        // Check required fields
        if (!coverInput.value.trim()) {
            isValid = false;
            errorMessage += 'Cover Letter is required.\n';
        }
        if (cvFileInput.files.length === 0) {
            isValid = false;
            errorMessage += 'CV file is required.\n';
        } else {
            // Basic file type/size validation (more robust on server)
            const file = cvFileInput.files[0];
            const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // PDF, DOCX
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowedTypes.includes(file.type)) {
                isValid = false;
                errorMessage += 'Invalid file type for CV. Only PDF and DOCX are allowed.\n';
            }
            if (file.size > maxSize) {
                isValid = false;
                errorMessage += 'CV file is too large (max 5MB).\n';
            }
        }
        if (!bidInput.value.trim()) {
            isValid = false;
            errorMessage += 'Bid is required.\n';
        } else if (isNaN(bidInput.value) || parseFloat(bidInput.value) < 0) {
            isValid = false;
            errorMessage += 'Please enter a valid positive number for your bid.\n';
        }

        /*if (!isValid) {
            event.preventDefault(); // Prevent form submission
            alert('Validation errors:\n' + errorMessage); // Simple alert for now
        }*/
        });
    }
    </script>
    <!-- Removed Bootstrap/jQuery JS links -->

</body>
</html>
