<?php
include('server.php');

if (isset($_SESSION["Username"])) {
    $username = $_SESSION["Username"];
    if ($_SESSION["Usertype"] == 1) {
        header("location: freelancerProfile.php");
    } else {
        header("location: employerProfile.php");
    }
} else {
    $username = "";
    // header("location: index.php");
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Freelance Marketplace</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="awesome/css/fontawesome-all.min.css">
<style>
body {
    padding-top: 3%;
    margin: 0;
}
.header1 {
    background-color: #EEEEEE;
    padding-left: 1%;
}
.card {
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
    background: #fff;
}
</style>
</head>
<body class="max-w-[100vw] overflow-x-hidden bg-gray-100 text-gray-800 dark:bg-gradient-to-tr from-blue-500 to-sky-500 dark:text-lightgray">

<!-- Navbar menu -->
<nav class="bg-gray-800 text-white fixed w-full z-10 top-0">
<div class="container mx-auto px-4">
<div class="flex items-center justify-between h-16">
<div class="flex items-center space-x-4">
<button class="text-white focus:outline-none md:hidden hover:text-cyan-700" id="navbar-toggle">
<svg class="w-8 h-8 stroke-white hover:stroke-cyan-600" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
</svg>
</button>
<a href="index.php" class="text-xl font-bold hover:text-cyan-600">Freelance Marketplace</a>
</div>
<div class="hidden md:flex space-x-4">
<a href="index.php" class="text-white hover:text-gray-400">Home</a>
<a href="#how" class="text-white hover:text-gray-400">How it works</a>
<a href="#faq" class="text-white hover:text-gray-400">FAQ</a>
<a href="loginReg.php" class="text-white hover:text-gray-400">Login</a>
<a href="loginReg.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Register</a>
</div>
</div>
<section class="relative">
<div class="hidden absolute z-20 left-0 top-0 bg-zinc-700 p-4 rounded-lg rounded-t-none" id="navbar-collapse">
<div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
<a href="index.php" class="block text-white hover:text-gray-300 hover:bg-zinc-600 active:bg-zinc-500 py-2 px-4 rounded transition duration-300">Home</a>
<a href="#how" class="block text-white hover:text-gray-300 hover:bg-zinc-600 active:bg-zinc-500 py-2 px-4 rounded transition duration-300">How it works</a>
<a href="#faq" class="block text-white hover:text-gray-300 hover:bg-zinc-600 active:bg-zinc-500 py-2 px-4 rounded transition duration-300">FAQ</a>
<a href="loginReg.php" class="block text-white hover:text-gray-300 hover:bg-zinc-600 active:bg-zinc-500 py-2 px-4 rounded transition duration-300">Login</a>
<a href="loginReg.php" class="block bg-blue-500 hover:bg-sky-600 active:bg-blue-700 text-white font-bold py-2 px-4 rounded w-fit transition duration-300 transform hover:scale-105">Register</a>
</div>
</div>
</section>
</div>
</nav>
<!-- End Navbar menu -->
<script>
document.getElementById('navbar-toggle').addEventListener('click', function() {
    document.getElementById('navbar-collapse').classList.toggle('hidden');
});
</script>

<!-- Header and slider -->
<div class="header1 mt-6">
<div class="container mx-auto px-4 py-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
<div class="text-center">
<h1 class="text-4xl font-bold mb-4">Freelance Marketplace</h1>
<p class="mb-4">Remember, time is money. Use it properly. Do not waste your time thinking when others are getting things done here.</p>
<a href="loginReg.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg">It's Free!! Join Now!!!</a>
<div class="mt-4 space-x-2">
<a href="#how" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">How it works</a>
<a href="#faq" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg">FAQ</a>
<a href="#category" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Categories</a>
</div>
</div>
<div>
<div id="myCarousel" class="carousel slide" data-ride="carousel">
<ol class="carousel-indicators">
<li data-target="#myCarousel" data-slide-to="0" class="active"></li>
<li data-target="#myCarousel" data-slide-to="1"></li>
<li data-target="#myCarousel" data-slide-to="2"></li>
</ol>
<div class="carousel-inner" role="listbox">
<div class="carousel-item active">
<img src="image/computer.jpg" alt="Work" class="w-full">
<div class="carousel-caption">
<h3 class="text-2xl font-bold">Work</h3>
<p>Work hard to be successful.</p>
</div>
</div>
<div class="carousel-item">
<img src="image/mug.jpg" alt="Time" class="w-full">
<div class="carousel-caption">
<h3 class="text-2xl font-bold">Time</h3>
<p>Do not waste your time.</p>
</div>
</div>
<div class="carousel-item">
<img src="image/coat.jpg" alt="Believe" class="w-full">
<div class="carousel-caption">
<h3 class="text-2xl font-bold">Believe</h3>
<p>Always believe in yourself.</p>
</div>
</div>
</div>
<a class="carousel-control-prev" href="#myCarousel" role="button" data-slide="prev">
<span class="carousel-control-prev-icon" aria-hidden="true"></span>
<span class="sr-only">Previous</span>
</a>
<a class="carousel-control-next" href="#myCarousel" role="button" data-slide="next">
<span class="carousel-control-next-icon" aria-hidden="true"></span>
<span class="sr-only">Next</span>
</a>
</div>
</div>
</div>
</div>
</div>
<!-- End Header and slider -->

<!-- Individual register tip -->
<div class="bg-blue-100 py-8">
<div class="container mx-auto px-4 text-center rounded-md">
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
<div class="card p-8">
<h1 class="text-3xl font-bold mb-4">Need works done?</h1>
<p class="mb-4">It's easy. Simply post a job you need completed and receive competitive bids from freelancers within minutes. Whatever your needs, there will be a freelancer to get it done: from web design, mobile app development, virtual assistants, product manufacturing, and graphic design (and a whole lot more). It is the simplest and safest way to get work done online.</p>
<a href="loginReg.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg">Get Started</a>
</div>
<div class="card p-8 mt-4 md:mt-0">
<h1 class="text-3xl font-bold mb-4">Looking for work?</h1>
<p class="mb-4">If you are an expert in any kind of computer-related or online work, then do not hesitate to join our platform. It is easy to use and payment is secured. It is a great platform for those who are skillful. So do not miss the chance to explore the job posts and make some money.</p>
<a href="loginReg.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Get Started</a>
</div>
</div>
</div>
</div>
<!-- End Individual register tip -->

<!-- Popular Categories -->
<div class="container mx-auto px-4 py-8 text-center rounded-md" id="category">
<h1 class="bg-blue-500 text-white py-4 mb-8">Popular Categories</h1>
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-credit-card text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Web Developer</h3>
<p>Please login and browse our web developers</p>
</a>
</div>
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-mobile-alt text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Mobile Developer</h3>
<p>Please login and browse our mobile developers</p>
</a>
</div>
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-paint-brush text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Graphics Designer</h3>
<p>Please login and browse our graphics designers</p>
</a>
</div>
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-pencil-alt text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Creative Writer</h3>
<p>Please login and browse our creative writers</p>
</a>
</div>
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-chart-line text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Marketing Expert</h3>
<p>Please login and browse our marketing experts</p>
</a>
</div>
<div class="card p-8">
<a href="loginReg.php" class="block">
<i class="fas fa-headset text-4xl mb-4"></i>
<h3 class="text-2xl font-bold mb-2">Virtual Assistant</h3>
<p>Please login and browse our virtual assistants</p>
</a>
</div>
</div>
</div>
<!-- End Popular Categories -->

<!-- How it works -->
<div class="bg-blue-100 py-8" id="how">
<div class="container mx-auto px-4 text-center">
<h1 class="bg-blue-500 text-white py-4 mb-8">How it works</h1>
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
<div class="card p-8 rounded-md">
<h3 class="text-2xl font-bold mb-4">Post Projects For Free</h3>
<p>It's always free to post your project. You’ll automatically begin to receive bids from our freelancers. Also, you can browse through the talent available on our site, and contact them by the contact information.</p>
</div>
<div class="card p-8 rounded-md">
<img src="image/img01.jpg" alt="Post Projects" class="w-full">
</div>
<div class="card p-8 rounded-md">
<h3 class="text-2xl font-bold mb-4">Feel Free To Talk</h3>
<p>It is easier to talk with the freelancers here. So before you hire any freelancer feel free to talk with them. Tell them what you need and get the project done in the shortest possible time.</p>
</div>
<div class="card p-8 rounded-md">
<img src="image/img03.jpg" alt="Feel Free To Talk" class="w-full">
</div>
<div class="card p-8 rounded-md">
<h3 class="text-2xl font-bold mb-4">Build An Employer Profile</h3>
<p>If you have a lot of works to be done or run a small business that needs some freelancers in a daily basis, this is the perfect place for you. Build your employer profile today and start hiring.</p>
</div>
<div class="card p-8 rounded-mds">
<img src="image/img04.jpg" alt="Build An Employer Profile" class="w-full">
</div>
</div>
</div>
</div>
<!-- End How it works -->

<!-- FAQ -->
<div class="container mx-auto px-4 py-8 text-center" id="faq">
<h1 class="bg-blue-600 text-white py-4 mb-8">FAQ</h1>
<div class="space-y-4">
<button class="bg-gray-200 hover:bg-gray-300 text-left w-full py-4 px-6 rounded-lg" onclick="toggleFAQ('faq1')">
<h3 class="text-xl font-bold">What is Freelance Marketplace?</h3>
</button>
<div id="faq1" class="hidden card p-6">
<h4>Freelance marketplace is an online outsourcing platform that puts employers and businesses in contact with a global network of freelancers. Any member can post a project, whether a short- or long-term job, and choose from skilled freelancers who offer bid proposals with rate for completing the work. It's a mutually beneficial arrangement. Employers can have their pick of thousands of freelancers who have the exact skills needed to get the job done, without undertaking the expense and commitment of hiring full-time employees in person. Freelancers can tap into a readily available source of constant part-time and full-time work opportunities from employers who are specifically seeking them out.</h4>
</div>
<button class="bg-gray-200 hover:bg-gray-300 text-left w-full py-4 px-6 rounded-lg" onclick="toggleFAQ('faq2')">
<h3 class="text-xl font-bold">I am an Employer, how will this site work for me?</h3>
</button>
<div id="faq2" class="hidden card p-6">
<h4>You can gain a competitive advantage over your competition by tapping into a skilled global workforce on demand. If you are a small business and can't afford to hire a full-time staff, don't fret! The power of Freelancer is available for small to medium businesses! Whether it's a website that needs building, business cards or stationery that needs designing, a product that needs to be designed or manufactured, or research that needs to be done, this is the place for you! Thousands of skilled workers are ready to start working right now! All you need to do is post a project!</h4>
</div>
<button class="bg-gray-200 hover:bg-gray-300 text-left w-full py-4 px-6 rounded-lg" onclick="toggleFAQ('faq3')">
<h3 class="text-xl font-bold">I am a Freelancer, how will this site work for me?</h3>
</button>
<div id="faq3" class="hidden card p-6">
<h4>With Freelancer, you can work at home and tap into a global network of businesses and projects across a huge range of industries - the ultimate opportunity in job flexibility! Work on what you want, when you want and where you want to! The lifestyle of a freelancer is taking off. By working as a Freelancer online, you can greatly increase your client base and job throughput. To start, all you need to do is sign up and start bidding. It's FREE!</h4>
</div>
<button class="bg-gray-200 hover:bg-gray-300 text-left w-full py-4 px-6 rounded-lg" onclick="toggleFAQ('faq4')">
<h3 class="text-xl font-bold">Do I have to pay to register?</h3>
</button>
<div id="faq4" class="hidden card p-6">
<h4>No. Freelance marketplace is absolutely free to register and explore the posted job offers, freelancers and employers.</h4>
</div>
</div>
</div>
<!-- End FAQ -->

<!-- Footer -->
<div class="bg-gray-800 text-white py-8 mt-8">
<div class="container mx-auto px-4">
<div class="grid grid-cols-1 md:grid-cols-4 gap-8">
<div>
<h3 class="text-xl font-bold mb-4">Quick Links</h3>
<p><a href="index.php" class="hover:text-gray-400">Home</a></p>
<p><a href="#how" class="hover:text-gray-400">How it works</a></p>
<p><a href="#faq" class="hover:text-gray-400">FAQ</a></p>
<p><a href="loginReg.php" class="hover:text-gray-400">Login</a></p>
<p><a href="loginReg.php" class="hover:text-gray-400">Register</a></p>
</div>
<div>
<h3 class="text-xl font-bold mb-4">About Us</h3>
<p>Freelance Marketplace</p>
<p>Nairobi, Kenya</p>
<p>&copy; 2025</p>
</div>
<div>
<h3 class="text-xl font-bold mb-4">Contact Us</h3>
<p><a href="tel:+254 725 146 071" class="hover:text-gray-400">+254 725 146 071</a></p>
<p>Nairobi, Kenya</p>
<p>&copy; 2025</p>
</div>
<div>
<h3 class="text-xl font-bold mb-4">Social Contact</h3>
<a href="#" class="text-blue-500 hover:text-blue-600"><i class="fab fa-facebook-square"></i> Facebook</a><br>
<a href="#" class="text-red-500 hover:text-red-600"><i class="fab fa-google-plus-square"></i> Google</a><br>
<a href="#" class="text-blue-400 hover:text-blue-500"><i class="fab fa-twitter-square"></i> Twitter</a><br>
<a href="#" class="text-blue-700 hover:text-blue-800"><i class="fab fa-linkedin"></i> Linkedin</a><br>
</div>
</div>
</div>
</div>
<!-- End Footer -->
<script>
function toggleFAQ(id) {
    const element = document.getElementById(id);
    element.classList.toggle('hidden');
}
</script>
<script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
