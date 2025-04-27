<?php include('db/server.php');
if (isset($_SESSION["Username"])) {
    $username = $_SESSION["Username"];
    if ($_SESSION["Usertype"] == 1) {
        $linkPro = "freelancerProfile.php";
        $linkEditPro = "editFreelancer.php";
        $linkBtn = "applyJob.php";
        $textBtn = "Apply for this job";
    } else {
        $linkPro = "employerProfile.php";
        $linkEditPro = "editEmployer.php";
        $linkBtn = "editJob.php";
        $textBtn = "Edit the job offer";
    }
} else {
    $username = "";
    //header("location: index.php");
}

if (isset($_SESSION["f_user"])) {
    $f_user = $_SESSION["f_user"];
    $_SESSION["msgRcv"] = $f_user;
}

$sql = "SELECT * FROM freelancer WHERE username='$f_user'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        $name = $row["Name"];
        $email = $row["email"];
        $contactNo = $row["contact_no"];
        $gender = $row["gender"];
        $birthdate = $row["birthdate"];
        $address = $row["address"];
        $prof_title = $row["prof_title"];
        $skills = $row["skills"];
        $profile_sum = $row["profile_sum"];
        $education = $row["education"];
        $experience = $row["experience"];
    }
} else {
    echo "0 results";
}

if (isset($_POST["rate-btn"])) {
    $rate = $_POST["rating"];


    $sql = "INSERT INTO rating (f_name,rating,rated_by)
    VALUES ('$f_user', '$rate', '$username')";

    $result = $conn->query($sql);
    if ($result === true) {
        echo "Result inserted successfully";
        header("location: viewFreelancer.php");
    }

    $sql = "SELECT AVG($rate) FROM ratings";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            echo "Average rating: " . $row["AVG(rate)"];
        }
    } else {
        echo "0 results";
    }
}

$sql = "SELECT f_name, rating, rated_by, last_rated FROM rating";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $Name = $row['f_name'];
        $Rating = $row['rating'];
        $RatedBy = $row['rated_by'];
        $LastRated = $row['last_rated'];
    }
} else {
    echo "0 results";
}




// Initialize the variables
$consumer_key = 'Lms5EIf2gK16o1sptYPaA3HsfbGUd7fv';
$consumer_secret = 'dgfk1IefQx1SnG1A';
$Business_Code = '174379';
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
$Type_of_Transaction = 'CustomerPayBillOnline';
$Token_URL = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$phone_number = $_POST['phone_number'];
$OnlinePayment = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$total_amount = $_POST['amount'];
$CallBackURL = 'https://server.mradi.co/api/v1/stkpush';
$Time_Stamp = date("Ymdhis");
$password = base64_encode($Business_Code . $Passkey . $Time_Stamp);

//generate authentication token.
$curl_Tranfer = curl_init();
curl_setopt($curl_Tranfer, CURLOPT_URL, $Token_URL);
$credentials = base64_encode($consumer_key . ':' . $consumer_secret);
curl_setopt($curl_Tranfer, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
curl_setopt($curl_Tranfer, CURLOPT_HEADER, false);
curl_setopt($curl_Tranfer, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl_Tranfer, CURLOPT_SSL_VERIFYPEER, false);
$curl_Tranfer_response = curl_exec($curl_Tranfer);
$token = json_decode($curl_Tranfer_response)->access_token;

// Initiate the STK push on the users’ phone
$curl_Tranfer2 = curl_init();
curl_setopt($curl_Tranfer2, CURLOPT_URL, $OnlinePayment);
curl_setopt($curl_Tranfer2, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $token));
$curl_Tranfer2_post_data = [
    'BusinessShortCode' => $Business_Code,
    'Password' => $password,
    'Timestamp' => $Time_Stamp,
    'TransactionType' => $Type_of_Transaction,
    'Amount' => $total_amount,
    'PartyA' => $phone_number,
    'PartyB' => $Business_Code,
    'PhoneNumber' => $phone_number,
    'CallBackURL' => $CallBackURL,
    'AccountReference' => 'Elvis',
    'TransactionDesc' => 'Freelance application payments',
];
$data2_string = json_encode($curl_Tranfer2_post_data);
curl_setopt($curl_Tranfer2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_Tranfer2, CURLOPT_POST, true);
curl_setopt($curl_Tranfer2, CURLOPT_POSTFIELDS, $data2_string);
curl_setopt($curl_Tranfer2, CURLOPT_HEADER, false);
curl_setopt($curl_Tranfer2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl_Tranfer2, CURLOPT_SSL_VERIFYHOST, 0);
$curl_Tranfer2_response = json_decode(curl_exec($curl_Tranfer2));
echo json_encode($curl_Tranfer2_response, JSON_PRETTY_PRINT);



if ($curl_Tranfer2_response->ResponseCode == 0) {
    echo "The transaction was successful";

    $sql = "INSERT INTO payments (freelancer_name, amount, phone_number, paid_by)
  VALUES ('$f_user', '$total_amount', '$phone_number', '$username')";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

?>


<!DOCTYPE html>
<html>

    <head>
        <title>Freelancer profile</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-theme.min.css">
        <link rel="stylesheet" type="text/css" href="awesome/css/fontawesome-all.min.css">

        <style>
        body {
            padding-top: 3%;
            margin: 0;
        }

        .card {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            background: #fff
        }

        input[type="radio"] {
            display: none;
        }

        label {
            font-size: 30px;
            color: #ddd;
        }

        input[type="radio"]:checked~label {
            color: blue;
        }

        .rating-text {
            color: black
        }
        </style>

    </head>

    <body>

        <!--Navbar menu-->
        <nav class="navbar navbar-inverse navbar-fixed-top" id="my-navbar">
            <div class="container">
                <div class="navber-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a href="index.php" class="navbar-brand">Freelance Marketplace</a>
                </div>
                <div class="collapse navbar-collapse" id="navbar-collapse">
                    <ul class="nav navbar-nav navbar-right">
                        <li><a href="allJob.php">Browse all jobs</a></li>
                        <li><a href="allFreelancer.php">Browse Freelancers</a></li>
                        <li><a href="allEmployer.php">Browse Employers</a></li>
                        <li class="dropdown" style="background:#000;padding:0 20px 0 20px;">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#"><span
                                    class="glyphicon glyphicon-user"></span> <?php echo $username; ?>
                            </a>
                            <ul class="dropdown-menu list-group list-group-item-info">
                                <a href="<?php echo $linkPro; ?>" class="list-group-item"><span
                                        class="glyphicon glyphicon-home"></span> View profile</a>
                                <a href="<?php echo $linkEditPro; ?>" class="list-group-item"><span
                                        class="glyphicon glyphicon-inbox"></span> Edit Profile</a>
                                <a href="message.php" class="list-group-item"><span
                                        class="glyphicon glyphicon-envelope"></span> Messages</a>
                                <a href="logout.php" class="list-group-item"><span
                                        class="glyphicon glyphicon-ok"></span> Logout</a>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!--End Navbar menu-->


        <!--main body-->
        <div style="padding:1% 3% 1% 3%;">
            <div class="row">

                <!--Column 1-->
                <div class="col-lg-3">

                    <!--Main profile card-->
                    <div class="card" style="padding:20px 20px 5px 20px;margin-top:20px">
                        <p></p>
                        <img src="image/img04.jpg">
                        <h2><?php echo $name; ?></h2>
                        <p><span class="glyphicon glyphicon-user"></span> <?php echo $f_user; ?></p>
                        <center><a href="sendMessage.php" class="btn btn-info"><span
                                    class="glyphicon glyphicon-envelope"></span> Send Message</a></center>
                        <p></p>
                    </div>
                    <!--End Main profile card-->

                    <!--Contact Information-->
                    <div class="card" style="padding:20px 20px 5px 20px;margin-top:20px">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h4>Contact Information</h4>
                            </div>
                        </div>
                        <div class="panel panel-success">
                            <div class="panel-heading">Email</div>
                            <div class="panel-body"><?php echo $email; ?></div>
                        </div>
                        <div class="panel panel-success">
                            <div class="panel-heading">Mobile</div>
                            <div class="panel-body"><?php echo $contactNo; ?></div>
                        </div>
                        <div class="panel panel-success">
                            <div class="panel-heading">Address</div>
                            <div class="panel-body"><?php echo $address; ?></div>
                        </div>
                    </div>

                    <!--End Contact Information-->

                    <!--Reputation-->
                    <div class="card" style="padding:20px 20px 5px 20px;margin-top:20px">
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4>Reputation</h4>
                            </div>
                        </div>


                    </div>
                    <!--End Reputation-->

                </div>
                <!--End Column 1-->

                <!--Column 2-->
                <div class="col-lg-7">

                    <!--Freelancer Profile Details-->
                    <div class="card" style="padding:20px 20px 5px 20px;margin-top:20px">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3>Freelancer Profile Details</h3>
                            </div>
                        </div>
                        <div class="panel panel-primary">
                            <div class="panel-heading">Professional Title</div>
                            <div class="panel-body">
                                <h4><?php echo $prof_title; ?></h4>
                            </div>
                        </div>
                        <div class="panel panel-primary">
                            <div class="panel-heading">Skills</div>
                            <div class="panel-body">
                                <h4><?php echo $skills; ?></h4>
                            </div>
                        </div>
                        <div class="panel panel-primary">
                            <div class="panel-heading">Profile Summery</div>
                            <div class="panel-body">
                                <h4><?php echo $profile_sum; ?></h4>
                            </div>
                        </div>
                        <div class="panel panel-primary">
                            <div class="panel-heading">Education</div>
                            <div class="panel-body">
                                <h4><?php echo $education; ?></h4>
                            </div>
                        </div>
                        <div class="panel panel-primary">
                            <div class="panel-heading">Experience</div>
                            <div class="panel-body">
                                <h4><?php echo $experience; ?></h4>
                            </div>
                        </div>
                        <div class=" col-lg-12 m-3">
                            <form action="" method="post">
                                <div class="form-group">
                                    <label class="col-sm-4 m-3 control-label rating-text">Rate FreeLancer</label>
                                    <div class="col-sm-5">
                                        <!-- <input type="number" class="form-control" name="rating" /> -->
                                        <input type="radio" id="star5" name="rating" value="5">
                                        <label for="star5">★</label>
                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4">★</label>
                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3">★</label>
                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2">★</label>
                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1">★</label>
                                    </div>

                                </div>
                                <div class="form-group">
                                    <div class="col-12">
                                        <!-- Do NOT use name="submit" or id="submit" for the Submit button -->
                                        <button type="submit" name="rate-btn" class="btn btn-info btn-lg">Post</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="panel panel-warning">
                            <div class="panel-heading">Ratings</div>

                            <div class="card">
                                <table class="table">
                                    <th class="m-3">
                                        <tr>
                                            <td>Username</td>
                                            <td>Rating</td>
                                            <td>Rated By</td>
                                            <td>Last Rated</td>
                                        </tr>
                                    </th>
                                    <tbody>
                                        <tr>
                                            <td><?php // Change this value to test different conditions
                                            echo $Name
                                            ?></td>
                                            <td><?php // Change this value to test different conditions
                                            // This should be the actual Rai$Rating value

                                            if ($Rating == 0) {
                                                echo "Not rated";
                                            } elseif ($Rating >= 1 && $Rating <= 2) {
                                                echo "Poor";
                                            } elseif ($Rating == 3) {
                                                echo "Average";
                                            } elseif ($Rating >= 4 && $Rating <= 5) {
                                                echo "Good";
                                            } else {
                                                echo "Invalid Rating";
                                            }

                                            // echo $Rating
                                            ?></td>

                                            <td><?php // Change this value to test different conditions

                                            echo $RatedBy
                                            ?></td>

                                            <td>
                                                <?php // Change this value to test different conditions

                                            echo $LastRated
                                            ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>



                        </div>

                    </div>


                </div>
                <!--End Freelancer Profile Details-->

                <div class="col-2 card">
                    <h4 class="text-center mt-5">Payments</h4>
                    <div class="card-body">
                        <p class="mb-0 text-center">Pay FreeLancer Using Mpesa:</p>
                        <form action="" method="POST">
                            <label for="phone_number">Phone Number:</label><br>
                            <input type="text" id="phone_number" name="phone_number"><br>
                            <label for="amount">Amount:</label><br>
                            <input type="text" id="amount" name="amount"><br>
                            <input type="submit" value="Pay">
                        </form>

                    </div>
                </div>

            </div>
            <!--End Column 2-->


            <!--Column 3-->

            <!--End Column 3-->

        </div>
        </div>
        <!--End main body-->


        <!--Footer-->
        <div class="text-center" style="padding:4%;background:#222;color:#fff;margin-top:20px;">
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
                    <p>&copy 2023</p>
                </div>
                <div class="col-lg-3">
                    <h3>Contact Us</h3>
                    <p>+254 725 146 071</p>
                    <p>Nairobi, Kenya</p>
                    <p>&copy CUET 2023</p>
                </div>
                <div class="col-lg-3">
                    <h3>Social Contact</h3>
                    <p style="font-size:20px;color:#3B579D;"><i class="fab fa-facebook-square"> Facebook</i></p>
                    <p style="font-size:20px;color:#D34438;"><i class="fab fa-google-plus-square"> Google</i></p>
                    <p style="font-size:20px;color:#2CAAE1;"><i class="fab fa-twitter-square"> Twitter</i></p>
                    <p style="font-size:20px;color:#0274B3;"><i class="fab fa-linkedin"> Linkedin</i></p>
                </div>
            </div>
        </div>
        <!--End Footer-->


        <script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script>
        <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
    </body>

</html>
