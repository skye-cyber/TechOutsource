<?php
// Include database connection and start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include('db/server.php'); // Assuming this file establishes the $conn database connection

// --- Input Validation and Initialization ---

// Ensure the user is logged in to view their profile
if (!isset($_SESSION["Username"])) {
    // User is not logged in, redirect to login or home page
    $_SESSION['error_message'] = "You must be logged in to view your profile.";
    header("location: login.html"); // Or login.php
    exit();
}

$username = htmlspecialchars($_SESSION["Username"]); // Sanitize logged-in username for display

// Determine links based on logged-in user type for the navbar
$linkPro = "index.php";
$linkEditPro = "index.php";

if (isset($_SESSION["Usertype"])) {
    if ($_SESSION["Usertype"] == 1) { // Logged in as Freelancer - should ideally not land here
        $linkPro = "freelancerProfile.php";
        $linkEditPro = "editFreelancer.php";
        // Optionally redirect freelancers away from employer profile page
        // header("location: freelancerProfile.php"); exit();
    } else { // Assuming Usertype 0 for Employer
        $linkPro = "employerProfile.php"; // Link to self
        $linkEditPro = "editEmployer.php";
    }
} else {
     // Log error or handle differently if Usertype is missing for a logged-in user
     error_log("Usertype not set for logged-in user: " . $username);
     // Default to generic links
}


// Handle POST requests for job or freelancer details pages
if (isset($_POST['jid'])) {
    $_SESSION['job_id'] = htmlspecialchars($_POST['jid']); // Sanitize job_id
    header('Location: jobDetails.php');
    exit();
}

if (isset($_POST['f_user'])) {
    $_SESSION['f_user'] = htmlspecialchars($_POST['f_user']); // Sanitize f_user
    header('Location: viewFreelancer.php');
    exit();
}

// --- Fetch Employer Details (Logged-in user) ---

$emp = []; // Initialize employer details array
$sql_fetch_freelancer = "SELECT name, email, contactNo, country, experience, education, skills, prof_title, profile_sum FROM freelancer WHERE username=? LIMIT 1"; // Removed gender, birthdate as they weren't used in the original HTML and simplified contactNo/address columns based on common usage
$stmt_fetch_freelancer = $conn->prepare($sql_fetch_freelancer);

if ($stmt_fetch_freelancer === false) {
    error_log("Prepare failed for fetching employer details: " . $conn->error);
    $_SESSION['error_message'] = "Error loading your profile details."; // User-friendly error
    // $emp will remain empty
} else {
    $stmt_fetch_freelancer->bind_param("s", $username); // Fetch details for the logged-in user
    $stmt_fetch_freelancer->execute();
    $result_fetch_employer = $stmt_fetch_freelancer->get_result();

    if ($result_fetch_employer->num_rows > 0) {
        $emp = $result_fetch_employer->fetch_assoc();
        // Data fetched will be sanitized when displayed
    } else {
        // Should not happen if user is logged in, but handle defensively
        error_log("Logged-in user '$username' not found in employer table.");
         $_SESSION['error_message'] = "Your profile data could not be found.";
         // $emp will remain empty
    }
    $stmt_fetch_freelancer->close();
}


// --- Fetch Records (Offers and Hires) ---
function fetchRecords($conn, $username, $valid, $type = 'offer') {
    $query = ($type === 'hire') ?
    "SELECT jo.job_id, jo.title, s.f_username, s.price, jo.timestamp FROM job_offer jo JOIN selected s ON jo.job_id=s.job_id WHERE s.e_username=? AND s.valid=? ORDER BY jo.timestamp DESC" :
    "SELECT job_id, title, timestamp FROM job_offer WHERE e_username=? AND valid=? ORDER BY timestamp DESC";
         $stmt = $conn->prepare($query);
         if ($stmt === false) {
             error_log("Prepare failed for fetching records ($type): " . $conn->error);
             return false;
         }
         $stmt->bind_param('si', $username, $valid);
         if (!$stmt->execute()) {
             error_log("Execute failed for fetching records ($type): " . $stmt->error);
             $stmt->close();
             return false;
         }
         $result = $stmt->get_result();
         $stmt->close();
         return $result;
}

// Fetch data for tables
$curOffers = fetchRecords($conn, $username, 1);
$prevOffers = fetchRecords($conn, $username, 0);
$hired = fetchRecords($conn, $username, 1, 'hire');
$prevHired = fetchRecords($conn, $username, 0, 'hire');

if ($hired === false) {
    error_log("Error fetching current hires.");
} else {
    error_log("Current hires fetched successfully: " . $hired->num_rows);
}

// Check if any fetch failed and set error message if necessary
if ($curOffers === false || $prevOffers === false || $hired === false || $prevHired === false) {
     $_SESSION['error_message'] = "Error fetching job and hire records."; // More general error
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Profile - <?php echo htmlspecialchars($username); ?></title>
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

         .text-body-light {
             color: #374151; /* gray-700 */
         }
         .dark .text-body-dark {
             color: #d1d5db; /* gray-300 */
         }
         .text-muted {
             color: #6b7280; /* gray-500 */
         }
         .dark .text-muted {
             color: #9ca3af; /* gray-400 */
         }

        /* Link styles for table */
        .table-link {
             color: #3b82f6; /* blue-500 */
             text-decoration: underline;
             transition: color 0.3s ease;
        }
        .table-link:hover {
             color: #2563eb; /* blue-600 */
        }
         .dark .table-link {
             color: #60a5fa; /* blue-400 */
         }
         .dark .table-link:hover {
             color: #90cdf4; /* blue-300 */
         }

    </style>
</head>
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-body-light dark:text-body-dark min-h-screen flex flex-col transition duration-300 ease-in-out">

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
    <div class="container mx-auto p-4 flex-1 pt-20"> <?php
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

        <div class="grid grid-cols-12 gap-6"> <aside class="col-span-12 lg:col-span-3 space-y-6 dark:text-white" data-aos="fade-right" data-aos-delay="200"> <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000 text-center">
			<section class="flex justify-center items-center mb-4"> <div class="w-24 h-24 bg-gradient-to-tr from-purple-300 to-pink-300 dark:from-indigo-600 dark:to-purple-600 rounded-full flex items-center justify-center shadow-md transition-all transform duration-700 hover:rotate-6">
				<span class="text-3xl font-semibold text-white transition-colors duration-1000"><?php echo $username ? htmlspecialchars(strtoupper(substr($username, 0, 1))) : 'U'; ?></span> </div>
			</section>
                    <h2 class="text-xl font-bold text-heading mb-1"><?php echo htmlspecialchars($emp['name'] ?? ''); ?></h2> <p class="text-muted mb-4">@<?php echo htmlspecialchars($username); ?></p> <nav class="space-y-3"> <a href="index.php" class="block px-4 py-2 bg-indigo-700 text-white rounded-full hover:bg-[#aa55ff] transition-colors duration-700 text-center">Home</a>  <a href="editFreelancer.php" class="block px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-1000 text-center">Edit Profile</a> <a href="message.php" class="block px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-1000 text-center">Messages</a> <a href="registration/logout.php" class="block px-4 py-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors duration-1000 text-center">Logout</a> </nav>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000" data-aos="fade-right" data-aos-delay="300"> <h4 class="text-lg font-semibold text-heading mb-4">Contact Info</h4> <?php if (!empty($emp)): ?>
                    <div class="space-y-3"> <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Email:</strong><span class="dark:text-white"> <?php echo htmlspecialchars($emp['email']); ?></span></p> <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Phone:</strong><span class="dark:text-white"> <?php echo htmlspecialchars($emp['contactNo']); ?></span></p> <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Country:</strong> <span class="dark:text-white"><?php echo htmlspecialchars($emp['country']); ?></span></p> </div>
                     <?php else: ?>
                        <p class="text-muted">Contact information not available.</p>
                     <?php endif; ?>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000" data-aos="fade-right" data-aos-delay="300"> <h4 class="text-lg font-semibold text-heading mb-4">Additional Info</h4> <?php if (!empty($emp)): ?>
                    <div class="space-y-3"> <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Experience:</strong><span class="dark:text-white"> <?php echo $emp['experience'] ? htmlspecialchars($emp['experience']) : 'NULL'; ?></span></p> <p class="text-body-light dark:text-body-dark"><p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Skills:</strong> <span class="dark:text-white"><?php echo $emp['skills'] ? htmlspecialchars($emp['skills']) : 'NULL'; ?></span></p> </div>
                     <?php else: ?>
                        <p class="text-muted">Contact information not available.</p>
                     <?php endif; ?>
                </div>
            </aside>

            <main class="col-span-12 lg:col-span-9 space-y-6" data-aos="fade-left" data-aos-delay="400">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000" data-aos="fade-up"> <h3 class="text-2xl font-semibold text-heading mb-4">Freelancer Details</h3> <?php if (!empty($emp)): ?>
                    <div class="space-y-3"> <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Professional Title:</strong> <span class="dark:text-white"><?php echo $emp['prof_title'] ? htmlspecialchars($emp['prof_title']) : 'NULL'; ?></span></p> <p class="text-body-light dark:text-body-dark mt-2 leading-relaxed"><strong class="text-subheading">Summary:</strong> <span class="dark:text-white"><?php echo $emp['profile_sum'] ? htmlspecialchars($emp['profile_sum']) : 'NULL'; ?></span></p> </div>
                     <?php else: ?>
                          <p class="text-muted">Freelancer details not available.</p>
                     <?php endif; ?>
                </div>

                <?php function renderTable($title, $resultSet) {
                        if ($resultSet && $resultSet->num_rows > 0) {
                            ?>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000" data-aos="fade-up">
                                <h4 class="text-lg font-semibold text-heading mb-4"><?php echo htmlspecialchars($title); echo $resultSet; ?> </h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors duration-1000 text-subheading">
                                                <th class="px-4 py-2 text-left">Job ID</th>
                                                <th class="px-4 py-2 text-left">Title</th>
                                                <?php if (str_contains($title, 'Hire')): ?>
                                                    <th class="px-4 py-2 text-left">Freelancer</th>
                                                    <th class="px-4 py-2 text-left">Price</th>
                                                <?php endif; ?>
                                                <th class="px-4 py-2 text-left">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($r = $resultSet->fetch_assoc()): ?>
                                                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 ease-in-out">
                                                    <td class="px-4 py-3 text-body-light dark:text-body-dark"><?php echo htmlspecialchars($r['job_id']); ?></td>
                                                    <td class="px-4 py-3 text-indigo-600 dark:text-indigo-300 hover:underline transition duration-300 ease-in-out">
                                                        <form method="post" class="inline-block">
                                                            <input type="hidden" name="jid" value="<?php echo htmlspecialchars($r['job_id']); ?>">
                                                            <button type="submit" class="bg-transparent border-none p-0 m-0 cursor-pointer text-current hover:underline">
                                                                <?php echo htmlspecialchars($r['title']); ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <?php if (isset($r['f_username'])): ?>
                                                        <td class="px-4 py-3 text-purple-700 dark:text-purple-300 hover:underline transition duration-300 ease-in-out">
                                                            <form method="post" class="inline-block">
                                                                <input type="hidden" name="f_user" value="<?php echo htmlspecialchars($r['f_username']); ?>">
                                                                <button type="submit" class="bg-transparent border-none p-0 m-0 cursor-pointer text-current hover:underline">
                                                                    <?php echo htmlspecialchars($r['f_username']); ?>
                                                                </button>
                                                            </form>
                                                        </td>
                                                        <td class="px-4 py-3 text-body-light dark:text-body-dark"><?php echo htmlspecialchars($r['price'] ?? ''); ?></td>
                                                    <?php elseif (str_contains($title, 'Hire')): ?>
                                                        <td class="px-4 py-3 text-muted">N/A</td>
                                                        <td class="px-4 py-3 text-muted">N/A</td>
                                                    <?php endif; ?>
                                                    <td class="px-4 py-3 text-muted text-sm"><?php echo htmlspecialchars($r['timestamp']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg transition-colors duration-1000" data-aos="fade-up">
                                <h4 class="text-lg font-semibold text-heading mb-4"><?php echo htmlspecialchars($title); ?></h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full table-auto">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors duration-1000 text-subheading">
                                                <th class="px-4 py-2 text-left">Job ID</th>
                                                <th class="px-4 py-2 text-left">Title</th>
                                                <?php if (str_contains($title, 'Hire')): ?>
                                                    <th class="px-4 py-2 text-left">Freelancer</th>
                                                    <th class="px-4 py-2 text-left">Price</th>
                                                <?php endif; ?>
                                                <th class="px-4 py-2 text-left">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="<?php echo str_contains($title, 'Hire') ? '5' : '3'; ?>" class="px-4 py-3 text-center text-muted">Nothing to show</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>

                <?php
                    // Render the tables
                    renderTable('Current Job Offers', $curOffers);
                    renderTable('Previous Job Offers', $prevOffers);
                    renderTable('Current Hires', $hired); // Changed title
                    renderTable('Previous Hires', $prevHired); // Changed title

                ?>

            </main>

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



         //Close alert messages (using onclick in HTML directly for simplicity)
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
