-- ============================================
-- APAM - Script di configurazione database
-- Esegui questo script una sola volta
-- ============================================

CREATE DATABASE IF NOT EXISTS apam_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE apam_db;

-- Tabella admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella messaggi dal form contatti
CREATE TABLE IF NOT EXISTS messaggi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    messaggio TEXT NOT NULL,
    letto TINYINT(1) DEFAULT 0,
    data_invio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Inserisci admin di default
-- Username: admin
-- Password: Admin@2025  <-- CAMBIA SUBITO DOPO IL PRIMO LOGIN
-- ============================================
INSERT INTO admin (username, password_hash, email)
VALUES (
    'admin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@2025
    'giulioprelati27@gmail.com'
)
ON DUPLICATE KEY UPDATE id = id;
