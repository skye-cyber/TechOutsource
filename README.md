---
TechOutsource

TechOutsource is a web-based platform designed to connect freelancers with employers seeking tech skills. It facilitates job postings, applications, messaging, and integrates payment solutions, aiming to streamline the outsourcing process for tech-related tasks. 


---

🚀 Features

User Roles: Separate dashboards and functionalities for Employers and Freelancers.

Job Management: Employers can post, edit, and manage job listings; freelancers can browse and apply.

Profile Management: Both user types can create and update their profiles.

Messaging System: Built-in communication between employers and freelancers.

Payment Integration: Support for transactions via M-Pesa.

Responsive Design: Utilizes Tailwind CSS for a mobile-friendly interface. 


---

🛠️ Tech Stack

Frontend: HTML, CSS (Tailwind CSS), JavaScript, jQuery

Backend: PHP

Database: MySQL

Payment Gateway: M-Pesa Integration 



---
📁 Project Structure
```shell
TechOutsource/
├── db/                    # Database connection scripts
├── registration/          # User registration and login
├── src/                   # Core application logic
├── dist/                  # Compiled assets
├── bootstrap/             # Bootstrap framework files
├── jquery/                # jQuery library
├── index.php              # Landing page
├── postJob.php            # Job posting interface
├── applyJob.php           # Job application handler
├── employerProfile.php    # Employer profile page
├── freelancerProfile.php  # Freelancer profile page
├── message.php            # Messaging interface
├── mpesaIntergration.php  # M-Pesa payment processing
├── tailwind.config.js     # Tailwind CSS configuration
├── package.json           # Node.js dependencies
├── .babelrc               # Babel configuration
└── .gitignore             # Git ignore rules


```

---

📦 Installation

1. Clone the repository:
```shell
git clone https://github.com/skye-cyber/TechOutsource.git
cd TechOutsource42
```

2. Set up the database:

Create a mysql databse by Importing the ``db/TechOutsiurce.sql`` SQL to set up tables.

3. Configure the environment:

Ensure PHP and MySQL are installed on your system.

Install Node.js dependencies:

npm install


4. Run the application:

Start your local server (e.g., Apache) and navigate to index.php in your browser. 

---

💡 Future Enhancements

Implement user authentication with JWT.

Add real-time chat functionality.

Integrate additional payment gateways.

Enhance UI/UX with modern frameworks.

Deploy the application using Docker containers. 



---

🤝 Contributing

Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes. 


---

📄 License

This project is licensed under the MIT License. 


---

##📬 Contact

For inquiries or support, please contact Skye. 


---

Let me know if you'd like assistance with setting up CI/CD pipelines, Dockerizing the application, or integrating additional features!

