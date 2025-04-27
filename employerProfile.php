<?php
include('db/server.php');

$username = $_SESSION["Username"] ?? "";

if (isset($_POST["jid"])) {
    $_SESSION["job_id"] = $_POST["jid"];
    header("location: jobDetails.php");
    exit();
}

if (isset($_POST["f_user"])) {
    $_SESSION["f_user"] = $_POST["f_user"];
    header("location: viewFreelancer.php");
    exit();
}

// Fetch employer info
$emp = [];
$sql = "SELECT * FROM employer WHERE username=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $emp = $result->fetch_assoc();
}

function fetchOffers($conn, $username, $valid) {
    $sql = "SELECT * FROM job_offer WHERE e_username=? AND valid=? ORDER BY timestamp DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $username, $valid);
    $stmt->execute();
    return $stmt->get_result();
}

function fetchHires($conn, $username, $valid) {
    $sql = "SELECT jo.job_id, jo.title, s.f_username, jo.timestamp FROM job_offer jo JOIN selected s ON jo.job_id=s.job_id WHERE s.e_username=? AND s.valid=? ORDER BY jo.timestamp DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $username, $valid);
    $stmt->execute();
    return $stmt->get_result();
}

$curOffers = fetchOffers($conn, $username, 1);
$prevOffers = fetchOffers($conn, $username, 0);
$hired = fetchHires($conn, $username, 1);
$prev_hired = fetchHires($conn, $username, 0);
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employer Profile</title>
  <script src='../src/js/themes.js'></script>
  <script src='../src/js/packed_animator.js'></script>
  <link rel="stylesheet" href="../src/css/styles.css">
</head>
<body class="bg-gradient-to-br from-pink-100 via-orange-50 to-blue-100 dark:from-blue-900 dark:via-purple-800 dark:to-pink-900 min-h-screen flex flex-col gradientTransition">

<!-- Theme Toggle -->
<div class="absolute top-1 right-4 z-10 select-none">
  <label class="inline-flex items-center cursor-pointer">
    <input id="theme-toggle" type="checkbox" class="sr-only peer" />
    <div class="w-12 h-7 bg-gray-300 rounded-full peer-checked:bg-indigo-400 transition-colors"></div>
    <div class="absolute w-6 h-6 bg-white rounded-full shadow transform peer-checked:translate-x-6 transition-transform transition-colors duration-700"></div>
  </label>
</div>

<!-- Main Container -->
<div class="container mx-auto p-4 flex-1 mt-2" data-aos="fade-up" data-aos-duration="800">
  <div class="grid grid-cols-12 gap-4">

    <!-- Sidebar -->
    <aside class="col-span-12 lg:col-span-3 space-y-4" data-aos="fade-right" data-aos-delay="200">
      <div class="bg-white dark:bg-gray-800 bg-opacity-80 rounded-2xl p-6 shadow-lg backdrop-blur-md transition-colors duration-700">
        <section class="flex justify-center items-center">
            <div class="w-24 h-24 bg-gradient-to-tr from-purple-300 to-pink-300 dark:from-indigo-600 dark:to-purple-600 rounded-full flex items-center justify-center shadow-md transition-all transform duration-700 hover:rotate-6">
            <span class="text-3xl font-semibold text-white transition-colors duration-1000"><?php echo $username ? htmlspecialchars(substr($username, 0, 1)) : 'U'; ?></span>
            </div>
        </section>
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-50"><?php echo htmlspecialchars($emp['Name'] ?? ''); ?></h2>
        <p class="text-gray-600 dark:text-gray-300 mb-4">@<?php echo htmlspecialchars($username); ?></p>
        <nav class="space-y-2">
          <a href="index.php" class="block px-4 py-2 bg-indigo-700 text-white rounded-full hover:bg-[#aa55ff] transition-colors duration-700">Home</a>
          <a href="postJob.php" class="block px-4 py-2 bg-indigo-400 text-white rounded-full hover:bg-indigo-500 transition-colors duration-700">Post a Job</a>
          <a href="editEmployer.php" class="block px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-700">Edit Profile</a>
          <a href="message.php" class="block px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-700">Messages</a>
          <a href="logout.php" class="block px-4 py-2 bg-red-400 text-white rounded-full hover:bg-red-500 transition-colors duration-700">Logout</a>
        </nav>
      </div>

      <div class="bg-white dark:bg-gray-800 bg-opacity-80 rounded-2xl p-6 shadow-lg backdrop-blur-md transition-colors duration-700">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2 transition-colors duration-700">Contact Info</h4>
        <p class="text-gray-600 dark:text-gray-300 transition-colors duration-700"><strong>Email:</strong> <span class="text-gray-600 dark:text-sky-500"><?php echo htmlspecialchars($emp['email'] ?? ''); ?></span></p>
        <p class="text-gray-600 dark:text-gray-300 transition-colors duration-700"><strong>Phone:</strong><span class="text-gray-600 dark:text-blue-500"> <?php echo htmlspecialchars($emp['contactNo'] ?? ''); ?></span></p>
        <p class="text-gray-600 dark:text-gray-300 transition-colors duration-700"><strong>Address:</strong> <span class="text-gray-600 dark:text-[#ff557f] transition-colors duration-700"><?php echo htmlspecialchars($emp['country'] ?? ''); ?></span></p>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="col-span-12 lg:col-span-9 space-y-6" data-aos="fade-left" data-aos-delay="400">
      <div class="bg-white dark:bg-gray-800 bg-opacity-80 rounded-2xl p-6 shadow-lg backdrop-blur-md transition-colors duration-700">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 transition-colors duration-700">Employer Details</h3>
        <p class="text-gray-600 dark:text-gray-300 transition-colors duration-700"><strong>Company:</strong> <span class="text-gray-600 dark:text-sky-500"><?php echo htmlspecialchars($emp['company'] ?? ''); ?></span></p>
        <p class="text-gray-600 dark:text-gray-300 mt-2 transition-colors duration-700"><strong>Summary:</strong><span class="text-gray-600 dark:text-pink-500"> <?php echo htmlspecialchars($emp['profile_sum'] ?? ''); ?></span></p>
      </div>

      <?php function renderTable($title, $resultSet) { ?>
      <div class="bg-white dark:bg-slate-900 bg-opacity-80 rounded-2xl p-6 shadow-lg backdrop-blur-md transition-colors duration-700" data-aos="fade-up">
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3 transition-colors duration-700"><?php echo htmlspecialchars($title); ?></h4>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors duration-700">
                <th class="py-2 dark:text-[#00aaff] transition-colors duration-700">Job ID</th>
                <th class="py-2 dark:text-[#55aaff] transition-colors duration-700">Title</th>
                <?php if (str_contains($title, 'Freelancer')) echo '<th class="py-2 py-2 dark:text-[#a3a3f3]">Freelancer</th>'; ?>
                <th class="py-2 dark:text-[#5555ff] transition-colors duration-700">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($resultSet && $resultSet->num_rows > 0): while ($r = $resultSet->fetch_assoc()): ?>
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-700">
                  <td class="py-2 dark:text-[#ffaaff] transition-colors duration-700"><?php echo htmlspecialchars($r['job_id']); ?></td>
                  <td class="py-2">
                    <form method="post">
                      <input type="hidden" name="jid" value="<?php echo htmlspecialchars($r['job_id']); ?>">
                      <button type="submit" class="text-indigo-500 hover:underline dark:text-[#00aaff] transition-colors duration-700"><?php echo htmlspecialchars($r['title']); ?></button>
                    </form>
                  </td>
                  <?php if (isset($r['f_username'])): ?>
                    <td class="py-2 dark:text-gray-100 transition-colors duration-700"><?php echo htmlspecialchars($r['f_username']); ?></td>
                  <?php endif; ?>
                  <td class="py-2 dark:text-gray-100 transition-colors duration-700"><?php echo htmlspecialchars($r['timestamp']); ?></td>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="4" class="py-2 text-center dark:text-gray-100 transition-colors duration-700">Nothing to show</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php } ?>

      <?php renderTable('Current Job Offerings', $curOffers); ?>
      <?php renderTable('Previous Job Offerings', $prevOffers); ?>
      <?php renderTable('Currently Hired Freelancers', $hired); ?>
      <?php renderTable('Previous Freelancers', $prev_hired); ?>

    </main>
  </div>
</div>

</body>
</html>
