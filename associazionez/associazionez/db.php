<?php
// ============================================
// db.php - Connessione al database MySQL
// MODIFICA le credenziali qui sotto
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'apam_db');
define('DB_USER', 'kai');        // <-- Cambia con il tuo utente MySQL
define('DB_PASS', 'Kaikeepitup');            // <-- Cambia con la tua password MySQL
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Errore DB: " . $e->getMessage());
            die("Errore di connessione al database. Controlla db.php.");
        }
    }
    return $pdo;
}
?>
