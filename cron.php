<?php
if (file_exists("config.php") == FALSE) {
    die ("Konfigurationsdatei fehlt! Bitte die Datei config.sample.php mit eigenen Einstellungen anpassen und in config.php umbenennen.");
}
include "config.php";

/* Klasse zur Behandlung von Ausnahmen und Fehlern */
require 'phpmailer/Exception.php';

/* PHPMailer-Klasse */
require 'phpmailer/PHPMailer.php';

/* SMTP-Klasse, die benötigt wird, um die Verbindung mit einem SMTP-Server herzustellen */
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$db = new SQLite3("data/priv/database.sqlite");
$db->busyTimeout(5000);
$db-> exec("CREATE TABLE IF NOT EXISTS 'fragen' ('title' TEXT, 'id' TEXT, 'time' INT, 'password' TEXT, 'shortcode' TEXT, 'token' TEXT, 'close' INT, 'ablauf' INT, 'email' TEXT)");
$db-> exec("CREATE TABLE IF NOT EXISTS 'antworten' ('id' TEXT, 'nicht_verstanden' TEXT, 'mehr_erfahren1' TEXT, 'mehr_erfahren2' TEXT, 'gelernt1' TEXT , 'gelernt2' TEXT , 'gelernt3' TEXT , 'time' INT)");

print ("abgelaufene Goodbyes mit E-Mail Benachrichtigung finden<br>");
$result_cron = $db->query('SELECT * FROM "fragen"');
$i = 0;
while ($row_cron = $result_cron->fetchArray())
{
    //print ($row_cron['id']."<br>");
    if (time() >= $row_cron['close'] and $row_cron['email'] != "" and $row_cron['close'] != "") {
        print ($row_cron['id']);
        print (" ->");
        $email = base64_decode ($row_cron['email']);
		print ("***");
        // print ($email);
        print ("<br>");
        $cron_titel = base64_decode ($row_cron['title']);
        $filename="data/".uniqid().".pdf";
        $_GET["pdf"] = base64_decode ($db->querySingle('SELECT "token" FROM "fragen" WHERE "id" = "'.$row_cron['id'].'" '));
        print ($_GET["pdf"]);
        $db-> exec ('UPDATE "fragen" SET "email"="" WHERE "id"="'.$row_cron['id'].'"');        
        
        //Aufruf der Funktion, Versand von 1 Datei
        //Create an instance; passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			$mail->SMTPDebug  = SMTP::DEBUG_OFF; 
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $mail_server;                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = $mail_user;                     //SMTP username
            $mail->Password   = $mail_password;                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = $mail_port;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom($mail_from, '1-2-3 Goodbye');
            $mail->addAddress($email, '');     //Add a recipient
            #$mail->addAddress('ellen@example.com');               //Name is optional
            #$mail->addReplyTo('info@example.com', 'Information');
            #$mail->addCC('cc@example.com');
            #$mail->addBCC('bcc@example.com');

            //Attachments
            $mail->addAttachment($filename, '123goodbye.pdf');         //Add attachments
            #$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = '3-2-1-Goodbye';
            $mail->Body    = 'Anbei findest du die eingereichten Antworten zum Goodbye mit dem Titel <b>'.$cron_titel.'</b>';
            
            $mail->send();
            print ("<br>");
            echo 'Message has been sent';
            unlink($filename);
            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }       
    }   
}
?>