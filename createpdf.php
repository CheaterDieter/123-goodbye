<?php

require('cellpdf.php');

$db = new SQLite3("data/priv/database.sqlite");
$db->busyTimeout(5000);
$id = $db->querySingle('SELECT "id" FROM "fragen" WHERE "token" = "'.base64_encode ($_GET["pdf"]).'" ');
$result = $db->query('SELECT * FROM "antworten" WHERE "id" = "'.$id.'"');
$i = 0;
while ($row = $result->fetchArray())
{
    $i++;
}

$h = 10;
$pdf=new CellPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);
$pdf->Cell(100,$h,base64_decode ($db->querySingle('SELECT "title" FROM "fragen" WHERE "id" = "'.$id.'" ')));
$pdf->Ln();


$pdf->Cell(100,$h,"Erstellt am ".strval(date('d.m.Y H:i', $db->querySingle('SELECT "time" FROM "fragen" WHERE "id" = "'.$id.'" ')).", Abgaben: ".strval($i)));
$pdf->Ln();
$pdf->SetFillColor(37,40,80);
$pdf->SetTextColor(255,255,255);
$pdf->Cell(63,$h,"Nicht verstanden",'B',0,'L', TRUE);
$pdf->Cell(63,$h,'Mehr erfahren','B',0,'L', TRUE);
$pdf->Cell(63,$h,"Gelernt",'B',0,'L', TRUE);
$pdf->Ln();
$h = 5;
$pdf->SetFont('Arial','',10);
$fill = false;
$pdf->SetFillColor(224,235,255);
$pdf->SetTextColor(0,0,0);
while ($row = $result->fetchArray())
{
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['nicht_verstanden'])),'',0,'L', $fill);
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['mehr_erfahren1'])),'',0,'L', $fill);
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['gelernt1'])),'',0,'L', $fill);
    $pdf->Ln();
    $pdf->Cell(63,$h,base64_decode (""),'',0,'L', $fill);
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['mehr_erfahren2'])),'',0,'L', $fill);
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['gelernt2'])),'',0,'L', $fill);
    $pdf->Ln();
    $pdf->Cell(63,$h,base64_decode (""),'',0,'L', $fill);
    $pdf->Cell(63,$h,base64_decode (""),'',0,'L', $fill);
    $pdf->Cell(63,$h,utf8_decode(base64_decode ($row['gelernt3'])),'',0,'L', $fill);
    $pdf->Ln();    
    $fill = !$fill;
}


$pdf->Ln();





if (isset($filename)){
    $pdf->Output($filename,'F');
} else {
    $pdf->Output();
}
?>