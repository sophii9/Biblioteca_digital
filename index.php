<?php 
require_once 'config/conexion.php';
// --- CONSULTAS PARA EL DASHBOARD (BDA - Requerimientos) ---

// 1. Operación Matemática: Suma total del valor de libros
$res_suma = mysqli_query($con, "SELECT SUM(precio_reposicion) as total FROM Libros");
$datos_suma = mysqli_fetch_assoc($res_suma);

// 2. Operación de Texto: Títulos en Mayúsculas (Limitado a 5 para el dashboard)
$res_texto = mysqli_query($con, "SELECT UPPER(titulo) as titulo_m FROM Libros LIMIT 5");

// 3. INNER JOIN: Últimos 3 préstamos realizados
$sql_recientes = "SELECT U.nombre, L.titulo FROM Prestamos P 
                  INNER JOIN Usuarios U ON P.id_usuario = U.id_usuario 
                  INNER JOIN Libros L ON P.id_libro = L.id_libro 
                  ORDER BY P.id_prestamo DESC LIMIT 3";
$res_recientes = mysqli_query($con, $sql_recientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Biblioteca Inteligente - AWOS/BDA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .card-menu { transition: transform 0.3s; cursor: pointer; }
        .card-menu:hover { transform: translateY(-5px); }
        .api-badge { font-size: 0.8rem; }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark mb-4 shadow">
        <div class="container">
            <a class="navbar-brand" href="#"> BIBLIOTECA INTELIGENTE <small class="text-muted">| Proyecto Integrador</small></a>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-primary d-flex justify-content-between align-items-center shadow-sm">
                    <span><strong>Reporte de Inventario:</strong> Valor total de los libros registrados:</span>
                    <h4 class="mb-0 text-primary">$<?php echo number_format($datos_suma['total'], 2); ?></h4> <!--echo number_format para formato moneda--
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card h-100 card-menu border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <h1 class="display-6">  </h1>
                        <h5 class="card-title">Libros</h5>
                        <p class="card-text text-muted small">CRUD de inventario, UPPER y SUM.</p>
                        <a href="modulos/crud_libros.php" class="btn btn-outline-primary w-100">Abrir Módulo</a>
                    </div>
                    <div class="card-footer bg-white border-0"><span class="badge bg-secondary api-badge">SQL Avanzado</span></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 card-menu border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <h1 class="display-6">👤</h1>
                        <h5 class="card-title">Usuarios</h5>
                        <p class="card-text text-muted small">Registro de alumnos y Subconsultas.</p>
                        <a href="modulos/crud_usuarios.php" class="btn btn-outline-success w-100">Abrir Módulo</a>
                    </div>
                    <div class="card-footer bg-white border-0"><span class="badge bg-secondary api-badge">SQL Subquery</span></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 card-menu border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <h1 class="display-6">📲</h1>
                        <h5 class="card-title">Préstamos</h5>
                        <p class="card-text text-muted small">INNER JOIN, Fechas y WhatsApp.</p>
                        <a href="modulos/crud_prestamos.php" class="btn btn-outline-warning w-100 text-dark">Abrir Módulo</a>
                    </div>
                    <div class="card-footer bg-white border-0"><span class="badge bg-success api-badge text-white">API Redes Sociales</span></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card h-100 card-menu border-0 shadow-sm text-center p-3">
                    <div class="card-body">
                        <h1 class="display-6">📍</h1>
                        <h5 class="card-title">Ubicaciones</h5>
                        <p class="card-text text-muted small">LEFT JOIN y Google Maps API.</p>
                        <a href="modulos/crud_ubicaciones.php" class="btn btn-outline-info w-100">Abrir Módulo</a>
                    </div>
                    <div class="card-footer bg-white border-0"><span class="badge bg-info api-badge text-white">API Geolocalización</span></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"><strong>Últimos Títulos (Operación de Texto)</strong></div>
                    <ul class="list-group list-group-flush">
                        <?php while($t = mysqli_fetch_assoc($res_texto)): ?>
                            <li class="list-group-item"><?php echo $t['titulo_m']; ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><strong>Actividad Reciente (INNER JOIN)</strong></div>
                    <table class="table mb-0 table-sm">
                        <thead><tr><th>Usuario</th><th>Libro</th></tr></thead>
                        <tbody>
                            <?php while($reciente = mysqli_fetch_assoc($res_recientes)): ?>
                            <tr><td><?php echo $reciente['nombre']; ?></td><td><?php echo $reciente['titulo']; ?></td></tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted mt-5 mb-4">
        <small>Materia: AWOS + BDA | Martes de Entrega</small>
    </footer>

</body>
</html>