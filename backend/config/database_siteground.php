<?php
class Database
{
    // Siteground Database credentials
    private $host = 'localhost';  // Siteground'da genellikle localhost
    private $db_name = 'YOUR_CPANEL_USERNAME_dbname';  // Örnek: john_otoasist
    private $username = 'YOUR_CPANEL_USERNAME_dbuser';  // Örnek: john_otoasist
    private $password = 'YOUR_DATABASE_PASSWORD';
    private $conn;

    // Get database connection
    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            error_log("Siteground MySQL connection successful to database: " . $this->db_name);

            // Test the connection
            $stmt = $this->conn->query("SELECT 1");
            if ($stmt) {
                error_log("Siteground database connection test successful");
                $this->createBasicTablesMySQL();
                return $this->conn;
            }
        } catch (PDOException $e) {
            error_log("Siteground MySQL connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }

    private function createBasicTablesMySQL()
    {
        try {
            // Users table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone VARCHAR(20) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    full_name VARCHAR(255),
                    email VARCHAR(255),
                    is_verified TINYINT(1) DEFAULT 0,
                    role_id INT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Vehicles table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS vehicles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    brand VARCHAR(100) NOT NULL,
                    model VARCHAR(100) NOT NULL,
                    year INT NOT NULL,
                    plate VARCHAR(20) NOT NULL,
                    color VARCHAR(50),
                    fuel_type VARCHAR(50) DEFAULT 'gasoline',
                    vehicle_type VARCHAR(10) DEFAULT 'D',
                    image VARCHAR(500),
                    last_service_date DATE,
                    last_inspection_date DATE,
                    next_inspection_date DATE,
                    inspection_end_date DATE,
                    insurance_expiry_date DATE,
                    kasko_expiry_date DATE,
                    registration_expiry_date DATE,
                    oil_change_date DATE,
                    tire_change_date DATE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Reminders table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS reminders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    vehicle_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    type VARCHAR(50) NOT NULL,
                    date DATE NOT NULL,
                    original_date DATE NOT NULL,
                    reminder_time TIME DEFAULT '09:00:00',
                    reminder_days JSON,
                    is_completed TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
                )
            ");

            // Campaigns table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS campaigns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    image_url VARCHAR(500),
                    discount_percentage DECIMAL(5,2) DEFAULT 0,
                    discount_amount DECIMAL(10,2) DEFAULT 0,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    terms_conditions TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // News table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS news (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    content TEXT,
                    image_url VARCHAR(500),
                    category VARCHAR(100) DEFAULT 'general',
                    is_featured TINYINT(1) DEFAULT 0,
                    is_sponsored TINYINT(1) DEFAULT 0,
                    author VARCHAR(255),
                    view_count INT DEFAULT 0,
                    publish_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Sliders table
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS sliders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    image_url VARCHAR(500) NOT NULL,
                    link_url VARCHAR(500),
                    is_active TINYINT(1) DEFAULT 1,
                    sort_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            error_log("Siteground basic tables created successfully");
        } catch (PDOException $e) {
            error_log("Error creating Siteground tables: " . $e->getMessage());
        }
    }
} 