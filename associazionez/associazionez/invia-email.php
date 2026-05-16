<?php

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$smtp_host     = "smtp.gmail.com";
$smtp_port     = 587;
$smtp_username = "giulioprelati8@gmail.com";
$smtp_password = "stba kcpf uocc lwpc";
$smtp_secure   = "tls";
$destinatario  = "giulioprelati27@gmail.com";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome     = htmlspecialchars(trim($_POST['nome']     ?? ''));
    $email    = htmlspecialchars(trim($_POST['email']    ?? ''));
    $messaggio = htmlspecialchars(trim($_POST['messaggio'] ?? ''));

    $errori = [];

    if (empty($nome))                                         $errori[] = "Il nome è obbligatorio";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errori[] = "Email non valida";
    if (empty($messaggio))                                    $errori[] = "Il messaggio è obbligatorio";

    if (empty($errori)) {

        // 1. Salva nel database
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("INSERT INTO messaggi (nome, email, messaggio) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $email, $messaggio]);
        } catch (Exception $e) {
            error_log("Errore salvataggio DB APAM: " . $e->getMessage());
            // Non blocchiamo l'invio se il DB fallisce, ma logghiamo
        }

        // 2. Invia email
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = $smtp_secure;
            $mail->Port       = $smtp_port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtp_username, 'APAM - Form Contatti');
            $mail->addAddress($destinatario);
            $mail->addReplyTo($email, $nome);

            $mail->isHTML(false);
            $mail->Subject = 'Nuovo messaggio dal sito APAM';
            $mail->Body    = "Grazie per esserti iscritto ad APAM! Il tuo messaggio è stato inviato!\n";
            $mail->Body   .= "Riceverai aggiornamenti sugli eventi disponibili a cui puoi partecipare per aiutare la ricerca.\n\n";
            $mail->Body   .= "=====================================\n";
            $mail->Body   .= "Nome:  $nome\n";
            $mail->Body   .= "Email: $email\n";
            $mail->Body   .= "=====================================\n\n";
            $mail->Body   .= "Messaggio:\n$messaggio\n\n";
            $mail->Body   .= "=====================================\n";
            $mail->Body   .= "Data e ora: " . date('d/m/Y H:i:s') . "\n";

            $mail->send();

            header("Location: index.html?success=1");
            exit();

        } catch (Exception $e) {
            error_log("Errore invio email APAM: " . $mail->ErrorInfo);
            header("Location: index.html?error=invio");
            exit();
        }

    } else {
        header("Location: index.html?error=validazione");
        exit();
    }

} else {
    header("Location: index.html");
    exit();
}
?>
