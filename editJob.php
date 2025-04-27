<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection and contains test_input()

// Ensure the user is logged in
if (!isset($_SESSION["Username"])) {
    // User is not logged in, redirect to login or home page
    header("location: index.php"); // Or login.php
    exit();
}

$username = htmlspecialchars($_SESSION["Username"]); // Sanitize username for display

// Ensure job_id is set in session
if (!isset($_SESSION["job_id"])) {
    // Job ID not specified, redirect or show error
    header("location: index.php"); // Or a page listing jobs
    exit();
}

$job_id = $_SESSION["job_id"]; // job_id from session

// --- Fetch Existing Job Details ---
$title = $type = $description = $budget = $skills = $special_skill = "";
$sql_fetch = "SELECT title, type, description, budget, skills, special_skill, e_username FROM job_offer WHERE job_id=? LIMIT 1"; // Added e_username to check ownership
$stmt_fetch = $conn->prepare($sql_fetch);
if ($stmt_fetch === false) {
    error_log("Prepare failed for fetching job details: " . $conn->error);
    die("Error fetching job details."); // Fatal error for now
}
$stmt_fetch->bind_param("s", $job_id); // 's' for string job_id
$stmt_fetch->execute();
$result_fetch = $stmt_fetch->get_result();
if ($result_fetch->num_rows > 0) {
    $row = $result_fetch->fetch_assoc();
    // Check if the logged-in user is the employer who posted this job
    if ($row["e_username"] !== $username) {
        // User is logged in but not the owner of the job
        // Redirect or show an access denied message
        header("location: jobDetails.php?job_id=" . $job_id); // Redirect back to details page
        exit();
    }
    // Sanitize fetched data for display in the form
    $title = htmlspecialchars($row["title"]);
    $type = htmlspecialchars($row["type"]);
    $description = htmlspecialchars($row["description"]);
    $budget = htmlspecialchars($row["budget"]);
    $skills = htmlspecialchars($row["skills"]);
    $special_skill = htmlspecialchars($row["special_skill"]);
} else {
    // Job ID not found in database
    echo "Job not found.";
    exit();
}
$stmt_fetch->close();

// --- Handle Form Submission (Edit Job) ---
if (isset($_POST["editJob"])) {
    // Basic Server-side Validation and Sanitization
    // Assuming test_input() handles basic cleaning (trim, stripslashes, htmlspecialchars)
    $new_title = test_input($_POST["title"]);
    $new_type = test_input($_POST["type"]);
    $new_description = test_input($_POST["description"]);
    $new_budget = test_input($_POST["budget"]);
    $new_skills = test_input($_POST["skills"]);
    $new_special_skill = test_input($_POST["special_skill"]);

    // Add more specific validation (e.g., budget is numeric, fields are not empty after test_input)
    if (empty($new_title) || empty($new_type) || empty($new_description) || empty($new_budget) || empty($new_skills)) {
        $_SESSION['error_message'] = "Required fields cannot be empty.";
        // The form below will be rendered with old values, which is acceptable for edit
    } elseif (!is_numeric($new_budget) || $new_budget < 0) {
        $_SESSION['error_message'] = "Budget must be a valid positive number.";
    } else {
        // Use prepared statement for the update query
        $sql_update = "UPDATE job_offer SET title=?, type=?, description=?, budget=?, skills=?, special_skill=?, valid=1 WHERE job_id=? AND e_username=?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) {
            error_log("Prepare failed for update: " . $conn->error);
            $_SESSION['error_message'] = "An internal error occurred during update preparation.";
        } else {
            // Bind parameters: sssssss -> 6 strings, 1 string (job_id), 1 string (username)
            // Adjust 'd' for budget if it's a decimal/float in your DB, and the corresponding bind type
            $stmt_update->bind_param("ssssssss", $new_title, $new_type, $new_description, $new_budget, $new_skills, $new_special_skill, $job_id, $username);
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Job offer updated successfully!";
                header("location: jobDetails.php"); // Redirect on success
                exit();
            } else {
                error_log("Execute failed for update: " . $stmt_update->error);
                $_SESSION['error_message'] = "Failed to update job offer. Please try again.";
                // The form below will be rendered with old values, which is acceptable for edit
            }
            $stmt_update->close();
        }
    }
}

// Generate a new CSRF token for the form (basic implementation)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Function to sanitize inputs
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Offer - <?php echo $title; ?></title>
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

        /* Consistent vibrant text colors */
        .text-heading {
             color: #4338ca; /* indigo-700 */
             /* Dark mode equivalent */
             --tw-text-opacity: 1;
             color: rgb(192 132 252 / var(--tw-text-opacity)); /* purple-400 */
        }
         .dark .text-heading {
             color: #c084fc; /* purple-400 */
         }

         .text-label {
             color: #0d9488; /* teal-700 */
              /* Dark mode equivalent */
             --tw-text-opacity: 1;
             color: rgb(103 232 249 / var(--tw-text-opacity)); /* cyan-400 */
         }
         .dark .text-label {
              color: #67e8f9; /* cyan-400 */
         }

         .text-body {
             color: #374151; /* gray-700 */
              /* Dark mode equivalent */
             --tw-text-opacity: 1;
             color: rgb(209 213 219 / var(--tw-text-opacity)); /* gray-300 */
         }
         .dark .text-body {
             color: #d1d5db; /* gray-300 */
         }

    </style>
</head>
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-body min-h-screen pt-16 transition duration-300 ease-in-out">

    <div class="fixed top-4 right-3 z-50">
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
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="employerProfile.php"><i class="fas fa-home mr-2"></i> View profile</a>
                             <a class="block px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 no-underline text-sm transition duration-300" href="editEmployer.php"><i class="fas fa-inbox mr-2"></i> Edit Profile</a>
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
                            <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="register.php">Register</a>
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
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert" data-aos="fade-down">
                    <span class="block sm:inline"><?php echo $_SESSION['error_message']; ?></span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15L6.306 6.058a1.2 1.2 0 1 1 1.697-1.697l2.651 3.029 2.651-3.029a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.15 2.758 3.15a1.2 1.2 0 0 1 0 1.697z"/></svg>
                    </span>
                </div>
            <?php unset($_SESSION['error_message']); endif; ?>

        <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 p-2 rounded-lg shadow-lg" data-aos="zoom-in">
        <h2 class="text-2xl font-semibold mb-6 text-heading text-center">Edit Job Offer</h2>

            <form method="post">
                <div class="mb-2">
                    <label for="title" class="block text-label text-sm font-medium mb-2">Job Title</label>
                    <input type="text" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600" name="title" value="<?php echo $title; ?>" required>
                </div>

                <div class="mb-2">
                    <label for="type" class="block text-label text-sm font-medium mb-2">Job Type</label>
                    <input type="text" id="type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600" name="type" value="<?php echo $type; ?>" required>
                </div>

                <div class="mb-2">
                    <label for="description" class="block text-label text-sm font-medium mb-2">Job Description</label>
                    <textarea id="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 h-32" name="description" required><?php echo $description; ?></textarea>
                </div>

                <div class="mb-2">
                    <label for="budget" class="block text-label text-sm font-medium mb-2">Budget</label>
                    <input type="text" id="budget" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600" name="budget" value="<?php echo $budget; ?>" required pattern="^[0-9]+$" title="Please enter a valid number for the budget.">
                </div>

                <div class="mb-2">
                    <label for="skills" class="block text-label text-sm font-medium mb-2">Required Skills</label>
                    <input type="text" id="skills" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600" name="skills" value="<?php echo $skills; ?>" required>
                </div>

                <div class="mb-4">
                    <label for="special_skill" class="block text-label text-sm font-medium mb-2">Special Requirement</label>
                    <input type="text" id="special_skill" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600" name="special_skill" value="<?php echo $special_skill; ?>">
                </div>

                 <?php // echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; ?>

                <div class="flex items-center justify-between">
                    <button type="submit" name="editJob" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300 ease-in-out transform hover:scale-105">
                        Edit Job
                    </button>
                </div>
            </form>
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

        // Dark Mode Toggle
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

        // Basic client-side form validation (replaces BootstrapValidator)
        const editForm = document.getElementById('registrationForm'); // Kept ID for compatibility
        if(editForm) {
            editForm.addEventListener('submit', function(event) {
                const titleInput = document.getElementById('title');
                const typeInput = document.getElementById('type');
                const descriptionInput = document.getElementById('description');
                const budgetInput = document.getElementById('budget');
                const skillsInput = document.getElementById('skills');

                let isValid = true;
                let errorMessage = '';

                // Check required fields
                if (!titleInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Job Title is required.\n';
                }
                if (!typeInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Job Type is required.\n';
                }
                 if (!descriptionInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Job Description is required.\n';
                }
                 if (!budgetInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Budget is required.\n';
                } else if (!/^[0-9]+$/.test(budgetInput.value.trim())) {
                     isValid = false;
                     errorMessage += 'Budget must be a valid number.\n';
                 }
                 if (!skillsInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Required Skills are required.\n';
                 }


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
