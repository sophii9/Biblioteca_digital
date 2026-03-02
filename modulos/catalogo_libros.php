<?php 
session_start();
require_once '../config/config.php';

$db = new Conexion($opciones);

$buscar    = isset($_POST['txtbuscar'])  ? $_POST['txtbuscar']  : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria']  : '';

$query = $db->select("Libros", "*");
$query->where("cantidad_disponible", ">", 0);
if ($buscar    != '') $query->where_and("titulo",    "LIKE", "%$buscar%");
if ($categoria != '') $query->where_and("categoria", "=",   $categoria);
$query->orderby("titulo ASC");
$libros = $query->execute();

$query_cat = $db->select("Libros", "DISTINCT categoria");
$query_cat->orderby("categoria ASC");
$categorias = $query_cat->execute();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Libros - Biblioteca Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

        .book-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
        }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .book-title  { font-size: 1.1rem; font-weight: bold; color: #333; margin-bottom: 6px; min-height: 48px; }
        .book-author { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
        .book-info   { font-size: 0.82rem; color: #777; margin-bottom: 10px; }
        .book-price  { font-size: 1.4rem; font-weight: bold; color: #667eea; margin: 12px 0; }
        .stock-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; margin-bottom: 10px; }
        .stock-available { background: #d4edda; color: #155724; }
        .stock-low       { background: #fff3cd; color: #856404; }

        .filters        { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header-section { text-align: center; padding: 40px 0; }
        .header-section h1 { font-size: 2.5rem; font-weight: bold; color: #667eea; }


        /* Galería de imágenes del libro */
        #galeria-imagenes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        #galeria-imagenes img {
            width: 120px;
            height: 120px;
            object-fit: cover;   /* recorta sin deformar */
            border-radius: 8px;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        #galeria-imagenes img:hover { border-color: #667eea; }

        /* Imagen ampliada al hacer clic en una miniatura */
        #imagen-ampliada {
            width: 100%;
            max-height: 260px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            margin-bottom: 14px;
            display: none;       /* oculta hasta que el usuario haga clic */
        }

        /* Sin imágenes: mensaje placeholder */
        #sin-imagenes {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            color: #adb5bd;
            margin-bottom: 14px;
            display: none;
        }

        /*CHAT*/
        #div-chat {
            height: 220px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 8px;
        }
        .burbuja {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 7px 12px;
            margin-bottom: 7px;
            font-size: 0.88rem;
        }
        .burbuja .nombre { font-weight: bold; color: #667eea; font-size: 0.78rem; }

        /* Píldora de estado WebSocket */
        #estado-ws {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .ws-ok  { background: #d4edda; color: #155724; }
        .ws-off { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<nav class="navbar navbar-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><strong>Biblioteca Digital</strong></a>
        <div>
            <?php if(isset($_SESSION['id_cliente'])): ?>
                <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Cerrar Sesión</a>
            <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="header-section">
        <h1>Catálogo de Libros</h1>
        <p class="lead">Explora nuestra colección de libros disponibles</p>
    </div>

    <div class="filters">
        <form method="post" action="catalogo_libros.php">
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="txtbuscar" class="form-control"
                           placeholder="Buscar por título..."
                           value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="col-md-4">
                    <select name="categoria" class="form-select">
                        <option value="">Todas las categorías</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['categoria']); ?>"
                                <?php echo ($categoria == $cat['categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </div>
            </div>
        </form>
    </div>


    <?php if(!empty($libros)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
            <?php foreach($libros as $libro): ?>
                <div class="col">
                    <div class="book-card">
                        <div class="book-title"><?php echo htmlspecialchars($libro['titulo']); ?></div>
                        <div class="book-author">por <?php echo htmlspecialchars($libro['autor']); ?></div>

                        <div class="book-info">
                            <div><strong>Categoría:</strong> <?php echo htmlspecialchars($libro['categoria']); ?></div>
                            <div><strong>Año:</strong> <?php echo $libro['anio_publicacion'] ?? 'N/A'; ?></div>
                            <?php if(!empty($libro['isbn'])): ?>
                                <div><strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn']); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php $cantidad = $libro['cantidad_disponible']; ?>
                        <?php if($cantidad > 10): ?>
                            <span class="stock-badge stock-available">✓ Disponible (<?php echo $cantidad; ?> uds.)</span>
                        <?php else: ?>
                            <span class="stock-badge stock-low">Pocas unidades (<?php echo $cantidad; ?>)</span>
                        <?php endif; ?>

                        <div class="book-price">$<?php echo number_format($libro['precio'], 2); ?> MXN</div>

                        <div class="d-flex gap-2">
                            <?php if(isset($_SESSION['id_cliente'])): ?>
                                <a href="proceso_compra.php?id=<?php echo $libro['id_libro']; ?>"
                                   class="btn btn-success flex-fill">🛒 Comprar</a>
                            <?php else: ?>
                                <a href="../login.php" class="btn btn-outline-primary flex-fill">Iniciar sesión</a>
                            <?php endif; ?>

                            <!--
                                Botón Vista Previa
                                ─────────────────
                                data-bs-toggle / data-bs-target → abren el modal de Bootstrap
                                onclick → llama a abrirVistaPrevia() con el id del libro
                                          para que el JS busque los datos en la BD
                            -->
                            <button class="btn btn-outline-info flex-fill"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalVistaPrevia"
                                    onclick="abrirVistaPrevia(<?php echo $libro['id_libro']; ?>)">
                                👁️ Vista Previa
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <h3>No se encontraron libros</h3>
            <p class="text-muted">Intenta con otros términos de búsqueda</p>
            <a href="catalogo_libros.php" class="btn btn-primary mt-3">Ver todos los libros</a>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center text-muted mt-5 pb-4">
    <small>Biblioteca Digital © <?php echo date('Y'); ?></small>
</footer>


<div class="modal fade" id="modalVistaPrevia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header" style="background:#667eea; color:white;">
                <h5 class="modal-title">
                    👁️ Vista Previa
                    <span id="spinner-carga" class="spinner-border spinner-border-sm ms-2" style="display:none;"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div id="datos-libro">

                    <!-- Título y autor -->
                    <h4 id="modal-titulo" class="mb-0" style="color:#333;"></h4>
                    <p  id="modal-autor"  class="text-muted mb-3"></p>

                    <!--
                        Imagen ampliada (aparece cuando el usuario hace clic
                        en una miniatura de la galería)
                    -->
                    <img id="imagen-ampliada" src="" alt="Imagen ampliada del libro">

                    <!--
                        Galería de miniaturas
                        Las <img> se crean dinámicamente en JS según cuántas
                        URLs haya devuelto libro_preview.php
                    -->
                    <div id="galeria-imagenes"></div>

                    <!-- Placeholder visible cuando el libro no tiene imágenes -->
                    <div id="sin-imagenes">📷 Este libro aún no tiene imágenes de demostración</div>

                    <!-- Descripción y precio -->
                    <p  id="modal-descripcion" class="mb-2" style="font-size:0.95rem;"></p>
                    <p  id="modal-precio"      class="fw-bold" style="color:#667eea; font-size:1.2rem;"></p>

                    <hr>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>💬 Preguntas en vivo sobre este libro</strong>
                    <span id="estado-ws" class="ws-off">⚫ Sin conexión</span>
                </div>
                <p class="text-muted" style="font-size:0.82rem;">
                    Pregunta dudas sobre el libro: disponibilidad, contenido, nivel recomendado...
                </p>

                <!-- Área donde aparecen los mensajes -->
                <div id="div-chat"></div>

                <!-- Formulario de mensaje -->
                <form id="frmChat">
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm"
                               id="inputNombre" placeholder="Tu nombre"
                               value="<?php echo isset($_SESSION['nombre_cliente']) ? htmlspecialchars($_SESSION['nombre_cliente']) : ''; ?>">
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="inputMensaje"
                               placeholder="Escribe tu pregunta o comentario...">
                        <button class="btn btn-primary" type="submit">Enviar</button>
                    </div>
                </form>

            </div><!-- fin modal-body -->
        </div>
    </div>
</div><!-- fin modal -->


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ================================================================
//  VARIABLES GLOBALES
// ================================================================
var wsConexion  = null;   // instancia del WebSocket
var idLibroActual = null; // id del libro que está abierto en el modal

// ================================================================
//  FUNCIÓN PRINCIPAL: se llama al hacer clic en "Vista Previa"
//
//  Recibe el id_libro del libro correspondiente (viene del PHP)
//  y hace dos cosas en paralelo:
//    1. Pide los datos del libro a get_libro_preview.php (fetch)
//    2. Conecta el WebSocket si no está conectado
// ================================================================
function abrirVistaPrevia(idLibro) {
    idLibroActual = idLibro;

    // Limpiar el modal antes de cargar el nuevo libro
    limpiarModal();

    // Mostrar spinner mientras carga
    document.getElementById('spinner-carga').style.display = 'inline-block';


    fetch('libro_preview.php?id=' + idLibro)
        .then(function(respuesta) {
            // .json() convierte el texto de la respuesta a un objeto JS
            return respuesta.json();
        })
        .then(function(datos) {
            document.getElementById('spinner-carga').style.display = 'none';

            if (!datos.exito) {
                document.getElementById('modal-titulo').textContent = 'Error al cargar el libro';
                return;
            }

            document.getElementById('modal-titulo').textContent      = datos.titulo;
            document.getElementById('modal-autor').textContent       = 'por ' + datos.autor;
            document.getElementById('modal-descripcion').textContent = datos.descripcion;
            document.getElementById('modal-precio').textContent      = '$' + parseFloat(datos.precio).toFixed(2) + ' MXN';

            cargarGaleria(datos.imagenes);
        })
        .catch(function(error) {
            // Si la petición falló (servidor caído, red, etc.)
            document.getElementById('spinner-carga').style.display = 'none';
            document.getElementById('modal-titulo').textContent = 'No se pudo cargar el libro';
            console.error('Error fetch:', error);
        });

    if (wsConexion === null || wsConexion.readyState === WebSocket.CLOSED) {
        conectarWebSocket();
    }
}

function cargarGaleria(imagenes) {
    var galeria    = document.getElementById('galeria-imagenes');
    var sinImg     = document.getElementById('sin-imagenes');
    var imgAmpliada = document.getElementById('imagen-ampliada');

    galeria.innerHTML = ''; // limpiar miniaturas anteriores

    if (!imagenes || imagenes.length === 0) {
        // Sin imágenes: mostrar placeholder
        sinImg.style.display    = 'block';
        galeria.style.display   = 'none';
        return;
    }

    sinImg.style.display  = 'none';
    galeria.style.display = 'flex';

    // Mostrar la primera imagen ya ampliada por defecto
    imgAmpliada.src           = imagenes[0];
    imgAmpliada.style.display = 'block';

    // Crear una miniatura por cada URL
    imagenes.forEach(function(url, indice) {
        var img = document.createElement('img');
        img.src   = url;
        img.alt   = 'Imagen ' + (indice + 1) + ' del libro';
        img.title = 'Clic para ampliar';

        // Al hacer clic en la miniatura, se muestra ampliada
        img.addEventListener('click', function() {
            imgAmpliada.src           = url;
            imgAmpliada.style.display = 'block';
            // Scroll suave hacia arriba del modal-body para ver la imagen
            imgAmpliada.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        galeria.appendChild(img);
    });
}

// ================================================================
//  WEBSOCKET
// ================================================================
function conectarWebSocket() {
    wsConexion = new WebSocket('ws://localhost:8080/chat');

    wsConexion.onopen = function() {
        document.getElementById('estado-ws').textContent  = '🟢 Conectado';
        document.getElementById('estado-ws').className    = 'estado-ws ws-ok';
    };

    wsConexion.onmessage = function(e) {
        // Cada mensaje que llega es un JSON con { usuario, mensaje }
        var datos = JSON.parse(e.data);
        mostrarMensaje(datos.usuario, datos.mensaje);
    };

    wsConexion.onclose = function() {
        document.getElementById('estado-ws').textContent = '⚫ Sin conexión';
        document.getElementById('estado-ws').className   = 'estado-ws ws-off';
        wsConexion = null;
    };

    wsConexion.onerror = function() {
        document.getElementById('estado-ws').textContent = '🔴 Error';
        document.getElementById('estado-ws').className   = 'estado-ws ws-off';
    };
}

// ================================================================
//  MOSTRAR UN MENSAJE EN EL CHAT
// ================================================================
function mostrarMensaje(usuario, mensaje) {
    var chat   = document.getElementById('div-chat');
    var div    = document.createElement('div');
    div.className = 'burbuja';
    div.innerHTML = '<span class="nombre">' + (usuario || 'Anónimo') + '</span><br>' + mensaje;
    chat.appendChild(div);
    // Scroll al último mensaje
    chat.scrollTop = chat.scrollHeight;
}

// ================================================================
//  ENVÍO DE MENSAJES DE CHAT
// ================================================================
document.getElementById('frmChat').addEventListener('submit', function(e) {
    e.preventDefault();

    var inputMensaje = document.getElementById('inputMensaje');
    var texto = inputMensaje.value.trim();

    if (!texto) return;

    if (!wsConexion || wsConexion.readyState !== WebSocket.OPEN) {
        alert('Sin conexión al chat. Espera un momento o recarga.');
        return;
    }

    var usuario = document.getElementById('inputNombre').value || 'Anónimo';

    // Enviar el mensaje como JSON al servidor WebSocket
    wsConexion.send(JSON.stringify({ usuario: usuario, mensaje: texto }));

    // También mostrarlo localmente (el WebSocket no te lo devuelve a ti)
    mostrarMensaje('Tú', texto);

    inputMensaje.value = '';
});

// ================================================================
//  LIMPIAR EL MODAL antes de cargar un libro diferente
// ================================================================
function limpiarModal() {
    document.getElementById('modal-titulo').textContent      = '';
    document.getElementById('modal-autor').textContent       = '';
    document.getElementById('modal-descripcion').textContent = '';
    document.getElementById('modal-precio').textContent      = '';
    document.getElementById('galeria-imagenes').innerHTML    = '';
    document.getElementById('imagen-ampliada').style.display = 'none';
    document.getElementById('sin-imagenes').style.display   = 'none';
    document.getElementById('div-chat').innerHTML            = '';
}

// Cerrar WebSocket cuando se cierra el modal
document.getElementById('modalVistaPrevia').addEventListener('hidden.bs.modal', function() {
    if (wsConexion) wsConexion.close();
});
</script>

</body>
</html>