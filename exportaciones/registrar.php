<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        $nuevaExportacion = [
            'id' => generarIdUnico(),
            'id_producto' => $idProducto,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'id_destino' => $idDestino,
            'fecha_exportacion' => $fechaExportacion,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'registrado_por' => $_SESSION['usuario']['dni'],
            'activo' => true
        ];
        
        $exportaciones[] = $nuevaExportacion;
        guardarDatos('exportaciones.txt', $exportaciones);
        
        registrarLog('Nueva exportación registrada: ' . obtenerNombreProducto($idProducto) . ' a ' . obtenerNombreDestino($idDestino));
        redirect('/sumaq-agroexport/exportaciones/registrar.php', 'Exportación registrada exitosamente');
    } else {
        $_SESSION['mensaje'] = implode('<br>', $errores);
        $_SESSION['mensaje_tipo'] = 'error';
    }
}

$productos = obtenerProductos();
$destinos = obtenerDestinos();
$titulo = 'Registrar Exportación';
require_once INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-plus-circle"></i> Registrar Nueva Exportación
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="producto" class="form-label">Producto</label>
                                <select class="form-select" id="producto" name="producto" required>
                                    <option value="">Seleccionar producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php if ($producto['activo']): ?>
                                            <option value="<?= $producto['id'] ?>" <?= isset($_POST['producto']) && $_POST['producto'] == $producto['id'] ? 'selected' : '' ?>>
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
                                            <option value="<?= $destino['id'] ?>" <?= isset($_POST['destino']) && $_POST['destino'] == $destino['id'] ? 'selected' : '' ?>>
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
                                       value="<?= htmlspecialchars($_POST['cantidad'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="precio" class="form-label">Precio por kg (S/.)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="precio" name="precio" 
                                       value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="fecha_exportacion" class="form-label">Fecha de Exportación</label>
                                <input type="date" class="form-control" id="fecha_exportacion" name="fecha_exportacion" 
                                       value="<?= htmlspecialchars($_POST['fecha_exportacion'] ?? date('Y-m-d')) ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Registrar Exportación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>