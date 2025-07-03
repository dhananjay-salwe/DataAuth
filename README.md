# DataAuth 🔐 – Resume vs LinkedIn Data Verifier

**DataAuth** is a web-based application that verifies the authenticity of a candidate’s resume by comparing it with the public data available on their LinkedIn profile. The system extracts structured data (Full Name, Skills, Education, Experience) from both sources and provides a match score in a user-friendly format.

---

## 🚀 Features

- 📝 Upload a resume (PDF) and extract structured information.
- 🔗 Enter LinkedIn profile URL to fetch public data using LinkedIn API.
- 📊 Compare and match resume vs LinkedIn data with visual circular progress indicators.
- 📁 Admin Dashboard to:
  - View/delete uploaded resumes
  - View/delete LinkedIn profile data
  - View/delete user feedback
- 🔒 Admin login with secure authentication
- 💬 Feedback form for user suggestions

---

## 🛠️ Technologies Used

### 🔧 Frontend:
- HTML, CSS
- JavaScript (AJAX for dynamic interaction)
- Bootstrap (for responsive UI)

### 🖥 Backend:
- PHP (Core PHP for handling form submissions and business logic)
- MySQL (Database management)
- XAMPP (Local server stack)

### 📄 PDF Parsing:
- [`smalot/pdfparser`](https://github.com/smalot/pdfparser) PHP library

### 🌐 LinkedIn Data Fetching:
- [LinkedIn API via RapidAPI](https://rapidapi.com/) for scraping public profile data

---

## ⚙️ Installation Instructions

### ✅ Prerequisites:
- PHP 7.x or above
- MySQL
- Composer (for PDF parser library)
- XAMPP (recommended for local setup)

### 🛠️ Steps to Run:

1. Clone the repository 
 
   git clone https://github.com/yourusername/DataAuth.git
   cd DataAuth


2. **Start Apache and MySQL** via XAMPP

3. Import the database

   * Open phpMyAdmin
   * Create a new database (e.g., `dataauth`)
   * Import the provided SQL file (if available)

4. Configure Database

   * In `config/db_connect.php`, update DB credentials as per your setup

5. Install PDF Parser library
   In the project root, run:

  **bash
  <br>
   composer require smalot/pdfparser


6. Run the app

   * Place the project in the `htdocs` directory of XAMPP
   * Open in browser: `http://localhost/DataAuth/frontend/index.php`


## 🙋‍♂️ Author

**Dhananjay Salwe**

Feel free to connect on [LinkedIn](www.linkedin.com/in/dhananjay-salwe)
Project created for educational and demonstration purposes.

---


