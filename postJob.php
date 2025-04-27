<?php
include('db/server.php');

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
    header('Location: login.html'); // Redirect to login if not logged in
    exit;
}

$username = $_SESSION['Username'];

// Handle form submission
$update_success = false;
if (isset($_POST['postJob'])) {
    $title = test_input($_POST["title"]);
    $type = test_input($_POST["type"]);
    $description = test_input($_POST["description"]);
    $budget = test_input($_POST["budget"]);
    $skills = test_input($_POST["skills"]);
    $special_skill = test_input($_POST["special_skill"]);
    $deadline = test_input($_POST["deadline"]);

    // Check if the date is in the future
    if (strtotime($deadline) > strtotime(date("Y-m-d"))) {
        echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Success!</strong>
                <span class='block sm:inline'>The deadline is in the future.</span>
                <span class='absolute top-0 bottom-0 right-0 px-4 py-3'>
                <svg class='fill-current h-6 w-6 text-green-500' role='button' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'>
                    <title>Close</title>
                    <path d='M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z'/>
                </svg>
                </span>
              </div>";
    } else {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Error!</strong>
                <span class='block sm:inline'>Invalid deadline. Please enter a future date.</span>
                <span class='absolute top-0 bottom-0 right-0 px-4 py-3'>
                <svg class='fill-current h-6 w-6 text-red-500' role='button' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'>
                    <title>Close</title>
                    <path d='M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z'/>
                </svg>
                </span>
              </div>";
    }

    $sql = "INSERT INTO job_offer (title, type, description, budget, skills, special_skill, deadline, e_username, valid)
            VALUES ('$title', '$type', '$description', '$budget', '$skills', '$special_skill', '$deadline', '$username', 1)";

    if ($conn->query($sql) === TRUE) {
        $_SESSION["job_id"] = $conn->insert_id;
        header("location: jobDetails.php");
    } else {
        echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <strong class='font-bold'>Error!</strong>
                <span class='block sm:inline'>Error inserting job: " . $conn->error . "</span>
                <span class='absolute top-0 bottom-0 right-0 px-4 py-3'>
                <svg class='fill-current h-6 w-6 text-red-500' role='button' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'>
                    <title>Close</title>
                    <path d='M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z'/>
                </svg>
                </span>
              </div>";
    }
}

// Function to sanitize inputs
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Post a Job</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/packed_animator.js'></script>
    <link rel="stylesheet" href="../src/css/styles.css">
    <style>
        /* Custom styles that might be needed beyond Tailwind's scope */
        .animated {
            animation-duration: 1s;
            animation-fill-mode: both;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .fadeIn {
            animation-name: fadeIn;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 100%, 0);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }
        .fadeInUp {
            animation-name: fadeInUp;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        .pulse {
            animation-name: pulse;
            animation-iteration-count: infinite;
        }
        @keyframes slideInDown {
            from {
                transform: translate3d(0, -100%, 0);
                visibility: visible;
            }
            to {
                transform: translate3d(0, 0, 0);
            }
        }
        .slideInDown {
            animation-name: slideInDown;
        }
        /* Dark Mode Toggle Styles */
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
            transition: .4s;
        }
        input:checked + .slider {
            background-color: #2196F3; /* Example vibrant color */
        }
        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }
        .slider.round:before {
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-100 transition duration-300 ease-in-out dark:bg-gray-950 dark:text-gray-100">
<div class="fixed top-4 right-4 z-50">
    <label class="switch">
        <input type="checkbox" id="theme-toggle">
        <span class="slider round"></span>
    </label>
</div>
<nav class="bg-blue-500 shadow-md py-4 sticky top-0 z-40 transition duration-300 ease-in-out dark:bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="index.php" class="text-white text-xl font-semibold animated fadeIn">Freelance Marketplace</a>
            </div>
            <div class="hidden md:flex space-x-4 animated fadeIn">
                <a href="allJob.php" class="text-white hover:text-blue-200 transition duration-200">Browse all jobs</a>
                <a href="allFreelancer.php" class="text-white hover:text-blue-200 transition duration-200">Browse Freelancers</a>
                <a href="allEmployer.php" class="text-white hover:text-blue-200 transition duration-200">Browse Employers</a>
                <div class="relative">
                    <button class="flex items-center text-white hover:text-blue-200 transition duration-200 focus:outline-none" onclick="toggleDropdown()">
                        <span class="mr-2"><i class="fas fa-user"></i></span>
                        <?php echo $username; ?>
                        <i class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <ul id="userDropdown" class="absolute right-0 mt-2 w-48 bg-gray-100 border border-gray-200 rounded-md shadow-lg origin-top-right hidden transition duration-150 ease-in-out dark:bg-gray-700 dark:border-gray-600">
                        <li>
                            <a href="employerProfile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-home mr-2"></i> View profile
                            </a>
                        </li>
                        <li>
                            <a href="editEmployer.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-inbox mr-2"></i> Edit Profile
                            </a>
                        </li>
                        <li>
                            <a href="message.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-envelope mr-2"></i> Messages
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="md:hidden">
                <button class="text-white focus:outline-none" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
        <div class="md:hidden absolute top-full left-0 right-0 bg-blue-500 shadow-md rounded-b-md overflow-hidden transition duration-300 ease-in-out transform origin-top scale-0" id="mobileMenu">
            <div class="px-4 py-2 flex flex-col space-y-2">
                <a href="allJob.php" class="text-white hover:text-blue-200 transition duration-200">Browse all jobs</a>
                <a href="allFreelancer.php" class="text-white hover:text-blue-200 transition duration-200">Browse Freelancers</a>
                <a href="allEmployer.php" class="text-white hover:text-blue-200 transition duration-200">Browse Employers</a>
                <div class="relative">
                    <button class="flex items-center text-white hover:text-blue-200 transition duration-200 focus:outline-none" onclick="toggleMobileDropdown()">
                        <span class="mr-2"><i class="fas fa-user"></i></span>
                        <?php echo $username; ?>
                        <i class="fas fa-chevron-down ml-1"></i>
                    </button>
                    <ul id="mobileUserDropdown" class="absolute top-full left-0 mt-2 w-48 bg-gray-100 border border-gray-200 rounded-md shadow-lg origin-top dark:bg-gray-700 dark:border-gray-600 hidden transition duration-150 ease-in-out">
                        <li>
                            <a href="employerProfile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-home mr-2"></i> View profile
                            </a>
                        </li>
                        <li>
                            <a href="editEmployer.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-inbox mr-2"></i> Edit Profile
                            </a>
                        </li>
                        <li>
                            <a href="message.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-envelope mr-2"></i> Messages
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200 transition duration-200 dark:text-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<div class="container mx-auto py-8">
    <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-8 transition duration-300 ease-in-out dark:bg-gray-800">
        <div class="page-header mb-6">
            <h2 class="text-2xl font-semibold text-blue-600 animated pulse dark:text-blue-400">Post A Job Offer</h2>
        </div>
        <form id="registrationForm" method="post" class="space-y-6">
            <div class="animated fadeIn delay-200">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Job Title</label>
                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="title" id="title" placeholder="e.g., Web Developer" />
            </div>
            <div class="animated fadeIn delay-300">
                <label for="type" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Job Type</label>
                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="type" id="type" placeholder="e.g., Full-time, Part-time, Contract" />
            </div>
            <div class="animated fadeIn delay-400">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Job Description</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="description" id="description" rows="4" placeholder="Describe the job in detail"></textarea>
            </div>
            <div class="animated fadeIn delay-500">
                <label for="budget" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Budget (KES)</label>
                <input type="number" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="budget" id="budget" placeholder="e.g., 10000" />
            </div>
            <div class="animated fadeIn delay-600">
                <label for="skills" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Required Skills</label>
                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="skills" id="skills" placeholder="e.g., PHP, JavaScript, MySQL" />
                <small class="text-gray-500 dark:text-gray-400">Separate skills with commas.</small>
            </div>
            <div class="animated fadeIn delay-700">
                <label for="special_skill" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Special Requirement</label>
                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="special_skill" id="special_skill" placeholder="e.g., Must have experience with Laravel framework" />
            </div>
            <div class="animated fadeIn delay-800">
                <label for="deadline" class="block text-gray-700 text-sm font-bold mb-2 dark:text-gray-300">Deadline</label>
                <input type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" name="deadline" id="deadline" min="<?php echo date("Y-m-d"); ?>" />
            </div>
            <div id="message" class="flex text-sm text-red-400">
            </div>
            <div class="animated fadeIn delay-900">
                <button type="submit" name="postJob" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline animated pulse">Post Job</button>
            </div>
        </form>
    </div>
</div>
<footer class="bg-gray-200 text-center py-8 mt-8 transition duration-300 ease-in-out dark:bg-gray-800 dark:text-gray-300">
    <div class="container mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="animated fadeInUp">
                <h3 class="text-lg font-semibold">Quick Links</h3>
                <p><a href="index.php" class="text-blue-500 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Home</a></p>
                <p><a href="allJob.php" class="text-blue-500 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Browse all jobs</a></p>
                <p><a href="allFreelancer.php" class="text-blue-500 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Browse Freelancers</a></p>
                <p><a href="allEmployer.php" class="text-blue-500 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Browse Employers</a></p>
            </div>
            <div class="animated fadeInUp delay-200">
                <p>Freelance Marketplace</p>
                <p>Nairobi, Kenya</p>
                <p>&copy; <?php echo date("Y"); ?></p>
            </div>
            <div class="animated fadeInUp delay-400">
                <h3 class="text-lg font-semibold">Contact Us</h3>
                <p>+254 725 146 071</p>
                <p>Nairobi, Kenya</p>
                <p>&copy; <?php echo date("Y"); ?></p>
            </div>
            <div class="animated fadeInUp delay-600">
                <h3 class="text-lg font-semibold">Social Contact</h3>
                <p style="font-size:20px;color:#3B579D;"><i class="fab fa-facebook-square"></i> Facebook</p>
                <p style="font-size:20px;color:#D34438;"><i class="fab fa-google-plus-square"></i> Google</p>
                <p style="font-size:20px;color:#2CAAE1;"><i class="fab fa-twitter-square"></i> Twitter</p>
                <p style="font-size:20px;color:#0274B3;"><i class="fab fa-linkedin"></i> Linkedin</p>
            </div>
        </div>
    </div>
</footer>
<script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="dist/js/bootstrapValidator.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css"></script>
<script>
    $(document).ready(function() {
        $('#registrationForm').bootstrapValidator({
            container: '#messages',
            feedbackIcons: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                title: {
                    validators: {
                        notEmpty: {
                            message: 'The job title is required and cannot be empty'
                        }
                    }
                },
                type: {
                    validators: {
                        notEmpty: {
                            message: 'The job type is required and cannot be empty'
                        }
                    }
                },
                description: {
                    validators: {
                        notEmpty: {
                            message: 'The job description is required and cannot be empty'
                        }
                    }
                },
                deadline: {
                    validators: {
                        notEmpty: {
                            message: 'The deadline is required'
                        },
                        date: {
                            format: 'YYYY-MM-DD',
                            message: 'The deadline is not a valid date'
                        }
                    }
                },
                budget: {
                    validators: {
                        notEmpty: {
                            message: 'The budget is required and cannot be empty'
                        },
                        stringLength: {
                            max: 11,
                            message: 'The budget cannot be more than 11 digits'
                        },
                        regexp: {
                            regexp: /^[0-9]+$/,
                            message: 'The budget must be a valid number'
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>
