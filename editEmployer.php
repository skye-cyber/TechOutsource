<?php
include('db/server.php');

// Ensure the user is logged in
if (!isset($_SESSION['Username'])) {
    header('Location: login.html'); // Redirect to login if not logged in
    exit;
}

$username = $_SESSION['Username'];

// Fetch employer data
$emp = null;
$stmt = $conn->prepare("SELECT * FROM employer WHERE username = ?");
if ($stmt) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $emp = $result->fetch_assoc();
    $stmt->close();
} else {
    error_log("Database error: " . $conn->error);
}

// Handle form submission
$update_success = false;
if (isset($_POST['editEmployer'])) {
    $fields = ['name', 'email', 'contactNo', 'country', 'company', 'profile_sum'];
    $updates = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field]) && !empty(trim($_POST[$field]))) {
            $updates[$field] = test_input($_POST[$field]);
        } else {
            // Handle required fields
            if ($field === 'name' || $field === 'email' || $field === 'contactNo' || $field === 'country') {
                error_log("Required field '$field' is missing or empty.");
                die("Required field '$field' is missing or empty.");
            }
            $updates[$field] = ''; // Optional fields can be empty
        }
    }

    $sql = "UPDATE employer SET name=?, email=?, contactNo=?, country=?, company=?, profile_sum=? WHERE username=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sssssss', $updates['name'], $updates['email'], $updates['contactNo'], $updates['country'], $updates['company'], $updates['profile_sum'], $username);
        if ($stmt->execute()) {
            $update_success = true;
            header('Location: employerProfile.php?update=success');
            exit;
        } else {
            error_log("Update error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Database error during update: " . $conn->error);
    }
}

// Function to sanitize inputs
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>


<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/packed_animator.js'></script>
    <link rel="stylesheet" href="../src/css/styles.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-teal-100 via-lime-100 to-yellow-100 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-900 transition-colors duration-700">
    <div class="fixed top-4 right-4 z-50">
        <button id="theme-toggle" class="p-2 bg-white dark:bg-gray-700 rounded-full shadow-lg transition-colors duration-300" aria-label="Toggle theme">
            <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.36 6.36l-1.42-1.42M7.05 7.05L5.64 5.64m12.02 0l-1.41 1.41M7.05 16.95l-1.41 1.41"/></svg>
            <svg id="moonIcon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-800 dark:text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z"/></svg>
        </button>
    </div>

    <div class="flex items-center justify-center py-12">
        <div class="w-full max-w-lg bg-white dark:bg-gray-800/70 backdrop-blur-md rounded-2xl shadow-xl p-8 transition-transform transform hover:scale-[1.01] duration-500" data-aos="zoom-in">
            <h2 class="text-3xl font-extrabold text-indigo-700 dark:text-cyan-400 mb-8 text-center tracking-tight">Edit Your Profile</h2>
            <form method="post" class="space-y-6">
                <?php
                $fields = [
                    'name' => 'Full Name',
                    'email' => 'Email Address',
                    'contactNo' => 'Phone Number',
                    'country' => 'Current Address',
                    'company' => 'Company Name',
                    'profile_sum' => 'Brief Profile Summary'
                ];
                ?>

                <div data-aos="fade-up" data-aos-delay="100">
                    <label for="name" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['name']?></label>
                    <input type="text" id="name" name="name" value="<?=htmlspecialchars($emp['name'] ?? '')?>" class="mt-2 w-full px-4 py-3 border border-blue-300 dark:border-blue-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-aos="fade-up" data-aos-delay="200">
                    <div>
                        <label for="email" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['email']?></label>
                        <input type="email" id="email" name="email" value="<?=htmlspecialchars($emp['email'] ?? '')?>" class="mt-2 w-full px-4 py-3 border border-green-300 dark:border-green-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" required />
                    </div>
                    <div>
                        <label for="contactNo" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['contactNo']?></label>
                        <input type="tel" id="contactNo" name="contactNo" value="<?=htmlspecialchars($emp['contactNo'] ?? '')?>" class="mt-2 w-full px-4 py-3 border border-yellow-300 dark:border-yellow-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" />
                        <small class="text-gray-600 dark:text-gray-400">e.g., +2547XXXXXXXX</small>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-delay="400">
                    <label for="country" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['country']?>/Country</label>
                    <input type="text" id="country" name="country" value="<?=htmlspecialchars($emp['country'] ?? '')?>" class="mt-2 w-full px-4 py-3 border border-indigo-300 dark:border-indigo-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-aos="fade-up" data-aos-delay="500">
                    <div>
                        <label for="company" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['company']?></label>
                        <input type="text" id="company" name="company" value="<?=htmlspecialchars($emp['company'] ?? '')?>" class="mt-2 w-full px-4 py-3 border border-lime-300 dark:border-lime-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300" />
                    </div>
                    <div>
                        <label for="profile_sum" class="block text-lg font-semibold text-gray-800 dark:text-gray-200"><?=$fields['profile_sum']?></label>
                        <textarea id="profile_sum" name="profile_sum" rows="4" class="mt-2 w-full px-4 py-3 border border-teal-300 dark:border-teal-700 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-100 transition-all duration-300"><?=htmlspecialchars($emp['profile_sum'] ?? '')?></textarea>
                    </div>
                </div>

                <div class="text-center" data-aos="fade-up" data-aos-delay="600">
                    <button type="submit" name="editEmployer" class="px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold rounded-full shadow-md transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:shadow-lg dark:shadow-indigo-800/50">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
