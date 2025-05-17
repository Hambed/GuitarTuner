#!/bin/bash

# CONFIGURATION — change these in production!
DB_ROOT_PASS="RootPass123!"
DB_NAME="TunerDB"
PHP_USER="php_user"
PHP_PASS="PhpPass123!"
APP_USER="app_user"
APP_PASS="AppPass123!"

echo "Updating and installing MariaDB server..."
sudo apt update
sudo apt install mariadb-server -y

echo "Securing MariaDB..."
sudo mysql_secure_installation <<EOF

y
$DB_ROOT_PASS
$DB_ROOT_PASS
y
y
y
y
EOF

echo "Creating database, users, and tables..."

sudo mysql -u root -p"$DB_ROOT_PASS" <<MYSQL_SCRIPT
-- Create the database
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create PHP user (limited permissions)
CREATE USER IF NOT EXISTS '$PHP_USER'@'localhost' IDENTIFIED BY '$PHP_PASS';
GRANT SELECT, INSERT, UPDATE, DELETE ON $DB_NAME.* TO '$PHP_USER'@'localhost';

-- Create app user (full access)
CREATE USER IF NOT EXISTS '$APP_USER'@'localhost' IDENTIFIED BY '$APP_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$APP_USER'@'localhost';

-- Use the database
USE $DB_NAME;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(11) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
);

-- Create logs table
CREATE TABLE IF NOT EXISTS logs (
    log_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    temperature FLOAT DEFAULT NULL,
    humidity FLOAT DEFAULT NULL,
    note_frequency VARCHAR(6) DEFAULT NULL,
    PRIMARY KEY (log_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo "Setup complete! Database '$DB_NAME' and tables created."
