CREATE DATABASE `Db_Management_App`;
USE `Db_Management_App`;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(50),
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    height DECIMAL(5,2) NOT NULL,
    bmi DECIMAL(5,2) GENERATED ALWAYS AS (weight / ((height / 100) * (height / 100))) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  available_date DATE NOT NULL,
  available_time TIME NOT NULL
);

ALTER TABLE users ADD is_admin TINYINT(1) DEFAULT 0;
--2. הפוך את המשתמש שלך ל־admin
--אם לדוגמה המשתמש שלך הוא עם id = 7, תריץ את הפקודה הבאה ב־phpMyAdmin או ב־MySQL:

UPDATE users SET is_admin = 1 WHERE id = 7;

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  full_name VARCHAR(255),
  available_date DATE NOT NULL,
  available_time TIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
