-- ============================================
-- APAM - Aggiornamento database v2
-- Esegui questo script per aggiungere
-- le nuove tabelle utenti e notizie
-- ============================================

USE apam_db;

-- Tabella utenti pubblici
CREATE TABLE IF NOT EXISTS utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    attivo TINYINT(1) DEFAULT 1,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella notizie (gestita dall'admin)
CREATE TABLE IF NOT EXISTS notizie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    contenuto TEXT NOT NULL,
    pubblicata TINYINT(1) DEFAULT 1,
    data_pubblicazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabella donazioni
CREATE TABLE IF NOT EXISTS donazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    messaggio TEXT,
    data_donazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);

-- Notizia di benvenuto di esempio
INSERT INTO notizie (titolo, contenuto) VALUES
('Benvenuti nel portale APAM',
 'Siamo lieti di presentare il nuovo portale riservato ai membri dell\'associazione. Da qui potrete rimanere aggiornati su tutti i nostri progetti e iniziative.'),
('Nuovo progetto di ricerca 2025',
 'L\'associazione ha avviato un nuovo progetto di ricerca in collaborazione con l\'Università di Perugia. Maggiori dettagli saranno disponibili nelle prossime settimane.');
