<?php
session_start();

if (isset($_SESSION['utente_logged_in']) && $_SESSION['utente_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Se admin già loggato, vai alla dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

require_once 'db.php';

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $errore = 'Inserisci email e password.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, nome, email, password_hash, attivo FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $utente = $stmt->fetch();

            if ($utente && password_verify($password, $utente['password_hash'])) {
                if (!$utente['attivo']) {
                    $errore = 'Account disabilitato. Contatta l\'amministratore.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['utente_logged_in'] = true;
                    $_SESSION['utente_id']        = $utente['id'];
                    $_SESSION['utente_nome']      = $utente['nome'];
                    $_SESSION['utente_email']     = $utente['email'];
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $errore = 'Email o password non validi.';
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
    <title>APAM – Accesso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,300;0,600;1,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
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
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: -50%;
            background:
                radial-gradient(ellipse 60% 40% at 20% 30%, rgba(0,77,127,0.45) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 70%, rgba(255,165,0,0.08) 0%, transparent 55%);
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
            width: min(420px, 92vw);
            background: var(--glass);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 48px 40px 44px;
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            animation: slideUp 0.6s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-area { text-align: center; margin-bottom: 36px; }
        .logo-badge {
            display: inline-block;
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--blue), #006aad);
            border-radius: 14px;
            line-height: 56px;
            font-family: 'Crimson Pro', serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: white;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(0,77,127,0.5);
        }
        .logo-area h1 {
            font-family: 'Crimson Pro', serif;
            font-size: 1.5rem;
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

        .divider { height: 1px; background: var(--border); margin: 0 0 28px; }

        label {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 0.95rem;
            margin-bottom: 20px;
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
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s, transform 0.15s;
        }
        .btn:hover  { background: #005f99; }
        .btn:active { transform: scale(0.98); }

        .errore {
            background: rgba(244,67,54,0.12);
            border: 1px solid rgba(244,67,54,0.35);
            color: #ff8a80;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.82rem;
            margin-bottom: 20px;
        }

        .register-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
            color: var(--muted);
            letter-spacing: 0.5px;
        }
        .register-link a { color: var(--accent); text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        .admin-link {
            display: block;
            text-align: center;
            margin-top: 12px;
            font-size: 0.68rem;
            color: rgba(232,237,244,0.2);
            text-decoration: none;
            letter-spacing: 1px;
            transition: color 0.2s;
        }
        .admin-link:hover { color: var(--muted); }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-area">
        <div class="logo-badge">A</div>
        <h1>Associazione per la Ricerca Scientifica</h1>
        <p>Area riservata membri</p>
    </div>
    <div class="divider"></div>

    <?php if ($errore): ?>
        <div class="errore">⚠ <?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>

    <form method="POST" action="login-utente.php">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               autocomplete="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               autocomplete="current-password" required>

        <button type="submit" class="btn">Accedi</button>
    </form>

    <p class="register-link">Non hai un account? <a href="register.php">Registrati</a></p>
    <a href="login.php" class="admin-link">accesso amministratori</a>
</div>
</body>
</html>
