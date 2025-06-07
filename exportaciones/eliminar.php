<?php
require_once __DIR__ . '/../config/config.php';
verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if (!empty($id)) {
        $exportaciones = cargarDatos('exportaciones.txt');
        $exportacionEliminada = null;
        
        foreach ($exportaciones as $key => $exportacion) {
            if ($exportacion['id'] === $id) {
                $exportacionEliminada = $exportacion;
                unset($exportaciones[$key]);
                break;
            }
        }
        
        if ($exportacionEliminada !== null) {
            guardarDatos('exportaciones.txt', $exportaciones);
            
            registrarLog('Exportación eliminada: ' . obtenerNombreProducto($exportacionEliminada['id_producto']) . ' a ' . obtenerNombreDestino($exportacionEliminada['id_destino']));
            redirect('/sumaq-agroexport/reportes/', 'Exportación eliminada exitosamente');
        } else {
            redirect('/sumaq-agroexport/reportes/', 'Exportación no encontrada', 'error');
        }
    } else {
        redirect('/sumaq-agroexport/reportes/', 'ID de exportación no especificado', 'error');
    }
} else {
    redirect('/sumaq-agroexport/reportes/', 'Método no permitido', 'error');
}
?>