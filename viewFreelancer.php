<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $name; ?>'s Profile
    </title>
    <!-- Tailwind CSS Link -->
    <link rel="stylesheet" href="src/css/styles.css">
    <script src='../src/js/packed_animator.js'></script>
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/smoothScroll.js'></script>
    <style>
        /* Custom gradients for body background */
        .bg-gradient-vibrant-light {
            background: linear-gradient(to bottom right, #FFA07A, #FA8072); /* Example vibrant light gradient (pink-300 to red-400) */
        }
        .dark .bg-gradient-vibrant-dark {
            background: linear-gradient(to bottom right, #FF6347, #FF4500); /* Example vibrant dark gradient (orange-600 to red-500) */
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
            color: #FF4500; /* red-500 */
        }
        .dark .text-heading {
            color: #FF6347; /* orange-600 */
        }
        .text-subheading {
            color: #FFA07A; /* pink-300 */
        }
        .dark .text-subheading {
            color: #FF6347; /* orange-600 */
        }
        .text-body-light {
            color: #FF8C00; /* darkorange */
        }
        .dark .text-body-dark {
            color: #FFA500; /* orange */
        }
        .text-muted {
            color: #FFD700; /* gold */
        }
        .dark .text-muted {
            color: #FFDAB9; /* peachpuff */
        }
        /* Star Rating Styles */
        .rating {
            display: inline-block;
            unicode-bidi: bidi-override;
            direction: rtl; /* Stars from right to left */
            text-align: left; /* Align stars left */
        }
        .rating > input {
            display: none;
        }
        .rating > label {
            display: inline-block;
            position: relative;
            width: 1.1em; /* Adjust size as needed */
            font-size: 2rem; /* Adjust size as needed */
            color: #ddd; /* Default star color */
            cursor: pointer;
            margin: 0 2px; /* Space between stars */
        }
        .rating > label::before {
            content: "★"; /* Unicode star character */
            position: absolute;
        }
        .rating > input:checked ~ label {
            color: #ffc107; /* Gold color for checked stars */
        }
        .rating > input:checked + label {
            color: #ffc107; /* Gold color for the selected star */
        }
        .rating > label:hover,
        .rating > label:hover ~ label,
        .rating:not(:hover) > input:checked ~ label:hover,
        .rating:not(:hover) > input:checked ~ label:hover ~ label {
            color: #ffc107; /* Gold color on hover */
        }
        .rating > label:hover::before,
        .rating > label:hover ~ label::before {
            color: #ffc107; /* Gold color on hover */
        }
    </style>
</head>
<!-- Apply base gradient class to the body -->
<body class="bg-gradient-vibrant-light dark:bg-gradient-vibrant-dark text-body-light dark:text-body-dark min-h-screen flex flex-col transition duration-300 ease-in-out">
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
                        <a class="inline-block py-2 px-4 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white no-underline transition duration-300" href="register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar menu -->
    <!--main body-->
    <div class="container mx-auto px-4 py-8 pt-20"> <!-- Added pt-20 for spacing below fixed navbar -->
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> <!-- Use lg:grid-cols-3 for desktop layout -->
        <!--Column 1 (Profile & Contact)-->
        <div class="lg:col-span-1">
            <!--Main profile card-->
            <div class="bg-white dark:bg-stone-950 p-6 rounded-lg shadow-lg mb-6 text-center" data-aos="fade-right">
                <img src="image/img04.jpg" alt="Profile Picture" class="rounded-full mx-auto mb-4 w-32 h-32 object-cover border-4 border-purple-500 dark:border-pink-500">
                <h2 class="text-xl font-semibold mb-2 text-heading"><?php echo $name; ?></h2>
                <p class="mb-4 text-muted"><i class="fas fa-user mr-2"></i> <?php echo $freelancer_username; ?></p>
                <?php if ($username && $username !== $freelancer_username): // Only show message button if logged in and not viewing own profile ?>
                <a href="sendMessage.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                    <i class="fas fa-envelope mr-2"></i> Send Message
                </a>
                <?php elseif (!$username): // If not logged in, perhaps show login prompt ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-4">Login to send a message.</p>
                <?php endif; ?>
            </div>
            <!--End Main profile card-->
            <!--Contact Information-->
            <div class="bg-white dark:bg-zinc-950 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-right" data-aos-delay="100">
                <h4 class="text-lg font-medium mb-4 text-heading">Contact Information</h4>
                <div class="space-y-3">
                    <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Email:</strong> <?php echo $email; ?></p>
                    <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Mobile:</strong> <?php echo $contactNo; ?></p>
                    <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Address:</strong> <?php echo $country; ?></p>
                </div>
            </div>
            <!--End Contact Information-->
            <!-- Rating Form (Only visible to Employers) -->
            <?php if ($username && $user_type !== null && $user_type != 1 && $username !== $freelancer_username): // Logged in, is employer, and not viewing own profile ?>
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-right" data-aos-delay="200">
                <h4 class="text-lg font-medium mb-4 text-heading">Rate This Freelancer</h4>
                <form action="" method="post" class="space-y-4">
                    <div class="rating">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars"></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"></label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"></label>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="rate-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                            Submit Rating
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            <!-- End Rating Form -->
            <!-- Payment Form (Only visible to Employers) -->
            <?php if ($username && $user_type !== null && $user_type != 1 && $username !== $freelancer_username): // Logged in, is employer, and not viewing own profile ?>
            <div class="bg-white dark:bg-gray-950 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-right" data-aos-delay="300">
                <h4 class="text-lg font-medium mb-4 text-heading">Pay Freelancer (Mpesa)</h4>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-subheading">Phone Number (254xxxxxxxxx)</label>
                        <input type="text" id="phone_number" name="phone_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required pattern="^(?:254|\+254|0)?(7\d{8}|1\d{8})$" title="Enter a valid Safaricom phone number (e.g., 2547xxxxxxxx or 07xxxxxxxx)">
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-subheading">Amount (KES)</label>
                        <input type="number" id="amount" name="amount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required min="1">
                    </div>
                    <div class="text-center">
                        <button type="submit" name="pay_btn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg transition duration-300 ease-in-out transform hover:scale-105">
                            Initiate Mpesa Payment
                        </button>
                    </div>
                    <p class="text-sm text-muted text-center">You will receive a push notification on your phone to complete the payment.</p>
                    <p class="text-xs text-red-600 dark:text-red-400 text-center">Note: This is a basic implementation. Full Mpesa integration requires secure callback handling.</p>
                </form>
            </div>
            <?php endif; ?>
            <!-- End Payment Form -->
        </div>
        <!--End Column 1-->
        <!--Column 2 (Freelancer Details & Ratings Table)-->
        <div class="lg:col-span-2">
            <!--Freelancer Profile Details Card-->
            <div class="bg-white dark:bg-slate-950 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-left">
                <h3 class="text-2xl font-semibold mb-4 text-blue-500 underline">Freelancer Profile Details</h3>
                <div class="space-y-3">
                    <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Professional Title:</strong> <?php echo $prof_title; ?></p>
                    <p class="text-body-light dark:text-body-dark"><strong class="text-subheading">Skills:</strong> <?php echo $skills; ?></p>
                    <p class="text-body-light dark:text-body-dark leading-relaxed"><strong class="text-subheading">Profile Summary:</strong> <?php echo nl2br($profile_sum); ?></p>
                    <p class="text-body-light dark:text-body-dark leading-relaxed"><strong class="text-subheading">Education:</strong> <?php echo nl2br($education); ?></p>
                    <p class="text-body-light dark:text-body-dark leading-relaxed"><strong class="text-subheading">Experience:</strong> <?php echo nl2br($experience); ?></p>
                </div>
            </div>
            <!--End Freelancer Profile Details-->
            <!-- Ratings Display -->
            <div class="bg-white dark:bg-gray-950 p-6 rounded-lg shadow-lg mb-6" data-aos="fade-left" data-aos-delay="100">
                <h4 class="text-lg font-medium mb-4 text-heading">Ratings (Average: <?php echo $average_rating; ?>)</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors duration-1000 text-subheading">
                                <th class="px-4 py-2 text-left">Rating</th>
                                <th class="px-4 py-2 text-left">Rated By</th>
                                <th class="px-4 py-2 text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ratings)): ?>
                            <?php foreach ($ratings as $rating): ?>
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-300 ease-in-out">
                                <td class="px-4 py-3 text-body-light dark:text-body-dark"><?php echo $rating['rating']; ?>/5</td>
                                <td class="px-4 py-3 text-blue-600 dark:text-blue-400 hover:underline">
                                    <!-- Link to employer profile if needed -->
                                    <form action="viewEmployer.php" method="post" class="inline-block">
                                        <input type="hidden" name="e_user" value="<?php echo $rating['rated_by']; ?>">
                                        <button type="submit" class="bg-transparent border-none p-0 m-0 cursor-pointer text-current hover:underline">
                                            <?php echo $rating['rated_by']; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-muted text-sm"><?php echo $rating['last_rated']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-muted">No ratings yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- End Ratings Display -->
        </div>
        <!--End Column 2-->
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
    <!-- Removed Bootstrap/jQuery JS links -->
</body>
</html>
