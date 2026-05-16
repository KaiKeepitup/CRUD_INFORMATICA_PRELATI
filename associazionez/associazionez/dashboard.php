<?php
session_start();
if (!isset($_SESSION['utente_logged_in']) || $_SESSION['utente_logged_in'] !== true) {
    header("Location: index.html?login=1");
    exit();
}

require_once 'db.php';
$pdo = getDB();

// Gestione donazione
$msg_donazione = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importo'])) {
    $importo   = floatval($_POST['importo']);
    $msgDon    = htmlspecialchars(trim($_POST['messaggio_donazione'] ?? ''));
    if ($importo >= 1) {
        $stmt = $pdo->prepare("INSERT INTO donazioni (utente_id, importo, messaggio) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['utente_id'], $importo, $msgDon]);
        $msg_donazione = 'success';
    } else {
        $msg_donazione = 'error';
    }
}

$notizie  = $pdo->query("SELECT * FROM notizie WHERE pubblicata = 1 ORDER BY data_pubblicazione DESC")->fetchAll();
$donStmt  = $pdo->prepare("SELECT * FROM donazioni WHERE utente_id = ? ORDER BY data_donazione DESC");
$donStmt->execute([$_SESSION['utente_id']]);
$donazioni      = $donStmt->fetchAll();
$totale_donato  = array_sum(array_column($donazioni, 'importo'));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APAM – Area Membri</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --apam-navy:#03112b; --apam-blue:#004d7f; --apam-accent:#ffa500; }

        body { background: var(--apam-navy); color: #e8edf4; font-family: Georgia, serif; min-height: 100vh; display: flex; flex-direction: column; }

        /* NAVBAR — identica al sito */
        .navbar { background: rgba(0,77,127,0.92); backdrop-filter: blur(10px); padding: 14px 0; box-shadow: 0 2px 20px rgba(0,0,0,0.4); }
        .navbar-brand { font-size: 1.15rem; font-weight: 700; color: #fff !important; }
        .navbar-brand span { color: var(--apam-accent); }
        .nav-link { color: rgba(255,255,255,0.85) !important; transition: color 0.2s; }
        .nav-link:hover { color: var(--apam-accent) !important; }

        .user-pill { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 20px; padding: 5px 14px; font-size: 0.82rem; color: #fff !important; display: flex; align-items: center; gap: 7px; text-decoration: none; }
        .user-pill .dot { width: 8px; height: 8px; background: #4caf50; border-radius: 50%; display: inline-block; }
        .btn-logout-nav { background: transparent; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; padding: 5px 14px; color: rgba(255,255,255,0.7) !important; font-size: 0.82rem; transition: all 0.2s; text-decoration: none; }
        .btn-logout-nav:hover { border-color: var(--apam-accent); color: var(--apam-accent) !important; }

        /* HERO DASHBOARD */
        .dash-hero {
            background: linear-gradient(135deg, rgba(0,77,127,0.5) 0%, rgba(3,17,43,0.8) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 48px 0 40px;
        }
        .dash-hero h1 { font-size: 1.9rem; font-weight: 700; margin-bottom: 6px; }
        .dash-hero h1 span { color: var(--apam-accent); }
        .dash-hero p { color: rgba(232,237,244,0.65); font-size: 0.95rem; margin: 0; }

        /* STAT CARDS */
        .stat-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 22px 24px; }
        .stat-label { font-size: 0.72rem; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(232,237,244,0.45); margin-bottom: 8px; }
        .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-value.accent { color: var(--apam-accent); }

        /* NOTIZIE */
        .news-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.09); border-radius: 10px; padding: 20px; margin-bottom: 14px; transition: border-color 0.2s; }
        .news-card:hover { border-color: rgba(255,255,255,0.2); }
        .news-card h5 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; }
        .news-card p { font-size: 0.86rem; color: rgba(232,237,244,0.72); line-height: 1.6; margin: 0; }
        .news-date { font-size: 0.7rem; color: rgba(232,237,244,0.35); margin-top: 10px; letter-spacing: 0.5px; }

        /* DONAZIONI */
        .don-box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.09); border-radius: 10px; padding: 22px; }
        .don-box .form-control { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); color: #e8edf4; }
        .don-box .form-control:focus { background: rgba(255,255,255,0.1); border-color: var(--apam-blue); box-shadow: 0 0 0 3px rgba(0,77,127,0.3); color: #e8edf4; }
        .don-box .form-control::placeholder { color: rgba(232,237,244,0.3); }
        .don-box .form-label { color: rgba(232,237,244,0.6); font-size: 0.82rem; }
        .btn-dona { background: var(--apam-accent); color: var(--apam-navy); border: none; padding: 11px; border-radius: 8px; font-size: 0.9rem; font-weight: 700; width: 100%; cursor: pointer; transition: opacity 0.2s; }
        .btn-dona:hover { opacity: 0.85; }

        .don-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,0.07); font-size: 0.82rem; }
        .don-row:last-child { border-bottom: none; }
        .don-amount { color: var(--apam-accent); font-weight: 700; }

        .alert-success-custom { background: rgba(76,175,80,0.12); border: 1px solid rgba(76,175,80,0.3); color: #a5d6a7; border-radius: 8px; padding: 10px 14px; font-size: 0.83rem; margin-bottom: 14px; }
        .alert-error-custom   { background: rgba(244,67,54,0.12);  border: 1px solid rgba(244,67,54,0.3);  color: #ff8a80; border-radius: 8px; padding: 10px 14px; font-size: 0.83rem; margin-bottom: 14px; }

        .section-heading { font-size: 1.15rem; font-weight: 700; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 18px; }
        .section-heading i { color: var(--apam-accent); margin-right: 8px; }

        .empty-state { text-align: center; padding: 30px; color: rgba(232,237,244,0.3); font-size: 0.85rem; }

        main { flex: 1; padding: 40px 0; }

        /* FOOTER — identico al sito */
        footer { background: #020d1e; color: rgba(255,255,255,0.3); padding: 24px 0; font-size: 0.82rem; text-align: center; }
        footer a { color: rgba(255,255,255,0.18); text-decoration: none; }
        footer a:hover { color: rgba(255,255,255,0.45); }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.html">A<span>P</span>AM</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="index.html#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="index.html#chi-siamo">Chi Siamo</a></li>
                <li class="nav-item"><a class="nav-link" href="index.html#progetti">Progetti</a></li>
                <li class="nav-item"><a class="nav-link" href="index.html#donazioni">Donazioni</a></li>
                <li class="nav-item"><a class="nav-link" href="index.html#contatti">Contatti</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="user-pill">
                    <span class="dot"></span>
                    <?= htmlspecialchars($_SESSION['utente_nome']) ?>
                </span>
                <a href="logout-utente.php" class="btn-logout-nav">
                    <i class="bi bi-box-arrow-right me-1"></i>Esci
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- HERO DASHBOARD -->
<div class="dash-hero">
    <div class="container">
        <h1>Ciao, <span><?= htmlspecialchars($_SESSION['utente_nome']) ?></span> 👋</h1>
        <p>Benvenuto nella tua area riservata — Associazione per la Ricerca Scientifica</p>
    </div>
</div>

<main>
    <div class="container">

        <!-- STATS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Notizie</div>
                    <div class="stat-value"><?= count($notizie) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Totale donato</div>
                    <div class="stat-value accent">€<?= number_format($totale_donato, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Donazioni</div>
                    <div class="stat-value"><?= count($donazioni) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Stato</div>
                    <div class="stat-value" style="font-size:1.1rem;color:#4caf50;">Attivo</div>
                </div>
            </div>
        </div>

        <!-- CORPO PRINCIPALE -->
        <div class="row g-4">

            <!-- NOTIZIE -->
            <div class="col-lg-6">
                <div class="section-heading">
                    <i class="bi bi-newspaper"></i>Notizie & Aggiornamenti
                </div>
                <?php if (empty($notizie)): ?>
                    <div class="empty-state"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Nessuna notizia disponibile.</div>
                <?php else: ?>
                    <?php foreach ($notizie as $n): ?>
                    <div class="news-card">
                        <h5><?= htmlspecialchars($n['titolo']) ?></h5>
                        <p><?= nl2br(htmlspecialchars($n['contenuto'])) ?></p>
                        <div class="news-date"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($n['data_pubblicazione'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- DONAZIONI -->
            <div class="col-lg-6">
                <div class="section-heading">
                    <i class="bi bi-heart-fill"></i>Fai una donazione
                </div>

                <?php if ($msg_donazione === 'success'): ?>
                    <div class="alert-success-custom">✓ Grazie per la tua donazione! Il tuo contributo è prezioso.</div>
                <?php elseif ($msg_donazione === 'error'): ?>
                    <div class="alert-error-custom">⚠ Importo non valido. Minimo €1.</div>
                <?php endif; ?>

                <div class="don-box mb-4">
                    <form method="POST" action="dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Importo (€)</label>
                            <input type="number" name="importo" class="form-control" min="1" step="0.50" placeholder="Es. 10.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Messaggio (opzionale)</label>
                            <textarea name="messaggio_donazione" class="form-control" rows="2" placeholder="Lascia un messaggio..."></textarea>
                        </div>
                        <button type="submit" class="btn-dona">
                            <i class="bi bi-heart-fill me-2"></i>Dona ora
                        </button>
                    </form>
                </div>

                <div class="section-heading">
                    <i class="bi bi-clock-history"></i>Le tue donazioni
                </div>
                <?php if (empty($donazioni)): ?>
                    <div class="empty-state"><i class="bi bi-wallet2 fs-2 d-block mb-2"></i>Nessuna donazione ancora.</div>
                <?php else: ?>
                    <?php foreach ($donazioni as $d): ?>
                    <div class="don-row">
                        <span><i class="bi bi-calendar3 me-1" style="opacity:.5"></i><?= date('d/m/Y', strtotime($d['data_donazione'])) ?></span>
                        <span class="don-amount">€ <?= number_format($d['importo'], 2, ',', '.') ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

<!-- FOOTER -->
<footer>
    <div class="container">
        <p class="mb-1">© 2025 APAM — Associazione per la Ricerca Scientifica · Agello</p>
        <p><a href="login.php">admin</a></p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
