# Secure Multi-Database Authentication System (Railway Optimized)

A complete, production-ready, and secure authentication system utilizing **MySQL** (Relational account credentials), **MongoDB Atlas** (Flexible profile storage), and **Redis Cloud** (Session state store). Fully containerized for one-click deployment directly to **Railway**.

---

## Features

- **Multi-DB Production Stack**: Integrates managed MySQL with MongoDB Atlas and Redis Cloud.
- **Glassmorphism UI**: Beautiful, interactive screens styled with Bootstrap 5 and custom CSS.
- **AJAX Interactions**: Dynamic form flows built with jQuery (no page reloads).
- **Auto-Migrations**: Automatically waits for MySQL to be online and executes DB migrations (`sql/init.sql`) on start.
- **Dynamic Port Binding**: Startup script overrides default Apache configurations to align with Railway's injected `$PORT` variables.
- **Secure Handling**:
  - Bcrypt hashing (`password_hash` with cost 12).
  - Exclusively prepared queries to mitigate SQL Injection (SQLi).
  - Double XSS protection (Input tags-sanitization and output HTML-escaping).
  - Secure session cookies (HTTPOnly, SameSite).
  - Transactional registration (reverts MySQL credentials insert if MongoDB Atlas fails).

---

## Folder Structure

```
project/
  ├── assets/
  │   ├── css/
  │   │   └── style.css            # Custom CSS Glassmorphic variables
  │   ├── images/
  │   │   └── default-avatar.png   # Premium neon abstract profile picture
  │   └── js/
  │       ├── login.js             # User login validation & AJAX submitter
  │       ├── profile.js           # AJAX Dashboard data fetcher & updates
  │       └── register.js          # User registration validation & AJAX submitter
  ├── php/
  │   ├── bootstrap.php            # Environment loader & JSON responders
  │   ├── db.php                   # MySQL connection wrapper (PDO)
  │   ├── mongo.php                # MongoDB Atlas connection (MongoDB Client)
  │   ├── redis.php                # Redis Cloud connection (Predis Client)
  │   ├── register.php             # Register REST endpoint
  │   ├── login.php                # Authentication REST endpoint
  │   ├── profile.php              # Retrieve and edit profile details
  │   ├── migrate.php              # Connection checking and schema migrations
  │   └── logout.php               # De-authorize token & expire cookie
  ├── sql/
  │   └── init.sql                 # MySQL schema initialization SQL script
  ├── .env.example                 # Configuration template
  ├── .gitignore                   # Version control ignore rules
  ├── apache.conf                  # Directory permissions and virtual hosts
  ├── composer.json                # PHP dependency management
  ├── docker-compose.yml           # Local multi-service environment setup
  ├── Dockerfile                   # Railway compilation specs
  ├── index.html                   # Project landing/welcome page
  ├── login.html                   # Sign-in portal page
  ├── profile.html                 # Dashboard panel interface
  ├── register.html                # Signup portal page
  ├── railway.json                 # Railway service behavior config
  ├── start.sh                     # Docker entrypoint (migration + port bind)
  └── README.md                    # System documentation
```

---

## Connection Environments

All connection parameters are managed via environment variables. Ensure the following keys are provided in Railway or local `.env`:

### 1. MySQL Variables
* `MYSQL_HOST`: The MySQL hostname (e.g. Railway MySQL database host).
* `MYSQL_PORT`: Port (default `3306`).
* `MYSQL_DATABASE`: Database name (e.g. `auth_system`).
* `MYSQL_USER`: Username.
* `MYSQL_PASSWORD`: User password.

### 2. MongoDB Atlas Variables
* `MONGO_URI`: The MongoDB Atlas connection string.
  - *Example*: `mongodb+srv://<username>:<password>@cluster.mongodb.net/auth_system?retryWrites=true&w=majority`

### 3. Redis Cloud Variables
* `REDIS_HOST`: Hostname of the Redis Cloud database.
* `REDIS_PORT`: Port of the Redis Cloud database.
* `REDIS_PASSWORD`: Connection password.

### 4. General Settings
* `APP_ENV`: `production` or `development`.
* `APP_DEBUG`: `false` or `true`.
* `SESSION_SECRET`: Secure string used for hash verification.

---

## Database Schemas

### MySQL (`users` table)
Relational account definitions.
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### MongoDB Atlas (`profiles` collection)
Rich profile documents linked via MySQL `user_id`.
```json
{
  "_id": "ObjectId",
  "user_id": 1,
  "name": "Full Name",
  "age": 25,
  "bio": "User biography",
  "interests": ["Technology", "Design"]
}
```

### Redis Cloud Sessions
Key format: `session:<session_token>` (64 hex characters)
Data type: **Hash**
Fields:
- `user_id`: Numeric primary key from MySQL
- `token`: Match token string
- `login_time`: UNIX epoch timestamp

---

## API Specifications

All endpoints respond with standardized JSON.

### 1. Registration
- **URL**: `POST /php/register.php`
- **Fields**: `username`, `email`, `password`, `confirm_password`
- **Success Response**: `{"status": "success", "message": "Registration Successful"}`

### 2. Login
- **URL**: `POST /php/login.php`
- **Fields**: `email`, `password`, `remember_me`
- **Success Response**: `{"status": "success", "message": "Login Successful", ...}`
- **Cookies**: Sets browser cookie `session_token` (HTTPOnly, secure).

### 3. Profile (Fetch & Update)
- **URL**: `GET` / `POST /php/profile.php`
- **Success Response (GET)**: Loads combined user metadata.
- **Success Response (POST)**: Updates user's Name, Age, Bio, and Interests in MongoDB Atlas.

### 4. Logout
- **URL**: `POST /php/logout.php`
- **Success Response**: Clears Redis session key and expires client cookie.

---

## Deployment to Railway

The project is structured to deploy smoothly directly from your GitHub repository to Railway.

### Step 1: Connect your repository
1. Go to [Railway](https://railway.app) and log in.
2. Click **New Project** -> **Deploy from GitHub repo**.
3. Select this repository.

### Step 2: Set up Database services
You can attach databases directly within Railway or point to cloud endpoints:
1. **MySQL**: Click **New** -> **Database** -> **MySQL** in Railway.
2. **MongoDB Atlas**: Set up a free cluster on [MongoDB Atlas](https://www.mongodb.com/cloud/atlas) and retrieve the connection URI.
3. **Redis Cloud**: Set up a free instance on [Redis Cloud](https://redis.com/) and retrieve the host, port, and password.

### Step 3: Link Environment Variables
In your Railway web container service settings, navigate to **Variables** and link the following variables:
1. Bind MySQL variables from Railway's MySQL database:
   - `MYSQL_HOST` = `${{MySQL.MYSQLHOST}}`
   - `MYSQL_PORT` = `${{MySQL.MYSQLPORT}}`
   - `MYSQL_DATABASE` = `${{MySQL.MYSQLDATABASE}}`
   - `MYSQL_USER` = `${{MySQL.MYSQLUSER}}`
   - `MYSQL_PASSWORD` = `${{MySQL.MYSQLPASSWORD}}`
2. Add your MongoDB Atlas environment variable:
   - `MONGO_URI` = `mongodb+srv://...`
3. Add your Redis Cloud environment variables:
   - `REDIS_HOST` = `<your-redis-cloud-endpoint>`
   - `REDIS_PORT` = `<your-redis-cloud-port>`
   - `REDIS_PASSWORD` = `<your-redis-cloud-password>`
4. Add generic environments:
   - `APP_ENV` = `production`
   - `APP_DEBUG` = `false`
   - `SESSION_SECRET` = `<generate-a-secure-random-key>`

Railway will build the image from the `Dockerfile`, execute `start.sh` (running migrations to the MySQL service), and bind Apache automatically to the public address.
