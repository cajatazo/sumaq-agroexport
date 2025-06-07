<?php
require_once __DIR__ . '/../config/config.php';
verificarRol(ROL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if ($accion === 'registrar') {
        $dni = trim($_POST['dni'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $rol = trim($_POST['rol'] ?? '');
        $activo = isset($_POST['activo']) ? true : false;
        
        if (!empty($dni) && validarDNI($dni) && !empty($nombres) && !empty($apellidos) && 
            !empty($email) && !empty($rol) && !empty($password) && $password === $confirm_password) {
            
            $usuarios = cargarDatos('usuarios.txt');
            
            // Verificar si el DNI ya existe
            foreach ($usuarios as $usuario) {
                if ($usuario['dni'] === $dni) {
                    redirect('/sumaq-agroexport/admin/usuarios.php', 'El DNI ya está registrado', 'error');
                    exit;
                }
            }
            
            $nuevoUsuario = [
                'id' => generarIdUnico(),
                'dni' => $dni,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'rol' => $rol,
                'fecha_registro' => date('Y-m-d H:i:s'),
                'activo' => $activo
            ];
            
            $usuarios[] = $nuevoUsuario;
            guardarDatos('usuarios.txt', $usuarios);
            
            registrarLog('Nuevo usuario registrado: ' . $dni);
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario registrado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Datos inválidos', 'error');
        }
    } elseif ($accion === 'cambiar_password') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!empty($password) && $password === $confirm_password) {
            $usuarios = cargarDatos('usuarios.txt');
            $encontrado = false;
            
            foreach ($usuarios as &$usuario) {
                if ($usuario['id'] === $id) {
                    $usuario['password'] = password_hash($password, PASSWORD_DEFAULT);
                    $encontrado = true;
                    break;
                }
            }
            
            if ($encontrado) {
                guardarDatos('usuarios.txt', $usuarios);
                registrarLog('Contraseña actualizada para usuario ID: ' . $id);
                redirect('/sumaq-agroexport/admin/usuarios.php', 'Contraseña actualizada exitosamente');
            } else {
                redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario no encontrado', 'error');
            }
        } else {
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Las contraseñas no coinciden', 'error');
        }
    } elseif ($accion === 'editar') {
        $dni = trim($_POST['dni'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $activo = isset($_POST['activo']) ? true : false;
        
        if (!empty($dni) && validarDNI($dni) && !empty($nombres) && !empty($apellidos) && !empty($email) && !empty($rol)) {
            $usuarios = cargarDatos('usuarios.txt');
            $encontrado = false;
            
            foreach ($usuarios as &$usuario) {
                if ($usuario['id'] === $id) {
                    $usuario['dni'] = $dni;
                    $usuario['nombres'] = $nombres;
                    $usuario['apellidos'] = $apellidos;
                    $usuario['email'] = $email;
                    $usuario['rol'] = $rol;
                    $usuario['activo'] = $activo;
                    $encontrado = true;
                    break;
                }
            }
            
            if ($encontrado) {
                guardarDatos('usuarios.txt', $usuarios);
                registrarLog('Usuario editado: ' . $dni);
                redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario actualizado exitosamente');
            } else {
                redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario no encontrado', 'error');
            }
        } else {
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Datos inválidos', 'error');
        }
    } elseif ($accion === 'eliminar') {
        $usuarios = cargarDatos('usuarios.txt');
        $usuarioEliminado = null;
        
        foreach ($usuarios as $key => $usuario) {
            if ($usuario['id'] === $id) {
                $usuarioEliminado = $usuario['dni'];
                unset($usuarios[$key]);
                break;
            }
        }
        
        if ($usuarioEliminado !== null) {
            guardarDatos('usuarios.txt', $usuarios);
            registrarLog('Usuario eliminado: ' . $usuarioEliminado);
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario eliminado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/usuarios.php', 'Usuario no encontrado', 'error');
        }
    }
}

$usuarios = cargarDatos('usuarios.txt');
$titulo = 'Gestión de Usuarios';
require_once INCLUDES_PATH . 'header.php';

// Calcular estadísticas
$totalUsuarios = count($usuarios);
$usuariosActivos = count(array_filter($usuarios, fn($u) => $u['activo']));
$usuariosInactivos = $totalUsuarios - $usuariosActivos;

// Distribución por rol
$roles = array_count_values(array_column($usuarios, 'rol'));
$rolesLabels = array_map(fn($rol) => ucfirst($rol), array_keys($roles));
$rolesData = array_values($roles);

// Registros por mes
$registrosPorMes = [];
$ultimos6Meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $ultimos6Meses[] = $mes;
    $registrosPorMes[$mes] = 0;
}

foreach ($usuarios as $usuario) {
    $mes = date('Y-m', strtotime($usuario['fecha_registro']));
    if (isset($registrosPorMes[$mes])) {
        $registrosPorMes[$mes]++;
    }
}

// Estadísticas de actividad
$usuariosPorDia = [];
$ultimos7Dias = [];
for ($i = 6; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $ultimos7Dias[] = $dia;
    $usuariosPorDia[$dia] = 0;
}

// Calcular tendencia de crecimiento
$crecimiento = 0;
if (count($registrosPorMes) >= 2) {
    $meses = array_values($registrosPorMes);
    $ultimoMes = end($meses);
    $mesAnterior = prev($meses);
    if ($mesAnterior > 0) {
        $crecimiento = (($ultimoMes - $mesAnterior) / $mesAnterior) * 100;
    }
}

// Calcular edad promedio de las cuentas
$edadPromedio = 0;
if ($totalUsuarios > 0) {
    $totalDias = 0;
    foreach ($usuarios as $usuario) {
        $registro = new DateTime($usuario['fecha_registro']);
        $hoy = new DateTime();
        $diferencia = $registro->diff($hoy);
        $totalDias += $diferencia->days;
    }
    $edadPromedio = round($totalDias / $totalUsuarios);
}

// Calcular estadísticas de actividad por hora
$actividadPorHora = array_fill(0, 24, 0);
foreach ($usuarios as $usuario) {
    $hora = (int)date('H', strtotime($usuario['fecha_registro']));
    $actividadPorHora[$hora]++;
}

// Calcular distribución por día de la semana
$diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$registrosPorDia = array_fill_keys($diasSemana, 0);
foreach ($usuarios as $usuario) {
    $dia = $diasSemana[date('w', strtotime($usuario['fecha_registro']))];
    $registrosPorDia[$dia]++;
}

// Calcular tasa de retención (usuarios activos en los últimos 30 días)
$usuariosRecientes = array_filter($usuarios, function($u) {
    return strtotime($u['fecha_registro']) >= strtotime('-30 days');
});
$tasaRetencion = count($usuariosRecientes) / $totalUsuarios * 100;

// Calcular inactividad por rol
$inactivosPorRol = [];
foreach ($usuarios as $usuario) {
    if (!$usuario['activo']) {
        $rol = $usuario['rol'];
        $inactivosPorRol[$rol] = ($inactivosPorRol[$rol] ?? 0) + 1;
    }
}

// Calcular tiempo promedio de inactividad
$tiempoInactividad = 0;
$usuariosInactivosCount = 0;
foreach ($usuarios as $usuario) {
    if (!$usuario['activo']) {
        $registro = new DateTime($usuario['fecha_registro']);
        $hoy = new DateTime();
        $diferencia = $registro->diff($hoy);
        $tiempoInactividad += $diferencia->days;
        $usuariosInactivosCount++;
    }
}
$tiempoPromedioInactividad = $usuariosInactivosCount > 0 ? round($tiempoInactividad / $usuariosInactivosCount) : 0;

// Calcular tendencias de actividad
$tendenciasActividad = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $usuariosMes = array_filter($usuarios, function($u) use ($mes) {
        return date('Y-m', strtotime($u['fecha_registro'])) === $mes;
    });
    $tendenciasActividad[$mes] = [
        'total' => count($usuariosMes),
        'activos' => count(array_filter($usuariosMes, fn($u) => $u['activo'])),
        'inactivos' => count(array_filter($usuariosMes, fn($u) => !$u['activo']))
    ];
}

// Calcular distribución por edad de cuenta
$distribucionEdad = [
    '0-30' => 0,
    '31-60' => 0,
    '61-90' => 0,
    '91-180' => 0,
    '181+' => 0
];

foreach ($usuarios as $usuario) {
    $registro = new DateTime($usuario['fecha_registro']);
    $hoy = new DateTime();
    $diferencia = $registro->diff($hoy);
    $dias = $diferencia->days;
    
    if ($dias <= 30) $distribucionEdad['0-30']++;
    elseif ($dias <= 60) $distribucionEdad['31-60']++;
    elseif ($dias <= 90) $distribucionEdad['61-90']++;
    elseif ($dias <= 180) $distribucionEdad['91-180']++;
    else $distribucionEdad['181+']++;
}
?>

<div class="container-fluid">
    <!-- Dashboard Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Usuarios</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalUsuarios ?></div>
                            <div class="text-xs text-muted mt-1">
                                <i class="bi bi-clock"></i> Edad promedio: <?= $edadPromedio ?> días
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
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
                                Usuarios Activos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $usuariosActivos ?></div>
                            <div class="text-xs text-muted mt-1">
                                <?= $totalUsuarios > 0 ? round(($usuariosActivos / $totalUsuarios) * 100, 1) : 0 ?>% del total
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?= $totalUsuarios > 0 ? ($usuariosActivos / $totalUsuarios) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check-fill fa-2x text-gray-300"></i>
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
                                Usuarios Inactivos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $usuariosInactivos ?></div>
                            <div class="text-xs text-muted mt-1">
                                Tiempo promedio: <?= $tiempoPromedioInactividad ?> días
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-danger" role="progressbar" 
                                     style="width: <?= $totalUsuarios > 0 ? ($usuariosInactivos / $totalUsuarios) * 100 : 0 ?>%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-x-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Crecimiento Mensual</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($crecimiento, 1) ?>%
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <i class="bi bi-arrow-<?= $crecimiento >= 0 ? 'up' : 'down' ?>"></i>
                                vs mes anterior
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?= min(abs($crecimiento), 100) ?>%"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Distribución por Rol</h6>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="cambiarTipoGrafico('roles')">Cambiar Tipo</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="graficoRoles" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Registros por Mes</h6>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="cambiarTipoGrafico('registros')">Cambiar Tipo</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="graficoRegistros" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-people"></i> Usuarios del Sistema
                    </h6>
                    <div class="d-flex gap-2">
                        <div class="input-group">
                            <input type="text" class="form-control" id="buscarUsuario" placeholder="Buscar usuario...">
                            <button class="btn btn-outline-secondary" type="button" onclick="buscarUsuarios()">
                                <i class="bi bi-search"></i>
                            </button>
                </div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
                            <i class="bi bi-person-plus"></i> Nuevo Usuario
                        </button>
                </div>
                </div>
                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3 mb-2">
                            <select class="form-select" id="filtroRol">
                                <option value="">Todos los Roles</option>
                                <option value="<?= ROL_ADMIN ?>">Administrador</option>
                                <option value="<?= ROL_TRABAJADOR ?>">Trabajador</option>
                            </select>
                </div>
                        <div class="col-md-3 mb-2">
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos los Estados</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
            </div>
                        <div class="col-md-3 mb-2">
                            <input type="date" class="form-control" id="filtroFechaInicio" placeholder="Fecha Inicio">
        </div>
                        <div class="col-md-3 mb-2">
                            <input type="date" class="form-control" id="filtroFechaFin" placeholder="Fecha Fin">
    </div>
                </div>
                    <div class="row mb-3">
                        <div class="col-md-3 mb-2">
                            <select class="form-select" id="filtroOrden">
                                <option value="reciente">Más Recientes</option>
                                <option value="antiguo">Más Antiguos</option>
                                <option value="nombre">Nombre</option>
                                <option value="dni">DNI</option>
                            </select>
                </div>
                        <div class="col-md-3 mb-2">
                            <select class="form-select" id="filtroMostrar">
                                <option value="10">10 registros</option>
                                <option value="25">25 registros</option>
                                <option value="50">50 registros</option>
                                <option value="100">100 registros</option>
                            </select>
            </div>
                        <div class="col-md-6 mb-2">
                            <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                                <i class="bi bi-funnel"></i> Aplicar Filtros
                            </button>
        </div>
    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>DNI</th>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Registro</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['dni']) ?></td>
                                    <td><?= htmlspecialchars($usuario['nombres']) ?></td>
                                    <td><?= htmlspecialchars($usuario['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td><?= ucfirst($usuario['rol']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></td>
                                    <td>
                                        <span class="badge <?= $usuario['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" 
                                                onclick="editarUsuario('<?= $usuario['id'] ?>', '<?= htmlspecialchars($usuario['dni'], ENT_QUOTES) ?>', '<?= htmlspecialchars($usuario['nombres'], ENT_QUOTES) ?>', '<?= htmlspecialchars($usuario['apellidos'], ENT_QUOTES) ?>', '<?= htmlspecialchars($usuario['email'], ENT_QUOTES) ?>', '<?= $usuario['rol'] ?>', <?= $usuario['activo'] ? 'true' : 'false' ?>)">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </button>
                                        
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalCambiarPassword"
                                                onclick="cambiarPassword('<?= $usuario['id'] ?>')">
                                            <i class="bi bi-key"></i> Cambiar Contraseña
                                        </button>
                                        
                                        <?php if ($usuario['dni'] !== $_SESSION['usuario']['dni']): ?>
                                        <form action="/sumaq-agroexport/admin/usuarios.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </form>
                                        <?php endif; ?>
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

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/sumaq-agroexport/admin/usuarios.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accionUsuario" value="editar">
                    <input type="hidden" name="id" id="idUsuario">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="dni" name="dni" required 
                                   pattern="[0-9]{8}" title="Ingrese un DNI válido (8 dígitos)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="<?= ROL_ADMIN ?>">Administrador</option>
                                <option value="<?= ROL_TRABAJADOR ?>">Trabajador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo">
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/sumaq-agroexport/admin/usuarios.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoUsuarioLabel">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="nuevo_dni" name="dni" required 
                                   pattern="[0-9]{8}" title="Ingrese un DNI válido (8 dígitos)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="nuevo_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_nombres" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="nuevo_nombres" name="nombres" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="nuevo_apellidos" name="apellidos" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="nuevo_password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nuevo_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_confirm_password" class="form-label">Confirmar Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="nuevo_confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nuevo_confirm_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nuevo_rol" class="form-label">Rol</label>
                            <select class="form-select" id="nuevo_rol" name="rol" required>
                                <option value="<?= ROL_ADMIN ?>">Administrador</option>
                                <option value="<?= ROL_TRABAJADOR ?>">Trabajador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="nuevo_activo" name="activo" checked>
                                <label class="form-check-label" for="nuevo_activo">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="modalCambiarPassword" tabindex="-1" aria-labelledby="modalCambiarPasswordLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/sumaq-agroexport/admin/usuarios.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCambiarPasswordLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="cambiar_password">
                    <input type="hidden" name="id" id="password_id">
                    
                    <div class="mb-3">
                        <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="nueva_password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('nueva_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmar_nueva_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmar_nueva_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_nueva_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function editarUsuario(id, dni, nombres, apellidos, email, rol, activo) {
    document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuario';
    document.getElementById('accionUsuario').value = 'editar';
    document.getElementById('idUsuario').value = id;
    document.getElementById('dni').value = dni;
    document.getElementById('nombres').value = nombres;
    document.getElementById('apellidos').value = apellidos;
    document.getElementById('email').value = email;
    document.getElementById('rol').value = rol;
    document.getElementById('activo').checked = activo;
}

// Datos para los gráficos
const rolesData = {
    labels: <?= json_encode($rolesLabels) ?>,
    datasets: [{
        data: <?= json_encode($rolesData) ?>,
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
        hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
        hoverBorderColor: "rgba(234, 236, 244, 1)",
    }]
};

const registrosData = {
    labels: <?= json_encode(array_keys($registrosPorMes)) ?>,
    datasets: [{
        label: 'Nuevos Registros',
        data: <?= json_encode(array_values($registrosPorMes)) ?>,
        backgroundColor: 'rgba(78, 115, 223, 0.05)',
        borderColor: 'rgba(78, 115, 223, 1)',
        pointRadius: 3,
        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
        pointBorderColor: 'rgba(78, 115, 223, 1)',
        pointHoverRadius: 3,
        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
        pointHitRadius: 10,
        pointBorderWidth: 2,
        fill: true
    }]
};

const actividadHoraData = {
    labels: Array.from({length: 24}, (_, i) => `${i}:00`),
    datasets: [{
        label: 'Registros por Hora',
        data: <?= json_encode($actividadPorHora) ?>,
        backgroundColor: 'rgba(54, 185, 204, 0.5)',
        borderColor: 'rgba(54, 185, 204, 1)',
        borderWidth: 1
    }]
};

const diasSemanaData = {
    labels: <?= json_encode($diasSemana) ?>,
    datasets: [{
        label: 'Registros por Día',
        data: <?= json_encode(array_values($registrosPorDia)) ?>,
        backgroundColor: 'rgba(246, 194, 62, 0.5)',
        borderColor: 'rgba(246, 194, 62, 1)',
        borderWidth: 1
    }]
};

const inactivosData = {
    labels: <?= json_encode(array_keys($inactivosPorRol)) ?>,
    datasets: [{
        label: 'Usuarios Inactivos por Rol',
        data: <?= json_encode(array_values($inactivosPorRol)) ?>,
        backgroundColor: 'rgba(220, 53, 69, 0.5)',
        borderColor: 'rgba(220, 53, 69, 1)',
        borderWidth: 1
    }]
};

const edadData = {
    labels: <?= json_encode(array_keys($distribucionEdad)) ?>,
    datasets: [{
        label: 'Distribución por Edad de Cuenta',
        data: <?= json_encode(array_values($distribucionEdad)) ?>,
        backgroundColor: 'rgba(75, 192, 192, 0.5)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 1
    }]
};

let graficoRoles, graficoRegistros, graficoActividadHora, graficoDiasSemana, graficoInactivos, graficoEdad;

// Configuración de los gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Roles
    graficoRoles = new Chart(document.getElementById('graficoRoles'), {
        type: 'doughnut',
        data: rolesData,
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // Gráfico de Registros
    graficoRegistros = new Chart(document.getElementById('graficoRegistros'), {
        type: 'line',
        data: registrosData,
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
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Registros: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Actividad por Hora
    graficoActividadHora = new Chart(document.getElementById('graficoActividadHora'), {
        type: 'bar',
        data: actividadHoraData,
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

    // Gráfico de Días de la Semana
    graficoDiasSemana = new Chart(document.getElementById('graficoDiasSemana'), {
        type: 'bar',
        data: diasSemanaData,
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

    // Gráfico de Inactivos por Rol
    graficoInactivos = new Chart(document.getElementById('graficoInactivos'), {
        type: 'bar',
        data: inactivosData,
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

    // Gráfico de Distribución por Edad
    graficoEdad = new Chart(document.getElementById('graficoEdad'), {
        type: 'bar',
        data: edadData,
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
});

// Funciones para exportar e imprimir gráficos
function exportarGrafico(tipo) {
    const canvas = document.getElementById(`grafico${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    const link = document.createElement('a');
    link.download = `grafico-${tipo}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function imprimirGrafico(tipo) {
    const canvas = document.getElementById(`grafico${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    const win = window.open('', '', 'width=800,height=600');
    win.document.write(`<img src='${canvas.toDataURL('image/png')}'/>`);
    win.document.close();
    win.print();
}

function cambiarTipoGrafico(tipo) {
    const grafico = tipo === 'roles' ? graficoRoles : graficoRegistros;
    const tipos = ['doughnut', 'pie', 'bar', 'line'];
    const tipoActual = grafico.config.type;
    const siguienteTipo = tipos[(tipos.indexOf(tipoActual) + 1) % tipos.length];
    grafico.config.type = siguienteTipo;
    grafico.update();
}

// Funciones para el manejo de usuarios
function cambiarPassword(id) {
    document.getElementById('password_id').value = id;
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function buscarUsuarios() {
    const busqueda = document.getElementById('buscarUsuario').value.toLowerCase();
    const tabla = document.getElementById('dataTable');
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let fila of filas) {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    }
}

function aplicarFiltros() {
    const rol = document.getElementById('filtroRol').value;
    const estado = document.getElementById('filtroEstado').value;
    const fechaInicio = document.getElementById('filtroFechaInicio').value;
    const fechaFin = document.getElementById('filtroFechaFin').value;
    const orden = document.getElementById('filtroOrden').value;
    const mostrar = parseInt(document.getElementById('filtroMostrar').value);
    
    const tabla = document.getElementById('dataTable');
    const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    let filasVisibles = 0;
    
    // Convertir todas las filas a un array para poder ordenarlas
    const filasArray = Array.from(filas);
    
    // Aplicar filtros
    filasArray.forEach(fila => {
        let mostrarFila = true;
        
        // Filtrar por rol
        if (rol && fila.cells[4].textContent.trim().toLowerCase() !== rol.toLowerCase()) {
            mostrarFila = false;
        }
        
        // Filtrar por estado
        if (estado !== '') {
            const esActivo = fila.cells[6].textContent.includes('Activo');
            if ((estado === '1' && !esActivo) || (estado === '0' && esActivo)) {
                mostrarFila = false;
            }
        }
        
        // Filtrar por fecha
        if (fechaInicio || fechaFin) {
            const fechaRegistro = new Date(fila.cells[5].textContent.split('/').reverse().join('-'));
            if (fechaInicio && fechaRegistro < new Date(fechaInicio)) {
                mostrarFila = false;
            }
            if (fechaFin && fechaRegistro > new Date(fechaFin)) {
                mostrarFila = false;
            }
        }
        
        // Aplicar límite de registros visibles
        if (mostrarFila) {
            filasVisibles++;
            if (filasVisibles > mostrar) {
                mostrarFila = false;
            }
        }
        
        fila.style.display = mostrarFila ? '' : 'none';
    });
    
    // Ordenar filas
    const tbody = tabla.getElementsByTagName('tbody')[0];
    
    filasArray.sort((a, b) => {
        switch (orden) {
            case 'reciente':
                return new Date(b.cells[5].textContent.split('/').reverse().join('-')) - 
                       new Date(a.cells[5].textContent.split('/').reverse().join('-'));
            case 'antiguo':
                return new Date(a.cells[5].textContent.split('/').reverse().join('-')) - 
                       new Date(b.cells[5].textContent.split('/').reverse().join('-'));
            case 'nombre':
                return (a.cells[1].textContent.trim() + ' ' + a.cells[2].textContent.trim()).localeCompare(
                       b.cells[1].textContent.trim() + ' ' + b.cells[2].textContent.trim());
            case 'dni':
                return a.cells[0].textContent.trim().localeCompare(b.cells[0].textContent.trim());
            default:
                return 0;
        }
    });
    
    // Reordenar filas en la tabla
    filasArray.forEach(fila => tbody.appendChild(fila));
}

// Evento para buscar al presionar Enter
document.getElementById('buscarUsuario').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarUsuarios();
    }
});

// Eventos para aplicar filtros automáticamente
document.getElementById('filtroRol').addEventListener('change', aplicarFiltros);
document.getElementById('filtroEstado').addEventListener('change', aplicarFiltros);
document.getElementById('filtroFechaInicio').addEventListener('change', aplicarFiltros);
document.getElementById('filtroFechaFin').addEventListener('change', aplicarFiltros);
document.getElementById('filtroOrden').addEventListener('change', aplicarFiltros);
document.getElementById('filtroMostrar').addEventListener('change', aplicarFiltros);
</script>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>