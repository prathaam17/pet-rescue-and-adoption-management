# 🐾 Pet Rescue & Adoption Management System

A comprehensive web-based platform designed to connect stray and injured animals in distress with compassionate individuals, registered NGOs, veterinary professionals, and adoption seekers.

---

## 🌟 Key Features

* **🚨 Emergency Rescue Reporting**: Instantly report stray or injured animals with location details and photos to alert nearby NGOs and rescue teams.
* **🐕 Pet Adoption Portal**: Browse verified pet profiles available for adoption and submit online adoption applications.
* **🤖 Paws AI Assistant**: Integrated AI chatbot powered by Groq API (`openai/gpt-oss-120b`) providing 24/7 pet care, nutrition tips, and emergency assistance.
* **🩺 Vets & Grooming Salons**: Book appointment slots with local veterinarians and pet grooming salons.
* **🏢 NGO & Admin Dashboards**: Dedicated management interfaces for NGOs and administrators to monitor rescue requests, update case logs, and oversee adoption applications.
* **📋 Post-Adoption Follow-ups**: Automated tracking and follow-up updates to ensure adopted pets remain safe and healthy.

---

## 🛠️ Technology Stack

* **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
* **Backend**: PHP 8.x (PDO Object-Oriented Database Connection)
* **Database**: MySQL 8.x (`pet_rescue_db`)
* **AI Engine**: Groq API (`openai/gpt-oss-120b`)
* **Server**: Apache (XAMPP Environment)

---

## 🚀 Getting Started & Installation

### Prerequisites
* [XAMPP](https://www.apachefriends.org/) (with PHP 8.x and MySQL)
* Git

### Step-by-Step Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/prathaam17/pet-rescue-and-adoption-management.git
   ```

2. **Place in Web Root**
   Move the project folder into your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\pet_rescue`

3. **Database Setup**
   * Start **MySQL** and **Apache** from the XAMPP Control Panel.
   * Open phpMyAdmin (`http://localhost/phpmyadmin/`).
   * Create a database named `pet_rescue_db`.
   * Import the database schema from `database/database.sql`.

4. **Configure Environment**
   * Database credentials are configured in `config.php`:
     ```php
     $host = "localhost";
     $user = "root";
     $pass = "";
     $dbname = "pet_rescue_db";
     ```
   * Set your Groq API key (optional for Paws AI chatbot) in environment variable `GROQ_API_KEY` or in `chat_handler.php`.

5. **Run the Application**
   Open your browser and navigate to:
   [http://localhost/pet_rescue/](http://localhost/pet_rescue/)

---

## 📁 Directory Structure

```
pet_rescue/
├── admin/               # Admin dashboard and management
├── assets/              # CSS stylesheets, JS scripts, and static images
│   ├── css/
│   ├── images/
│   └── js/
├── database/            # SQL database export file (database.sql)
├── includes/            # Reusable header, footer, and navigation components
├── ngo/                 # NGO portal and dashboard features
├── uploads/             # User uploaded pet photos, IDs, and certificates
├── user/                # User profile and rescue reporting interface
├── config.php           # Database & base URL configuration
├── chat_handler.php     # Groq AI backend API endpoint
├── index.php            # Homepage
├── adoption.php         # Pet adoption listing page
├── ngos.php             # Directory of registered NGOs
├── vets.php             # Directory of veterinary salons & clinics
└── README.md            # Project documentation
```

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check out the repository and open a pull request.

---

## 📜 License

This project is open-source and available under the [MIT License](LICENSE).
