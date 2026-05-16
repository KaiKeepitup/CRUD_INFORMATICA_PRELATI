<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); exit();
}
require_once 'db.php';
$pdo = getDB();

// Segna come letto
if (isset($_GET['letto']) && is_numeric($_GET['letto'])) {
    $pdo->prepare("UPDATE messaggi SET letto=1 WHERE id=?")->execute([(int)$_GET['letto']]);
    header("Location: admin.php"); exit();
}
// Elimina messaggio
if (isset($_GET['elimina_msg']) && is_numeric($_GET['elimina_msg'])) {
    $pdo->prepare("DELETE FROM messaggi WHERE id=?")->execute([(int)$_GET['elimina_msg']]);
    header("Location: admin.php"); exit();
}
// Elimina utente
if (isset($_GET['elimina_utente']) && is_numeric($_GET['elimina_utente'])) {
    $pdo->prepare("DELETE FROM utenti WHERE id=?")->execute([(int)$_GET['elimina_utente']]);
    header("Location: admin.php"); exit();
}
// Aggiungi notizia
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['titolo'])) {
    $t = htmlspecialchars(trim($_POST['titolo']));
    $c = htmlspecialchars(trim($_POST['contenuto']));
    if ($t && $c) {
        $pdo->prepare("INSERT INTO notizie (titolo,contenuto) VALUES (?,?)")->execute([$t,$c]);
    }
    header("Location: admin.php#notizie"); exit();
}
// Elimina notizia
if (isset($_GET['elimina_notizia']) && is_numeric($_GET['elimina_notizia'])) {
    $pdo->prepare("DELETE FROM notizie WHERE id=?")->execute([(int)$_GET['elimina_notizia']]);
    header("Location: admin.php"); exit();
}

$tab = $_GET['tab'] ?? 'messaggi';
$messaggi = $pdo->query("SELECT * FROM messaggi ORDER BY data_invio DESC")->fetchAll();
$utenti   = $pdo->query("SELECT * FROM utenti ORDER BY data_registrazione DESC")->fetchAll();
$notizie  = $pdo->query("SELECT * FROM notizie ORDER BY data_pubblicazione DESC")->fetchAll();
$donazioni= $pdo->query("SELECT d.*, u.nome, u.cognome, u.email FROM donazioni d JOIN utenti u ON d.utente_id=u.id ORDER BY d.data_donazione DESC")->fetchAll();

$tot_msg    = $pdo->query("SELECT COUNT(*) FROM messaggi")->fetchColumn();
$non_letti  = $pdo->query("SELECT COUNT(*) FROM messaggi WHERE letto=0")->fetchColumn();
$tot_utenti = $pdo->query("SELECT COUNT(*) FROM utenti")->fetchColumn();
$tot_don    = $pdo->query("SELECT COALESCE(SUM(importo),0) FROM donazioni")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APAM – Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root { --apam-navy:#03112b; --apam-blue:#004d7f; --apam-accent:#ffa500; }
        body { background:#0a1929; color:#e8edf4; font-family:Georgia,serif; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:230px; min-height:100vh; background:#020d1e; border-right:1px solid rgba(255,255,255,0.07); position:fixed; top:0; left:0; padding:24px 0; display:flex; flex-direction:column; z-index:200; }
        .sidebar-brand { padding:0 20px 24px; border-bottom:1px solid rgba(255,255,255,0.07); margin-bottom:16px; }
        .sidebar-brand .logo { font-size:1.3rem; font-weight:700; color:#fff; }
        .sidebar-brand .logo span { color:var(--apam-accent); }
        .sidebar-brand .sub { font-size:0.65rem; color:rgba(232,237,244,0.3); letter-spacing:1.5px; text-transform:uppercase; margin-top:2px; }
        .nav-side { list-style:none; padding:0; margin:0; }
        .nav-side li a { display:flex; align-items:center; gap:10px; padding:11px 20px; color:rgba(232,237,244,0.5); font-size:0.88rem; text-decoration:none; transition:all 0.2s; border-left:3px solid transparent; }
        .nav-side li a:hover { color:#fff; background:rgba(255,255,255,0.04); }
        .nav-side li a.active { color:#fff; background:rgba(0,77,127,0.3); border-left-color:var(--apam-accent); }
        .nav-side li a i { font-size:1rem; }
        .sidebar-bottom { margin-top:auto; padding:16px 20px; border-top:1px solid rgba(255,255,255,0.07); }
        .sidebar-bottom a { color:rgba(232,237,244,0.35); font-size:0.78rem; text-decoration:none; display:flex; align-items:center; gap:8px; transition:color 0.2s; }
        .sidebar-bottom a:hover { color:var(--apam-accent); }

        /* MAIN */
        .main-content { margin-left:230px; padding:32px; }

        /* TOPBAR */
        .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:32px; }
        .topbar h2 { font-size:1.5rem; font-weight:700; margin:0; }
        .topbar .user { font-size:0.82rem; color:rgba(232,237,244,0.5); }
        .topbar .user strong { color:#e8edf4; }

        /* STAT CARDS */
        .stat-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.09); border-radius:12px; padding:22px; }
        .stat-label { font-size:0.68rem; letter-spacing:2px; text-transform:uppercase; color:rgba(232,237,244,0.4); margin-bottom:8px; }
        .stat-value { font-size:2rem; font-weight:700; line-height:1; }
        .stat-value.accent { color:var(--apam-accent); }
        .stat-value.green  { color:#4caf50; }

        /* BADGE */
        .badge-new  { background:rgba(255,165,0,0.15); color:var(--apam-accent); border:1px solid rgba(255,165,0,0.3); font-size:0.65rem; padding:3px 9px; border-radius:20px; }
        .badge-read { background:rgba(255,255,255,0.06); color:rgba(232,237,244,0.4); border:1px solid rgba(255,255,255,0.08); font-size:0.65rem; padding:3px 9px; border-radius:20px; }

        /* CARDS */
        .item-card { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:18px 20px; margin-bottom:12px; transition:border-color 0.2s; }
        .item-card:hover { border-color:rgba(255,255,255,0.18); }
        .item-card.unread { border-left:3px solid var(--apam-accent); }
        .item-name { font-size:1rem; font-weight:700; margin-bottom:2px; }
        .item-meta { font-size:0.75rem; color:rgba(232,237,244,0.4); }
        .item-body { font-size:0.84rem; color:rgba(232,237,244,0.72); background:rgba(0,0,0,0.15); border-radius:6px; padding:10px 13px; margin:10px 0; line-height:1.6; white-space:pre-wrap; word-break:break-word; }

        .btn-act { padding:4px 12px; border-radius:6px; font-size:0.7rem; letter-spacing:0.5px; border:1px solid rgba(255,255,255,0.12); color:rgba(232,237,244,0.5); background:transparent; cursor:pointer; transition:all 0.2s; text-decoration:none; display:inline-block; }
        .btn-act:hover { color:#fff; border-color:rgba(255,255,255,0.3); }
        .btn-act.green:hover { border-color:#4caf50; color:#4caf50; }
        .btn-act.red:hover   { border-color:#ef5350; color:#ef5350; }
        .btn-act.blue        { border-color:rgba(0,100,200,0.4); color:#64b5f6; }
        .btn-act.blue:hover  { background:rgba(0,77,127,0.25); }

        /* FORM NOTIZIA */
        .form-dark .form-control { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14); color:#e8edf4; }
        .form-dark .form-control:focus { background:rgba(255,255,255,0.09); border-color:var(--apam-blue); box-shadow:0 0 0 3px rgba(0,77,127,0.3); color:#e8edf4; }
        .form-dark .form-control::placeholder { color:rgba(232,237,244,0.25); }
        .form-dark .form-label { color:rgba(232,237,244,0.6); font-size:0.82rem; }
        .btn-add { background:var(--apam-blue); border:none; color:#fff; padding:10px 24px; border-radius:8px; font-size:0.88rem; cursor:pointer; transition:background 0.25s; }
        .btn-add:hover { background:var(--apam-accent); color:var(--apam-navy); }

        .section-divider { border-color:rgba(255,255,255,0.07); margin:28px 0; }
        .empty-state { text-align:center; padding:40px; color:rgba(232,237,244,0.25); font-size:0.85rem; }

        @media(max-width:768px) {
            .sidebar { display:none; }
            .main-content { margin-left:0; padding:20px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="logo">A<span>P</span>AM</div>
        <div class="sub">Admin Panel</div>
    </div>
    <ul class="nav-side">
        <li><a href="admin.php?tab=messaggi" class="<?= $tab==='messaggi' ? 'active':'' ?>"><i class="bi bi-envelope"></i> Messaggi <?php if($non_letti>0): ?><span class="badge-new ms-auto"><?= $non_letti ?></span><?php endif; ?></a></li>
        <li><a href="admin.php?tab=utenti"   class="<?= $tab==='utenti'   ? 'active':'' ?>"><i class="bi bi-people"></i> Utenti</a></li>
        <li><a href="admin.php?tab=notizie"  class="<?= $tab==='notizie'  ? 'active':'' ?>"><i class="bi bi-newspaper"></i> Notizie</a></li>
        <li><a href="admin.php?tab=donazioni"class="<?= $tab==='donazioni'? 'active':'' ?>"><i class="bi bi-heart"></i> Donazioni</a></li>
    </ul>
    <div class="sidebar-bottom">
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">

    <div class="topbar">
        <h2>
            <?= match($tab) { 'messaggi'=>'Messaggi', 'utenti'=>'Utenti', 'notizie'=>'Notizie', 'donazioni'=>'Donazioni', default=>'Dashboard' } ?>
        </h2>
        <span class="user">Admin: <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong></span>
    </div>

    <!-- STAT CARDS (sempre visibili) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Messaggi</div>
                <div class="stat-value"><?= $tot_msg ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Non letti</div>
                <div class="stat-value accent"><?= $non_letti ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Utenti</div>
                <div class="stat-value green"><?= $tot_utenti ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Totale donazioni</div>
                <div class="stat-value accent">€<?= number_format($tot_don,0,',','.') ?></div>
            </div>
        </div>
    </div>

    <!-- ── TAB MESSAGGI ── -->
    <?php if ($tab === 'messaggi'): ?>
    <?php if(empty($messaggi)): ?>
        <div class="empty-state"><i class="bi bi-inbox fs-1 d-block mb-3"></i>Nessun messaggio ricevuto.</div>
    <?php else: foreach($messaggi as $m): ?>
    <div class="item-card <?= !$m['letto'] ? 'unread' : '' ?>">
        <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-2">
            <div>
                <div class="item-name"><?= htmlspecialchars($m['nome']) ?></div>
                <div class="item-meta"><?= htmlspecialchars($m['email']) ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?= !$m['letto'] ? '<span class="badge-new">Nuovo</span>' : '<span class="badge-read">Letto</span>' ?>
                <span class="item-meta"><?= date('d/m/Y H:i', strtotime($m['data_invio'])) ?></span>
            </div>
        </div>
        <div class="item-body"><?= htmlspecialchars($m['messaggio']) ?></div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if(!$m['letto']): ?>
                <a href="admin.php?letto=<?= $m['id'] ?>&tab=messaggi" class="btn-act green">✓ Segna letto</a>
            <?php endif; ?>
            <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn-act blue">✉ Rispondi</a>
            <a href="admin.php?elimina_msg=<?= $m['id'] ?>&tab=messaggi" class="btn-act red" onclick="return confirm('Eliminare?')">✕ Elimina</a>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- ── TAB UTENTI ── -->
    <?php elseif ($tab === 'utenti'): ?>
    <?php if(empty($utenti)): ?>
        <div class="empty-state"><i class="bi bi-people fs-1 d-block mb-3"></i>Nessun utente registrato.</div>
    <?php else: foreach($utenti as $u): ?>
    <div class="item-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="item-name"><?= htmlspecialchars($u['nome'].' '.$u['cognome']) ?></div>
                <div class="item-meta"><?= htmlspecialchars($u['email']) ?> &nbsp;·&nbsp; Registrato il <?= date('d/m/Y', strtotime($u['data_registrazione'])) ?></div>
            </div>
            <div class="d-flex gap-2">
                <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="btn-act blue">✉ Scrivi</a>
                <a href="admin.php?elimina_utente=<?= $u['id'] ?>&tab=utenti" class="btn-act red" onclick="return confirm('Eliminare utente?')">✕ Rimuovi</a>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- ── TAB NOTIZIE ── -->
    <?php elseif ($tab === 'notizie'): ?>
    <div class="item-card mb-4" id="notizie">
        <h6 class="mb-3" style="color:var(--apam-accent)"><i class="bi bi-plus-circle me-2"></i>Pubblica nuova notizia</h6>
        <form method="POST" action="admin.php?tab=notizie" class="form-dark">
            <div class="mb-3">
                <label class="form-label">Titolo</label>
                <input type="text" name="titolo" class="form-control" placeholder="Titolo della notizia" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contenuto</label>
                <textarea name="contenuto" class="form-control" rows="4" placeholder="Testo della notizia..." required></textarea>
            </div>
            <button type="submit" class="btn-add"><i class="bi bi-send me-2"></i>Pubblica</button>
        </form>
    </div>
    <hr class="section-divider">
    <?php if(empty($notizie)): ?>
        <div class="empty-state"><i class="bi bi-newspaper fs-1 d-block mb-3"></i>Nessuna notizia pubblicata.</div>
    <?php else: foreach($notizie as $n): ?>
    <div class="item-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="item-name"><?= htmlspecialchars($n['titolo']) ?></div>
                <div class="item-meta"><?= date('d/m/Y', strtotime($n['data_pubblicazione'])) ?></div>
            </div>
            <a href="admin.php?elimina_notizia=<?= $n['id'] ?>&tab=notizie" class="btn-act red" onclick="return confirm('Eliminare notizia?')">✕ Elimina</a>
        </div>
        <div class="item-body mt-2"><?= htmlspecialchars($n['contenuto']) ?></div>
    </div>
    <?php endforeach; endif; ?>

    <!-- ── TAB DONAZIONI ── -->
    <?php elseif ($tab === 'donazioni'): ?>
    <?php if(empty($donazioni)): ?>
        <div class="empty-state"><i class="bi bi-heart fs-1 d-block mb-3"></i>Nessuna donazione ancora.</div>
    <?php else: foreach($donazioni as $d): ?>
    <div class="item-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="item-name"><?= htmlspecialchars($d['nome'].' '.$d['cognome']) ?></div>
                <div class="item-meta"><?= htmlspecialchars($d['email']) ?> &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($d['data_donazione'])) ?></div>
                <?php if($d['messaggio']): ?><div class="item-meta mt-1" style="font-style:italic">"<?= htmlspecialchars($d['messaggio']) ?>"</div><?php endif; ?>
            </div>
            <div class="stat-value accent" style="font-size:1.4rem;">€<?= number_format($d['importo'],2,',','.') ?></div>
        </div>
    </div>
    <?php endforeach; endif; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
