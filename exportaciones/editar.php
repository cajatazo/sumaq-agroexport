<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $idProducto = $_POST['producto'] ?? '';
    $cantidad = (float)($_POST['cantidad'] ?? 0);
    $precio = (float)($_POST['precio'] ?? 0);
    $idDestino = $_POST['destino'] ?? '';
    $fechaExportacion = $_POST['fecha_exportacion'] ?? date('Y-m-d');
    
    // Validaciones
    $errores = [];
    
    if (empty($idProducto)) {
        $errores[] = 'Debe seleccionar un producto';
    }
    
    if ($cantidad <= 0) {
        $errores[] = 'La cantidad debe ser mayor a cero';
    }
    
    if ($precio <= 0) {
        $errores[] = 'El precio debe ser mayor a cero';
    }
    
    if (empty($idDestino)) {
        $errores[] = 'Debe seleccionar un destino';
    }
    
    if (empty($errores)) {
        $exportaciones = cargarDatos('exportaciones.txt');
        $encontrado = false;
        
        foreach ($exportaciones as &$exportacion) {
            if ($exportacion['id'] === $id) {
                $exportacion['id_producto'] = $idProducto;
                $exportacion['cantidad'] = $cantidad;
                $exportacion['precio'] = $precio;
                $exportacion['id_destino'] = $idDestino;
                $exportacion['fecha_exportacion'] = $fechaExportacion;
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            guardarDatos('exportaciones.txt', $exportaciones);
            
            registrarLog('Exportación editada: ID ' . substr($id, 0, 8));
            redirect('/sumaq-agroexport/reportes/', 'Exportación actualizada exitosamente');
        } else {
            redirect('/sumaq-agroexport/reportes/', 'Exportación no encontrada', 'error');
        }
    } else {
        $_SESSION['mensaje'] = implode('<br>', $errores);
        $_SESSION['mensaje_tipo'] = 'error';
    }
} else {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        redirect('/sumaq-agroexport/reportes/', 'ID de exportación no especificado', 'error');
    }
    
    $exportaciones = cargarDatos('exportaciones.txt');
    $exportacion = null;
    
    foreach ($exportaciones as $exp) {
        if ($exp['id'] === $id) {
            $exportacion = $exp;
            break;
        }
    }
    
    if (!$exportacion) {
        redirect('/sumaq-agroexport/reportes/', 'Exportación no encontrada', 'error');
    }
}

$productos = obtenerProductos();
$destinos = obtenerDestinos();
$titulo = 'Editar Exportación';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-pencil-square"></i> Editar Exportación
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $exportacion['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="producto" class="form-label">Producto</label>
                                <select class="form-select" id="producto" name="producto" required>
                                    <option value="">Seleccionar producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php if ($producto['activo']): ?>
                                            <option value="<?= $producto['id'] ?>" <?= $exportacion['id_producto'] == $producto['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($producto['nombre']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="destino" class="form-label">País de Destino</label>
                                <select class="form-select" id="destino" name="destino" required>
                                    <option value="">Seleccionar destino</option>
                                    <?php foreach ($destinos as $destino): ?>
                                        <?php if ($destino['activo']): ?>
                                            <option value="<?= $destino['id'] ?>" <?= $exportacion['id_destino'] == $destino['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($destino['nombre']) ?>
                                                <?= $destino['especial'] ? ' (Especial)' : '' ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cantidad" class="form-label">Cantidad (kg)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="cantidad" name="cantidad" 
                                       value="<?= htmlspecialchars($exportacion['cantidad']) ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="precio" class="form-label">Precio por kg (S/.)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="precio" name="precio" 
                                       value="<?= htmlspecialchars($exportacion['precio']) ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="fecha_exportacion" class="form-label">Fecha de Exportación</label>
                                <input type="date" class="form-control" id="fecha_exportacion" name="fecha_exportacion" 
                                       value="<?= htmlspecialchars($exportacion['fecha_exportacion']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/sumaq-agroexport/reportes/" class="btn btn-secondary me-md-2">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>