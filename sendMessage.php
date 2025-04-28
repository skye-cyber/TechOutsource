<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection

// --- Input Validation and Initialization ---

// Ensure the user is logged in to send messages
if (!isset($_SESSION["Username"])) {
    $_SESSION['error_message'] = "You must be logged in to send messages.";
    header("location: index.php"); // Redirect to login/home if not logged in
    exit();
}

$username = htmlspecialchars($_SESSION["Username"]); // Sanitize logged-in username for display
$user_type = $_SESSION["Usertype"] ?? null; // Logged-in user's type (null if not set, though shouldn't happen if logged in)

$linkPro = "index.php"; // Default link
$linkEditPro = "index.php"; // Default link

// Determine links based on logged-in user type for the navbar
if ($user_type !== null) {
    if ($user_type == 1) { // Logged in as Freelancer
        $linkPro = "freelancerProfile.php";
        $linkEditPro = "editFreelancer.php";
    } else { // Assuming Usertype 0 for Employer
        $linkPro = "employerProfile.php";
        $linkEditPro = "editEmployer.php";
    }
}

$msgRcv = ""; // Initialize recipient username

// Get the message recipient from session
if (isset($_SESSION["msgRcv"])) {
    $msgRcv = htmlspecialchars($_SESSION["msgRcv"]); // Sanitize recipient username for display
} else {
     // If recipient is not set in session, redirect back or show error
     $_SESSION['error_message'] = "Message recipient not specified.";
     header("location: message.php"); // Redirect to message list or another page
     exit();
}


// --- Handle Message Submission ---

if (isset($_POST["send"])) {
     // Check for CSRF token (Basic implementation)
     // if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
     //      $_SESSION['error_message'] = "Invalid request. Please try again.";
     //      // Redirect back to the form
     //      header("location: sendMessage.php");
     //      exit();
     // }

    $msgTo = $_POST["msgTo"] ?? '';
    $msgBody = $_POST["msgBody"] ?? '';

     // Sanitize inputs
    $msgTo_sanitized = htmlspecialchars(trim($msgTo));
    $msgBody_sanitized = htmlspecialchars(trim($msgBody)); // Sanitize for display if needed later

    // Basic server-side validation
     if (empty($msgTo_sanitized) || empty($msgBody_sanitized)) {
         $_SESSION['error_message'] = "Recipient and message body cannot be empty.";
     } elseif ($msgTo_sanitized === $username) {
          $_SESSION['error_message'] = "You cannot send a message to yourself.";
     }
    // Optional: Validate if $msgTo_sanitized is a valid existing user in your database

    else {
         // Use prepared statement for inserting message
        $sql_insert_message = "INSERT INTO message (sender, receiver, msg) VALUES (?, ?, ?)";
        $stmt_insert_message = $conn->prepare($sql_insert_message);

        if ($stmt_insert_message === false) {
            error_log("Prepare failed for inserting message: " . $conn->error);
            $_SESSION['error_message'] = "An internal error occurred while sending your message.";
        } else {
            // Bind parameters: sss -> s=string, s=string, s=string
            $stmt_insert_message->bind_param("sss", $username, $msgTo_sanitized, $msgBody_sanitized); // Use sender username

            if ($stmt_insert_message->execute()) {
                 $_SESSION['success_message'] = "Message sent successfully!";
                 header("location: message.php"); // Redirect to message list on success
                 exit();
            } else {
                error_log("Execute failed for inserting message: " . $stmt_insert_message->error);
                $_SESSION['error_message'] = "Failed to send message. Please try again.";
            }
            $stmt_insert_message->close();
        }
    }
    // Redirect after POST request (even on error) to prevent form resubmission on refresh
    header("location: sendMessage.php");
    exit();
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
    <title>Send Message</title>
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

        <div class="w-full max-w-lg bg-white dark:bg-gray-800/70 backdrop-blur-md rounded-2xl shadow-xl p-8 transition-transform transform hover:scale-[1.01] duration-500" data-aos="zoom-in">
            <h2 class="text-3xl font-extrabold text-heading mb-8 text-center tracking-tight dark:text-cyan-200">Write Message</h2> <!-- Used text-heading -->

            <form id="sendMessageForm" method="post" class="space-y-2"> <!-- Added ID for JS validation -->
                 <!-- Optional: Add CSRF token field -->
                 <?php // if(isset($csrf_token)): echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">'; endif; ?>

                <div data-aos="fade-up" data-aos-delay="100">
                    <label for="msgTo" class="block text-lg font-semibold text-label dark:text-sky-200">To <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <input type="text" id="msgTo" name="msgTo" value="<?php echo $msgRcv; ?>" class="mt-2 w-full px-4 py-3 border border-blue-300 dark:border-blue-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required <?php echo !empty($msgRcv) ? 'readonly' : ''; ?> /> <!-- Added readonly if recipient is pre-filled -->
                </div>

                <div data-aos="fade-up" data-aos-delay="200">
                    <label for="msgBody" class="block text-lg font-semibold text-label dark:text-blue-200">Message Body <span class="text-red-500">*</span></label> <!-- Used text-label -->
                    <textarea id="msgBody" name="msgBody" rows="8" class="mt-2 w-full px-4 py-3 border border-green-400 dark:border-green-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required></textarea>
                </div>

                <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                    <button type="submit" name="send" class="px-8 py-4 bg-gradient-to-r from-blue-500 to-sky-500 hover:from-green-600 hover:to-green-400 text-white font-bold rounded-full shadow-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:shadow-lg dark:shadow-indigo-800/50 transition-colors duration-700">Send Message</button>
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
        const sendMessageForm = document.getElementById('sendMessageForm');
        if(sendMessageForm) {
            sendMessageForm.addEventListener('submit', function(event) {
                const msgToInput = document.getElementById('msgTo');
                const msgBodyInput = document.getElementById('msgBody');

                let isValid = true;
                let errorMessage = '';

                if (!msgToInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Recipient is required and cannot be empty.\n';
                }
                 if (!msgBodyInput.value.trim()) {
                    isValid = false;
                    errorMessage += 'Message body is required and cannot be empty.\n';
                }

                // Add more validation if needed (e.g., check if recipient exists via AJAX)

                if (!isValid) {
                    event.preventDefault(); // Prevent form submission
                    alert('Validation errors:\n' + errorMessage); // Simple alert for now
                }
            });
        }


    </script>
    <!-- Removed Bootstrap/jQuery JS links -->
</body>
</html>
