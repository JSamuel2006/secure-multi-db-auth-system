# Secure Multi-Database Authentication System

A complete, production-ready, and secure authentication system demonstrating the integration of multiple databases: **MySQL** for relational accounts, **MongoDB** for flexible profiles, and **Redis** for high-performance session tracking. Built with PHP 8.2 and styled with a modern glassmorphic Bootstrap 5 theme.

---

## Features

- **Multi-DB Architecture**: Relational account data, non-relational profile metadata, and in-memory session tracking.
- **Glassmorphism UI**: Beautiful dark-mode user interfaces styled with Bootstrap 5 and custom CSS.
- **AJAX Driven**: Dynamic interactions powered by jQuery AJAX.
- **Production-Ready Security**:
  - Secure Bcrypt password hashing (`password_hash` with cost 12).
  - Prepared statements only (SQL injection mitigation).
  - Multi-tier cross-site scripting (XSS) mitigation (Input sanitization and context-aware output escaping).
  - HTTPOnly, secure, and SameSite cookie policies.
  - Transactional user creation (reverts MySQL entry if MongoDB profile initialization fails).

---

## Folder Structure

```
project/
  ├── assets/
  │   ├── css/
  │   │   └── style.css            # Custom CSS Glassmorphism tokens & styles
  │   ├── images/
  │   │   └── default-avatar.png   # Premium neon visual avatar asset
  │   └── js/
  │       ├── login.js             # Sign-in logic & input validation
  │       ├── profile.js           # AJAX dashboard & updates controller
  │       └── register.js          # Sign-up logic & validations
  ├── php/
  │   ├── bootstrap.php            # Autoloader & dotenv environment loader
  │   ├── db.php                   # MySQL connection interface
  │   ├── mongo.php                # MongoDB client database selector
  │   ├── redis.php                # Redis server client interface
  │   ├── register.php             # Registration endpoint
  │   ├── login.php                # Authentication & session generation
  │   ├── profile.php              # Retrieve/Update profile details
  │   └── logout.php               # De-authorize token & expire cookie
  ├── sql/
  │   └── init.sql                 # MySQL schema auto-migration script
  ├── .env.example                 # Configuration template file
  ├── .gitignore                   # Ignore list for version control
  ├── apache.conf                  # Containerized server permissions config
  ├── composer.json                # Project dependencies config
  ├── docker-compose.yml           # Local multi-service environment setup
  ├── Dockerfile                   # Deployment container image specs
  ├── index.html                   # Static landing introduction page
  ├── login.html                   # Sign-in web page
  ├── profile.html                 # Dashboard web page
  ├── register.html                # Registration web page
  ├── railway.json                 # Railway service deployment rules
  └── README.md                    # System documentation
```

---

## Database Specifications

### 1. MySQL Schema
Table: `users`
Used for credential authorization.
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. MongoDB Collection Structure
Collection: `profiles`
Holds flexible user profile details linked by relational `user_id`.
```json
{
  "_id": "ObjectId",
  "user_id": 1,
  "name": "Full Name",
  "age": 25,
  "bio": "User biography statement",
  "interests": ["Coding", "Technology", "Design"]
}
```

### 3. Redis Session Layout
Data Type: `Hash`
Key: `session:<session_token>` (where session_token is a secure cryptographically generated 64 hex character token)
```
Fields:
  - user_id: 1 (Integer ID)
  - token: "<session_token>" (String)
  - login_time: 1783933558 (UNIX Timestamp)
```
*Note: Key expires automatically after 2 hours (7200s) by default, or 30 days (2592000s) if "Remember Me" is activated.*

---

## API Documentation

All API responses are formatted in JSON. Example:
```json
{
  "status": "success",
  "message": "Operation completed successfully."
}
```

### 1. Registration API
- **Endpoint**: `POST /php/register.php`
- **Payload**: Form-Data or JSON
  - `username` (string, required)
  - `email` (string, required)
  - `password` (string, required)
  - `confirm_password` (string, required)
- **Response**:
  - Success: `{"status": "success", "message": "Registration Successful. You can now login."}`
  - Error: `{"status": "error", "message": "Passwords do not match."}`

### 2. Login API
- **Endpoint**: `POST /php/login.php`
- **Payload**: Form-Data or JSON
  - `email` (string, required)
  - `password` (string, required)
  - `remember_me` (boolean/string, optional)
- **Response**:
  - Success: `{"status": "success", "message": "Login Successful", "user": {"id": 1, "username": "johndoe"}}`
  - Sets browser cookie: `session_token` (HTTPOnly, secure, Lax).

### 3. Profile API (Load)
- **Endpoint**: `GET /php/profile.php`
- **Headers**: Requires cookie `session_token`
- **Response**:
  - Success: 
    ```json
    {
      "status": "success",
      "message": "Profile loaded",
      "profile": {
        "user_id": 1,
        "username": "johndoe",
        "email": "johndoe@example.com",
        "created_at": "2026-07-13 14:30:00",
        "name": "John Doe",
        "age": 25,
        "bio": "Bio content...",
        "interests": ["Coding", "Design"]
      }
    }
    ```
  - Error: `{"status": "error", "message": "Unauthorized. Session has expired."}` (HTTP 401)

### 4. Profile API (Update)
- **Endpoint**: `POST /php/profile.php`
- **Headers**: Requires cookie `session_token`
- **Payload**: Form-Data or JSON
  - `name` (string, optional)
  - `age` (integer, optional)
  - `bio` (string, optional)
  - `interests` (comma-separated string or array, optional)
- **Response**:
  - Success: `{"status": "success", "message": "Profile updated successfully."}`

### 5. Logout API
- **Endpoint**: `POST /php/logout.php`
- **Headers**: Requires cookie `session_token`
- **Response**:
  - Success: `{"status": "success", "message": "Logged out successfully."}`
  - Clears `session_token` cookie from browser.

---

## Installation & Setup

### Option 1: Running locally using Docker Compose (Recommended)
This runs the entire stack (Apache/PHP, MySQL, MongoDB, Redis) locally without manually installing any database engines.

1. Ensure [Docker](https://www.docker.com/) and Docker Compose are installed.
2. Clone the repository and navigate to the directory:
   ```bash
   git clone <repo-url>
   cd project
   ```
3. Copy the environment template:
   ```bash
   cp .env.example .env
   ```
4. Spin up the containers:
   ```bash
   docker-compose up -d --build
   ```
5. Wait for databases to initialize, then access the app at:
   `http://localhost:8080`

### Option 2: Manual Local Setup
1. Setup Apache/Nginx web server with PHP 8.1+.
2. Install PECL extensions `mongodb`.
3. Install and run MySQL 8, MongoDB 6+, and Redis 7+.
4. Create database `auth_system` in MySQL and import `sql/init.sql`.
5. Populate local `.env` file with corresponding host IPs and credentials.
6. Install PHP packages using Composer:
   ```bash
   composer install
   ```

---

## Deployment Configuration (Railway / Render)

### Railway Deployment
To deploy successfully on Railway:
1. Railway automatically provisions services. Add **MySQL**, **MongoDB**, and **Redis** service attachments to your project in the Railway UI.
2. Link the repository to the Web Service.
3. Railway automatically sets connection environment variables. In the web service variables dashboard, map the default Railway variables:
   - `MYSQL_HOST` to `${{MySQL.MYSQLHOST}}`
   - `MYSQL_PORT` to `${{MySQL.MYSQLPORT}}`
   - `MYSQL_DATABASE` to `${{MySQL.MYSQLDATABASE}}`
   - `MYSQL_USER` to `${{MySQL.MYSQLUSER}}`
   - `MYSQL_PASSWORD` to `${{MySQL.MYSQLPASSWORD}}`
   - `MONGO_URI` to `${{MongoDB.MONGODB_URL}}`
   - `REDIS_HOST` to `${{Redis.REDISHOST}}`
   - `REDIS_PORT` to `${{Redis.REDISPORT}}`
   - `REDIS_PASSWORD` to `${{Redis.REDISPASSWORD}}`
   - Add `SESSION_SECRET` with a secure random key.
4. The service will build automatically from the `Dockerfile` and go live.
