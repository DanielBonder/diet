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

ALTER TABLE appointments ADD meeting_type VARCHAR(20) NOT NULL;

CREATE TABLE payment_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50), -- ליווי חודשי / ל-3 חודשים / ל-6 חודשים
  duration_months INT, -- 1 / 3 / 6
  price DECIMAL(10,2)
);

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  plan_id INT,
  due_date DATE,
  amount DECIMAL(10,2),
  status ENUM('שולם', 'לא שולם') DEFAULT 'לא שולם',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (plan_id) REFERENCES payment_plans(id)
);

ALTER TABLE payments
ADD COLUMN request_status ENUM('בהמתנה', 'מאושר', 'נדחה') DEFAULT 'בהמתנה';


INSERT INTO payment_plans (name, duration_months, price) VALUES
('ליווי חודשי', 1, 650),
('ליווי ל-3 חודשים', 3, 1350),
('ליווי ל-6 חודשים', 6, 2400);


CREATE TABLE user_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week ENUM('ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'),
    meal_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
ALTER TABLE user_meals_actual ADD COLUMN comment TEXT;



