<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection and contains test_input()

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
    $_SESSION['error_message'] = "You must be logged in to edit your profile.";
    header('Location: index.php'); // Redirect to login/home if not logged in
    exit();
}

$username = $_SESSION['Username']; // Get logged-in username

// Determine links based on logged-in user type for the navbar
$linkPro = "index.php";
$linkEditPro = "index.php";

if (isset($_SESSION["Usertype"])) {
    if ($_SESSION["Usertype"] == 1) { // Logged in as Freelancer
        $linkPro = "freelancerProfile.php"; // Link to self
        $linkEditPro = "editFreelancer.php";
    } else { // Assuming Usertype 0 for Employer - should ideally not land here
        $linkPro = "employerProfile.php";
        $linkEditPro = "editEmployer.php";
         // Optionally redirect employers away from freelancer edit page
         // header("location: employerProfile.php"); exit();
    }
} else {
     // Log error or handle differently if Usertype is missing for a logged-in user
     error_log("Usertype not set for logged-in user: " . $username);
     // Default to generic links
}


// --- Fetch Freelancer Details (Logged-in user) ---

$freelancer_data = []; // Initialize freelancer data array
$sql_fetch = "SELECT name, email, contactNo, country, prof_title, skills, profile_sum, education, experience FROM freelancer WHERE username=? LIMIT 1";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch === false) {
    error_log("Prepare failed for fetching freelancer details: " . $conn->error);
    $_SESSION['error_message'] = "Error loading your profile details."; // User-friendly error
    // $freelancer_data will remain empty
} else {
    $stmt_fetch->bind_param("s", $username); // Fetch details for the logged-in user
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows > 0) {
        $freelancer_data = $result_fetch->fetch_assoc();
        // Data fetched will be sanitized when displayed
    } else {
        // Should not happen if user is logged in, but handle defensively
        error_log("Logged-in user '$username' not found in freelancer table.");
         $_SESSION['error_message'] = "Your profile data could not be found.";
         // $freelancer_data will remain empty
    }
    $stmt_fetch->close();
}


// --- Handle Form Submission (Edit Freelancer) ---

$update_success = false;
if (isset($_POST["editFreelancer"])) {

    // Basic Server-side Validation and Sanitization
    // Assuming test_input() handles basic cleaning (trim, stripslashes, htmlspecialchars)
    $new_name = test_input($_POST["name"] ?? ''); // Use ?? '' for null coalescing
    $new_email = test_input($_POST["email"] ?? '');
    $new_contactNo = test_input($_POST["contactNo"] ?? '');
    $new_gender = test_input($_POST["gender"] ?? '');
    $new_country = test_input($_POST["country"] ?? ''); // Original used 'country' in form, but 'address' in DB
    $new_prof_title = test_input($_POST["prof_title"] ?? '');
    $new_skills = test_input($_POST["skills"] ?? '');
    $new_profile_sum = test_input($_POST["profile_sum"] ?? '');
    $new_education = test_input($_POST["education"] ?? '');
    $new_experience = test_input($_POST["experience"] ?? '');


    // Add more specific server-side validation
    if (empty($new_name) || empty($new_email) || empty($new_contactNo) || empty($new_country) || empty($new_prof_title) || empty($new_skills) || empty($new_profile_sum) || empty($new_experience) || empty($new_education)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
         // Keep current data for re-display
         $freelancer_data = [
             'name' => $new_name, 'email' => $new_email, 'contactNo' => $new_contactNo, 'country' => $new_country,
             'prof_title' => $new_prof_title, 'skills' => $new_skills, 'profile_sum' => $new_profile_sum,
             'education' => $new_education, 'experience' => $new_experience
         ];

    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
         $_SESSION['error_message'] = "Please enter a valid email address.";
          $freelancer_data = [
             'name' => $new_name, 'email' => $new_email, 'contactNo' => $new_contactNo, 'country' => $new_country,
             'prof_title' => $new_prof_title, 'skills' => $new_skills, 'profile_sum' => $new_profile_sum,
             'education' => $new_education, 'experience' => $new_experience
         ];
    }
     // Add more validation (e.g., contactNo format, birthdate format) if needed

    else {
         // Use prepared statement for the update query
        $sql_update = "UPDATE freelancer SET Name=?, email=?, contactNo=?, address=?, gender=?, prof_title=?, profile_sum=?, education=?, experience=?, birthdate=?, skills=? WHERE username=?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update === false) {
            error_log("Prepare failed for update: " . $conn->error);
            $_SESSION['error_message'] = "An internal error occurred during update preparation.";
        } else {
            // Bind parameters: sssssssssssd -> 11 strings, 1 string (username)
             // Adjusted binding based on the order in the UPDATE query
            $stmt_update->bind_param("sssssssssss",
                $new_name, $new_email, $new_contactNo, $new_country,
                $new_prof_title, $new_profile_sum, $new_education, $new_experience,
                $new_birthdate, $new_skills, $username
            );


            if ($stmt_update->execute()) {
                 $_SESSION['success_message'] = "Profile updated successfully!";
                 header("location: freelancerProfile.php"); // Redirect on success
                 exit();
            } else {
                error_log("Execute failed for update: " . $stmt_update->error);
                $_SESSION['error_message'] = "Failed to update profile. Please try again.";
                 // Keep current data for re-display if update failed
                 $freelancer_data = [
                     'name' => $new_name, 'email' => $new_email, 'contactNo' => $new_contactNo, 'country' => $new_country, 'prof_title' => $new_prof_title, 'skills' => $new_skills, 'profile_sum' => $new_profile_sum, 'education' => $new_education, 'experience' => $new_experience
                 ];
            }
            $stmt_update->close();
        }
    }

    // If there was an error, the script continues to display the form with the error message
}

// Function to sanitize inputs (assuming it's not in db/server.php)
// If test_input is already defined in db/server.php, remove this function
if (!function_exists('test_input')) {
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data); // Sanitize for display
        return $data;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo htmlspecialchars($username); ?></title>
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

        /* Consistent vibrant text colors */
         .text-heading {
             color: #4338ca; /* indigo-700 */
         }
         .dark .text-heading {
             color: #c084fc; /* purple-400 */
         }

         .text-label {
             color: #0d9488; /* teal-700 */
         }
         .dark .text-label {
             color: #67e8f9; /* cyan-400 */
         }

         .text-body-light {
             color: #374151; /* gray-700 */
         }
         .dark .text-body-dark {
             color: #d1d5db; /* gray-300 */
         }

         .border-themed-blue { border-color: #60a5fa; } /* blue-400 */
         .dark .border-themed-blue { border-color: #2563eb; } /* blue-600 */

         .border-themed-green { border-color: #86efac; } /* green-300 */
         .dark .border-themed-green { border-color: #16a34a; } /* green-600 */

         .border-themed-yellow { border-color: #fcd34d; } /* yellow-300 */
         .dark .border-themed-yellow { border-color: #a16207; } /* amber-700 */

          .border-themed-indigo { border-color: #a5b4fc; } /* indigo-300 */
          .dark .border-themed-indigo { border-color: #4f46e5; } /* indigo-600 */

          .border-themed-lime { border-color: #bef264; } /* lime-300 */
          .dark .border-themed-lime { border-color: #65a30d; } /* lime-700 */

          .border-themed-teal { border-color: #5eead4; } /* teal-300 */
          .dark .border-themed-teal { border-color: #0f766e; } /* teal-700 */


    </style>
</head>
<!-- Apply base gradient class to the body -->
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-body-light dark:text-body-dark min-h-screen pt-16 transition duration-300 ease-in-out">

    <!-- Dark mode toggle -->
    <div class="fixed top-4 right-4 z-50">
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
                            <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($username); ?> <i class="fas fa-angle-down ml-1"></i>
                        </button>
                         <!-- Use group-hover:block to show on hover for larger screens -->
                        <div id="profile-dropdown-menu" class="dropdown-menu absolute hidden bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded shadow-md mt-1 py-2 z-50 right-0 group-hover:block lg:focus-within:block">
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="<?php echo $linkPro; ?>"><i class="fas fa-home mr-2"></i> View profile</a>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="<?php echo $linkEditPro; ?>"><i class="fas fa-inbox mr-2"></i> Edit Profile</a>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="message.php"><i class="fas fa-envelope mr-2"></i> Messages</a>
                             <div class="border-b border-gray-200 dark:border-gray-600 my-2"></div>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                        </div>
                     </li>
                     <?php else: // Show login/register if not logged in ?>
                        <li class="lg:mr-3">
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="login.php">Login</a>
                        </li>
                        <li class="lg:mr-3">
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="signup.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar menu -->

    <div class="flex items-center justify-center py-12 pt-20"> <!-- Added pt-20 for spacing below fixed navbar -->
         <?php
            // Display potential success or error messages
            if (isset($_SESSION['success_message'])): ?>
                <div class="fixed top-24 left-1/2 transform -translate-x-1/2 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative z-50 w-auto max-w-sm" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['success_message']; ?></span>
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


        <div id="edit-form-container" class="w-full max-w-lg bg-white dark:bg-gray-800/70 backdrop-blur-md rounded-2xl shadow-xl p-4 transition-transform transform hover:scale-[1.01] duration-500" data-aos="zoom-in">
            <h2 class="text-3xl font-extrabold text-heading mb-2 text-center tracking-tight">Edit Your Profile</h2> <!-- Used text-heading -->

            <form id="freelancerEditForm" method="post" class="space-y-3"> <!-- Added an ID for JS validation -->

                <div data-aos="fade-up" data-aos-delay="100">
                    <label for="name" class="block text-lg font-semibold text-label">Full Name <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($freelancer_data['name'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-blue rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-1" data-aos="fade-up" data-aos-delay="200">
                    <div>
                        <label for="email" class="block text-lg font-semibold text-label">Email Address <span class="text-red-500">*</span></label> <!-- Used text-label -->
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($freelancer_data['email'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-green rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                    </div>
                    <div>
                        <label for="contactNo" class="block text-lg font-semibold text-label">Phone Number <span class="text-red-500">*</span></label> <!-- Used text-label -->
                        <input type="tel" id="contactNo" name="contactNo" value="<?php echo htmlspecialchars($freelancer_data['contactNo'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-yellow rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required pattern="^\+?[\d\s()-]{7,}" title="Enter a valid phone number"/> <!-- Added basic pattern -->
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-delay="400">
                    <label for="address" class="block text-lg font-semibold text-label">Address/Country <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($freelancer_data['country'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-lime rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                </div>

                <div data-aos="fade-up" data-aos-delay="500">
                    <label for="prof_title" class="block text-lg font-semibold text-label">Professional Title <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <input type="text" id="prof_title" name="prof_title" value="<?php echo htmlspecialchars($freelancer_data['prof_title'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-teal rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                </div>

                 <div data-aos="fade-up" data-aos-delay="600">
                     <label for="skills" class="block text-lg font-semibold text-label">Skills <span class="text-red-500">*</span></label> <!-- Used text-label -->
                     <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($freelancer_data['skills'] ?? ''); ?>" class="mt-1 w-full px-4 py-3 border border-themed-blue rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                 </div>

                 <div data-aos="fade-up" data-aos-delay="700">
                     <label for="profile_sum" class="block text-lg font-semibold text-label">Profile Summary <span class="text-red-500">*</span></label> <!-- Used text-label -->
                     <textarea id="profile_sum" name="profile_sum" rows="4" class="mt-2 w-full px-4 py-3 border border-themed-green rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required><?php echo htmlspecialchars($freelancer_data['profile_sum'] ?? ''); ?></textarea>
                 </div>

                 <div data-aos="fade-up" data-aos-delay="800">
                     <label for="education" class="block text-lg font-semibold text-label">Education</label> <!-- Used text-label -->
                     <textarea id="education" name="education" rows="4" class="mt-2 w-full px-4 py-3 border border-themed-yellow rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300"><?php echo htmlspecialchars($freelancer_data['education'] ?? ''); ?></textarea>
                 </div>

                 <div data-aos="fade-up" data-aos-delay="900">
                     <label for="experience" class="block text-lg font-semibold text-label">Experience</label> <!-- Used text-label -->
                     <textarea id="experience" name="experience" rows="4" class="mt-2 w-full px-4 py-3 border border-themed-indigo rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300"><?php echo htmlspecialchars($freelancer_data['experience'] ?? ''); ?></textarea>
                 </div>

                 <!-- Optional: Add CSRF token field -->
                 <?php // if(isset($csrf_token)): echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; endif; ?>


                <div class="text-center" data-aos="fade-up" data-aos-delay="1000">
                    <button type="submit" name="editFreelancer" class="px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold rounded-full shadow-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:shadow-lg dark:shadow-indigo-800/50">Save Changes</button>
                </div>
            </form>
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
                     <p><i class="fas fa-envelope mr-2"></i> info@yourmarketplace.com</p> <!-- Example email -->
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
        AOS.init({
            duration: 800, // animation duration
            once: false, // whether animation should happen only once - outside the viewport
        });

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
              // Note: Hover might conflict with mobile click, consider breakpoint check
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


        // Dark Mode Toggle (Integrated directly)
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;

        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('dark');
                body.classList.remove('bg-gradient-vibrant-light');
                body.classList.add('bg-gradient-vibrant-dark');
                themeToggle.checked = true;
            } else {
                body.classList.remove('dark');
                 body.classList.remove('bg-gradient-vibrant-dark');
                 body.classList.add('bg-gradient-vibrant-light');
                themeToggle.checked = false;
            }
             // Store preference (optional)
             localStorage.setItem('theme', theme);
        }

        // Apply saved theme on load
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            setTheme(savedTheme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
             // Default to system preference if no saved theme
             setTheme('dark');
        } else {
             setTheme('light'); // Default to light if no system preference or saved theme
        }

        // Listen for toggle change
        if (themeToggle) {
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    setTheme('dark');
                } else {
                    setTheme('light');
                }
            });
        }

         // Close alert messages (using onclick in HTML directly for simplicity)
         // document.querySelectorAll('[role="alert"]').forEach(function(alert) {
         //     const closeButton = alert.querySelector('svg');
         //     if (closeButton) {
         //         closeButton.parentElement.addEventListener('click', function() {
         //             alert.remove(); // or style.display = 'none';
         //         });
         //     }
         // });

        // Basic client-side form validation (replacing BootstrapValidator)
        const freelancerEditForm = document.getElementById('freelancerEditForm');
        if(freelancerEditForm) {
            freelancerEditForm.addEventListener('submit', function(event) {
                // Client-side validation logic here
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('email');
                const contactNoInput = document.getElementById('contactNo');
                 const genderInputs = document.querySelectorAll('input[name="gender"]');
                 const birthdateInput = document.getElementById('birthdate');
                 const addressInput = document.getElementById('address');
                 const profTitleInput = document.getElementById('prof_title');
                 const skillsInput = document.getElementById('skills');
                 const profileSumInput = document.getElementById('profile_sum');

                let isValid = true;
                let errorMessage = '';

                // Check required fields
                if (!nameInput.value.trim()) { isValid = false; errorMessage += 'Full Name is required.\n'; }
                if (!emailInput.value.trim()) { isValid = false; errorMessage += 'Email Address is required.\n'; }
                if (!contactNoInput.value.trim()) { isValid = false; errorMessage += 'Phone Number is required.\n'; }

                 let genderSelected = false;
                 for (const radio of genderInputs) {
                     if (radio.checked) {
                         genderSelected = true;
                         break;
                     }
                 }
                 if (!genderSelected) { isValid = false; errorMessage += 'Gender is required.\n'; }

                 if (!birthdateInput.value.trim()) { isValid = false; errorMessage += 'Date of birth is required.\n'; }
                 // Basic date format validation (HTML5 input type="date" helps, but JS adds robustness)
                 if (birthdateInput.value.trim() && !/^\d{4}-\d{2}-\d{2}$/.test(birthdateInput.value.trim())) {
                      isValid = false; errorMessage += 'Invalid date format for Date of birth (YYYY-MM-DD).\n';
                 }

                 if (!addressInput.value.trim()) { isValid = false; errorMessage += 'Address/Country is required.\n'; }
                 if (!profTitleInput.value.trim()) { isValid = false; errorMessage += 'Professional Title is required.\n'; }
                 if (!skillsInput.value.trim()) { isValid = false; errorMessage += 'Skills are required.\n'; }
                 if (!profileSumInput.value.trim()) { isValid = false; errorMessage += 'Profile Summary is required.\n'; }

                 // Add more specific format validations (e.g., phone number regex) if needed

                if (!isValid) {
                    event.preventDefault(); // Prevent form submission
                    alert('Validation errors:\n' + errorMessage); // Simple alert for now
                }
                // Note: More sophisticated validation UI would replace the alert
            });
        }


    </script>

</body>
</html>
