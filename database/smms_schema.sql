-- ============================================================
-- Smart Money Management System (SMMS) - Database Schema
-- Engine: MySQL (XAMPP / phpMyAdmin)
-- ============================================================

CREATE DATABASE IF NOT EXISTS smms_db;
USE smms_db;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id      INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,   -- store hashed password (password_hash())
    role         ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. CATEGORIES  (income/expense categories)
--    user_id = NULL  -> default/system category available to everyone
--    user_id = X     -> custom category created by that user
-- ------------------------------------------------------------
CREATE TABLE categories (
    category_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NULL,
    category_name  VARCHAR(50) NOT NULL,
    category_type  ENUM('income', 'expense') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. TRANSACTIONS (income + expense records / transaction history)
-- ------------------------------------------------------------
CREATE TABLE transactions (
    transaction_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    category_id      INT NOT NULL,
    type             ENUM('income', 'expense') NOT NULL,
    amount           DECIMAL(12,2) NOT NULL,
    description      VARCHAR(255),
    transaction_date DATE NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. BUDGETS (monthly budget planning per category)
-- ------------------------------------------------------------
CREATE TABLE budgets (
    budget_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    category_id    INT NOT NULL,
    month          TINYINT NOT NULL,      -- 1-12
    year           SMALLINT NOT NULL,
    budget_amount  DECIMAL(12,2) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_budget (user_id, category_id, month, year),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. SAVINGS GOALS
-- ------------------------------------------------------------
CREATE TABLE savings_goals (
    goal_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    goal_name      VARCHAR(100) NOT NULL,
    target_amount  DECIMAL(12,2) NOT NULL,
    saved_amount   DECIMAL(12,2) DEFAULT 0,
    target_date    DATE,
    status         ENUM('active', 'completed') DEFAULT 'active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. BUDGET ALERTS (triggered when spending exceeds a budget)
-- ------------------------------------------------------------
CREATE TABLE budget_alerts (
    alert_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    budget_id      INT NOT NULL,
    alert_message  VARCHAR(255) NOT NULL,
    is_read        BOOLEAN DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (budget_id) REFERENCES budgets(budget_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE / DEFAULT DATA
-- ============================================================

-- Default system categories (visible to all users)
INSERT INTO categories (user_id, category_name, category_type) VALUES
(NULL, 'Salary',        'income'),
(NULL, 'Freelance',     'income'),
(NULL, 'Other Income',  'income'),
(NULL, 'Food',          'expense'),
(NULL, 'Transport',     'expense'),
(NULL, 'Rent',          'expense'),
(NULL, 'Utilities',     'expense'),
(NULL, 'Entertainment', 'expense'),
(NULL, 'Education',     'expense'),
(NULL, 'Miscellaneous', 'expense');

-- Demo users (password below is a bcrypt hash of "password123")
INSERT INTO users (full_name, email, password, role) VALUES
('Test User', 'test@example.com', '$2y$10$WqQF8fJ0kY2r9C9GZP6PIuJgxG3o3Zf1v0tX5s2b7d1kQe4mN6r7K', 'user'),
('

System Admin', 'admin@smms.com', '$2y$10$WqQF8fJ0kY2r9C9GZP6PIuJgxG3o3Zf1v0tX5s2b7d1kQe4mN6r7K', 'admin');

-- Sample transactions for the demo user
INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date) VALUES
(1, 1, 'income',  25000.00, 'Monthly salary', '2026-07-01'),
(1, 4, 'expense',  3500.00, 'Groceries',       '2026-07-03'),
(1, 6, 'expense', 10000.00, 'Monthly rent',    '2026-07-05');

-- Sample budget
INSERT INTO budgets (user_id, category_id, month, year, budget_amount) VALUES
(1, 4, 7, 2026, 5000.00);

-- Sample savings goal
INSERT INTO savings_goals (user_id, goal_name, target_amount, saved_amount, target_date) VALUES
(1, 'Emergency Fund', 50000.00, 12000.00, '2026-12-31');