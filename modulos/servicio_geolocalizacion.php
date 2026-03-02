<?php
session_start();
require_once '../config/config.php';

$db = new Conexion($opciones);
$pdo = $db->con;

$stmt = $pdo->query("SELECT * FROM vista_ventas_geolocalizacion ORDER BY fecha_venta DESC LIMIT 50");
$ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$markers = [];
foreach ($ubicaciones as $ubi) {
    $markers[] = [
        'lat'         => floatval($ubi['latitud']),
        'lng'         => floatval($ubi['longitud']),
        'title'       => htmlspecialchars($ubi['titulo_libro']),
        'cliente'     => htmlspecialchars($ubi['nombre_cliente']),
        'direccion'   => htmlspecialchars($ubi['direccion_envio']),
        'ciudad'      => htmlspecialchars($ubi['ciudad']),
        'estado_venta'=> $ubi['estado_venta'],
        'pedido'      => str_pad($ubi['id_venta'], 6, '0', STR_PAD_LEFT),
    ];
}

$ciudades = array_unique(array_column($ubicaciones, 'ciudad'));
$enviados = array_filter($ubicaciones, fn($u) =>
    in_array($u['estado_venta'], ['enviado', 'entregado'])
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio de Geolocalización - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root { --accent: #667eea; --accent2: #764ba2; }
        body { background: #f5f5f5; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        .section-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,.1);
            margin-bottom: 30px;
        }
        #map { height: 500px; border-radius: 10px; }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: var(--accent); }
        /* ── Demo BD cards ── */
        .demo-card {
            border: 1px solid #e2e8f0; border-radius: 12px;
            overflow: hidden; display: flex; flex-direction: column;
        }
        .demo-card-header {
            background: #f8fafc; border-bottom: 1px solid #e2e8f0;
            padding: 14px 18px; display: flex; align-items: center; gap: 10px;
        }
        .demo-card-body { padding: 18px; flex: 1; }
        .badge-tipo {
            font-size: .7rem; font-weight: 700; padding: 3px 8px;
            border-radius: 6px; white-space: nowrap; text-transform: uppercase;
        }
        .badge-tipo.vista  { background: #dbeafe; color: #1d4ed8; }
        .badge-tipo.proc-c { background: #dcfce7; color: #15803d; }
        .badge-tipo.proc-u { background: #fef9c3; color: #854d0e; }
        .badge-tipo.proc-d { background: #fee2e2; color: #b91c1c; }
        .sql-block {
            background: #1e1e2e; color: #cdd6f4;
            font-family: 'Courier New', monospace; font-size: .8rem;
            padding: 14px; border-radius: 8px; margin-bottom: 14px;
            white-space: pre-wrap; line-height: 1.6;
        }
        .sql-block .kw { color: #89b4fa; font-weight: 600; }
        .sql-block .fn { color: #a6e3a1; }
        .sql-block .cm { color: #6c7086; font-style: italic; }
        .btn-ejecutar {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white; border: none;
        }
        .btn-ejecutar:hover { opacity: .9; color: white; }
        .spinner-overlay { display: none; padding: 10px 0; color: var(--accent); font-size: .88rem; }
        .table-resultado { font-size: .8rem; }
        .table-resultado thead { background: var(--accent); color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><strong>Biblioteca Digital</strong></a>
        <div>
            <?php if (isset($_SESSION['id_cliente'])): ?>
                <span class="me-3">👤 <?= htmlspecialchars($_SESSION['nombre_cliente']) ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
            <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">

    <!-- ENCABEZADO -->
    <div class="section-card text-center">
        <h1 class="mb-1">Servicio de Geolocalización</h1>
        <p class="text-muted mb-0">Mapa interactivo con ubicaciones de entrega</p>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="section-card py-4 text-center">
                <div class="stat-number"><?= count($ubicaciones) ?></div>
                <div class="text-muted">Entregas Registradas</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="section-card py-4 text-center">
                <div class="stat-number"><?= count($ciudades) ?></div>
                <div class="text-muted">Ciudades Cubiertas</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="section-card py-4 text-center">
                <div class="stat-number"><?= count($enviados) ?></div>
                <div class="text-muted">Pedidos en Tránsito/Entregados</div>
            </div>
        </div>
    </div>

    <!-- MAPA -->
    <div class="section-card">
        <h4 class="mb-3"><i class="bi bi-map me-2"></i>Mapa de Entregas</h4>
        <?php if (!empty($markers)): ?>
        <div class="mb-3">
            <label class="form-label"><strong>Seleccionar pedido</strong></label>
            <select class="form-select" onchange="mostrarInfo(this.value)">
                <?php foreach ($markers as $i => $m): ?>
                    <option value="<?= $i ?>">
                        Pedido #<?= $m['pedido'] ?> &ndash; <?= $m['ciudad'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div id="map" class="bg-light d-flex align-items-center justify-content-center">
            <span class="text-muted">Selecciona un pedido para ver la ubicación</span>
        </div>
        <div class="mt-3 p-3 bg-light rounded" id="infoPedido"></div>
    </div>

    <!-- ══════════════════════════════════════════════
         DEMO BD
    ══════════════════════════════════════════════ -->
    <div class="section-card" id="demoBD">
        <h4 class="mb-1"><i class="bi bi-database me-2"></i>Demo de Consultas BD</h4>
        <p class="text-muted mb-4">
            Instrucciones implementadas sobre la tabla <code>Ventas</code> usando
            <strong>VISTAS</strong> (R) y <strong>PROCEDIMIENTOS ALMACENADOS</strong> (CUD).
        </p>

        <!-- ── Encabezado de sección: Vistas ── -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-primary fs-6 px-3 py-2">VISTAS</span>
            <span class="text-muted">Consultas de solo lectura — resultados en vivo</span>
        </div>

        <div class="row g-4 mb-5">

            <!-- CARD VISTA 1 -->
            <div class="col-md-6">
                <div class="demo-card h-100">
                    <div class="demo-card-header">
                        <span class="badge-tipo vista">VIEW</span>
                        <h6 class="mb-0">vista_ventas_geolocalizacion</h6>
                    </div>
                    <div class="demo-card-body">
                        <p class="text-muted small mb-3">
                            Retorna todas las ventas con coordenadas GPS, unidas con datos
                            del cliente y del libro. Es la consulta base del mapa de entregas.
                        </p>
                        <div class="sql-block"><span class="kw">SELECT</span> * <span class="kw">FROM</span>
  vista_ventas_geolocalizacion
<span class="kw">ORDER BY</span> fecha_venta <span class="kw">DESC</span>;</div>
                        <button class="btn btn-ejecutar w-100" onclick="ejecutarVista('vista_ventas_geo','res-v1','spin-v1')">
                            <i class="bi bi-play-fill me-1"></i> Ejecutar consulta
                        </button>
                        <div class="spinner-overlay" id="spin-v1"><div class="spinner-border spinner-border-sm"></div> Consultando...</div>
                        <div id="res-v1" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- CARD VISTA 2 -->
            <div class="col-md-6">
                <div class="demo-card h-100">
                    <div class="demo-card-header">
                        <span class="badge-tipo vista">VIEW</span>
                        <h6 class="mb-0">vista_resumen_ventas_por_ciudad</h6>
                    </div>
                    <div class="demo-card-body">
                        <p class="text-muted small mb-3">
                            Agrupa ventas por ciudad con totales, ticket promedio y
                            coordenadas promedio del grupo. Útil para heatmaps o clusters.
                        </p>
                        <div class="sql-block"><span class="kw">SELECT</span> * <span class="kw">FROM</span>
  vista_resumen_ventas_por_ciudad
<span class="kw">ORDER BY</span> total_ventas <span class="kw">DESC</span>;</div>
                        <button class="btn btn-ejecutar w-100" onclick="ejecutarVista('vista_resumen_ciudad','res-v2','spin-v2')">
                            <i class="bi bi-play-fill me-1"></i> Ejecutar consulta
                        </button>
                        <div class="spinner-overlay" id="spin-v2"><div class="spinner-border spinner-border-sm"></div> Consultando...</div>
                        <div id="res-v2" class="mt-3"></div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Encabezado de sección: Procedimientos ── -->
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-success fs-6 px-3 py-2">PROCEDIMIENTOS</span>
            <span class="text-muted">Operaciones CUD — código de implementación</span>
        </div>

        <div class="row g-4">

            <!-- CARD PROC 1: Registrar -->
            <div class="col-md-4">
                <div class="demo-card h-100">
                    <div class="demo-card-header">
                        <span class="badge-tipo proc-c">CREATE</span>
                        <h6 class="mb-0">sp_registrar_venta_con_geo</h6>
                    </div>
                    <div class="demo-card-body">
                        <p class="text-muted small mb-3">
                            Inserta una nueva venta con coordenadas GPS. Valida stock
                            antes de insertar y lo descuenta automáticamente.
                            Retorna el ID generado y un mensaje de resultado.
                        </p>
                        <div class="sql-block"><span class="kw">CALL</span> sp_registrar_venta_con_geo(
  p_id_cliente,
  p_id_libro,
  p_cantidad,
  p_direccion,
  p_ciudad,
  p_estado,
  p_cp,
  p_latitud,
  p_longitud,
  p_referencia,
  p_instrucciones,
  <span class="kw">@</span>p_id_venta,
  <span class="kw">@</span>p_mensaje
);
<span class="kw">SELECT
  @</span>p_id_venta <span class="kw">AS</span> id_venta,
  <span class="kw">@</span>p_mensaje  <span class="kw">AS</span> mensaje;</div>
                        <div class="alert alert-light border small mb-0 mt-2">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Usado en el proceso de checkout al confirmar un pedido con dirección GPS.
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD PROC 2: Actualizar -->
            <div class="col-md-4">
                <div class="demo-card h-100">
                    <div class="demo-card-header">
                        <span class="badge-tipo proc-u">UPDATE</span>
                        <h6 class="mb-0">sp_actualizar_ubicacion_venta</h6>
                    </div>
                    <div class="demo-card-body">
                        <p class="text-muted small mb-3">
                            Actualiza la dirección y coordenadas de una venta existente.
                            Bloquea la edición si el pedido ya fue enviado o entregado.
                        </p>
                        <div class="sql-block"><span class="kw">CALL</span> sp_actualizar_ubicacion_venta(
  p_id_venta,
  p_direccion,
  p_ciudad,
  p_estado,
  p_cp,
  p_latitud,
  p_longitud,
  p_referencia,
  p_instrucciones,
  <span class="kw">@</span>p_resultado
);
<span class="kw">SELECT @</span>p_resultado;</div>
                        <div class="alert alert-light border small mb-0 mt-2">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Usado cuando el servicio de geocodificación resuelve
                            las coordenadas exactas después del registro.
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD PROC 3: Cancelar -->
            <div class="col-md-4">
                <div class="demo-card h-100">
                    <div class="demo-card-header">
                        <span class="badge-tipo proc-d">DELETE lógico</span>
                        <h6 class="mb-0">sp_cancelar_venta</h6>
                    </div>
                    <div class="demo-card-body">
                        <p class="text-muted small mb-3">
                            Cancela una venta cambiando su estado (borrado lógico)
                            y restaura el stock del libro. Conserva el historial
                            de pedidos en la tabla.
                        </p>
                        <div class="sql-block"><span class="kw">CALL</span> sp_cancelar_venta(
  p_id_venta,
  <span class="kw">@</span>p_exito,
  <span class="kw">@</span>p_mensaje
);
<span class="kw">SELECT
  @</span>p_exito   <span class="kw">AS</span> exito,
  <span class="kw">@</span>p_mensaje <span class="kw">AS</span> mensaje;</div>
                        <div class="alert alert-light border small mb-0 mt-2">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Usado en el panel de cliente para cancelar un pedido
                            antes de que sea enviado.
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /row procedimientos -->
    </div><!-- /demoBD -->

    <footer class="text-center text-muted mb-5">
        <small>Biblioteca Digital &copy; <?= date('Y') ?></small>
    </footer>
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ─── MAPA ─────────────────────────────────────── */
const markersData = <?= json_encode($markers) ?>;

function cargarMapa(lat, lng) {
    document.getElementById('map').innerHTML =
        `<iframe width="100%" height="500" style="border:0;border-radius:10px"
            loading="lazy" allowfullscreen
            src="https://www.google.com/maps?q=${lat},${lng}&z=15&output=embed">
         </iframe>`;
}

function mostrarInfo(index) {
    const p = markersData[index];
    cargarMapa(p.lat, p.lng);
    const badges = {
        pendiente:'secondary', procesando:'warning text-dark',
        enviado:'primary', entregado:'success', cancelado:'danger'
    };
    document.getElementById('infoPedido').innerHTML = `
        <h6><strong>Información del Pedido</strong></h6>
        <p class="mb-1"><strong>Pedido:</strong> #${p.pedido}</p>
        <p class="mb-1"><strong>Cliente:</strong> ${p.cliente}</p>
        <p class="mb-1"><strong>Libro:</strong> ${p.title}</p>
        <p class="mb-1"><strong>Dirección:</strong> ${p.direccion}</p>
        <p class="mb-0"><strong>Ciudad:</strong> ${p.ciudad} &nbsp;
            <span class="badge bg-${badges[p.estado_venta]||'secondary'}">${p.estado_venta.toUpperCase()}</span>
        </p>`;
}

window.onload = () => { if (markersData.length > 0) mostrarInfo(0); };

/* ─── DEMO BD ───────────────────────────────────── */
const AJAX_URL = 'ajax_geo_demo.php';

function renderTabla(datos, resId) {
    if (!datos || datos.length === 0) {
        document.getElementById(resId).innerHTML =
            '<div class="alert alert-warning mb-0">Sin resultados.</div>';
        return;
    }
    const cols = Object.keys(datos[0]);
    const badges = { enviado:'primary', entregado:'success', cancelado:'danger',
                     pendiente:'secondary', procesando:'warning text-dark' };
    let html = `<div class="table-responsive">
        <table class="table table-sm table-bordered table-hover table-resultado mb-0">
        <thead><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead><tbody>`;
    datos.forEach(row => {
        html += '<tr>' + cols.map(c => {
            let val = row[c] ?? '&mdash;';
            if (c === 'estado_venta' && badges[val]) {
                val = `<span class="badge bg-${badges[val]}">${String(val).toUpperCase()}</span>`;
            }
            return `<td>${val}</td>`;
        }).join('') + '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById(resId).innerHTML = html;
}

function renderMensaje(ok, datos, resId) {
    const tipo  = ok ? 'success' : 'danger';
    const icono = ok ? '&#10004;' : '&#10008;';
    let parts = [];
    if (datos.mensaje)   parts.push(datos.mensaje);
    if (datos.resultado) parts.push(datos.resultado);
    if (datos.id_venta)  parts.push('ID Venta generada: <strong>' + datos.id_venta + '</strong>');
    if (datos.exito !== undefined) parts.push('Éxito: <strong>' + datos.exito + '</strong>');
    document.getElementById(resId).innerHTML =
        `<div class="alert alert-${tipo} mb-0">${icono} ${parts.join(' &nbsp;|&nbsp; ')}</div>`;
}

function ejecutarVista(accion, resId, spinId) {
    document.getElementById(spinId).style.display = 'block';
    document.getElementById(resId).innerHTML = '';
    fetch(AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'accion=' + accion
    })
    .then(r => r.json())
    .then(json => {
        document.getElementById(spinId).style.display = 'none';
        if (json.ok) renderTabla(json.datos, resId);
        else document.getElementById(resId).innerHTML =
            '<div class="alert alert-danger mb-0">&#10008; ' + (json.datos?.mensaje || 'Error') + '</div>';
    })
    .catch(() => {
        document.getElementById(spinId).style.display = 'none';
        document.getElementById(resId).innerHTML =
            '<div class="alert alert-danger mb-0">&#10008; Error de conexión.</div>';
    });
}
</script>
</body>
</html>