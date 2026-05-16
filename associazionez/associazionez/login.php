<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php"); exit();
}
require_once 'db.php';
$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($username) || empty($password)) {
        $errore = 'Inserisci username e password.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id']        = $admin['id'];
                $_SESSION['admin_username']  = $admin['username'];
                header("Location: admin.php"); exit();
            } else {
                $errore = 'Credenziali non valide.';
            }
        } catch (Exception $e) {
            $errore = 'Errore di sistema.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APAM – Accesso Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --apam-navy:#03112b; --apam-blue:#004d7f; --apam-accent:#ffa500; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--apam-navy); font-family: Georgia, serif; }
        body::before { content:''; position:fixed; inset:-50%; background: radial-gradient(ellipse 60% 40% at 20% 30%, rgba(0,77,127,0.4) 0%, transparent 60%), radial-gradient(ellipse 50% 60% at 80% 70%, rgba(255,165,0,0.07) 0%, transparent 55%); animation: drift 14s ease-in-out infinite alternate; z-index:0; }
        @keyframes drift { from{transform:translate(0,0)} to{transform:translate(3%,2%)} }
        .card { position:relative; z-index:1; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; background: rgba(255,255,255,0.04); backdrop-filter: blur(18px); box-shadow: 0 24px 60px rgba(0,0,0,0.5); animation: up 0.5s cubic-bezier(.22,.68,0,1.2) both; }
        @keyframes up { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
        .badge-logo { width:54px; height:54px; background: linear-gradient(135deg, var(--apam-blue), #006aad); border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:700; color:#fff; box-shadow: 0 8px 24px rgba(0,77,127,0.45); }
        .form-control { background: rgba(255,255,255,0.07) !important; border: 1px solid rgba(255,255,255,0.15) !important; color: #e8edf4 !important; }
        .form-control:focus { background: rgba(255,255,255,0.1) !important; border-color: var(--apam-blue) !important; box-shadow: 0 0 0 3px rgba(0,77,127,0.3) !important; color: #e8edf4 !important; }
        .form-control::placeholder { color: rgba(232,237,244,0.3) !important; }
        .form-label { color: rgba(232,237,244,0.6); font-size: 0.82rem; }
        .btn-login { background: var(--apam-blue); border: none; width:100%; padding:12px; border-radius:8px; color:#fff; font-size:0.95rem; font-weight:600; transition: background 0.25s; }
        .btn-login:hover { background: var(--apam-accent); color: var(--apam-navy); }
        h1 { color: #e8edf4; font-size: 1.3rem; }
        p.sub { color: rgba(232,237,244,0.4); font-size:0.75rem; letter-spacing:1.5px; text-transform:uppercase; }
        .back-link { color: rgba(232,237,244,0.25); font-size:0.75rem; text-decoration:none; }
        .back-link:hover { color: var(--apam-accent); }
        hr { border-color: rgba(255,255,255,0.08); }
    </style>
</head>
<body>
<div class="card p-4 p-md-5" style="width:min(420px,94vw)">
    <div class="text-center mb-4">
        <div class="badge-logo mx-auto mb-3">A</div>
        <h1 class="mb-1">Pannello Admin</h1>
        <p class="sub mb-0">Associazione per la Ricerca Scientifica</p>
    </div>
    <hr class="mb-4">

    <?php if ($errore): ?>
    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:rgba(244,67,54,0.12);border:1px solid rgba(244,67,54,0.35);color:#ff8a80;border-radius:8px;font-size:.83rem;">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($errore) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn-login">
            <i class="bi bi-shield-lock me-2"></i>Accedi
        </button>
    </form>

    <div class="text-center mt-4">
        <a href="index.html" class="back-link"><i class="bi bi-arrow-left me-1"></i>Torna al sito</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
