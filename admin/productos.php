<?php
require_once __DIR__ . '/../config/config.php';
verificarRol(ROL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        if (!empty($nombre)) {
            $productos = cargarDatos('productos.txt');
            
            $nuevoProducto = [
                'id' => generarIdUnico(),
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'activo' => true
            ];
            
            $productos[] = $nuevoProducto;
            guardarDatos('productos.txt', $productos);
            
            registrarLog('Nuevo producto creado: ' . $nombre);
            redirect('/sumaq-agroexport/admin/productos.php', 'Producto creado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/productos.php', 'El nombre del producto es requerido', 'error');
        }
    } elseif ($accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        if (!empty($nombre)) {
            $productos = cargarDatos('productos.txt');
            $encontrado = false;
            
            foreach ($productos as &$producto) {
                if ($producto['id'] === $id) {
                    $producto['nombre'] = $nombre;
                    $producto['descripcion'] = $descripcion;
                    $encontrado = true;
                    break;
                }
            }
            
            if ($encontrado) {
                guardarDatos('productos.txt', $productos);
                registrarLog('Producto editado: ' . $nombre);
                redirect('/sumaq-agroexport/admin/productos.php', 'Producto actualizado exitosamente');
            } else {
                redirect('/sumaq-agroexport/admin/productos.php', 'Producto no encontrado', 'error');
            }
        } else {
            redirect('/sumaq-agroexport/admin/productos.php', 'El nombre del producto es requerido', 'error');
        }
    } elseif ($accion === 'eliminar') {
        $productos = cargarDatos('productos.txt');
        $productoEliminado = null;
        
        foreach ($productos as $key => $producto) {
            if ($producto['id'] === $id) {
                $productoEliminado = $producto['nombre'];
                unset($productos[$key]);
                break;
            }
        }
        
        if ($productoEliminado !== null) {
            guardarDatos('productos.txt', $productos);
            registrarLog('Producto eliminado: ' . $productoEliminado);
            redirect('/sumaq-agroexport/admin/productos.php', 'Producto eliminado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/productos.php', 'Producto no encontrado', 'error');
        }
    }
}

$productos = cargarDatos('productos.txt');
$titulo = 'Gestión de Productos';
require_once INCLUDES_PATH . 'header.php';

// Calcular estadísticas
$totalProductos = count($productos);
$productosActivos = count(array_filter($productos, function($p) { return $p['activo']; }));
$productosInactivos = $totalProductos - $productosActivos;

// Agrupar productos por mes de creación
$productosPorMes = [];
foreach ($productos as $producto) {
    $mes = date('F Y', strtotime($producto['fecha_creacion']));
    if (!isset($productosPorMes[$mes])) {
        $productosPorMes[$mes] = 0;
    }
    $productosPorMes[$mes]++;
}
?>

<style>
.floating-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.floating-button:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    background: linear-gradient(45deg, #224abe, #4e73df);
    color: white;
}

.dashboard-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-card {
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
}

.stat-card .icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.chart-container {
    position: relative;
    height: 300px;
    margin: auto;
}

.table-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
    padding: 20px;
    margin-top: 30px;
}

.table thead th {
    background: #f8f9fc;
    border-bottom: 2px solid #e3e6f0;
    color: #4e73df;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fc;
}

.action-buttons .btn {
    margin: 0 5px;
    border-radius: 8px;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: #4e73df;
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-body {
    padding: 25px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e3e6f0;
    padding: 12px;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
}
</style>

<!-- Dashboard -->
<div class="container-fluid">
    <!-- Tarjetas de Resumen -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                Total de Productos</div>
                            <div class="h2 mb-0 font-weight-bold"><?= $totalProductos ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-basket icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card" style="background: linear-gradient(45deg, #1cc88a, #13855c);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                Productos Activos</div>
                            <div class="h2 mb-0 font-weight-bold text-white"><?= $productosActivos ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle icon text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card dashboard-card" style="background: linear-gradient(45deg, #f6c23e, #dda20a);">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                Productos Inactivos</div>
                            <div class="h2 mb-0 font-weight-bold text-white"><?= $productosInactivos ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-x-circle icon text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-xl-6 col-lg-6">
            <div class="card dashboard-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Estado de Productos</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="estadoProductosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-6">
            <div class="card dashboard-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Productos por Mes</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="productosPorMesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="m-0 font-weight-bold text-primary">Lista de Productos</h5>
            <div class="d-flex">
                <input type="text" class="form-control me-2" id="searchInput" placeholder="Buscar producto...">
                <select class="form-select" id="filterStatus">
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="dataTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Fecha Creación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= substr($producto['id'], 0, 8) ?></td>
                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td><?= htmlspecialchars($producto['descripcion']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($producto['fecha_creacion'])) ?></td>
                        <td>
                            <span class="badge <?= $producto['activo'] ? 'bg-success' : 'bg-warning' ?>">
                                <?= $producto['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalProducto" 
                                    onclick="editarProducto('<?= $producto['id'] ?>', '<?= htmlspecialchars($producto['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($producto['descripcion'], ENT_QUOTES) ?>')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="/sumaq-agroexport/admin/productos.php" method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este producto?')">
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

<!-- Botón Flotante -->
<button class="btn btn-primary floating-button" data-bs-toggle="modal" data-bs-target="#modalProducto">
    <i class="bi bi-plus-lg"></i>
</button>

<!-- Modal Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-labelledby="modalProductoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/sumaq-agroexport/admin/productos.php" id="formProducto">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProductoLabel">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accionProducto" value="crear">
                    <input type="hidden" name="id" id="idProducto">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráficos
const estadoProductosCtx = document.getElementById('estadoProductosChart').getContext('2d');
new Chart(estadoProductosCtx, {
    type: 'doughnut',
    data: {
        labels: ['Activos', 'Inactivos'],
        datasets: [{
            data: [<?= $productosActivos ?>, <?= $productosInactivos ?>],
            backgroundColor: ['#1cc88a', '#f6c23e'],
            hoverBackgroundColor: ['#17a673', '#dda20a'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        cutout: '70%'
    }
});

const productosPorMesCtx = document.getElementById('productosPorMesChart').getContext('2d');
new Chart(productosPorMesCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($productosPorMes)) ?>,
        datasets: [{
            label: 'Productos Creados',
            data: <?= json_encode(array_values($productosPorMes)) ?>,
            backgroundColor: '#4e73df',
            hoverBackgroundColor: '#2e59d9',
            borderColor: '#4e73df',
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Funciones de edición y búsqueda
function editarProducto(id, nombre, descripcion) {
    document.getElementById('modalProductoLabel').textContent = 'Editar Producto';
    document.getElementById('accionProducto').value = 'editar';
    document.getElementById('idProducto').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('descripcion').value = descripcion;
}

// Búsqueda y filtrado
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('#dataTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

document.getElementById('filterStatus').addEventListener('change', function() {
    const status = this.value;
    const rows = document.querySelectorAll('#dataTable tbody tr');
    
    rows.forEach(row => {
        if (!status) {
            row.style.display = '';
            return;
        }
        
        const isActive = row.querySelector('.badge').classList.contains('bg-success');
        row.style.display = (status === '1' && isActive) || (status === '0' && !isActive) ? '' : 'none';
    });
});

// Validación del formulario
document.getElementById('formProducto').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    if (!nombre) {
        e.preventDefault();
        alert('Por favor, ingrese el nombre del producto');
    }
});
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>