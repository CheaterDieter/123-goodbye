<?php
$version = "V0.4-beta";

if (file_exists("config.php") == FALSE) {
    die ("Konfigurationsdatei fehlt! Bitte die Datei config.sample.php mit eigenen Einstellungen anpassen und in config.php umbenennen.");
}

include "config.php";

 

include "phpqrcode.php";

if (isset ($_GET["qr"])){
    $filenameqr = "data/qr/".uniqid().".png";
	QRcode::png(base64_decode($_GET["qr"]),$filenameqr, QR_ECLEVEL_L, 10, 1);

    header('Content-type: image/png');
    $gd = imagecreatefrompng($filenameqr);
    imagepng($gd);
    imagedestroy($gd);
    unlink ($filenameqr);
	exit();
}

$method = "aes-128-cbc";
$iv_length = openssl_cipher_iv_length($method);

$db = new SQLite3("data/priv/database.sqlite");
$db->busyTimeout(5000);
$db-> exec("CREATE TABLE IF NOT EXISTS 'fragen' ('title' TEXT, 'id' TEXT, 'time' INT, 'password' TEXT, 'shortcode' TEXT, 'token' TEXT, 'close' INT, 'ablauf' INT, 'email' TEXT)");
$db-> exec("CREATE TABLE IF NOT EXISTS 'antworten' ('id' TEXT, 'nicht_verstanden' TEXT, 'mehr_erfahren1' TEXT, 'mehr_erfahren2' TEXT, 'gelernt1' TEXT , 'gelernt2' TEXT , 'gelernt3' TEXT , 'time' INT)");

$url = "http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'')."://";
$url .= $_SERVER['HTTP_HOST'];
?>

<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <title>1-2-3-Goodbye</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?php print ($url); ?>/style.css" rel="stylesheet">
    <?php if (isset ($_GET["realtime"])){print ('<meta http-equiv="refresh" content="5">'); }?>
</head>

<body>
    <?php
    if (isset ($_GET["realtime"])){    # Realtime Anzahl Abgaben
        if ($_GET["realtime"] != ""){
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["realtime"]).'" ');
            $result = $db->query('SELECT * FROM "antworten" WHERE "id" = "'.$id.'"');
            $i = 0;
            while ($row = $result->fetchArray())
            {
                $i++;
            }	
            print ("<div class='realtime'>".$i."</div></body></html>");
            die();
        } 
    }

   
?>
    <div class="content">
    <?php
    if (isset ($_GET["createform"]) & isset ($_POST["titel"])){ # Neue Form anlegen
        if ($_POST["name"] != "") {die("");} # Honeypot
        if ($_POST["titel"] != ""){
            $id = uniqid();
            $titel = base64_encode ($_POST["titel"]);
            $pw = hash('sha256', $_POST["password"]);
            $pw_header = "";
            if (isset ($_POST["password"])) {
                $pw_header = $_POST["password"];
            }
            $ablauf_abfrage = $_POST["ablauf"];
            $ablauf = 0;
            if ($ablauf_abfrage == "5 Minuten nach erster Antwort") {
                $ablauf = -5;
            }
            if ($ablauf_abfrage == "30 Minuten nach erster Antwort") {
                $ablauf = -20;
            }            
            if ($ablauf_abfrage == "90 Minuten nach erster Antwort") {
                $ablauf = -90;
            }    
            if ($_POST["email"] != "") {
                $email = base64_encode ($_POST["email"]);
            } else {
                $email = NULL;
            }

            $token = $id . random_int(1000000000, 9999999999);

            $token_store = base64_encode ($token);
            
            $laenge_shortcode = 3;
            $shortcode = base64_encode (substr(str_shuffle('123456789abcdefghijklmnopqrstuvwxyz'), 0, $laenge_shortcode));

            while ($db->querySingle('SELECT * FROM "fragen" WHERE "shortcode" = "'.$shortcode.'" ') != FALSE){
                // Shortcode schon vergeben -> neuen generieren
                $laenge_shortcode = $laenge_shortcode + 1;
                $shortcode = base64_encode (substr(str_shuffle('123456789abcdefghijklmnopqrstuvwxyz'), 0, $laenge_shortcode));
            }
            
            print ("<h1>1-2-3-Goodbye</h1><p>Dein neues Goodbye wurde angelegt.<br>Es trägt den Namen:</p><h3>".base64_decode ($titel)."</h3>");
            
            $url = "http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'')."://";
            $url .= $_SERVER['HTTP_HOST'];
            $url_server=$url;
            $url .= $_SERVER['REQUEST_URI']; // $url enthält jetzt die komplette URL
            

            $url_neu = str_replace ("index.php?createform","".base64_decode ($shortcode), $url);
            $url_neu_display = $url_neu;
            $url_neu_display = str_replace ("http://","", $url_neu_display);
            $url_neu_display = str_replace ("https://","", $url_neu_display);



            $db-> exec ("INSERT INTO 'fragen' ('title','id','time','password', 'shortcode', 'token', 'close', 'ablauf', 'email') VALUES ('".$titel."','".$id."','".time()."','".$pw."','".$shortcode."','".$token_store."','',".$ablauf.",'".$email."')");
            if ($pw_header != "") {
                header("Location: ".$url_server."?get=".base64_decode ($shortcode)."&pw=".base64_encode (openssl_encrypt($pw_header, $method, $secret_key, 0, $iv)));
            } else {
                header("Location: ".$url_server."/".base64_decode ($shortcode)."/t");
            }
            die();

        } else {
            print ('Kein Titel vergeben!<br><br><a href="javascript:history.back()">Zurück</a>');
        }

    }
    elseif (isset ($_GET["a"])){    # Antwort auf Formular
        $gegebenershortcode = base64_encode ($_GET["a"]);

        if ($db->querySingle('SELECT * FROM "fragen" WHERE "shortcode" = "'.$gegebenershortcode.'" ') != FALSE ) {
            
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "shortcode" = "'.$gegebenershortcode.'" ');
            
            $close = $db->querySingle('SELECT "close" FROM "fragen" WHERE "id" = "'.$id.'" ');

            if (($close == "") or ($close > time())) {
           
            ?>
            <h1><?php print (base64_decode ($db->querySingle('SELECT "title" FROM "fragen" WHERE "shortcode" = "'.$gegebenershortcode.'" '))) ?></h1>
            <form action="index.php?submitfeedback=<?php print ($_GET["a"]) ?>"  method="post">
                <p><?php if (isset ($_GET["msg"])) {echo ($_GET["msg"]);} ?></p>
                <p><div class="fat">Eine Sache,</div>die ich nicht verstanden habe:</p>
                <input class="text" type="text" id="nicht_verstanden" name="nicht_verstanden" value="<?php if (isset ($_GET["1"])) {echo ($_GET["1"]);} ?>">
                <br><br>

                <p><div class="fat">Zwei Dinge,</div>über die ich mehr erfahren möchte:</p>
                <input class="text" type="text" id="mehr_erfahren1" name="mehr_erfahren1" value="<?php if (isset ($_GET["2"])) {echo ($_GET["2"]);} ?>">
                <br><br>
                <input class="text" type="text" id="mehr_erfahren2" name="mehr_erfahren2" value="<?php if (isset ($_GET["3"])) {echo ($_GET["3"]);} ?>">

                <p><div class="fat">Drei Dinge,</div>die ich gelernt habe:</p>
                <input class="text" type="text" id="gelernt1" name="gelernt1" value="<?php if (isset ($_GET["4"])) {echo ($_GET["4"]);} ?>">
                <br><br>
                <input class="text" type="text" id="gelernt2" name="gelernt2" value="<?php if (isset ($_GET["5"])) {echo ($_GET["5"]);} ?>">
                <br><br>
                <input class="text" type="text" id="gelernt3" name="gelernt3" value="<?php if (isset ($_GET["6"])) {echo ($_GET["6"]);} ?>">

                <br><br>
                
                <input class="enter" type="submit" value="Goodbye!">
            </form> 
            <?php
            } else {
                print ("Dieses Goodbye ist leider abgelaufen.");
            }            
        }
        else {
            print ("Shortcode fehlt/nicht vorhanden!");
        }
    } elseif (isset ($_GET["submitfeedback"])){
        if ($_GET["submitfeedback"] != ""){
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "shortcode" = "'.base64_encode ($_GET["submitfeedback"]).'" ');

            if ($id != FALSE) {
                $nicht_verstanden = $_POST["nicht_verstanden"];
                $mehr_erfahren1 = $_POST["mehr_erfahren1"];
                $mehr_erfahren2 = $_POST["mehr_erfahren2"];
                $gelernt1 = $_POST["gelernt1"];
                $gelernt2 = $_POST["gelernt2"];
                $gelernt3 = $_POST["gelernt3"];

                if ($nicht_verstanden == "" or $mehr_erfahren1 == "" or $mehr_erfahren2 == "" or $gelernt1 == "" or $gelernt2 == "" or $gelernt3 == "") {
                    $url = "http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'')."://";
                    $url .= $_SERVER['HTTP_HOST'];

                    header("Location: ". $url."?a=".$_GET["submitfeedback"]."&msg=Bitte alle Felder ausfüllen&1=".$nicht_verstanden."&2=".$mehr_erfahren1."&3=".$mehr_erfahren2."&4=".$gelernt1."&5=".$gelernt2."&6=".$gelernt3);
                    die();
                }
                

                $nicht_verstanden = base64_encode ($nicht_verstanden);
                $mehr_erfahren1 = base64_encode ($mehr_erfahren1);
                $mehr_erfahren2 = base64_encode ($mehr_erfahren2);
                $gelernt1 = base64_encode ($gelernt1);
                $gelernt2 = base64_encode ($gelernt2);
                $gelernt3 = base64_encode ($gelernt3);



                $ablauf = $db->querySingle('SELECT "ablauf" FROM "fragen" WHERE "id" = "'.$id.'" ');
                if (($ablauf < 0) and ($db->querySingle('SELECT "close" FROM "fragen" WHERE "id" = "'.$id.'" ') == "")) {
                    $ablauf = $ablauf * (-1) * 60 + time();
                    $db->exec('UPDATE "fragen" SET "close"="'. $ablauf.'" WHERE "id"="'.$id.'"');
                }

                if (($ablauf == 0) or ($db->querySingle('SELECT "close" FROM "fragen" WHERE "id" = "'.$id.'" ') > time())) {
                    $db->exec('INSERT INTO "antworten" ("id","nicht_verstanden","mehr_erfahren1","mehr_erfahren2","gelernt1","gelernt2","gelernt3", "time") VALUES ("'.$id.'","'.$nicht_verstanden.'","'.$mehr_erfahren1.'","'.$mehr_erfahren2.'","'.$gelernt1.'","'.$gelernt2.'","'.$gelernt3.'","'.time().'")');
                    print ("<h1>1-2-3-Goodbye!</h1><br>Dein Goodbye zum Thema<br><b>".base64_decode($db->querySingle('SELECT "title" FROM "fragen" WHERE "shortcode" = "'.base64_encode ($_GET["submitfeedback"]).'" '))."</b><br>wurde abgegeben!");
     
                } else {
                    print ("Dieses Goodbye ist leider abgelaufen.");
                }

           } else {
                print ("Shortcode fehlt/nicht vorhanden!");
            }
        }
    } elseif (isset ($_GET["about"])){ # Über
        ?>
        <h3>Über dieses Projekt</h3>
        <p>1-2-3 goodbye ist ein Diagnoseinstrument für das Ende einer jeder Unterrichtsstunde. Diese Methode wurde entwickelt von Jonas Müller (Seminar berufl. Schulen Karlsruhe, R13).</p>
        <p>Umsetzung als Webapp von David Heger im Februar 2023</p>
        <?php
    } elseif (isset ($_GET["impressum"])){ # Impressum
        if (file_exists("impressum-und-datenschutz.html")) {
				echo (file_get_contents('impressum-und-datenschutz.html'));
		} else {
			echo (file_get_contents('impressum-und-datenschutz.sample.html'));
		}
    } elseif (isset ($_GET["get"])){ # Lehreransicht
        $passwordneeded = 0;
        if ($_GET["get"] != ""){
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "shortcode" = "'.base64_encode ($_GET["get"]).'" ');
            if ($id != FALSE) {
                $pw = $db->querySingle('SELECT "password" FROM "fragen" WHERE "id" = "'.$id.'" ');
                $pw_get = "";
                if (isset ($_POST["pw"])) {
                $pw_get = $_POST["pw"];
                }
                if (isset ($_GET["pw"])) {
                    $pw_get = openssl_decrypt(base64_decode ($_GET["pw"]), $method, $secret_key, 0, $iv);
                }                

                if ($pw != FALSE && hash('sha256',$pw_get) == $pw) {

                    $result = $db->query('SELECT * FROM "antworten" WHERE "id" = "'.$id.'"');
                   

                    print ("<h1>1-2-3-Goodbye!</h1>");
                    print ("<h3>".base64_decode ($db->querySingle('SELECT "title" FROM "fragen" WHERE "id" = "'.$id.'" '))."</h3>");
                    $url = "http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'')."://";
                    $url .= $_SERVER['HTTP_HOST'];


                    $url_neu = $url."/".base64_decode($db->querySingle('SELECT "shortcode" FROM "fragen" WHERE "id" = "'.$id.'" '));
                    $url_neu_display = $url_neu;
                    $url_neu_display = str_replace ("http://","", $url_neu_display);
                    $url_neu_display = str_replace ("https://","", $url_neu_display);
                    print ("<p>Link zum Goodbye:</p>");
                    print ('<h3><a href="');
                    print ($url_neu);
                    print ('">'.$url_neu_display.'</a></h3>');
                    print ("<br>");
                    print ('<img class="qr" src="'.$url."?qr=".base64_encode ($url_neu).'">');
                    print ("<br>Bisherige Abgaben:");
                    $liveupdateurl = $url."/"."?realtime=".base64_decode ($db->querySingle('SELECT "token" FROM "fragen" WHERE "id" = "'.$id.'" '));
                    ?>
                    <iframe frameborder="no" scrolling="no" src="<?php print ($liveupdateurl); ?>" title=""></iframe><br>
                    <?php
                    print ("<a href='".$url."/createpdf.php?pdf=".base64_decode ($db->querySingle('SELECT "token" FROM "fragen" WHERE "id" = "'.$id.'" '))."'>Ergebnisse</a>");
                    print ("<br>");
                    print ("<br>");

                    print ("<small>Goodbye erstellt am ");
                    print (date('d.m.Y H:i', $db->querySingle('SELECT "time" FROM "fragen" WHERE "id" = "'.$id.'" ')));
                    print ("<br>");
                    print ("<a href='".$url."/index.php?del=".base64_decode ($db->querySingle('SELECT "token" FROM "fragen" WHERE "id" = "'.$id.'" '))."'>Löschen</a>");
                    print ("<br>");
                    print ("<br>");

                    $selector = $db->querySingle('SELECT "ablauf" FROM "fragen" WHERE "id" = "'.$id.'" ');
                    if ($selector != "" or $db->querySingle('SELECT "email" FROM "fragen" WHERE "id" = "'.$id.'" ') != "" or $pw_get != "") {
                        print ("<a href='");
                        print ($url."/?mail=".$db->querySingle('SELECT "email" FROM "fragen" WHERE "id" = "'.$id.'" '));
                        print ("&pw=".base64_encode (openssl_encrypt($pw_get, $method, $secret_key, 0, $iv)));
                        
                        if ($selector != "") {
                            print ("&selector=".$selector);
                        }
                        print ("'>Link mit eigenen Einstellungen</a></small>");
                    }




                } else {
                    $passwordneeded = 1;
                }
                if ($passwordneeded > 0) {
                    print ("Das Goodbye mit dem Titel <b>".base64_decode ($db->querySingle('SELECT "title" FROM "fragen" WHERE "id" = "'.$id.'" '))."</b> ist passwortgeschützt.");
                    ?>
                    <form action="<?php echo ($url)."/?get=".$_GET["get"] ?>"  method="post">
                    <input class="text" type="password" id="pw" name="pw">
                    <input class="hide" type="text" id="get" name="get" value="<?php echo ($_GET["get"]); ?>">
                        <input class="enter" type="submit" value="Goodbye erstellen">
                    </form>
                <?php
                }

            } else {
                print ($_GET["get"]);
                print ("<br>Shortcode fehlt/nicht vorhanden! (a)");
            }            
        } else {
            print ("Shortcode fehlt/nicht vorhanden!");
        }
    } elseif (isset ($_GET["del"])){    # Löschanforderung
        if ($_GET["del"] != ""){
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["del"]).'" ');
            print ($id);
            print ("<br>");
            $db->exec('DELETE FROM "fragen" WHERE ("id" = "'.$id.'")');
            $db->exec('DELETE FROM "antworten" WHERE ("id" = "'.$id.'")');
            print ("Gelöscht");
        } else {
            print ("Shortcode fehlt/nicht vorhanden!");
        }
    } 
    else {     # Kein Paramter gesetzt -> Neue Form erstellen
    ?>
    <script>
function toggle() {

  var element=document.getElementById('createemail');
  var element2=document.getElementById('createemail-text');
  var element3=document.getElementById('beschreibung-mail');
   val = document.getElementById('ablaufoption').value;
  if ( val!='Nie' ) {
    element.style.display='';
    element2.style.display='';
    element3.style.display='';
  } else {
    element.style.display='none';
    element2.style.display='none';
    element3.style.display='none';
  }
}
document.addEventListener('DOMContentLoaded', toggle, false);
</script>
        <h1>1-2-3-Goodbye!</h1>
        <form action="index.php?createform"  method="post" onload="toggle()">
            <p>Erstelle jetzt ein neues Goodbye.</p>
            <h2>TITEL DES GOODBYES</h2>
            <input required class="text" type="text" id="titel" name="titel">
            <br><br>
            <h2>PASSWORT</h2>
            <div class="beschreibungstext">Angeben, falls die Ergebnisse geschützt werden sollen</div>
            <input class="text" placeholder="" type="password" id="password" name="password" value="<?php if (isset ($_GET["pw"])) { echo (openssl_decrypt(base64_decode ($_GET["pw"]), $method, $secret_key, 0, $iv)); } ?>">
            <br><br>
            <h2>GOOBYE LÄUFT AB</h2>
            <select id="ablaufoption" name="ablauf" onchange="toggle();">
            <option class="option">Nie</option>
            <option <?php if (isset($_GET["selector"])){if ($_GET["selector"]=="-5"){print ("selected");}} ?> class="option">5 Minuten nach erster Antwort</option>
            <option <?php if (isset($_GET["selector"])){if ($_GET["selector"]=="-30"){print ("selected");}} ?> class="option">30 Minuten nach erster Antwort</option>
            <option <?php if (isset($_GET["selector"])){if ($_GET["selector"]=="-90"){print ("selected");}} ?> class="option">90 Minuten nach erster Antwort</option>
            </select>
            <br><br>
            <h2 id="createemail-text">ERGEBNISSE MAILEN AN</h2>
            <div id="beschreibung-mail" class="beschreibungstext">Angeben, um die Ergebnisse des Goodbyes nach Ablauf automatisch per Mail zu erhalten</div>
            <input class="text" type="email" id="createemail" name="email" placeholder="" value="<?php if (isset($_GET["mail"])){echo(base64_decode($_GET["mail"]));} ?>">
            <input class="carwash" autocomplete="off" type="text" id="name" name="name" placeholder="Your name here">
            <input class="enter" type="submit" value="Goodbye erstellen">
        </form>
        <br><br>
                    
                    <small><small><a href="index.php?about">Über</a> - <a href="index.php?impressum">Impressum & Datenschutz</a></small></small>
                    <small><small><br><?php print ($version);?></small></small>
    <?php
    }
    ?>
    <br><br>
    </div>

</body>
</html>