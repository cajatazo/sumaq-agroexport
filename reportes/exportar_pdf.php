<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

// Cargar la librería TCPDF
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

// Crear nuevo documento PDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('Sumaq Agroexport');
$pdf->SetAuthor('Sumaq Agroexport');
$pdf->SetTitle('Reporte de Exportaciones');
$pdf->SetSubject('Reporte de Exportaciones');

// Eliminar header y footer por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Agregar página
$pdf->AddPage();

// Configurar fuente
$pdf->SetFont('helvetica', 'B', 16);

// Título del reporte
$pdf->Cell(0, 10, 'REPORTE DE EXPORTACIONES - SUMAQ AGROEXPORT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Ln(5);

// Obtener datos con los mismos filtros que en el reporte
$filtroProducto = $_GET['producto'] ?? '';
$filtroDestino = $_GET['destino'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';
$filtroAltaExportacion = isset($_GET['alta_exportacion']) ? true : false;
$filtroDestinoEspecial = isset($_GET['destino_especial']) ? true : false;

$exportaciones = obtenerExportaciones();
$lotesArray = []; // Renombramos la variable para evitar confusión
$totalAcumulado = 0;
$contadorAltaExportacion = 0;
$contadorDestinosEspeciales = 0;

foreach ($exportaciones as $exportacion) {
    // Aplicar filtros
    if ($filtroProducto && $exportacion['id_producto'] !== $filtroProducto) continue;
    if ($filtroDestino && $exportacion['id_destino'] !== $filtroDestino) continue;
    if ($filtroFechaInicio && $exportacion['fecha_exportacion'] < $filtroFechaInicio) continue;
    if ($filtroFechaFin && $exportacion['fecha_exportacion'] > $filtroFechaFin) continue;
    
    $producto = obtenerNombreProducto($exportacion['id_producto']);
    $destino = obtenerNombreDestino($exportacion['id_destino']);
    $esDestinoEspecial = esDestinoEspecial($exportacion['id_destino']);
    
    if ($filtroAltaExportacion && $exportacion['cantidad'] <= 1000) continue;
    if ($filtroDestinoEspecial && !$esDestinoEspecial) continue;
    
    $valorTotal = $exportacion['cantidad'] * $exportacion['precio'];
    
    $lotesArray[] = [
        'id' => substr($exportacion['id'], 0, 8),
        'producto' => $producto,
        'cantidad' => number_format($exportacion['cantidad'], 2),
        'precio' => number_format($exportacion['precio'], 2),
        'destino' => $destino,
        'fecha' => date('d/m/Y', strtotime($exportacion['fecha_exportacion'])),
        'valor_total' => number_format($valorTotal, 2),
        'tipo' => $exportacion['cantidad'] > 1000 ? 'Alta Exportación' : ($esDestinoEspecial ? 'Destino Especial' : 'Normal')
    ];
    
    $totalAcumulado += $valorTotal;
    
    if ($exportacion['cantidad'] > 1000) {
        $contadorAltaExportacion++;
    }
    
    if ($esDestinoEspecial) {
        $contadorDestinosEspeciales++;
    }
}

// Resumen estadístico
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Resumen Estadístico', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(60, 10, 'Total Exportado:', 1, 0);
$pdf->Cell(30, 10, 'S/. ' . number_format($totalAcumulado, 2), 1, 1, 'R');

$pdf->Cell(60, 10, 'Lotes Registrados:', 1, 0);
$pdf->Cell(30, 10, count($lotesArray), 1, 1, 'R');

$pdf->Cell(60, 10, 'Alta Exportación (>1000 kg):', 1, 0);
$pdf->Cell(30, 10, $contadorAltaExportacion, 1, 1, 'R');

$pdf->Cell(60, 10, 'Destinos Especiales:', 1, 0);
$pdf->Cell(30, 10, $contadorDestinosEspeciales, 1, 1, 'R');

$pdf->Ln(10);

// Estadísticas por Producto
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Exportación por Producto', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Encabezados de la tabla de productos
$headerProductos = ['Producto', 'Cantidad Total (kg)', 'Valor Total (S/.)', 'Lotes'];
$widthsProductos = [80, 40, 40, 30];

// Colores de fondo para el encabezado
$pdf->SetFillColor(44, 124, 63);
$pdf->SetTextColor(255);

// Imprimir encabezados de productos
for ($i = 0; $i < count($headerProductos); $i++) {
    $pdf->Cell($widthsProductos[$i], 7, $headerProductos[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Restaurar colores
$pdf->SetTextColor(0);
$pdf->SetFillColor(255);

// Alternar colores de fila
$fill = false;

// Imprimir datos de productos
$productos = obtenerProductos();
foreach ($productos as $producto) {
    $cantidadTotal = 0;
    $valorTotal = 0;
    $lotes = 0;
    
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_producto'] === $producto['id']) {
            $cantidadTotal += $exportacion['cantidad'];
            $valorTotal += ($exportacion['cantidad'] * $exportacion['precio']);
            $lotes++;
        }
    }
    
    if ($lotes > 0) {
        $pdf->Cell($widthsProductos[0], 6, $producto['nombre'], 'LR', 0, 'L', $fill);
        $pdf->Cell($widthsProductos[1], 6, number_format($cantidadTotal, 2), 'LR', 0, 'R', $fill);
        $pdf->Cell($widthsProductos[2], 6, number_format($valorTotal, 2), 'LR', 0, 'R', $fill);
        $pdf->Cell($widthsProductos[3], 6, $lotes, 'LR', 1, 'C', $fill);
        $fill = !$fill;
    }
}

// Cerrar la tabla de productos
$pdf->Cell(array_sum($widthsProductos), 0, '', 'T');
$pdf->Ln(10);

// Estadísticas por Destino
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Exportación por Destino', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Encabezados de la tabla de destinos
$headerDestinos = ['Destino', 'Cantidad Total (kg)', 'Valor Total (S/.)', 'Lotes'];
$widthsDestinos = [80, 40, 40, 30];

// Colores de fondo para el encabezado
$pdf->SetFillColor(44, 124, 63);
$pdf->SetTextColor(255);

// Imprimir encabezados de destinos
for ($i = 0; $i < count($headerDestinos); $i++) {
    $pdf->Cell($widthsDestinos[$i], 7, $headerDestinos[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Restaurar colores
$pdf->SetTextColor(0);
$pdf->SetFillColor(255);

// Alternar colores de fila
$fill = false;

// Imprimir datos de destinos
$destinos = obtenerDestinos();
foreach ($destinos as $destino) {
    $cantidadTotal = 0;
    $valorTotal = 0;
    $lotes = 0;
    
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_destino'] === $destino['id']) {
            $cantidadTotal += $exportacion['cantidad'];
            $valorTotal += ($exportacion['cantidad'] * $exportacion['precio']);
            $lotes++;
        }
    }
    
    if ($lotes > 0) {
        $pdf->Cell($widthsDestinos[0], 6, $destino['nombre'], 'LR', 0, 'L', $fill);
        $pdf->Cell($widthsDestinos[1], 6, number_format($cantidadTotal, 2), 'LR', 0, 'R', $fill);
        $pdf->Cell($widthsDestinos[2], 6, number_format($valorTotal, 2), 'LR', 0, 'R', $fill);
        $pdf->Cell($widthsDestinos[3], 6, $lotes, 'LR', 1, 'C', $fill);
        $fill = !$fill;
    }
}

// Cerrar la tabla de destinos
$pdf->Cell(array_sum($widthsDestinos), 0, '', 'T');
$pdf->Ln(10);

// Tabla de exportaciones
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Detalle de Exportaciones', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Encabezados de la tabla
$header = ['ID', 'Producto', 'Cantidad (kg)', 'Precio (S/.)', 'Destino', 'Fecha', 'Valor Total (S/.)', 'Tipo'];
$widths = [15, 40, 25, 25, 40, 25, 30, 30];

// Colores de fondo para el encabezado
$pdf->SetFillColor(44, 124, 63);
$pdf->SetTextColor(255);

// Imprimir encabezados
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Restaurar colores
$pdf->SetTextColor(0);
$pdf->SetFillColor(255);

// Ordenar lotes por fecha (más reciente primero)
usort($lotesArray, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Alternar colores de fila
$fill = false;

// Imprimir datos
foreach ($lotesArray as $lote) {
    $pdf->Cell($widths[0], 6, $lote['id'], 'LR', 0, 'C', $fill);
    $pdf->Cell($widths[1], 6, $lote['producto'], 'LR', 0, 'L', $fill);
    $pdf->Cell($widths[2], 6, $lote['cantidad'], 'LR', 0, 'R', $fill);
    $pdf->Cell($widths[3], 6, $lote['precio'], 'LR', 0, 'R', $fill);
    $pdf->Cell($widths[4], 6, $lote['destino'], 'LR', 0, 'L', $fill);
    $pdf->Cell($widths[5], 6, $lote['fecha'], 'LR', 0, 'C', $fill);
    $pdf->Cell($widths[6], 6, $lote['valor_total'], 'LR', 0, 'R', $fill);
    $pdf->Cell($widths[7], 6, $lote['tipo'], 'LR', 1, 'C', $fill);
    $fill = !$fill;
}

// Agregar totales
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($widths[0] + $widths[1], 6, 'TOTALES:', 'LR', 0, 'R', true);
$pdf->Cell($widths[2], 6, number_format(array_sum(array_column($lotesArray, 'cantidad')), 2), 'LR', 0, 'R', true);
$pdf->Cell($widths[3], 6, '', 'LR', 0, 'R', true);
$pdf->Cell($widths[4], 6, '', 'LR', 0, 'R', true);
$pdf->Cell($widths[5], 6, '', 'LR', 0, 'R', true);
$pdf->Cell($widths[6], 6, number_format(array_sum(array_column($lotesArray, 'valor_total')), 2), 'LR', 0, 'R', true);
$pdf->Cell($widths[7], 6, '', 'LR', 1, 'R', true);

// Cerrar la tabla
$pdf->Cell(array_sum($widths), 0, '', 'T');

// Salida del PDF
$pdf->Output('reporte_exportaciones_' . date('YmdHis') . '.pdf', 'D');
exit;
?>