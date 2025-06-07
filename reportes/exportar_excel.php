<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_exportaciones_' . date('YmdHis') . '.xls"');
header('Cache-Control: max-age=0');

// Obtener datos con los mismos filtros que en el reporte
$filtroProducto = $_GET['producto'] ?? '';
$filtroDestino = $_GET['destino'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';
$filtroAltaExportacion = isset($_GET['alta_exportacion']) ? true : false;
$filtroDestinoEspecial = isset($_GET['destino_especial']) ? true : false;

$exportaciones = obtenerExportaciones();
$lotes = [];
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
    
    $lotes[] = [
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

// Crear contenido Excel
echo '<table border="1">';
echo '<tr><th colspan="8" style="text-align:center;background:#2c7c3f;color:white;">REPORTE DE EXPORTACIONES - SUMAQ AGROEXPORT</th></tr>';
echo '<tr><th colspan="8" style="text-align:center;">Generado el ' . date('d/m/Y H:i:s') . '</th></tr>';
echo '<tr><td colspan="8"></td></tr>';

// Resumen Estadístico
echo '<tr><th colspan="8" style="text-align:center;background:#f2f2f2;">RESUMEN ESTADÍSTICO</th></tr>';
echo '<tr><td colspan="4">Total Exportado:</td><td colspan="4">S/. ' . number_format($totalAcumulado, 2) . '</td></tr>';
echo '<tr><td colspan="4">Lotes Registrados:</td><td colspan="4">' . count($lotes) . '</td></tr>';
echo '<tr><td colspan="4">Alta Exportación (>1000 kg):</td><td colspan="4">' . $contadorAltaExportacion . '</td></tr>';
echo '<tr><td colspan="4">Destinos Especiales:</td><td colspan="4">' . $contadorDestinosEspeciales . '</td></tr>';
echo '<tr><td colspan="8"></td></tr>';

// Estadísticas por Producto
echo '<tr><th colspan="8" style="text-align:center;background:#f2f2f2;">EXPORTACIÓN POR PRODUCTO</th></tr>';
echo '<tr style="background:#f2f2f2;font-weight:bold;"><th>Producto</th><th>Cantidad Total (kg)</th><th>Valor Total (S/.)</th><th>Lotes</th></tr>';

$productos = obtenerProductos();
foreach ($productos as $producto) {
    $cantidadTotal = 0;
    $valorTotal = 0;
    $lotesProducto = 0;
    
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_producto'] === $producto['id']) {
            $cantidadTotal += $exportacion['cantidad'];
            $valorTotal += ($exportacion['cantidad'] * $exportacion['precio']);
            $lotesProducto++;
        }
    }
    
    if ($lotesProducto > 0) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($producto['nombre']) . '</td>';
        echo '<td style="text-align:right">' . number_format($cantidadTotal, 2) . '</td>';
        echo '<td style="text-align:right">' . number_format($valorTotal, 2) . '</td>';
        echo '<td style="text-align:center">' . $lotesProducto . '</td>';
echo '</tr>';
    }
}

echo '<tr><td colspan="8"></td></tr>';

// Estadísticas por Destino
echo '<tr><th colspan="8" style="text-align:center;background:#f2f2f2;">EXPORTACIÓN POR DESTINO</th></tr>';
echo '<tr style="background:#f2f2f2;font-weight:bold;"><th>Destino</th><th>Cantidad Total (kg)</th><th>Valor Total (S/.)</th><th>Lotes</th></tr>';

$destinos = obtenerDestinos();
foreach ($destinos as $destino) {
    $cantidadTotal = 0;
    $valorTotal = 0;
    $lotesDestino = 0;
    
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_destino'] === $destino['id']) {
            $cantidadTotal += $exportacion['cantidad'];
            $valorTotal += ($exportacion['cantidad'] * $exportacion['precio']);
            $lotesDestino++;
        }
    }
    
    if ($lotesDestino > 0) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($destino['nombre']) . '</td>';
        echo '<td style="text-align:right">' . number_format($cantidadTotal, 2) . '</td>';
        echo '<td style="text-align:right">' . number_format($valorTotal, 2) . '</td>';
        echo '<td style="text-align:center">' . $lotesDestino . '</td>';
        echo '</tr>';
    }
}

echo '<tr><td colspan="8"></td></tr>';

// Tabla de Exportaciones
echo '<tr><th colspan="8" style="text-align:center;background:#f2f2f2;">DETALLE DE EXPORTACIONES</th></tr>';
echo '<tr style="background:#f2f2f2;font-weight:bold;"><th>ID</th><th>Producto</th><th>Cantidad (kg)</th><th>Precio (S/.)</th><th>Destino</th><th>Fecha</th><th>Valor Total (S/.)</th><th>Tipo</th></tr>';

// Ordenar lotes por fecha (más reciente primero)
if (!empty($lotes)) {
    usort($lotes, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
}

foreach ($lotes as $lote) {
    echo '<tr>';
    echo '<td style="text-align:center">' . $lote['id'] . '</td>';
    echo '<td>' . htmlspecialchars($lote['producto']) . '</td>';
    echo '<td style="text-align:right">' . $lote['cantidad'] . '</td>';
    echo '<td style="text-align:right">' . $lote['precio'] . '</td>';
    echo '<td>' . htmlspecialchars($lote['destino']) . '</td>';
    echo '<td style="text-align:center">' . $lote['fecha'] . '</td>';
    echo '<td style="text-align:right">' . $lote['valor_total'] . '</td>';
    echo '<td style="text-align:center">' . $lote['tipo'] . '</td>';
    echo '</tr>';
}

// Agregar totales al final
if (!empty($lotes)) {
    echo '<tr style="background:#f2f2f2;font-weight:bold;">';
    echo '<td colspan="2" style="text-align:right">TOTALES:</td>';
    echo '<td style="text-align:right">' . number_format(array_sum(array_map(function($lote) { return floatval(str_replace(',', '', $lote['cantidad'])); }, $lotes)), 2) . '</td>';
    echo '<td></td>';
    echo '<td></td>';
    echo '<td></td>';
    echo '<td style="text-align:right">' . number_format(array_sum(array_map(function($lote) { return floatval(str_replace(',', '', $lote['valor_total'])); }, $lotes)), 2) . '</td>';
    echo '<td></td>';
    echo '</tr>';
}

echo '</table>';
exit;