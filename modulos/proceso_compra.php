<?php
session_start();
require_once '../config/config.php';

if(!isset($_SESSION['id_cliente'])) {
    header('Location: ../login.php');
    exit();
}

$id_libro = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id_libro <= 0) {
    header('Location: catalogo_libros.php');
    exit();
}

$db = new Conexion($opciones);

// Obtener información del libro
$query = $db->select("Libros", "*");
$query->where("id_libro", "=", $id_libro);
$libros = $query->execute();

if(empty($libros)) {
    header('Location: catalogo_libros.php');
    exit();
}

$libro = $libros[0];

// Verificar disponibilidad
if($libro['cantidad_disponible'] <= 0) {
    header('Location: catalogo_libros.php?error=agotado');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso de Compra - Biblioteca Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f5f5;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .checkout-container {
            max-width: 1200px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        .order-summary {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .step-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 10px;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qty-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            font-size: 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .qty-btn:hover {
            background: #667eea;
            color: white;
        }
        .qty-input {
            width: 80px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .summary-total {
            border-top: 2px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.2rem;
        }
        #map {
            height: 350px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: relative;
                top: 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <strong>Biblioteca Digital</strong>
            </a>
            <div>
                <span class="me-3">👤 <?php echo htmlspecialchars($_SESSION['nombre_cliente']); ?></span>
                <a href="catalogo_libros.php" class="btn btn-sm btn-outline-primary">← Volver al catálogo</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="checkout-container">
            <!-- Resumen del pedido -->
            <div class="order-summary">
                <h4 class="mb-4">📦 Resumen de tu compra</h4>
                
                <div class="text-center mb-4">
                    <h5 class="mt-3"><?php echo htmlspecialchars($libro['titulo']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($libro['autor']); ?></p>
                </div>
                
                <div class="summary-details">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cantidad:</span>
                        <strong id="cantidad-display">1</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Precio unitario:</span>
                        <span>$<?php echo number_format($libro['precio'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between summary-total">
                        <span><strong>Total:</strong></span>
                        <span><strong id="total-display" class="text-primary">$<?php echo number_format($libro['precio'], 2); ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- Formulario de compra -->
            <div class="checkout-form">
                <h2 class="mb-4">Finalizar Compra</h2>
                
                <form id="formCompra" method="post">
                    <!-- Paso 1: Cantidad -->
                    <div class="form-section active" data-step="1">
                        <h4 class="mb-4"><span class="step-number">1</span> Selecciona la cantidad</h4>
                        
                        <div class="mb-4">
                            <label class="form-label">Cantidad</label>
                            <div class="quantity-selector">
                                <button type="button" class="qty-btn" onclick="cambiarCantidad(-1)">−</button>
                                <input type="number" id="cantidad" name="cantidad" value="1" 
                                       min="1" max="<?php echo $libro['cantidad_disponible']; ?>" 
                                       readonly class="qty-input">
                                <button type="button" class="qty-btn" onclick="cambiarCantidad(1)">+</button>
                            </div>
                            <small class="text-muted">Disponibles: <?php echo $libro['cantidad_disponible']; ?> unidades</small>
                        </div>
                        
                        <button type="button" class="btn btn-primary" onclick="siguientePaso(2)">
                            Continuar →
                        </button>
                    </div>

                    <!-- Paso 2: Datos de envío -->
                    <div class="form-section" data-step="2">
                        <h4 class="mb-4"><span class="step-number">2</span> Dirección de envío</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Dirección completa *</label>
                            <input type="text" id="direccion" name="direccion" class="form-control" required
                                   placeholder="Calle, número, colonia">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Ciudad *</label>
                                <input type="text" id="ciudad" name="ciudad" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Estado *</label>
                                <input type="text" id="estado" name="estado" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Código Postal *</label>
                                <input type="text" id="codigo_postal" name="codigo_postal" class="form-control"
                                       pattern="[0-9]{5}" required placeholder="12345">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Referencia visual</label>
                            <textarea id="referencia" name="referencia" class="form-control" rows="2"
                                      placeholder="Casa blanca con reja negra, junto a la tienda..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Instrucciones de entrega</label>
                            <textarea id="instrucciones" name="instrucciones" class="form-control" rows="2"
                                      placeholder="Tocar el timbre, dejar con el portero..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="usar_ubicacion_actual" class="form-check-input">
                                <label class="form-check-label" for="usar_ubicacion_actual">
                                    Usar mi ubicación actual
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div id="map"></div>
                        </div>
                        
                        <input type="hidden" id="latitud" name="latitud">
                        <input type="hidden" id="longitud" name="longitud">
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" onclick="siguientePaso(1)">
                                ← Anterior
                            </button>
                            <button type="button" class="btn btn-primary flex-grow-1" onclick="siguientePaso(3)">
                                Continuar →
                            </button>
                        </div>
                    </div>

                    <!-- Paso 3: Método de pago -->
                    <div class="form-section" data-step="3">
                        <h4 class="mb-4"><span class="step-number">3</span> Método de pago</h4>
                        
                        <div class="mb-3">
                            <label class="form-label">Selecciona método de pago *</label>
                            <select id="metodo_pago" name="metodo_pago" class="form-select" required>
                                <option value="">Selecciona...</option>
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="paypal">PayPal</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                            </select>
                        </div>
                        
                        <div id="datos_tarjeta" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Número de tarjeta *</label>
                                <input type="text" id="numero_tarjeta" name="numero_tarjeta" class="form-control"
                                       pattern="[0-9]{16}" maxlength="16" placeholder="1234 5678 9012 3456">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre del titular *</label>
                                <input type="text" id="nombre_titular" name="nombre_titular" class="form-control"
                                       placeholder="Como aparece en la tarjeta">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Fecha de expiración *</label>
                                    <input type="text" id="fecha_exp" name="fecha_exp" class="form-control"
                                           pattern="[0-9]{2}/[0-9]{2}" placeholder="MM/AA">
                                </div>
                                
                                <div class="col-6">
                                    <label class="form-label">CVV *</label>
                                    <input type="text" id="cvv" name="cvv" class="form-control"
                                           pattern="[0-9]{3,4}" maxlength="4" placeholder="123">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            Tus datos de pago están protegidos
                        </div>
                        
                        <input type="hidden" name="id_libro" value="<?php echo $id_libro; ?>">
                        <input type="hidden" name="id_cliente" value="<?php echo $_SESSION['id_cliente']; ?>">
                        
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" onclick="siguientePaso(2)">
                                ← Anterior
                            </button>
                            <button type="submit" class="btn btn-success flex-grow-1" id="btnFinalizar">
                                ✓ Finalizar Compra
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const precioUnitario = <?php echo $libro['precio']; ?>;
        const stockMax = <?php echo $libro['cantidad_disponible']; ?>;
        let currentLat = 27.4827;
        let currentLng = -99.5070;

        function cargarMapa(lat, lng) {
            document.getElementById("map").innerHTML = `
                <iframe
                    width="100%"
                    height="600"
                    style="border:0; border-radius:12px"
                    loading="lazy"
                    allowfullscreen
                    src="https://www.google.com/maps?q=${lat},${lng}&z=15&output=embed">
                </iframe>
            `;
        }

        cargarMapa(currentLat, currentLng);

        function cambiarCantidad(delta) {
            const input = document.getElementById('cantidad');
            let valor = parseInt(input.value) + delta;
            
            if(valor < 1) valor = 1;
            if(valor > stockMax) valor = stockMax;
            
            input.value = valor;
            actualizarTotal();
        }

        function actualizarTotal() {
            const cantidad = parseInt(document.getElementById('cantidad').value);
            const total = precioUnitario * cantidad;
            
            document.getElementById('cantidad-display').textContent = cantidad;
            document.getElementById('total-display').textContent = '$' + total.toFixed(2);
        }

        function siguientePaso(paso) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.querySelector(`[data-step="${paso}"]`).classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.getElementById('usar_ubicacion_actual').addEventListener('change', function() {
            if(this.checked) {
                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        cargarMapa(position.coords.latitude, position.coords.longitude);
                    }, function(error) {
                        alert('No se pudo obtener tu ubicación');
                    });
                }
            } else {
                cargarMapa(27.4827, -99.5070);
            }
        });

        document.getElementById('metodo_pago').addEventListener('change', function() {
            const datosTarjeta = document.getElementById('datos_tarjeta');
            const requiereTarjeta = ['tarjeta_credito', 'tarjeta_debito'].includes(this.value);
            
            if(requiereTarjeta) {
                datosTarjeta.style.display = 'block';
                datosTarjeta.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                datosTarjeta.style.display = 'none';
                datosTarjeta.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
        });

        document.getElementById('formCompra').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btnFinalizar = document.getElementById('btnFinalizar');
            btnFinalizar.disabled = true;
            btnFinalizar.textContent = 'Procesando...';
            
            const formData = new FormData(this);
            
            fetch('procesar_compra.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    window.location.href = 'confirmacion.php?id=' + data.id_venta;
                } else {
                    alert('Error: ' + data.error);
                    btnFinalizar.disabled = false;
                    btnFinalizar.textContent = '✓ Finalizar Compra';
                }
            })
            .catch(error => {
                alert('Error al procesar la compra');
                btnFinalizar.disabled = false;
                btnFinalizar.textContent = '✓ Finalizar Compra';
            });
        });
    </script>
</body>
</html>