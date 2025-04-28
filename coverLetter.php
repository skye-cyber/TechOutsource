<?php
include('db/server.php');

if (isset($_SESSION["Username"])) {
    $username = $_SESSION["Username"];
} else {
    $username = "";
    // header("location: index.php");
}

if (isset($_SESSION["c_letter"])) {
	$c_letter = __DIR__ . '/' . $_SESSION["c_letter"]; // Get the absolute path
}

// Function to determine file type
function getFileType($filePath) {
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $filePath);
    finfo_close($fileInfo);
    return $mimeType;
}

// Function to display cover letter
function displayCoverLetter($filePath) {
	if (!file_exists($filePath)) {
		return 'FILE NOT FOUND!'; // Return 'unsupported' if the file does not exist
	}
    $fileType = getFileType($filePath);
    if ($fileType === 'application/pdf') {
        return "<iframe src='$filePath' width='100%' height='600px'></iframe>";
    } elseif ($fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
		 $encodedFilePath = urlencode($filePath);
        return "<iframe src='https://view.officeapps.live.com/op/embed.aspx?src=$encodedFilePath' width='100%' height='600px' frameborder='0'></iframe>";
    } else {
        return "<p>Unsupported file type.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cover Letter</title>
    <link rel="stylesheet" href="src/css/styles.css">
    <script src='../src/js/packed_animator.js'></script>
    <script src='../src/js/themes.js'></script>
    <script src='../src/js/smoothScroll.js'></script>
    <style>
        body {
            padding-top: 3%;
            margin: 0;
        }
        .card {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            background: #fff;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .footer {
            padding: 4%;
            background: #222;
            color: #fff;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar menu -->
    <nav class="navbar navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Freelance Marketplace</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="allJob.php">Browse all jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="allFreelancer.php">Browse Freelancers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="allEmployer.php">Browse Employers</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="employerProfile.php"><i class="fas fa-home"></i> View profile</a>
                            <a class="dropdown-item" href="editEmployer.php"><i class="fas fa-inbox"></i> Edit Profile</a>
                            <a class="dropdown-item" href="message.php"><i class="fas fa-envelope"></i> Messages</a>
                            <a class="dropdown-item" href="registration/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- End Navbar menu -->

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card p-4">
                    <h2 class="card-title">Cover Letter</h2>
                    <div class="card-body">
                        <?php
                        if (isset($c_letter)) {
                            echo displayCoverLetter($c_letter);
                        } else {
                            echo "<p>No cover letter available.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="row">
            <div class="col-lg-3">
                <h3>Quick Links</h3>
                <p><a href="index.php">Home</a></p>
                <p><a href="allJob.php">Browse all jobs</a></p>
                <p><a href="allFreelancer.php">Browse Freelancers</a></p>
                <p><a href="allEmployer.php">Browse Employers</a></p>
            </div>
            <div class="col-lg-3">
                <h3>About Us</h3>
                <p>Freelance Marketplace</p>
                <p>Nairobi, Kenya</p>
                <p>&copy; 2023</p>
            </div>
            <div class="col-lg-3">
                <h3>Contact Us</h3>
                <p>+254 725 146 071</p>
                <p>Nairobi, Kenya</p>
                <p>&copy; 2023</p>
            </div>
            <div class="col-lg-3">
                <h3>Social Contact</h3>
                <p style="font-size:20px;color:#3B579D;"><i class="fab fa-facebook-square"> Facebook</i></p>
                <p style="font-size:20px;color:#D34438;"><i class="fab fa-google-plus-square"> Google</i></p>
                <p style="font-size:20px;color:#2CAAE1;"><i class="fab fa-twitter-square"> Twitter</i></p>
                <p style="font-size:20px;color:#0274B3;"><i class="fab fa-linkedin"> Linkedin</i></p>
            </div>
        </div>
    </footer>
    <!-- End Footer -->

    <script src="src/js/jquery-3.2.1.min.js"></script>
    <script src="src/js/bootstrap.min.js"></script>
</body>
</html>
