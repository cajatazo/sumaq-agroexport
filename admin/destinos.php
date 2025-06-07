<?php
// Configurar la zona horaria de Perú
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../config/config.php';
verificarRol(ROL_ADMIN);

// Verificar y crear la carpeta data si no existe
if (!file_exists(DATA_PATH)) {
    mkdir(DATA_PATH, 0777, true);
}

// Verificar y crear el archivo destinos.txt si no existe
if (!file_exists(DATA_PATH . 'destinos.txt')) {
    file_put_contents(DATA_PATH . 'destinos.txt', '[]');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $capital = trim($_POST['capital'] ?? '');
        $moneda = trim($_POST['moneda'] ?? '');
        $idioma = trim($_POST['idioma'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
        $email_cliente = trim($_POST['email_cliente'] ?? '');
        $telefono_cliente = trim($_POST['telefono_cliente'] ?? '');
        $direccion_cliente = trim($_POST['direccion_cliente'] ?? '');
        $especial = isset($_POST['especial']) ? true : false;
        
        if (!empty($nombre) && !empty($region)) {
            $destinos = cargarDatos('destinos.txt');
            
            $nuevoDestino = [
                'id' => generarIdUnico(),
                'nombre' => $nombre,
                'region' => $region,
                'capital' => $capital,
                'moneda' => $moneda,
                'idioma' => $idioma,
                'codigo_postal' => $codigo_postal,
                'nombre_cliente' => $nombre_cliente,
                'email_cliente' => $email_cliente,
                'telefono_cliente' => $telefono_cliente,
                'direccion_cliente' => $direccion_cliente,
                'especial' => $especial,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'activo' => true
            ];
            
            $destinos[] = $nuevoDestino;
            guardarDatos('destinos.txt', $destinos);
            
            registrarLog('Nuevo destino creado: ' . $nombre);
            redirect('/sumaq-agroexport/admin/destinos.php', 'Destino creado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/destinos.php', 'Nombre y región son requeridos', 'error');
        }
    } elseif ($accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $capital = trim($_POST['capital'] ?? '');
        $moneda = trim($_POST['moneda'] ?? '');
        $idioma = trim($_POST['idioma'] ?? '');
        $codigo_postal = trim($_POST['codigo_postal'] ?? '');
        $nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
        $email_cliente = trim($_POST['email_cliente'] ?? '');
        $telefono_cliente = trim($_POST['telefono_cliente'] ?? '');
        $direccion_cliente = trim($_POST['direccion_cliente'] ?? '');
        $especial = isset($_POST['especial']) ? true : false;
        
        if (!empty($nombre) && !empty($region)) {
            $destinos = cargarDatos('destinos.txt');
            $encontrado = false;
            
            foreach ($destinos as &$destino) {
                if ($destino['id'] === $id) {
                    $destino['nombre'] = $nombre;
                    $destino['region'] = $region;
                    $destino['capital'] = $capital;
                    $destino['moneda'] = $moneda;
                    $destino['idioma'] = $idioma;
                    $destino['codigo_postal'] = $codigo_postal;
                    $destino['nombre_cliente'] = $nombre_cliente;
                    $destino['email_cliente'] = $email_cliente;
                    $destino['telefono_cliente'] = $telefono_cliente;
                    $destino['direccion_cliente'] = $direccion_cliente;
                    $destino['especial'] = $especial;
                    $encontrado = true;
                    break;
                }
            }
            
            if ($encontrado) {
                guardarDatos('destinos.txt', $destinos);
                registrarLog('Destino editado: ' . $nombre);
                redirect('/sumaq-agroexport/admin/destinos.php', 'Destino actualizado exitosamente');
            } else {
                redirect('/sumaq-agroexport/admin/destinos.php', 'Destino no encontrado', 'error');
            }
        } else {
            redirect('/sumaq-agroexport/admin/destinos.php', 'Nombre y región son requeridos', 'error');
        }
    } elseif ($accion === 'eliminar') {
        $destinos = cargarDatos('destinos.txt');
        $destinoEliminado = null;
        
        foreach ($destinos as $key => $destino) {
            if ($destino['id'] === $id) {
                $destinoEliminado = $destino['nombre'];
                unset($destinos[$key]);
                break;
            }
        }
        
        if ($destinoEliminado !== null) {
            guardarDatos('destinos.txt', $destinos);
            registrarLog('Destino eliminado: ' . $destinoEliminado);
            redirect('/sumaq-agroexport/admin/destinos.php', 'Destino eliminado exitosamente');
        } else {
            redirect('/sumaq-agroexport/admin/destinos.php', 'Destino no encontrado', 'error');
        }
    }
}

$destinos = cargarDatos('destinos.txt');
$titulo = 'Gestión de Destinos';

// Asegurarnos de que los datos estén ordenados por fecha
usort($destinos, function($a, $b) {
    return strtotime($b['fecha_creacion']) - strtotime($a['fecha_creacion']);
});

require_once INCLUDES_PATH . 'header.php';

// Calcular estadísticas con validación
$totalDestinos = count($destinos);
$destinosActivos = count(array_filter($destinos, fn($d) => isset($d['activo']) && $d['activo']));
$destinosEspeciales = count(array_filter($destinos, fn($d) => isset($d['especial']) && $d['especial']));

// Agrupar por región con validación
$destinosPorRegion = [];
foreach ($destinos as $destino) {
    $region = isset($destino['region']) ? $destino['region'] : 'Sin región';
    if (!isset($destinosPorRegion[$region])) {
        $destinosPorRegion[$region] = [
            'total' => 0,
            'activos' => 0,
            'especiales' => 0,
            'ultimo_mes' => 0
        ];
    }
    $destinosPorRegion[$region]['total']++;
    if (isset($destino['activo']) && $destino['activo']) {
        $destinosPorRegion[$region]['activos']++;
    }
    if (isset($destino['especial']) && $destino['especial']) {
        $destinosPorRegion[$region]['especiales']++;
    }
    
    // Contar destinos del último mes con validación de fecha
    if (isset($destino['fecha_creacion'])) {
        $fechaCreacion = strtotime($destino['fecha_creacion']);
        if ($fechaCreacion && $fechaCreacion >= strtotime('-1 month')) {
            $destinosPorRegion[$region]['ultimo_mes']++;
        }
    }
}

// Agrupar por mes con validación
$destinosPorMes = [];
foreach ($destinos as $destino) {
    if (isset($destino['fecha_creacion'])) {
        $mes = date('Y-m', strtotime($destino['fecha_creacion']));
        if (!isset($destinosPorMes[$mes])) {
            $destinosPorMes[$mes] = [
                'total' => 0,
                'activos' => 0,
                'especiales' => 0
            ];
        }
        $destinosPorMes[$mes]['total']++;
        if (isset($destino['activo']) && $destino['activo']) {
            $destinosPorMes[$mes]['activos']++;
        }
        if (isset($destino['especial']) && $destino['especial']) {
            $destinosPorMes[$mes]['especiales']++;
        }
    }
}
ksort($destinosPorMes);

// Calcular tendencias con validación
$ultimos6Meses = array_slice($destinosPorMes, -6, 6, true);
$tendenciaCrecimiento = 0;
if (count($ultimos6Meses) > 1) {
    $primerMes = reset($ultimos6Meses)['total'];
    $ultimoMes = end($ultimos6Meses)['total'];
    $tendenciaCrecimiento = $primerMes > 0 ? (($ultimoMes - $primerMes) / $primerMes) * 100 : 0;
}

// Calcular destinos más recientes con validación
$destinosRecientes = array_slice($destinos, 0, 5);

// Calcular métricas de rendimiento con validación
$metricasRendimiento = [
    'crecimiento_mensual' => $tendenciaCrecimiento,
    'tasa_actividad' => $totalDestinos > 0 ? ($destinosActivos / $totalDestinos) * 100 : 0,
    'tasa_especiales' => $totalDestinos > 0 ? ($destinosEspeciales / $totalDestinos) * 100 : 0,
    'nuevos_ultimo_mes' => array_sum(array_map(fn($r) => $r['ultimo_mes'], $destinosPorRegion))
];

// Coordenadas de países por región
$coordenadasPaises = [
    'Sudamérica' => [
        'Perú' => [-9.1900, -75.0152],
        'Chile' => [-35.6751, -71.5430],
        'Argentina' => [-38.4161, -63.6167],
        'Brasil' => [-14.2350, -51.9253],
        'Colombia' => [4.5709, -74.2973],
        'Ecuador' => [-1.8312, -78.1834],
        'Bolivia' => [-16.2902, -63.5887],
        'Paraguay' => [-23.4425, -58.4438],
        'Uruguay' => [-32.5228, -55.7658],
        'Venezuela' => [6.4237, -66.5897]
    ],
    'Norteamérica' => [
        'Estados Unidos' => [37.0902, -95.7129],
        'Canadá' => [56.1304, -106.3468],
        'México' => [23.6345, -102.5528]
    ],
    'Europa' => [
        'España' => [40.4637, -3.7492],
        'Francia' => [46.2276, 2.2137],
        'Alemania' => [51.1657, 10.4515],
        'Italia' => [41.8719, 12.5674],
        'Reino Unido' => [55.3781, -3.4360]
    ],
    'Asia' => [
        'China' => [35.8617, 104.1954],
        'Japón' => [36.2048, 138.2529],
        'Corea del Sur' => [35.9078, 127.7669],
        'India' => [20.5937, 78.9629]
    ],
    'África' => [
        'Sudáfrica' => [-30.5595, 22.9375],
        'Egipto' => [26.8206, 30.8025],
        'Marruecos' => [31.7917, -7.0926]
    ],
    'Oceanía' => [
        'Australia' => [-25.2744, 133.7751],
        'Nueva Zelanda' => [-40.9006, 174.8860]
    ]
];

// Preparar datos para el mapa
$puntosMapa = [];
foreach ($destinos as $destino) {
    $region = $destino['region'];
    $pais = $destino['nombre'];
    if (isset($coordenadasPaises[$region][$pais])) {
        $coords = $coordenadasPaises[$region][$pais];
        $puntosMapa[] = [
            'nombre' => $pais,
            'region' => $region,
            'lat' => $coords[0],
            'lng' => $coords[1],
            'especial' => $destino['especial'],
            'activo' => $destino['activo']
        ];
    }
}

// Lista completa de países por región
$paisesPorRegion = [
    'Sudamérica' => [
        'Perú', 'Chile', 'Argentina', 'Brasil', 'Colombia', 'Ecuador', 
        'Bolivia', 'Paraguay', 'Uruguay', 'Venezuela', 'Guyana', 'Surinam', 
        'Guyana Francesa'
    ],
    'Norteamérica' => [
        'Estados Unidos', 'Canadá', 'México', 'Guatemala', 'Belice', 
        'Honduras', 'El Salvador', 'Nicaragua', 'Costa Rica', 'Panamá'
    ],
    'Europa' => [
        'España', 'Francia', 'Alemania', 'Italia', 'Reino Unido', 'Portugal', 
        'Países Bajos', 'Bélgica', 'Suiza', 'Austria', 'Suecia', 'Noruega', 
        'Dinamarca', 'Finlandia', 'Polonia', 'Rusia', 'Ucrania', 'Grecia'
    ],
    'Asia' => [
        'China', 'Japón', 'Corea del Sur', 'India', 'Indonesia', 'Malasia', 
        'Tailandia', 'Vietnam', 'Singapur', 'Filipinas', 'Taiwán', 'Hong Kong'
    ],
    'África' => [
        'Sudáfrica', 'Egipto', 'Marruecos', 'Nigeria', 'Kenia', 'Ghana', 
        'Etiopía', 'Tanzania', 'Uganda', 'Argelia', 'Túnez'
    ],
    'Oceanía' => [
        'Australia', 'Nueva Zelanda', 'Fiyi', 'Papúa Nueva Guinea', 
        'Islas Salomón', 'Vanuatu', 'Samoa'
    ]
];

// Estructura de datos para países con información detallada
$paisesDetallados = [
    'América del Sur' => [
        'Perú' => [
            'capital' => 'Lima',
            'moneda' => 'Sol (PEN)',
            'idioma' => 'Español',
            'codigo_postal' => '51',
            'codigo_telefono' => '+51'
        ],
        'Chile' => [
            'capital' => 'Santiago',
            'moneda' => 'Peso Chileno (CLP)',
            'idioma' => 'Español',
            'codigo_postal' => '56',
            'codigo_telefono' => '+56'
        ],
        'Colombia' => [
            'capital' => 'Bogotá',
            'moneda' => 'Peso Colombiano (COP)',
            'idioma' => 'Español',
            'codigo_postal' => '57',
            'codigo_telefono' => '+57'
        ],
        'Ecuador' => [
            'capital' => 'Quito',
            'moneda' => 'Dólar Estadounidense (USD)',
            'idioma' => 'Español',
            'codigo_postal' => '593',
            'codigo_telefono' => '+593'
        ],
        'Bolivia' => [
            'capital' => 'La Paz',
            'moneda' => 'Boliviano (BOB)',
            'idioma' => 'Español',
            'codigo_postal' => '591',
            'codigo_telefono' => '+591'
        ],
        'Argentina' => [
            'capital' => 'Buenos Aires',
            'moneda' => 'Peso Argentino (ARS)',
            'idioma' => 'Español',
            'codigo_postal' => '54',
            'codigo_telefono' => '+54'
        ],
        'Brasil' => [
            'capital' => 'Brasilia',
            'moneda' => 'Real (BRL)',
            'idioma' => 'Portugués',
            'codigo_postal' => '55',
            'codigo_telefono' => '+55'
        ],
        'Uruguay' => [
            'capital' => 'Montevideo',
            'moneda' => 'Peso Uruguayo (UYU)',
            'idioma' => 'Español',
            'codigo_postal' => '598',
            'codigo_telefono' => '+598'
        ],
        'Paraguay' => [
            'capital' => 'Asunción',
            'moneda' => 'Guaraní (PYG)',
            'idioma' => 'Español',
            'codigo_postal' => '595',
            'codigo_telefono' => '+595'
        ],
        'Venezuela' => [
            'capital' => 'Caracas',
            'moneda' => 'Bolívar (VES)',
            'idioma' => 'Español',
            'codigo_postal' => '58',
            'codigo_telefono' => '+58'
        ]
    ],
    'Europa' => [
        'España' => [
            'capital' => 'Madrid',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Español',
            'codigo_postal' => '34',
            'codigo_telefono' => '+34'
        ],
        'Portugal' => [
            'capital' => 'Lisboa',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Portugués',
            'codigo_postal' => '351',
            'codigo_telefono' => '+351'
        ],
        'Francia' => [
            'capital' => 'París',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Francés',
            'codigo_postal' => '33',
            'codigo_telefono' => '+33'
        ],
        'Italia' => [
            'capital' => 'Roma',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Italiano',
            'codigo_postal' => '39',
            'codigo_telefono' => '+39'
        ],
        'Alemania' => [
            'capital' => 'Berlín',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Alemán',
            'codigo_postal' => '49',
            'codigo_telefono' => '+49'
        ],
        'Reino Unido' => [
            'capital' => 'Londres',
            'moneda' => 'Libra Esterlina (GBP)',
            'idioma' => 'Inglés',
            'codigo_postal' => '44',
            'codigo_telefono' => '+44'
        ],
        'Países Bajos' => [
            'capital' => 'Ámsterdam',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Neerlandés',
            'codigo_postal' => '31',
            'codigo_telefono' => '+31'
        ],
        'Bélgica' => [
            'capital' => 'Bruselas',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Neerlandés, Francés, Alemán',
            'codigo_postal' => '32',
            'codigo_telefono' => '+32'
        ],
        'Suiza' => [
            'capital' => 'Berna',
            'moneda' => 'Franco Suizo (CHF)',
            'idioma' => 'Alemán, Francés, Italiano',
            'codigo_postal' => '41',
            'codigo_telefono' => '+41'
        ],
        'Austria' => [
            'capital' => 'Viena',
            'moneda' => 'Euro (EUR)',
            'idioma' => 'Alemán',
            'codigo_postal' => '43',
            'codigo_telefono' => '+43'
        ]
    ]
];

// Agregar códigos postales y de teléfono por país
$codigosPaises = [
    'Perú' => ['postal' => '51', 'telefono' => '+51'],
    'Chile' => ['postal' => '56', 'telefono' => '+56'],
    'Argentina' => ['postal' => '54', 'telefono' => '+54'],
    'Brasil' => ['postal' => '55', 'telefono' => '+55'],
    'Colombia' => ['postal' => '57', 'telefono' => '+57'],
    'Ecuador' => ['postal' => '593', 'telefono' => '+593'],
    'Bolivia' => ['postal' => '591', 'telefono' => '+591'],
    'Paraguay' => ['postal' => '595', 'telefono' => '+595'],
    'Uruguay' => ['postal' => '598', 'telefono' => '+598'],
    'Venezuela' => ['postal' => '58', 'telefono' => '+58'],
    'Estados Unidos' => ['postal' => '1', 'telefono' => '+1'],
    'Canadá' => ['postal' => '1', 'telefono' => '+1'],
    'México' => ['postal' => '52', 'telefono' => '+52'],
    'España' => ['postal' => '34', 'telefono' => '+34'],
    'Francia' => ['postal' => '33', 'telefono' => '+33'],
    'Alemania' => ['postal' => '49', 'telefono' => '+49'],
    'Italia' => ['postal' => '39', 'telefono' => '+39'],
    'Reino Unido' => ['postal' => '44', 'telefono' => '+44'],
    'China' => ['postal' => '86', 'telefono' => '+86'],
    'Japón' => ['postal' => '81', 'telefono' => '+81'],
    'Corea del Sur' => ['postal' => '82', 'telefono' => '+82'],
    'India' => ['postal' => '91', 'telefono' => '+91'],
    'Sudáfrica' => ['postal' => '27', 'telefono' => '+27'],
    'Egipto' => ['postal' => '20', 'telefono' => '+20'],
    'Marruecos' => ['postal' => '212', 'telefono' => '+212'],
    'Australia' => ['postal' => '61', 'telefono' => '+61'],
    'Nueva Zelanda' => ['postal' => '64', 'telefono' => '+64']
];

// Modificar el array de países detallados para incluir los códigos
foreach ($paisesDetallados as $region => &$paises) {
    foreach ($paises as $pais => &$detalles) {
        if (isset($codigosPaises[$pais])) {
            $detalles['codigo_postal'] = $codigosPaises[$pais]['postal'];
            $detalles['codigo_telefono'] = $codigosPaises[$pais]['telefono'];
        }
    }
}

// Convertir a formato para autocompletado
$paisesAutocompletado = [];
foreach ($paisesDetallados as $region => $paises) {
    foreach ($paises as $pais => $detalles) {
        $paisesAutocompletado[] = [
            'label' => $pais,
            'value' => $pais,
            'region' => $region,
            'capital' => $detalles['capital'],
            'moneda' => $detalles['moneda'],
            'idioma' => $detalles['idioma']
        ];
    }
}
?>

<!-- Agregar Leaflet CSS y JS en el header -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Botón flotante para nuevo destino -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
    <button class="btn btn-primary btn-lg rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#modalDestino">
        <i class="bi bi-plus-lg"></i>
    </button>
</div>

<div class="container-fluid">
    <!-- Barra de Herramientas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" onclick="exportarDatos('excel')">
                                <i class="bi bi-file-excel"></i> Exportar Excel
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportarDatos('pdf')">
                                <i class="bi bi-file-pdf"></i> Exportar PDF
                            </button>
                            <button class="btn btn-outline-primary" onclick="exportarDatos('csv')">
                                <i class="bi bi-file-text"></i> Exportar CSV
                            </button>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-success" onclick="actualizarDashboard()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                            <button class="btn btn-outline-info" onclick="mostrarAyuda()">
                                <i class="bi bi-question-circle"></i> Ayuda
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros del Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Rango de Fechas</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="fechaInicio">
                                <input type="date" class="form-control" id="fechaFin">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Región</label>
                            <select class="form-select" id="filtroRegion">
                                <option value="">Todas las regiones</option>
                                <?php foreach (array_keys($destinosPorRegion) as $region): ?>
                                    <option value="<?= $region ?>"><?= $region ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activos</option>
                                <option value="inactivo">Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="filtroTipo">
                                <option value="">Todos los tipos</option>
                                <option value="especial">Especiales</option>
                                <option value="normal">Normales</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button class="btn btn-primary" onclick="aplicarFiltros()">
                                <i class="bi bi-funnel"></i> Aplicar Filtros
                            </button>
                            <button class="btn btn-secondary" onclick="resetearFiltros()">
                                <i class="bi bi-arrow-counterclockwise"></i> Resetear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Destinos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalDestinos ?></div>
                            <div class="text-xs text-muted mt-1">
                                <i class="bi bi-arrow-up text-success"></i> 
                                <?= abs(round($tendenciaCrecimiento, 1)) ?>% vs último mes
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-globe-americas fa-2x text-gray-300"></i>
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
                                Destinos Activos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $destinosActivos ?></div>
                            <div class="text-xs text-muted mt-1">
                                <?= $totalDestinos > 0 ? round(($destinosActivos / $totalDestinos) * 100, 1) : 0 ?>% del total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300"></i>
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
                                Destinos Especiales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $destinosEspeciales ?></div>
                            <div class="text-xs text-muted mt-1">
                                <?= $totalDestinos > 0 ? round(($destinosEspeciales / $totalDestinos) * 100, 1) : 0 ?>% del total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-star fa-2x text-gray-300"></i>
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
                                Nuevos este Mes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $metricasRendimiento['nuevos_ultimo_mes'] ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                Últimos 30 días
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

    <!-- Mapa de Destinos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-map"></i> Mapa de Destinos
                    </h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="filtrarMapa('todos')">Todos</button>
                        <button class="btn btn-sm btn-outline-info" onclick="filtrarMapa('especiales')">Especiales</button>
                        <button class="btn btn-sm btn-outline-success" onclick="filtrarMapa('activos')">Activos</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="mapaDestinos" style="height: 500px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Destinos Recientes -->
    <div class="row">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Destinos Recientes</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="verTodosDestinos()">
                        Ver Todos
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>País</th>
                                    <th>Región</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($destinosRecientes) as $destino): ?>
                                <tr>
                                    <td><?= htmlspecialchars($destino['nombre']) ?></td>
                                    <td><?= htmlspecialchars($destino['region']) ?></td>
                                    <td>
                                        <span class="badge <?= $destino['especial'] ? 'bg-info' : 'bg-primary' ?>">
                                            <?= $destino['especial'] ? 'Especial' : 'Normal' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($destino['fecha_creacion'])) ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" onclick="verDetalles('<?= $destino['id'] ?>')">
                                            <i class="bi bi-eye"></i> Ver
                                        </button>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalDestino" 
                                                onclick="editarDestino('<?= $destino['id'] ?>', '<?= htmlspecialchars($destino['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['region'], ENT_QUOTES) ?>', <?= $destino['especial'] ? 'true' : 'false' ?>, '<?= htmlspecialchars($destino['capital'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['moneda'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['idioma'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['codigo_postal'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['nombre_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['email_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['telefono_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['direccion_cliente'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </button>
                                        
                                        <form action="/sumaq-agroexport/admin/destinos.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $destino['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este destino?')">
                                                <i class="bi bi-trash"></i> Eliminar
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

    <!-- Tabla de Destinos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-globe-americas"></i> Países de Destino
                    </h6>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDestino">
                        <i class="bi bi-plus-circle"></i> Nuevo Destino
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>País</th>
                                    <th>Región</th>
                                    <th>Tipo</th>
                                    <th>Fecha Creación</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($destinos as $destino): ?>
                                <tr>
                                    <td><?= substr($destino['id'], 0, 8) ?></td>
                                    <td><?= htmlspecialchars($destino['nombre']) ?></td>
                                    <td><?= htmlspecialchars($destino['region']) ?></td>
                                    <td>
                                        <span class="badge <?= $destino['especial'] ? 'bg-info' : 'bg-primary' ?>">
                                            <?= $destino['especial'] ? 'Especial' : 'Normal' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($destino['fecha_creacion'])) ?></td>
                                    <td>
                                        <span class="badge <?= $destino['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $destino['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm" onclick="verDetalles('<?= $destino['id'] ?>')">
                                            <i class="bi bi-eye"></i> Ver
                                        </button>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalDestino" 
                                                onclick="editarDestino('<?= $destino['id'] ?>', '<?= htmlspecialchars($destino['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['region'], ENT_QUOTES) ?>', <?= $destino['especial'] ? 'true' : 'false' ?>, '<?= htmlspecialchars($destino['capital'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['moneda'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['idioma'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['codigo_postal'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['nombre_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['email_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['telefono_cliente'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($destino['direccion_cliente'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </button>
                                        
                                        <form action="/sumaq-agroexport/admin/destinos.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $destino['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este destino?')">
                                                <i class="bi bi-trash"></i> Eliminar
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

<!-- Modal Destino -->
<div class="modal fade" id="modalDestino" tabindex="-1" aria-labelledby="modalDestinoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/sumaq-agroexport/admin/destinos.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDestinoLabel">Nuevo Destino</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accionDestino" value="crear">
                    <input type="hidden" name="id" id="idDestino">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del País</label>
                                <div class="input-group">
                                    <select class="form-select" id="nombre" name="nombre" required>
                                        <option value="">Seleccionar país</option>
                                        <?php foreach ($paisesDetallados as $region => $paises): ?>
                                            <optgroup label="<?= $region ?>">
                                                <?php foreach ($paises as $pais => $detalles): ?>
                                                    <option value="<?= $pais ?>" 
                                                            data-region="<?= $region ?>"
                                                            data-capital="<?= $detalles['capital'] ?>"
                                                            data-moneda="<?= $detalles['moneda'] ?>"
                                                            data-idioma="<?= $detalles['idioma'] ?>"
                                                            data-codigo-postal="<?= $detalles['codigo_postal'] ?? '' ?>"
                                                            data-codigo-telefono="<?= $detalles['codigo_telefono'] ?? '' ?>">
                                                        <?= $pais ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="form-control" id="buscarPais" placeholder="Buscar país..." style="max-width: 200px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="region" class="form-label">Región</label>
                                <select class="form-select" id="region" name="region" required>
                                    <option value="">Seleccionar región</option>
                                    <option value="Sudamérica">Sudamérica</option>
                                    <option value="Norteamérica">Norteamérica</option>
                                    <option value="Europa">Europa</option>
                                    <option value="Asia">Asia</option>
                                    <option value="África">África</option>
                                    <option value="Oceanía">Oceanía</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capital" class="form-label">Capital</label>
                                <input type="text" class="form-control" id="capital" name="capital">
                            </div>
                            
                            <div class="mb-3">
                                <label for="moneda" class="form-label">Moneda</label>
                                <input type="text" class="form-control" id="moneda" name="moneda">
                            </div>
                            
                            <div class="mb-3">
                                <label for="idioma" class="form-label">Idioma</label>
                                <input type="text" class="form-control" id="idioma" name="idioma">
                            </div>

                            <div class="mb-3">
                                <label for="codigo_postal" class="form-label">Código Postal</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Ej: 15001">
                                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalCodigoPostal">
                                        <i class="bi bi-map"></i> Buscar en Mapa
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_cliente" class="form-label">Nombre del Cliente</label>
                                <input type="text" class="form-control" id="nombre_cliente" name="nombre_cliente" placeholder="Nombre completo del cliente">
                            </div>

                            <div class="mb-3">
                                <label for="email_cliente" class="form-label">Email del Cliente</label>
                                <input type="email" class="form-control" id="email_cliente" name="email_cliente" placeholder="ejemplo@correo.com">
                            </div>

                            <div class="mb-3">
                                <label for="telefono_cliente" class="form-label">Teléfono del Cliente</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="codigo_telefono">+XX</span>
                                    <input type="tel" class="form-control" id="telefono_cliente" name="telefono_cliente" 
                                           placeholder="XXX XXX XXX" 
                                           pattern="[0-9]{3} [0-9]{3} [0-9]{3}"
                                           title="Formato: XXX XXX XXX">
                                </div>
                                <small class="form-text text-muted">Ingrese el número sin el código de país</small>
                            </div>

                            <div class="mb-3">
                                <label for="direccion_cliente" class="form-label">Dirección del Cliente</label>
                                <textarea class="form-control" id="direccion_cliente" name="direccion_cliente" rows="2" placeholder="Dirección completa del cliente"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="especial" name="especial">
                        <label class="form-check-label" for="especial">Destino Especial</label>
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

<!-- Modal de Detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Destino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detallesContenido"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ayuda del Dashboard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Filtros</h6>
                <p>Utilice los filtros para refinar la información mostrada en el dashboard.</p>
                
                <h6>Exportación</h6>
                <p>Puede exportar los datos en diferentes formatos: Excel, PDF y CSV.</p>
                
                <h6>Gráficos</h6>
                <p>Los gráficos son interactivos y pueden cambiar de tipo usando los botones correspondientes.</p>
                
                <h6>Mapa</h6>
                <p>El mapa muestra la distribución geográfica de los destinos.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Código Postal -->
<div class="modal fade" id="modalCodigoPostal" tabindex="-1" aria-labelledby="modalCodigoPostalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCodigoPostalLabel">Buscador de Códigos Postales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="paisBusqueda" class="form-label">País</label>
                        <select class="form-select" id="paisBusqueda" required>
                            <option value="">Seleccione un país</option>
                            <?php foreach ($paisesDetallados as $region => $paises): ?>
                                <optgroup label="<?= $region ?>">
                                    <?php foreach ($paises as $pais => $detalles): ?>
                                        <option value="<?= $pais ?>"><?= $pais ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="codigoPostalBusqueda" class="form-label">Código Postal</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigoPostalBusqueda" placeholder="Ingrese código postal">
                            <button class="btn btn-primary" type="button" onclick="buscarCodigoPostal()">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Puede hacer clic en el mapa para seleccionar una ubicación o usar el buscador.
                </div>
                
                <div id="mapaCodigoPostal" class="mb-3"></div>
                
                <div id="resultadosCodigoPostal" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="regresarANuevoDestino()">
                    <i class="fas fa-arrow-left"></i> Regresar a Nuevo Destino
                </button>
                <button type="button" class="btn btn-primary" onclick="seleccionarCodigoPostal()">
                    <i class="fas fa-check"></i> Seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear nuevo destino -->
<div class="modal fade" id="modalCrearDestino" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Destino</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCrearDestino" method="POST">
                    <input type="hidden" name="action" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">País</label>
                        <select class="form-select" id="nombre" name="nombre" required>
                            <option value="">Selecciona un país</option>
                            <?php foreach ($paisesDetallados as $region => $paises): ?>
                                <optgroup label="<?= $region ?>">
                                    <?php foreach ($paises as $pais => $detalles): ?>
                                        <option value="<?= $pais ?>" 
                                                data-region="<?= $region ?>"
                                                data-capital="<?= $detalles['capital'] ?? '' ?>"
                                                data-moneda="<?= $detalles['moneda'] ?? '' ?>"
                                                data-idioma="<?= $detalles['idioma'] ?? '' ?>"
                                                data-codigo-postal="<?= $detalles['codigo_postal'] ?? '' ?>"
                                                data-codigo-telefono="<?= $detalles['codigo_telefono'] ?? '' ?>">
                                            <?= $pais ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="region" class="form-label">Región</label>
                        <input type="text" class="form-control" id="region" name="region" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="capital" class="form-label">Capital</label>
                        <input type="text" class="form-control" id="capital" name="capital" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moneda" class="form-label">Moneda</label>
                        <input type="text" class="form-control" id="moneda" name="moneda" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="idioma" class="form-label">Idioma</label>
                        <input type="text" class="form-control" id="idioma" name="idioma" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="codigo_postal" class="form-label">Código Postal</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Ej: 15001">
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalCodigoPostal">
                                <i class="bi bi-map"></i> Buscar en Mapa
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono del Cliente</label>
                        <div class="input-group">
                            <span class="input-group-text" id="codigo_telefono">+51</span>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   placeholder="XXX XXX XXX" pattern="[0-9]{3} [0-9]{3} [0-9]{3}"
                                   aria-describedby="telefonoHelp">
                        </div>
                        <div id="telefonoHelp" class="form-text">Formato: XXX XXX XXX</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="normal">Normal</option>
                            <option value="especial">Especial</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_creacion" class="form-label">Fecha de Creación</label>
                        <input type="text" class="form-control" id="fecha_creacion" name="fecha_creacion" 
                               value="<?= date('Y-m-d H:i:s') ?>" readonly>
                        <small class="text-muted">Horario de Perú (UTC-5)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('formCrearDestino').submit()">
                    <i class="bi bi-plus-circle"></i> Crear Destino
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// Registrar el plugin de datalabels
Chart.register(ChartDataLabels);

function verDetalles(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const destinos = <?= json_encode($destinos) ?>;
    const destino = destinos.find(d => d.id === id);
    
    if (destino) {
        const contenido = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px;">ID</th>
                        <td>${destino.id}</td>
                    </tr>
                    <tr>
                        <th>País</th>
                        <td>${destino.nombre}</td>
                    </tr>
                    <tr>
                        <th>Región</th>
                        <td>${destino.region}</td>
                    </tr>
                    <tr>
                        <th>Tipo</th>
                        <td>
                            <span class="badge ${destino.especial ? 'bg-info' : 'bg-primary'}">
                                ${destino.especial ? 'Especial' : 'Normal'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td>
                            <span class="badge ${destino.activo ? 'bg-success' : 'bg-secondary'}">
                                ${destino.activo ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Fecha de Creación</th>
                        <td>${new Date(destino.fecha_creacion).toLocaleString()}</td>
                    </tr>
                </table>
            </div>
        `;
        document.getElementById('detallesContenido').innerHTML = contenido;
    } else {
        document.getElementById('detallesContenido').innerHTML = '<div class="alert alert-danger">Destino no encontrado</div>';
    }
    modal.show();
}

function verTodosDestinos() {
    // Implementar vista de todos los destinos
    alert('Mostrando todos los destinos...');
}

function editarDestino(id, nombre, region, especial, capital, moneda, idioma, codigo_postal, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente) {
    document.getElementById('accionDestino').value = 'editar';
    document.getElementById('idDestino').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('region').value = region;
    document.getElementById('capital').value = capital || '';
    document.getElementById('moneda').value = moneda || '';
    document.getElementById('idioma').value = idioma || '';
    document.getElementById('codigo_postal').value = codigo_postal || '';
    document.getElementById('nombre_cliente').value = nombre_cliente || '';
    document.getElementById('email_cliente').value = email_cliente || '';
    document.getElementById('telefono_cliente').value = telefono_cliente || '';
    document.getElementById('direccion_cliente').value = direccion_cliente || '';
    document.getElementById('especial').checked = especial;
    
    // Actualizar título del modal
    document.getElementById('modalDestinoLabel').textContent = 'Editar Destino';
}

// Funciones de exportación mejoradas
function exportarDatos(formato) {
    const tabla = document.getElementById('dataTable');
    let datos = [];
    
    // Obtener datos de la tabla con validación
    const filas = tabla.querySelectorAll('tbody tr');
    filas.forEach(fila => {
        if (fila.style.display !== 'none') {
            const id = fila.dataset.id || '';
            const nombre = fila.cells[1]?.textContent || '';
            const region = fila.cells[2]?.textContent || '';
            const tipo = fila.cells[3]?.textContent || '';
            const fecha = fila.cells[4]?.textContent || '';
            const estado = fila.cells[5]?.textContent || '';
            
            datos.push({ id, nombre, region, tipo, fecha, estado });
        }
    });
    
    if (datos.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    switch(formato) {
        case 'excel':
            exportarExcel(datos);
            break;
        case 'pdf':
            exportarPDF(datos);
            break;
        case 'csv':
            exportarCSV(datos);
            break;
    }
}

function exportarExcel(datos) {
    try {
        const ws = XLSX.utils.json_to_sheet(datos);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Destinos");
        XLSX.writeFile(wb, `destinos-${new Date().toISOString().split('T')[0]}.xlsx`);
    } catch (error) {
        console.error('Error al exportar a Excel:', error);
        alert('Error al exportar a Excel');
    }
}

function exportarPDF(datos) {
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Configurar el documento
        doc.setFontSize(16);
        doc.text('Reporte de Destinos', 14, 15);
        doc.setFontSize(10);
        
        // Agregar datos
        let y = 30;
        datos.forEach((dato, index) => {
            if (y > 280) {
                doc.addPage();
                y = 20;
            }
            doc.text(`${index + 1}. ${dato.nombre} - ${dato.region}`, 14, y);
            y += 7;
        });
        
        doc.save(`destinos-${new Date().toISOString().split('T')[0]}.pdf`);
    } catch (error) {
        console.error('Error al exportar a PDF:', error);
        alert('Error al exportar a PDF');
    }
}

function exportarCSV(datos) {
    try {
        const headers = ['ID', 'Nombre', 'Región', 'Tipo', 'Fecha', 'Estado'];
        const csvContent = [
            headers.join(','),
            ...datos.map(d => [
                d.id,
                `"${d.nombre}"`,
                `"${d.region}"`,
                `"${d.tipo}"`,
                `"${d.fecha}"`,
                `"${d.estado}"`
            ].join(','))
        ].join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `destinos-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
    } catch (error) {
        console.error('Error al exportar a CSV:', error);
        alert('Error al exportar a CSV');
    }
}

// Funciones de filtrado mejoradas
function aplicarFiltros() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const region = document.getElementById('filtroRegion').value;
    const estado = document.getElementById('filtroEstado').value;
    const tipo = document.getElementById('filtroTipo').value;

    const filas = document.querySelectorAll('#dataTable tbody tr');
    filas.forEach(fila => {
        let mostrar = true;
        
        // Filtrar por fecha
        if (fechaInicio && fechaFin) {
            const fechaDestino = new Date(fila.dataset.fecha);
            const inicio = new Date(fechaInicio);
            const fin = new Date(fechaFin);
            mostrar = mostrar && fechaDestino >= inicio && fechaDestino <= fin;
        }
        
        // Filtrar por región
        if (region) {
            mostrar = mostrar && fila.dataset.region === region;
        }
        
        // Filtrar por estado
        if (estado) {
            mostrar = mostrar && fila.dataset.estado === estado;
        }
        
        // Filtrar por tipo
        if (tipo) {
            mostrar = mostrar && fila.dataset.tipo === tipo;
        }
        
        fila.style.display = mostrar ? '' : 'none';
    });

    // Actualizar contadores
    actualizarContadores();
}

function actualizarContadores() {
    const filasVisibles = document.querySelectorAll('#dataTable tbody tr:not([style*="display: none"])').length;
    document.getElementById('totalFiltrado').textContent = filasVisibles;
}

function resetearFiltros() {
    document.getElementById('fechaInicio').value = '';
    document.getElementById('fechaFin').value = '';
    document.getElementById('filtroRegion').value = '';
    document.getElementById('filtroEstado').value = '';
    document.getElementById('filtroTipo').value = '';
    
    const filas = document.querySelectorAll('#dataTable tbody tr');
    filas.forEach(fila => fila.style.display = '');
    
    actualizarContadores();
}

// Inicializar mapa
let mapa;
let marcadores = [];
const puntosMapa = <?= json_encode($puntosMapa) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar mapa
    mapa = L.map('mapaDestinos').setView([0, 0], 2);
    
    // Agregar capa de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(mapa);
    
    // Agregar marcadores
    puntosMapa.forEach(punto => {
        const icono = L.divIcon({
            className: `marcador ${punto.especial ? 'especial' : 'normal'} ${punto.activo ? 'activo' : 'inactivo'}`,
            html: `<i class="bi bi-geo-alt-fill"></i>`,
            iconSize: [30, 30]
        });
        
        const marcador = L.marker([punto.lat, punto.lng], {icon: icono})
            .bindPopup(`
                <strong>${punto.nombre}</strong><br>
                Región: ${punto.region}<br>
                Tipo: ${punto.especial ? 'Especial' : 'Normal'}<br>
                Estado: ${punto.activo ? 'Activo' : 'Inactivo'}
            `);
        
        marcadores.push(marcador);
        marcador.addTo(mapa);
    });
});

function filtrarMapa(tipo) {
    marcadores.forEach(marcador => {
        const punto = puntosMapa[marcadores.indexOf(marcador)];
        let mostrar = false;
        
        switch(tipo) {
            case 'todos':
                mostrar = true;
                break;
            case 'especiales':
                mostrar = punto.especial;
                break;
            case 'activos':
                mostrar = punto.activo;
                break;
        }
        
        if (mostrar) {
            marcador.addTo(mapa);
        } else {
            marcador.remove();
        }
    });
}

// Función para filtrar países en el select
function filtrarPaises(valor) {
    const select = document.getElementById('nombre');
    const options = select.getElementsByTagName('option');
    const groups = select.getElementsByTagName('optgroup');
    
    // Si el valor está vacío, mostrar todo
    if (!valor) {
        for (let option of options) {
            option.style.display = '';
        }
        for (let group of groups) {
            group.style.display = '';
        }
        return;
    }
    
    valor = valor.toLowerCase();
    
    // Ocultar todas las opciones primero
    for (let option of options) {
        option.style.display = 'none';
    }
    
    // Filtrar opciones
    for (let option of options) {
        if (option.text.toLowerCase().includes(valor)) {
            option.style.display = '';
            // Mostrar el grupo padre
            const group = option.parentElement;
            if (group.tagName === 'OPTGROUP') {
                group.style.display = '';
            }
        }
    }
    
    // Ocultar grupos sin opciones visibles
    for (let group of groups) {
        const hasVisibleOptions = Array.from(group.getElementsByTagName('option'))
            .some(option => option.style.display !== 'none');
        group.style.display = hasVisibleOptions ? '' : 'none';
    }
}

// Evento de búsqueda
document.getElementById('buscarPais').addEventListener('input', function() {
    filtrarPaises(this.value);
});

// Limpiar búsqueda cuando se selecciona un país
document.getElementById('nombre').addEventListener('change', function() {
    document.getElementById('buscarPais').value = '';
    filtrarPaises('');
    
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        document.getElementById('region').value = selectedOption.dataset.region || '';
        document.getElementById('capital').value = selectedOption.dataset.capital || '';
        document.getElementById('moneda').value = selectedOption.dataset.moneda || '';
        document.getElementById('idioma').value = selectedOption.dataset.idioma || '';
    } else {
        document.getElementById('region').value = '';
        document.getElementById('capital').value = '';
        document.getElementById('moneda').value = '';
        document.getElementById('idioma').value = '';
    }
});

// Inicializar el select con todos los países visibles
document.addEventListener('DOMContentLoaded', function() {
    filtrarPaises('');
});

// Actualizar mapa cuando se agrega/edita un destino
function actualizarMapa() {
    if (typeof mapa !== 'undefined') {
        // Limpiar marcadores existentes
        marcadores.forEach(marcador => marcador.remove());
        marcadores = [];
        
        // Recargar datos
        fetch('/sumaq-agroexport/admin/destinos.php?ajax=1')
            .then(response => response.json())
            .then(data => {
                puntosMapa = data;
                // Agregar nuevos marcadores
                puntosMapa.forEach(punto => {
                    const icono = L.divIcon({
                        className: `marcador ${punto.especial ? 'especial' : 'normal'} ${punto.activo ? 'activo' : 'inactivo'}`,
                        html: `<i class="bi bi-geo-alt-fill"></i>`,
                        iconSize: [30, 30]
                    });
                    
                    const marcador = L.marker([punto.lat, punto.lng], {icon: icono})
                        .bindPopup(`
                            <strong>${punto.nombre}</strong><br>
                            Región: ${punto.region}<br>
                            Tipo: ${punto.especial ? 'Especial' : 'Normal'}<br>
                            Estado: ${punto.activo ? 'Activo' : 'Inactivo'}
                        `);
                    
                    marcadores.push(marcador);
                    marcador.addTo(mapa);
                });
            });
    }
}

// Actualizar mapa después de guardar
document.querySelector('form').addEventListener('submit', function() {
    setTimeout(actualizarMapa, 1000);
});

document.addEventListener('DOMContentLoaded', function() {
    const buscarPais = document.getElementById('buscarPais');
    const selectPais = document.getElementById('nombre');
    const codigoTelefonoSpan = document.getElementById('codigo_telefono');
    const telefonoInput = document.getElementById('telefono_cliente');
    
    // Función para formatear el número de teléfono
    function formatearTelefono(input) {
        // Eliminar todo excepto números
        let numero = input.value.replace(/\D/g, '');
        
        // Formatear como XXX XXX XXX
        if (numero.length > 0) {
            numero = numero.match(new RegExp('.{1,3}', 'g')).join(' ');
        }
        
        input.value = numero;
    }
    
    // Aplicar formato mientras se escribe
    telefonoInput.addEventListener('input', function() {
        formatearTelefono(this);
    });
    
    buscarPais.addEventListener('input', function() {
        const valor = this.value.toLowerCase();
        const options = selectPais.getElementsByTagName('option');
        const groups = selectPais.getElementsByTagName('optgroup');
        
        // Mostrar/ocultar opciones
        for (let option of options) {
            const texto = option.text.toLowerCase();
            option.style.display = texto.includes(valor) ? '' : 'none';
        }
        
        // Mostrar/ocultar grupos
        for (let group of groups) {
            const hasVisibleOptions = Array.from(group.getElementsByTagName('option'))
                .some(option => option.style.display !== 'none');
            group.style.display = hasVisibleOptions ? '' : 'none';
        }
    });
    
    selectPais.addEventListener('change', function() {
        buscarPais.value = '';
        const selectedOption = this.options[this.selectedIndex];
        
        // Actualizar campos con valores por defecto si están vacíos
        if (!document.getElementById('capital').value) {
            document.getElementById('capital').value = selectedOption.dataset.capital || '';
        }
        if (!document.getElementById('moneda').value) {
            document.getElementById('moneda').value = selectedOption.dataset.moneda || '';
        }
        if (!document.getElementById('idioma').value) {
            document.getElementById('idioma').value = selectedOption.dataset.idioma || '';
        }
        if (!document.getElementById('codigo_postal').value) {
            document.getElementById('codigo_postal').value = selectedOption.dataset.codigoPostal || '';
        }
        
        // Actualizar el código de teléfono
        const codigoTelefono = selectedOption.dataset.codigoTelefono || '';
        codigoTelefonoSpan.textContent = codigoTelefono;
        
        // Limpiar el campo de teléfono si está vacío
        if (!telefonoInput.value) {
            telefonoInput.value = '';
        }
        
        // Mostrar todas las opciones
        const options = this.getElementsByTagName('option');
        const groups = this.getElementsByTagName('optgroup');
        for (let option of options) option.style.display = '';
        for (let group of groups) group.style.display = '';
    });
});

// Variables globales para el mapa
let mapaCodigoPostal;
let marcadorCodigoPostal;
let circuloPrecision;

// Inicializar el mapa cuando se abre el modal
document.getElementById('modalCodigoPostal').addEventListener('show.bs.modal', function () {
    setTimeout(() => {
        inicializarMapa();
        mapaCodigoPostal.invalidateSize();
    }, 200);
});

function inicializarMapa() {
    if (!mapaCodigoPostal) {
        mapaCodigoPostal = L.map('mapaCodigoPostal', {
            zoomControl: true,
            scrollWheelZoom: true,
            center: [0, 0],
            zoom: 2
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapaCodigoPostal);

        // Evento de doble clic
        mapaCodigoPostal.on('dblclick', function(e) {
            const latlng = e.latlng;
            buscarUbicacionPorCoordenadas(latlng.lat, latlng.lng);
        });

        // Evento de clic derecho
        mapaCodigoPostal.on('contextmenu', function(e) {
            e.originalEvent.preventDefault();
            const latlng = e.latlng;
            buscarUbicacionPorCoordenadas(latlng.lat, latlng.lng);
        });

        // Evento de clic normal
        mapaCodigoPostal.on('click', function(e) {
            const latlng = e.latlng;
            buscarUbicacionPorCoordenadas(latlng.lat, latlng.lng);
        });
    }
}

function buscarUbicacionPorCoordenadas(lat, lng) {
    // Mostrar indicador de carga
    document.getElementById('resultadosCodigoPostal').innerHTML = 
        '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    // Usar Nominatim para obtener la información de la ubicación
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                // Actualizar el campo de búsqueda
                document.getElementById('codigoPostalBusqueda').value = data.display_name;
                
                // Mostrar en el mapa
                mostrarEnMapa(lat, lng);
                
                // Mostrar resultados
                mostrarResultadosNominatim(data);
            }
        })
        .catch(error => {
            document.getElementById('resultadosCodigoPostal').innerHTML = 
                '<div class="alert alert-danger">Error al buscar la ubicación. Por favor, intente nuevamente.</div>';
        });
}

function mostrarResultadosNominatim(data) {
    const resultadosDiv = document.getElementById('resultadosCodigoPostal');
    
    let html = '<div class="card">';
    html += '<div class="card-header bg-primary text-white">';
    html += '<h5 class="mb-0">Ubicación Seleccionada</h5>';
    html += '</div>';
    html += '<div class="card-body">';
    
    html += '<div class="mb-3">';
    html += '<h6 class="card-subtitle mb-2 text-muted">Detalles de la Ubicación</h6>';
    html += '<p class="card-text">' + data.display_name + '</p>';
    
    if (data.address) {
        html += '<div class="mt-2">';
        if (data.address.postcode) html += '<p><strong>Código Postal:</strong> ' + data.address.postcode + '</p>';
        if (data.address.city) html += '<p><strong>Ciudad:</strong> ' + data.address.city + '</p>';
        if (data.address.state) html += '<p><strong>Estado/Región:</strong> ' + data.address.state + '</p>';
        if (data.address.country) html += '<p><strong>País:</strong> ' + data.address.country + '</p>';
        html += '</div>';
    }
    
    html += '<button class="btn btn-sm btn-outline-primary mt-2" onclick="seleccionarUbicacion(\'' + 
            data.display_name + '\', \'' + data.lat + '\', \'' + data.lon + '\')">';
    html += '<i class="fas fa-map-marker-alt"></i> Seleccionar esta ubicación';
    html += '</button>';
    
    html += '</div></div></div>';
    resultadosDiv.innerHTML = html;
}

// Modificar el evento de apertura del modal
document.getElementById('modalCodigoPostal').addEventListener('show.bs.modal', function () {
    setTimeout(() => {
        inicializarMapa();
        mapaCodigoPostal.invalidateSize();
    }, 200);
});

// ... rest of the existing code ...
</script>

<style>
.marcador {
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.marcador i {
    font-size: 20px;
}

.marcador.especial {
    color: #17a2b8;
}

.marcador.normal {
    color: #007bff;
}

.marcador.activo {
    border: 2px solid #28a745;
}

.marcador.inactivo {
    border: 2px solid #6c757d;
    opacity: 0.7;
}

/* Estilos para el autocompletado */
.ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 9999 !important;
}

.ui-autocomplete .ui-menu-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 14px;
}

.ui-autocomplete .ui-menu-item:hover {
    background-color: #f8f9fa;
}

.ui-autocomplete .ui-menu-item div {
    padding: 4px 0;
}

.ui-autocomplete .ui-menu-item strong {
    color: #007bff;
}

.ui-autocomplete .ui-menu-item small {
    color: #6c757d;
    font-size: 12px;
}

/* Estilos para el botón flotante */
.btn-flotante {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 24px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.btn-flotante:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 12px rgba(0,0,0,0.3);
}

.form-select {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

optgroup {
    font-weight: bold;
    color: #495057;
}

optgroup option {
    font-weight: normal;
    padding-left: 1rem;
}

.input-group {
    display: flex;
    align-items: stretch;
}

.input-group .form-select {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: 0;
}

#buscarPais {
    background-color: #f8f9fa;
    border-color: #ced4da;
    padding-right: 30px; /* Espacio para el icono */
}

#buscarPais:focus {
    background-color: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.input-group {
    position: relative;
}

.input-group .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: 0;
}

.input-group .form-select {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

/* Estilo para las opciones del select */
.form-select option {
    padding: 8px;
}

.form-select optgroup {
    font-weight: bold;
    color: #495057;
    padding: 8px;
}

.form-select optgroup option {
    font-weight: normal;
    padding-left: 20px;
}

/* Estilos para el campo de teléfono */
.input-group-text {
    background-color: #f8f9fa;
    border-right: none;
    font-weight: 500;
}

#telefono_cliente {
    border-left: none;
}

#telefono_cliente:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.input-group:focus-within .input-group-text {
    border-color: #86b7fe;
}

/* Estilos para el modal de código postal */
#mapaCodigoPostal {
    height: 400px;
    width: 100%;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

/* Estilos para el cursor del mapa */
.leaflet-container {
    cursor: crosshair;
}

/* Estilos para el marcador */
.leaflet-marker-icon {
    cursor: move;
}

.card {
    margin-bottom: 1rem;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-bottom: none;
}

.card-body {
    padding: 1.25rem;
}

.btn-outline-primary {
    margin-top: 0.5rem;
}

.text-muted {
    font-size: 0.875rem;
}
</style>

<?php require_once INCLUDES_PATH . 'footer.php'; ?>