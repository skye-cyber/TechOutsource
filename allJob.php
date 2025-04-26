<?php
include('db/server.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine user links
$username = $_SESSION['Username'] ?? '';
$usertype = $_SESSION['Usertype'] ?? 0;
if ($username) {
    if ($usertype == 1) {
        $linkPro = 'freelancerProfile.php';
        $linkEditPro = 'editFreelancer.php';
        $textBtn = 'Apply for this job';
    } else {
        $linkPro = 'employerProfile.php';
        $linkEditPro = 'editEmployer.php';
        $textBtn = 'Edit Job Offer';
    }
}

// Handle job details redirect
if (isset($_POST['jid'])) {
    $_SESSION['job_id'] = $_POST['jid'];
    header('Location: jobDetails.php');
    exit;
}

// Search filters
$filters = ['s_title' => 'title', 's_type' => 'type', 's_employer' => 'e_username', 's_id' => 'job_id'];
$where = [];
foreach ($filters as $input => $col) {
    if (!empty($_POST[$input])) {
        $val = $conn->real_escape_string($_POST[$input]);
        $where[] = "$col = '$val'";
    }
}
$sql = 'SELECT * FROM job_offer WHERE valid=1' . (!empty($where) ? ' AND ' . implode(' AND ', $where) : '') . ' ORDER BY timestamp DESC';
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.dark == 'true' }" :class="{ 'dark': dark }">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Job Offers</title>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link rel="stylesheet" href="src/css/styles.css">
  <script src='../src/js/packed_animator.js'></script>
  <script src='../src/js/themes.js'></script>
  <script src='../src/js/smoothScroll.js'></script>
  <style>
    @layer utilities {
      .bg-dark { background-color: #1a202c; }
      .text-dark { color: #e2e8f0; }
    }
    [x-cloak] { display: none; }
  </style>
</head>
<body class="bg-[#3a3b7f] backdrop-blur-sm dark:bg-dark text-gray-900 dark:text-dark transition-colors duration-700">

<nav class="bg-white dark:bg-gray-800 shadow sticky top-0 z-50 transition-colors duration-700">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-bold text-blue-600 dark:text-blue-400 hover:scale-105 dark:text-white transition-colors duration-700">Marketplace</a>
    <div class="flex items-center space-x-6">
      <a href="allJob.php" class="hover:text-blue-600 dark:hover:text-blue-400 transition dark:text-white transition-colors duration-700">Jobs</a>
      <a href="allFreelancer.php" class="hover:text-blue-600 dark:hover:text-blue-400 transition dark:text-white transition-colors duration-700">Freelancers</a>
      <a href="allEmployer.php" class="hover:text-blue-600 dark:hover:text-blue-400 transition dark:text-white transition-colors duration-700">Employers</a>
      <div class="relative" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center space-x-2">
          <svg class="w-6 h-6 fill-slate-900 dark:fill-white hover:fill-blue-300 transition-colors duration-700" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-4.2 0-8 2.1-8 6v2h16v-2c0-3.9-3.8-6-8-6z"/></svg>
          <span><?php echo htmlspecialchars($username); ?></span>
        </button>
        <div x-show="open" @click.away="open = false" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg">
          <a href="<?php echo $linkPro; ?>" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white">Profile</a>
          <a href="<?php echo $linkEditPro; ?>" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white">Edit Profile</a>
          <a href="message.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white">Messages</a>
          <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-red-500 dark:text-white">Logout</a>
        </div>
      </div>
      <button id="theme-toggle" @click="dark = !dark; localStorage.dark = dark" class="bg-gray-300 dark:bg-gray-600 p-2 rounded-lg dark:bg-sky-400">
        <span x-show="!dark">🌙</span><span x-show="dark">☀️</span>
      </button>
    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <div class="lg:col-span-3 space-y-6">
      <h1 class="text-3xl font-bold text-cyan-200 dark:text-cyan-100 transition-colors duration-700">Job Offers</h1>
      <div class="grid gap-6 md:grid-cols-2 transition-colors duration-700">
        <?php if($result->num_rows): while($job = $result->fetch_assoc()): ?>
          <form method="post" action="allJob.php" data-aos="fade-up" class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow hover:shadow-lg transition-colors duration-1000">
            <input type="hidden" name="jid" value="<?= $job['job_id'] ?>">
            <h2 class="text-xl font-semibold mb-2 text-gray-800 dark:text-gray-200 transition-colors duration-700"><?= htmlspecialchars($job['title']) ?></h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-700">Type: <span class="font-medium"><?= htmlspecialchars($job['type']) ?></span></p>
            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-700">Budget: <span class="font-medium"><?= htmlspecialchars($job['budget']) ?></span></p>
            <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-700">Posted by: <?= htmlspecialchars($job['e_username']) ?></p>
            <p class="text-xs text-gray-500 mt-4"><?= date('M j, Y', strtotime($job['timestamp'])) ?></p>
            <button type="submit" class="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg transition-colors duration-700"><?= $textBtn ?></button>
          </form>
        <?php endwhile; else: ?>
          <p class="col-span-3 text-center text-gray-500 dark:text-gray-400">No jobs found.</p>
        <?php endif; ?>
      </div>
    </div>

    <aside class="space-y-6">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow transition-colors duration-700" data-aos="fade-left">
        <h2 class="text-2xl font-semibold mb-4 text-blue-600 dark:text-blue-400 transition-colors duration-700">Search</h2>
        <?php foreach(['s_title'=>'Title','s_type'=>'Type','s_employer'=>'Employer','s_id'=>'Job ID'] as $input=>$label): ?>
          <form method="post" action="allJob.php" class="space-y-2 mb-4">
            <label class="block text-gray-700 dark:text-gray-300 transition-colors duration-700"><?= $label ?></label>
            <input name="<?= $input ?>" class="w-full p-2 rounded border focus:ring-2 focus:ring-blue-400 dark:bg-gray-700 dark:border-gray-600 transition-colors duration-700" placeholder="Search by <?= $label ?>">
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg transition-colors duration-700">Go</button>
          </form>
        <?php endforeach; ?>
        <form method="post" action="allJob.php" class="space-y-2">
          <button name="recentJob" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg transition-colors duration-700">Newest First</button>
          <button name="oldJob" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg transition-colors duration-700">Oldest First</button>
        </form>
      </div>
    </aside>
  </div>
</main>

<footer class="bg-gray-100 dark:bg-gray-900 text-center py-6 mt-12 border-t dark:border-gray-700">
  <p class="text-sm text-gray-600 dark:text-gray-400">&copy; <?= date('Y') ?> Freelance Marketplace</p>
</footer>
</body>
</html>
