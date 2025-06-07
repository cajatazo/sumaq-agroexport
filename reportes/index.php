<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

// Filtros
$filtroProducto = $_GET['producto'] ?? '';
$filtroDestino = $_GET['destino'] ?? '';
$filtroFechaInicio = $_GET['fecha_inicio'] ?? '';
$filtroFechaFin = $_GET['fecha_fin'] ?? '';
$filtroAltaExportacion = isset($_GET['alta_exportacion']) ? true : false;
$filtroDestinoEspecial = isset($_GET['destino_especial']) ? true : false;

// Obtener datos
$exportaciones = obtenerExportaciones();
$productos = obtenerProductos();
$destinos = obtenerDestinos();

// Procesar datos para el reporte
$lotes = [];
$totalAcumulado = 0;
$contadorAltaExportacion = 0;
$contadorDestinosEspeciales = 0;
$contadorTotalLotes = 0;

// Estadísticas por producto
$estadisticasProductos = [];
foreach ($productos as $producto) {
    $estadisticasProductos[$producto['id']] = [
        'nombre' => $producto['nombre'],
        'cantidad_total' => 0,
        'valor_total' => 0,
        'lotes' => 0
    ];
}

// Estadísticas por destino
$estadisticasDestinos = [];
foreach ($destinos as $destino) {
    $estadisticasDestinos[$destino['id']] = [
        'nombre' => $destino['nombre'],
        'cantidad_total' => 0,
        'valor_total' => 0,
        'lotes' => 0
    ];
}

// Filtrar y procesar exportaciones
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
    
    // Calcular valores
    $valorTotal = $exportacion['cantidad'] * $exportacion['precio'];
    
    // Agregar al listado de lotes
    $lotes[] = [
        'id' => $exportacion['id'],
        'producto' => $producto,
        'id_producto' => $exportacion['id_producto'],
        'cantidad' => $exportacion['cantidad'],
        'precio' => $exportacion['precio'],
        'destino' => $destino,
        'id_destino' => $exportacion['id_destino'],
        'fecha_exportacion' => $exportacion['fecha_exportacion'],
        'valor_total' => $valorTotal,
        'es_alta_exportacion' => $exportacion['cantidad'] > 1000,
        'es_destino_especial' => $esDestinoEspecial
    ];
    
    // Actualizar totales
    $totalAcumulado += $valorTotal;
    $contadorTotalLotes++;
    
    if ($exportacion['cantidad'] > 1000) {
        $contadorAltaExportacion++;
    }
    
    if ($esDestinoEspecial) {
        $contadorDestinosEspeciales++;
    }
    
    // Actualizar estadísticas por producto
    if (isset($estadisticasProductos[$exportacion['id_producto']])) {
        $estadisticasProductos[$exportacion['id_producto']]['cantidad_total'] += $exportacion['cantidad'];
        $estadisticasProductos[$exportacion['id_producto']]['valor_total'] += $valorTotal;
        $estadisticasProductos[$exportacion['id_producto']]['lotes']++;
    }
    
    // Actualizar estadísticas por destino
    if (isset($estadisticasDestinos[$exportacion['id_destino']])) {
        $estadisticasDestinos[$exportacion['id_destino']]['cantidad_total'] += $exportacion['cantidad'];
        $estadisticasDestinos[$exportacion['id_destino']]['valor_total'] += $valorTotal;
        $estadisticasDestinos[$exportacion['id_destino']]['lotes']++;
    }
}

// Ordenar lotes por fecha (más reciente primero)
usort($lotes, function($a, $b) {
    return strtotime($b['fecha_exportacion']) - strtotime($a['fecha_exportacion']);
});

$titulo = 'Reporte de Exportaciones';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reporte de Exportaciones
                    </h6>
                    <div class="export-btn-group">
                        <a href="/sumaq-agroexport/reportes/exportar_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-file-excel"></i> Excel
                        </a>
                        <a href="/sumaq-agroexport/reportes/exportar_pdf.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm">
                            <i class="bi bi-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="producto" class="form-label">Producto</label>
                                <select class="form-select" id="producto" name="producto">
                                    <option value="">Todos los productos</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php if ($producto['activo']): ?>
                                            <option value="<?= $producto['id'] ?>" <?= $filtroProducto == $producto['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($producto['nombre']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="destino" class="form-label">Destino</label>
                                <select class="form-select" id="destino" name="destino">
                                    <option value="">Todos los destinos</option>
                                    <?php foreach ($destinos as $destino): ?>
                                        <?php if ($destino['activo']): ?>
                                            <option value="<?= $destino['id'] ?>" <?= $filtroDestino == $destino['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($destino['nombre']) ?>
                                                <?= $destino['especial'] ? ' (Especial)' : '' ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                       value="<?= htmlspecialchars($filtroFechaInicio) ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                       value="<?= htmlspecialchars($filtroFechaFin) ?>">
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-funnel"></i> Filtrar
                                </button>
                                <a href="/sumaq-agroexport/reportes/" class="btn btn-secondary">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="alta_exportacion" name="alta_exportacion" <?= $filtroAltaExportacion ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="alta_exportacion">Solo alta exportación (>1000 kg)</label>
                                </div>
                                
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="destino_especial" name="destino_especial" <?= $filtroDestinoEspecial ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="destino_especial">Solo destinos especiales</label>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Gráficos de Estadísticas -->
                    <div class="row">
                        <!-- Gráfico por Producto -->
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="bi bi-basket"></i> Exportación por Producto
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoProductos"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico por Destino -->
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="bi bi-globe-americas"></i> Exportación por Destino
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="graficoDestinos"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumen Estadístico -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Total Exportado</h5>
                                    <p class="card-text fs-4"><?= formatoMoneda($totalAcumulado) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Lotes Registrados</h5>
                                    <p class="card-text fs-4"><?= $contadorTotalLotes ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card text-white bg-warning mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Alta Exportación</h5>
                                    <p class="card-text fs-4"><?= $contadorAltaExportacion ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Destinos Especiales</h5>
                                    <p class="card-text fs-4"><?= $contadorDestinosEspeciales ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de Exportaciones -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Cantidad (kg)</th>
                                    <th>Precio (S/.)</th>
                                    <th>Destino</th>
                                    <th>Fecha Exportación</th>
                                    <th>Valor Total (S/.)</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes as $lote): ?>
                                <tr class="<?= $lote['es_alta_exportacion'] ? 'table-success' : ($lote['es_destino_especial'] ? 'table-info' : '') ?>">
                                    <td><?= substr($lote['id'], 0, 8) ?></td>
                                    <td><?= htmlspecialchars($lote['producto']) ?></td>
                                    <td><?= number_format($lote['cantidad'], 2) ?></td>
                                    <td><?= number_format($lote['precio'], 2) ?></td>
                                    <td><?= htmlspecialchars($lote['destino']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($lote['fecha_exportacion'])) ?></td>
                                    <td><?= number_format($lote['valor_total'], 2) ?></td>
                                    <td>
                                        <?php if ($lote['es_alta_exportacion']): ?>
                                            <span class="badge" style="background-color:rgb(254, 0, 0);">Alta Exportación</span>
                                        <?php elseif ($lote['es_destino_especial']): ?>
                                            <span class="badge bg-warning">Destino Especial</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/sumaq-agroexport/exportaciones/editar.php?id=<?= $lote['id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <form action="/sumaq-agroexport/exportaciones/eliminar.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $lote['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta exportación?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agregar Chart.js antes del cierre del body -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos para el gráfico de productos
const datosProductos = {
        labels: [
        <?php foreach ($estadisticasProductos as $estadistica): ?>
            <?php if ($estadistica['lotes'] > 0): ?>
                '<?= htmlspecialchars($estadistica['nombre']) ?>',
            <?php endif; ?>
        <?php endforeach; ?>
    ],
    datasets: [{
        label: 'Cantidad Total (kg)',
        data: [
            <?php foreach ($estadisticasProductos as $estadistica): ?>
                <?php if ($estadistica['lotes'] > 0): ?>
                    <?= $estadistica['cantidad_total'] ?>,
                <?php endif; ?>
            <?php endforeach; ?>
        ],
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
    }]
};

// Datos para el gráfico de destinos
const datosDestinos = {
    labels: [
        <?php foreach ($estadisticasDestinos as $estadistica): ?>
            <?php if ($estadistica['lotes'] > 0): ?>
                '<?= htmlspecialchars($estadistica['nombre']) ?>',
            <?php endif; ?>
            <?php endforeach; ?>
        ],
        datasets: [{
        label: 'Cantidad Total (kg)',
            data: [
            <?php foreach ($estadisticasDestinos as $estadistica): ?>
                <?php if ($estadistica['lotes'] > 0): ?>
                    <?= $estadistica['cantidad_total'] ?>,
                <?php endif; ?>
                <?php endforeach; ?>
            ],
        backgroundColor: 'rgba(255, 99, 132, 0.5)',
        borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
};

// Configuración común para los gráficos
const config = {
    type: 'bar',
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Cantidad Exportada (kg)'
            }
        },
        scales: {
            y: {
                beginAtZero: true
                    }
                }
            }
};

// Crear gráficos
new Chart(
    document.getElementById('graficoProductos'),
    {
        ...config,
        data: datosProductos
    }
);

new Chart(
    document.getElementById('graficoDestinos'),
    {
        ...config,
        data: datosDestinos
    }
);
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>