<?php
require_once __DIR__ . '/config/config.php';
verificarAutenticacion();

// Obtener estadísticas para el dashboard
$exportaciones = obtenerExportaciones();
$productos = obtenerProductos();
$destinos = obtenerDestinos();
$usuarios = obtenerUsuarios();

// Estadísticas generales
$totalExportaciones = count($exportaciones);
$totalProductos = count($productos);
$totalDestinos = count($destinos);
$totalUsuarios = count($usuarios);

// Últimas exportaciones (5 más recientes)
usort($exportaciones, function($a, $b) {
    return strtotime($b['fecha_exportacion']) - strtotime($a['fecha_exportacion']);
});
$ultimasExportaciones = array_slice($exportaciones, 0, 5);

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

// Calcular totales
$totalAcumulado = 0;
$contadorAltaExportacion = 0;
$contadorDestinosEspeciales = 0;

foreach ($exportaciones as $exportacion) {
    $valorTotal = $exportacion['cantidad'] * $exportacion['precio'];
    $totalAcumulado += $valorTotal;
    
    if ($exportacion['cantidad'] > 1000) {
        $contadorAltaExportacion++;
    }
    
    if (esDestinoEspecial($exportacion['id_destino'])) {
        $contadorDestinosEspeciales++;
    }
    
    // Estadísticas por producto
    if (isset($estadisticasProductos[$exportacion['id_producto']])) {
        $estadisticasProductos[$exportacion['id_producto']]['cantidad_total'] += $exportacion['cantidad'];
        $estadisticasProductos[$exportacion['id_producto']]['valor_total'] += $valorTotal;
        $estadisticasProductos[$exportacion['id_producto']]['lotes']++;
    }
    
    // Estadísticas por destino
    if (isset($estadisticasDestinos[$exportacion['id_destino']])) {
        $estadisticasDestinos[$exportacion['id_destino']]['cantidad_total'] += $exportacion['cantidad'];
        $estadisticasDestinos[$exportacion['id_destino']]['valor_total'] += $valorTotal;
        $estadisticasDestinos[$exportacion['id_destino']]['lotes']++;
    }
}

$titulo = 'Dashboard';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Estadísticas Rápidas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Exportaciones Registradas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalExportaciones ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Valor Total Exportado</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatoMoneda($totalAcumulado) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Alta Exportación</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $contadorAltaExportacion ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up-arrow fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Destinos Especiales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $contadorDestinosEspeciales ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Gráfico de Productos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-box-seam"></i> Exportaciones por Producto
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="productosChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de Destinos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-globe"></i> Distribución por Destino
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="destinosChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Productos y Destinos -->
    <div class="row">
        <!-- Top Productos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-trophy"></i> Top 5 Productos
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $topProductos = [];
                    foreach ($productos as $producto) {
                        $cantidadTotal = 0;
                        foreach ($exportaciones as $exportacion) {
                            if ($exportacion['id_producto'] === $producto['id']) {
                                $cantidadTotal += $exportacion['cantidad'];
                            }
                        }
                        if ($cantidadTotal > 0) {
                            $topProductos[] = [
                                'nombre' => $producto['nombre'],
                                'cantidad' => $cantidadTotal
                            ];
                        }
                    }
                    usort($topProductos, function($a, $b) {
                        return $b['cantidad'] - $a['cantidad'];
                    });
                    $topProductos = array_slice($topProductos, 0, 5);
                    ?>
                    <div class="list-group">
                        <?php foreach ($topProductos as $index => $producto): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-2">#<?= $index + 1 ?></span>
                                <?= htmlspecialchars($producto['nombre']) ?>
                            </div>
                            <span class="badge bg-success rounded-pill">
                                <?= number_format($producto['cantidad'], 2) ?> kg
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Destinos -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-trophy"></i> Top 5 Destinos
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $topDestinos = [];
                    foreach ($destinos as $destino) {
                        $cantidadTotal = 0;
                        foreach ($exportaciones as $exportacion) {
                            if ($exportacion['id_destino'] === $destino['id']) {
                                $cantidadTotal += $exportacion['cantidad'];
                            }
                        }
                        if ($cantidadTotal > 0) {
                            $topDestinos[] = [
                                'nombre' => $destino['nombre'],
                                'cantidad' => $cantidadTotal,
                                'es_especial' => esDestinoEspecial($destino['id'])
                            ];
                        }
                    }
                    usort($topDestinos, function($a, $b) {
                        return $b['cantidad'] - $a['cantidad'];
                    });
                    $topDestinos = array_slice($topDestinos, 0, 5);
                    ?>
                    <div class="list-group">
                        <?php foreach ($topDestinos as $index => $destino): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-2">#<?= $index + 1 ?></span>
                                <?= htmlspecialchars($destino['nombre']) ?>
                                <?php if ($destino['es_especial']): ?>
                                    <span class="badge bg-warning ms-2">Especial</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-success rounded-pill">
                                <?= number_format($destino['cantidad'], 2) ?> kg
                        </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Exportaciones -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Últimas Exportaciones Registradas</h6>
                    <a href="/sumaq-agroexport/exportaciones/registrar.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Nueva Exportación
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad (kg)</th>
                                    <th>Destino</th>
                                    <th>Fecha</th>
                                    <th>Valor Total (S/.)</th>
                                    <th>Tipo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimasExportaciones as $exportacion): ?>
                                <?php
                                    $producto = obtenerNombreProducto($exportacion['id_producto']);
                                    $destino = obtenerNombreDestino($exportacion['id_destino']);
                                    $valorTotal = $exportacion['cantidad'] * $exportacion['precio'];
                                    $esAltaExportacion = $exportacion['cantidad'] > 1000;
                                    $esDestinoEspecial = esDestinoEspecial($exportacion['id_destino']);
                                ?>
                                <tr class="<?= $esAltaExportacion ? 'table-success' : ($esDestinoEspecial ? 'table-info' : '') ?>">
                                    <td><?= htmlspecialchars($producto) ?></td>
                                    <td><?= number_format($exportacion['cantidad'], 2) ?></td>
                                    <td><?= htmlspecialchars($destino) ?></td>
                                    <td><?= date('d/m/Y', strtotime($exportacion['fecha_exportacion'])) ?></td>
                                    <td><?= number_format($valorTotal, 2) ?></td>
                                    <td>
                                        <?php if ($esAltaExportacion): ?>
                                            <span class="badge" style="background-color:rgb(254, 0, 0);">Alta Exportación</span>
                                        <?php elseif ($esDestinoEspecial): ?>
                                            <span class="badge bg-warning">Destino Especial</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/sumaq-agroexport/exportaciones/editar.php?id=<?= $exportacion['id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
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

<script>
// Preparar datos para los gráficos
const productosData = <?= json_encode(array_map(function($producto) use ($exportaciones) {
    $cantidadTotal = 0;
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_producto'] === $producto['id']) {
            $cantidadTotal += $exportacion['cantidad'];
        }
    }
    return [
        'nombre' => $producto['nombre'],
        'cantidad' => $cantidadTotal
    ];
}, $productos)) ?>;

const destinosData = <?= json_encode(array_map(function($destino) use ($exportaciones) {
    $cantidadTotal = 0;
    foreach ($exportaciones as $exportacion) {
        if ($exportacion['id_destino'] === $destino['id']) {
            $cantidadTotal += $exportacion['cantidad'];
        }
    }
    return [
        'nombre' => $destino['nombre'],
        'cantidad' => $cantidadTotal,
        'es_especial' => esDestinoEspecial($destino['id'])
    ];
}, $destinos)) ?>;

// Filtrar y ordenar datos
const productosFiltrados = productosData
    .filter(p => p.cantidad > 0)
    .sort((a, b) => b.cantidad - a.cantidad)
    .slice(0, 5);

const destinosFiltrados = destinosData
    .filter(d => d.cantidad > 0)
    .sort((a, b) => b.cantidad - a.cantidad)
    .slice(0, 5);

// Gráfico de productos
const productosCtx = document.getElementById('productosChart').getContext('2d');
const productosChart = new Chart(productosCtx, {
    type: 'bar',
    data: {
        labels: productosFiltrados.map(p => p.nombre),
        datasets: [{
            label: 'Cantidad (kg)',
            data: productosFiltrados.map(p => p.cantidad),
            backgroundColor: 'rgba(44, 124, 63, 0.8)',
            borderColor: 'rgba(44, 124, 63, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' kg';
                    }
                }
            }
        }
    }
});

// Gráfico de destinos
const destinosCtx = document.getElementById('destinosChart').getContext('2d');
const destinosChart = new Chart(destinosCtx, {
    type: 'doughnut',
    data: {
        labels: destinosFiltrados.map(d => d.nombre),
        datasets: [{
            data: destinosFiltrados.map(d => d.cantidad),
            backgroundColor: [
                'rgba(44, 124, 63, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(13, 110, 253, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(111, 66, 193, 0.8)'
            ],
            borderColor: [
                'rgba(44, 124, 63, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(13, 110, 253, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(111, 66, 193, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>