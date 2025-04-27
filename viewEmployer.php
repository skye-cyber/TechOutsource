<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection

// --- Input Validation and Initialization ---

$username = ""; // Logged-in user's username
$linkPro = "login.html"; // Default link if not logged in
$linkEditPro = "index.php"; // Default link

// Determine links based on logged-in user type
if (isset($_SESSION["Username"])) {
    $username = htmlspecialchars($_SESSION["Username"]); // Sanitize logged-in username for display
    if (isset($_SESSION["Usertype"])) {
        if ($_SESSION["Usertype"] == 1) { // Logged in as Freelancer
            $linkPro = "freelancerProfile.php";
            $linkEditPro = "editFreelancer.php";
        } else { // Assuming Usertype 0 for Employer
            $linkPro = "employerProfile.php";
            $linkEditPro = "editEmployer.php";
        }
    } else {
         // Log error or handle differently if Usertype is missing
         error_log("Usertype not set for logged-in user: " . $username);
         // Optionally redirect
    }
} // Note: We don't require login to *view* an employer profile based on the original code

$e_user = ""; // The employer username to view

// Get the employer username to view from session
if (isset($_SESSION["e_user"])) {
    $e_user = $_SESSION["e_user"];
    $_SESSION["msgRcv"] = htmlspecialchars($e_user); // Sanitize for messaging recipient
} else {
    // Employer username not specified to view, redirect or show error
    // header("location: allEmployer.php"); // Redirect to browse employers
    // exit();
     echo "Error: Employer profile not specified.";
     exit(); // Stop execution if e_user is missing
}


// --- Fetch Employer Details ---

$name = $email = $contactNo = $gender = $birthdate = $address = $company = $profile_sum = "";
$sql_fetch_employer = "SELECT name, email, contactNo, country, company, profile_sum FROM employer WHERE username=? LIMIT 1";
$stmt_fetch_employer = $conn->prepare($sql_fetch_employer);

if ($stmt_fetch_employer === false) {
    error_log("Prepare failed for fetching employer details: " . $conn->error);
    die("Error fetching employer details."); // Fatal error for now
}

$stmt_fetch_employer->bind_param("s", $e_user); // 's' for string username
$stmt_fetch_employer->execute();
$result_fetch_employer = $stmt_fetch_employer->get_result();

if ($result_fetch_employer->num_rows > 0) {
    $row = $result_fetch_employer->fetch_assoc();
    // Sanitize fetched data for display
    $name = htmlspecialchars($row["name"]);
    $email = htmlspecialchars($row["email"]);
    $contactNo = htmlspecialchars($row["contactNo"]);
    // $gender = htmlspecialchars($row["gender"]); // Not displayed in original HTML
    // $birthdate = htmlspecialchars($row["birthdate"]); // Not displayed in original HTML
    $address = htmlspecialchars($row["country"]);
    $company = $row["company"] ? htmlspecialchars($row["company"]) : 'NULL';
    $profile_sum = $row["profile_sum"] ? htmlspecialchars($row["profile_sum"]) : 'NULL';

} else {
    // Employer username not found in database
    echo "Employer not found.";
    exit();
}
$stmt_fetch_employer->close();

// --- Fetch Jobs Posted by this Employer (Optional: to show on profile) ---
// The original code didn't explicitly show jobs posted by this employer,
// but this would be a common feature for an employer profile page.
// I'll add a placeholder or example query if you want to include it.

// --- Fetch Freelancers Previously Hired by this Employer ---
// The original code displayed the *logged-in* username here, which is wrong.
// To display freelancers *this employer* hired, you'd need to query the 'selected' table.
$hired_freelancers = [];
$sql_hired = "SELECT DISTINCT f_username FROM selected WHERE e_username=?";
$stmt_hired = $conn->prepare($sql_hired);

if ($stmt_hired === false) {
     error_log("Prepare failed for fetching hired freelancers: " . $conn->error);
     // Handle error, maybe the list remains empty
} else {
    $stmt_hired->bind_param("s", $e_user);
    $stmt_hired->execute();
    $result_hired = $stmt_hired->get_result();
    while ($row_hired = $result_hired->fetch_assoc()) {
        $hired_freelancers[] = htmlspecialchars($row_hired["f_username"]); // Sanitize
    }
    $stmt_hired->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $name; ?>'s Profile</title>
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

         .text-subheading {
             color: #0d9488; /* teal-700 */
         }
         .dark .text-subheading {
             color: #67e8f9; /* cyan-400 */
         }

         .text-body {
             color: #374151; /* gray-700 */
         }
         .dark .text-body {
             color: #d1d5db; /* gray-300 */
         }
         .text-muted {
             color: #6b7280; /* gray-500 */
         }
         .dark .text-muted {
             color: #9ca3af; /* gray-400 */
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

    <!--main body-->
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> <!-- Use lg:grid-cols-3 for desktop layout -->

            <!--Column 1 (Profile & Contact)-->
            <div class="lg:col-span-1">

                <!--Main profile card-->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6 text-center" data-aos="fade-right">
                    <img src="image/img04.jpg" alt="Profile Picture" class="rounded-full mx-auto mb-4 w-32 h-32 object-cover border-4 border-purple-500 dark:border-pink-500">
                    <h2 class="text-xl font-semibold mb-2 text-heading"><?php echo $name; ?></h2>
                    <p class="mb-4 text-muted"><i class="fas fa-user mr-2"></i> <?php echo $e_user; ?></p>
                    <?php if ($username && $username !== $e_user): // Only show message button if logged in and not viewing own profile ?>
                    <a href="sendMessage.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                         <i class="fas fa-envelope mr-2"></i> Send Message
                     </a>
                     <?php elseif (!$username): // If not logged in, perhaps show login prompt ?>
                         <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">Login to send a message.</p>
                    <?php endif; ?>
                </div>
                <!--End Main profile card-->

                <!--Contact Information-->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-right" data-aos-delay="100">
                     <h4 class="text-lg font-medium mb-4 text-heading">Contact Information</h4>
                     <div class="mb-3">
                        <h5 class="font-medium text-subheading">Email</h5>
                        <p class="text-body"><?php echo $email; ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="font-medium text-subheading">Mobile</h5>
                        <p class="text-body"><?php echo $contactNo; ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="font-medium text-subheading">Address</h5>
                        <p class="text-body"><?php echo $address; ?></p>
                    </div>
                     <!-- Gender and Birthdate were fetched but not displayed in original, adding here if desired -->
                     <?php /* ?>
                    <div class="mb-3">
                        <h5 class="font-medium text-subheading">Gender</h5>
                        <p class="text-body"><?php echo $gender; ?></p>
                    </div>
                    <div class="mb-3">
                        <h5 class="font-medium text-subheading">Birthdate</h5>
                        <p class="text-body"><?php echo $birthdate; ?></p>
                    </div>
                    <?php */ ?>
                </div>
                <!--End Contact Information-->

            </div>
            <!--End Column 1-->

            <!--Column 2 (Employer Details & Hired Freelancers/Posted Jobs)-->
            <div class="lg:col-span-2">

                <!--Employer Profile Details Card-->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-left">
                    <h3 class="text-2xl font-semibold mb-4 text-heading">Employer Profile Details</h3>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-subheading">Company Name</h4>
                        <p class="text-body"><?php echo $company; ?></p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-subheading">Profile Summary</h4>
                        <p class="text-body leading-relaxed"><?php echo nl2br($profile_sum); ?></p> <!-- Use nl2br -->
                    </div>

                     <!-- Previously Hired Freelancers - Corrected Logic -->
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-subheading">Previously Hired Freelancers</h4>
                         <?php if (!empty($hired_freelancers)): ?>
                            <ul class="list-disc list-inside text-body">
                                <?php foreach ($hired_freelancers as $freelancer): ?>
                                    <li>
                                         <!-- Link to freelancer profile - assumes you have a viewFreelancer.php -->
                                         <form action="viewFreelancer.php" method="post" class="inline-block">
                                             <input type="hidden" name="f_user" value="<?php echo $freelancer; ?>">
                                             <button type="submit" class="text-blue-600 hover:underline dark:text-blue-400 transition duration-300 btn-link-form"><?php echo $freelancer; ?></button>
                                         </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">This employer hasn't marked any jobs as completed with hired freelancers yet.</p>
                        <?php endif; ?>
                    </div>

                     <!-- Jobs Posted by this Employer (Optional section) -->
                     <?php /*
                    <div class="mb-4">
                         <h4 class="text-lg font-medium text-subheading">Active Job Postings</h4>
                         // Add PHP logic here to fetch and display active jobs posted by $e_user
                         <p class="text-body">Jobs posted by <?php echo $e_user; ?> will appear here...</p>
                    </div>
                    */ ?>

                </div>
                <!--End Employer Profile Details-->

            </div>
            <!--End Column 2-->

             <!-- Column 3 (Empty in original - kept for potential future use or removed based on layout) -->
             <div class="lg:col-span-0">
                 <!-- Can be removed or used for other content -->
             </div>
            <!-- End Column 3 -->


        </div>
    </div>
    <!--End main body-->


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


    </script>
    <!-- Remove Bootstrap/jQuery JS links -->
    <!-- <script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script> -->
    <!-- <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script> -->
</body>
</html>
