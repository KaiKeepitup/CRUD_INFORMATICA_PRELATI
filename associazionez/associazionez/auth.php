<?php
session_start();
header('Content-Type: application/json');

require_once 'db.php';

$action = $_GET['action'] ?? '';

function json_out(bool $success, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        json_out(false, 'Inserisci email e password.');
    }

    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, nome, email, password_hash, attivo FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        $utente = $stmt->fetch();

        if ($utente && password_verify($password, $utente['password_hash'])) {
            if (!$utente['attivo']) {
                json_out(false, 'Account disabilitato. Contatta l\'amministratore.');
            }
            session_regenerate_id(true);
            $_SESSION['utente_logged_in'] = true;
            $_SESSION['utente_id']        = $utente['id'];
            $_SESSION['utente_nome']      = $utente['nome'];
            $_SESSION['utente_email']     = $utente['email'];
            json_out(true);
        } else {
            json_out(false, 'Email o password non validi.');
        }
    } catch (Exception $e) {
        json_out(false, 'Errore di sistema. Riprova più tardi.');
    }
}

if ($action === 'register') {
    $nome     = trim($_POST['nome']     ?? '');
    $cognome  = trim($_POST['cognome']  ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $conferma = trim($_POST['conferma'] ?? '');

    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        json_out(false, 'Compila tutti i campi.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(false, 'Email non valida.');
    }
    if (strlen($password) < 6) {
        json_out(false, 'La password deve essere di almeno 6 caratteri.');
    }
    if ($password !== $conferma) {
        json_out(false, 'Le password non coincidono.');
    }

    try {
        $pdo  = getDB();
        $chk  = $pdo->prepare("SELECT id FROM utenti WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            json_out(false, 'Email già registrata.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare("INSERT INTO utenti (nome, cognome, email, password_hash) VALUES (?, ?, ?, ?)");
        $ins->execute([$nome, $cognome, $email, $hash]);

        session_regenerate_id(true);
        $_SESSION['utente_logged_in'] = true;
        $_SESSION['utente_id']        = $pdo->lastInsertId();
        $_SESSION['utente_nome']      = $nome;
        $_SESSION['utente_email']     = $email;
        json_out(true);
    } catch (Exception $e) {
        json_out(false, 'Errore di sistema. Riprova più tardi.');
    }
}

json_out(false, 'Azione non valida.');
?>
