<?php
require('fpdf/fpdf.php');

// Parámetro idFactura enviado por GET
$idFactura = isset($_GET['idFactura']) ? intval($_GET['idFactura']) : 0;

if ($idFactura <= 0) {
    die('Invalid Factura ID');
}

// Llamada al API para obtener la factura
$url = "factura.controller.php?op=uno";
$data = array("idFactura" => $idFactura);
$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ),
);
$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === FALSE) {
    die('Error fetching data');
}

$factura = json_decode($response, true);

if (isset($factura['error'])) {
    die($factura['error']);
}

// Creación del PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Título
$pdf->Cell(0, 10, 'Factura', 0, 1, 'C');

// Información del Cliente
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Cliente: ' . $factura['Cliente_Nombre'], 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Dirección: ' . $factura['Cliente_Direccion'], 0, 1);
$pdf->Cell(0, 10, 'Teléfono: ' . $factura['Cliente_Telefono'], 0, 1);
$pdf->Cell(0, 10, 'Fecha: ' . $factura['Fecha'], 0, 1);

// Encabezado de la tabla
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, 'Descripción', 1);
$pdf->Cell(30, 10, 'Cantidad', 1);
$pdf->Cell(30, 10, 'Precio Unitario', 1);
$pdf->Cell(30, 10, 'Total', 1);
$pdf->Ln();

// Contenido de la tabla
$pdf->SetFont('Arial', '', 12);
foreach ($factura['productos'] as $producto) {
    $pdf->Cell(60, 10, $producto['descripcion'], 1);
    $pdf->Cell(30, 10, $producto['cantidad'], 1);
    $pdf->Cell(30, 10, number_format($producto['precio_unitario'], 2), 1);
    $pdf->Cell(30, 10, number_format($producto['cantidad'] * $producto['precio_unitario'], 2), 1);
    $pdf->Ln();
}

// Total
$pdf->Cell(120, 10, 'Total:', 1);
$pdf->Cell(30, 10, number_format(array_sum(array_column($factura['productos'], 'cantidad') * array_column($factura['productos'], 'precio_unitario')), 2), 1);

// Salida del PDF
$pdf->Output();
