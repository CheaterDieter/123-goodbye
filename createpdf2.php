<?php
$db = new SQLite3("data/priv/database.sqlite");
$db->busyTimeout(5000);
require('fpdf.php');

function GetMultiCellHeight($w, $txt, $pdf)
{
    $height = 10;
    $strlen = strlen($txt);
    $wdth = 0;
    for ($i = 0; $i <= $strlen; $i++) {
        $char = substr($txt, $i, 1);
        $wdth += $pdf->GetStringWidth($char);
        if($char == "\n"){
            $height = $height+10;
            $wdth = 0;
        }
        if($wdth >= $w){
            $height = $height+10;
            $wdth = 0;
        }
    }
    return $height;
}

if (isset ($_GET["pdf"])){    # Löschanforderung
    if ($_GET["pdf"] != ""){

        
        class PDF extends FPDF
        {
        // Page header
        function Header()
        {
            $db = new SQLite3("data/priv/database.sqlite");
            $db->busyTimeout(5000);
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["pdf"]).'" ');

            // Arial bold 15
            $this->SetFont('Arial','B',15);
            $this->SetDrawColor(0,0,0);
            // Move to the right
            $this->Cell(80);
            // Title
            $this->Cell(30,10,base64_decode ($db->querySingle('SELECT "title" FROM "fragen" WHERE "id" = "'.$id.'" ')),1,0,'C');
            // Line break
            $this->Ln(20);
        }

        // Page footer
        function Footer()
        {
            $db = new SQLite3("data/priv/database.sqlite");
            $db->busyTimeout(5000);
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["pdf"]).'" ');

            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            // Arial italic 8
            $this->SetFont('Arial','I',8);
            // Page number
            $this->Cell(0,10,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
        }
        // Colored table
        function FancyTable()
        {
            $db = new SQLite3("data/priv/database.sqlite");
            $db->busyTimeout(5000);
            $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["pdf"]).'" ');

            // Colors, line width and bold font
            $this->SetFillColor(255,0,0);
            $this->SetTextColor(255);
            $this->SetDrawColor(128,0,0);
            $this->SetLineWidth(.3);
            $this->SetFont('','B',12);
            // Header

            $w = array(60, 60, 60);
            $this->Cell($w[0],7,"Nicht Verstanden",1,0,'C',true);
            $this->Cell($w[1],7,"Mehr erfahren",1,0,'C',true);
            $this->Cell($w[2],7,"Gelernt",1,0,'C',true);
            $this->Ln();
            // Color and font restoration
            $this->SetFillColor(224,235,255);
            $this->SetTextColor(0);
            $this->SetFont('');
            // Data
            $fill = false;
            $result = $db->query('SELECT * FROM "antworten" WHERE "id" = "'.$id.'"');
            while ($row = $result->fetchArray())
            {
                $time = date('d.m.Y H:m', $row['time']);

                $nicht_verstanden = base64_decode ($row['nicht_verstanden']);
                $mehr_erfahren1 = base64_decode ($row['mehr_erfahren1']);
                $mehr_erfahren2 = base64_decode ($row['mehr_erfahren2']);
                $gelernt1 = base64_decode ($row['gelernt1']);
                $gelernt2 = base64_decode ($row['gelernt2']);
                $gelernt3 = base64_decode ($row['gelernt3']);

                $h = 10;
                $x = $this->x;
                $y = $this->y;
                $push_right = 0;
                $h = GetMultiCellHeight ($w[0], $nicht_verstanden, $this);
                
                $this->Multicell($w[0],$h,$nicht_verstanden, "LR", "L", $fill);
                
                $push_right += $w[0];
                $this->SetXY($x + $push_right, $y);

                $this->Multicell($w[1],$h,$mehr_erfahren1, "LR", "L", $fill);

                $push_right += $w[1];
                $this->SetXY($x + $push_right, $y);

                $this->Multicell($w[2],$h,$gelernt1, "LR", "L", $fill); 
                
                /*




                $this->Cell($w[1],6,$mehr_erfahren1,'LR',0,'L',$fill);
                $this->Cell($w[1],6,$gelernt1,'LR',0,'L',$fill);
                $this->Ln();
                $this->Cell($w[0],6,"",'LR',0,'L',$fill);
                $this->Cell($w[1],6,$mehr_erfahren2,'LR',0,'L',$fill);
                $this->Cell($w[1],6,$gelernt2,'LR',0,'L',$fill);
                $this->Ln();
                $this->Cell($w[0],6,"",'LR',0,'L',$fill);
                $this->Cell($w[1],6,"",'LR',0,'L',$fill);
                $this->Cell($w[1],6,$gelernt3,'LR',0,'L',$fill);
                */

                $fill = !$fill;
            }
            /*$this->Cell(array_sum($w),0,'','T');*/


          

            // Closing line
            
        }
        }

        $id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["pdf"]).'" ');
            
        $pdf=new PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->FancyTable();
        $pdf->Output();
            
    } else {
        print ("Shortcode fehlt/nicht vorhanden!");
    }
}
?>