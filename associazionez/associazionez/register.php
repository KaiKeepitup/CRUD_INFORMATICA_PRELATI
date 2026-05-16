<?php
session_start();

if (isset($_SESSION['utente_logged_in']) && $_SESSION['utente_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db.php';

$errore  = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $cognome  = trim($_POST['cognome']  ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $conferma = trim($_POST['conferma'] ?? '');

    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        $errore = 'Compila tutti i campi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errore = 'Email non valida.';
    } elseif (strlen($password) < 6) {
        $errore = 'La password deve essere di almeno 6 caratteri.';
    } elseif ($password !== $conferma) {
        $errore = 'Le password non coincidono.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errore = 'Email già registrata.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $pdo->prepare("INSERT INTO utenti (nome, cognome, email, password_hash) VALUES (?, ?, ?, ?)");
                $ins->execute([$nome, $cognome, $email, $hash]);
                // Auto-login dopo registrazione
                $utente_id = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['utente_logged_in'] = true;
                $_SESSION['utente_id']        = $utente_id;
                $_SESSION['utente_nome']      = $nome;
                $_SESSION['utente_email']     = $email;
                header("Location: dashboard.php");
                exit();
            }
        } catch (Exception $e) {
            $errore = 'Errore di sistema. Riprova più tardi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APAM – Registrazione</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,300;0,400;0,600;1,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #03112b;
            --blue:   #004d7f;
            --accent: #ffa500;
            --glass:  rgba(255,255,255,0.05);
            --border: rgba(255,255,255,0.12);
            --text:   #e8edf4;
            --muted:  rgba(232,237,244,0.5);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--navy);
            font-family: 'DM Mono', monospace;
            padding: 40px 16px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: -50%;
            background:
                radial-gradient(ellipse 60% 40% at 20% 30%, rgba(0,77,127,0.4) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 70%, rgba(255,165,0,0.07) 0%, transparent 55%);
            animation: drift 14s ease-in-out infinite alternate;
            z-index: 0;
        }
        @keyframes drift {
            from { transform: translate(0,0) rotate(0deg); }
            to   { transform: translate(3%,2%) rotate(2deg); }
        }

        .card {
            position: relative;
            z-index: 1;
            width: min(480px, 96vw);
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 44px 40px;
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            animation: slideUp 0.6s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-area {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-badge {
            display: inline-block;
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--blue), #006aad);
            border-radius: 13px;
            line-height: 52px;
            font-family: 'Crimson Pro', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 12px;
            box-shadow: 0 8px 24px rgba(0,77,127,0.45);
        }
        .logo-area h1 {
            font-family: 'Crimson Pro', serif;
            font-size: 1.4rem;
            font-weight: 300;
            color: var(--text);
        }
        .logo-area p {
            font-size: 0.7rem;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .divider { height: 1px; background: var(--border); margin: 0 0 26px; }

        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        label {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 11px 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 0.9rem;
            margin-bottom: 18px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0,77,127,0.25);
        }

        .btn {
            width: 100%;
            padding: 13px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'DM Mono', monospace;
            font-size: 0.82rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s, transform 0.15s;
            margin-top: 4px;
        }
        .btn:hover  { background: #005f99; }
        .btn:active { transform: scale(0.98); }

        .errore {
            background: rgba(244,67,54,0.12);
            border: 1px solid rgba(244,67,54,0.35);
            color: #ff8a80;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.8rem;
            margin-bottom: 18px;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
            color: var(--muted);
            letter-spacing: 0.5px;
        }
        .login-link a { color: var(--accent); text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-area">
        <div class="logo-badge">A</div>
        <h1>Associazione per la Ricerca Scientifica</h1>
        <p>Crea il tuo account</p>
    </div>
    <div class="divider"></div>

    <?php if ($errore): ?>
        <div class="errore">⚠ <?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="row">
            <div>
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
            </div>
            <div>
                <label for="cognome">Cognome</label>
                <input type="text" id="cognome" name="cognome" value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>" required>
            </div>
        </div>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Minimo 6 caratteri" required>

        <label for="conferma">Conferma password</label>
        <input type="password" id="conferma" name="conferma" required>

        <button type="submit" class="btn">Registrati</button>
    </form>

    <p class="login-link">Hai già un account? <a href="login-utente.php">Accedi</a></p>
</div>
</body>
</html>
