<?php
/**
 * Recibe hasta 3 imágenes de demostración para la Vista Previa de un libro.
 * Las guarda en la carpeta uploads/previews/ y devuelve las URLs en JSON.
 *
 * CÓMO FUNCIONA:
 * 1. El formulario del modal envía las imágenes aquí vía fetch (AJAX)
 * 2. Este archivo las guarda en el servidor
 * 3. Devuelve un JSON con las URLs de las imágenes guardadas
 * 4. El JavaScript usa esas URLs para construir el mensaje del WebSocket
 */

header('Content-Type: application/json');

// Carpeta donde se guardarán las imágenes
$carpeta_destino = __DIR__ . '/../uploads/previews/';

// Tipos de imagen permitidos
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$urls = [];    // aquí guardaremos las URLs de las imágenes subidas
$errores = []; // aquí guardaremos mensajes de error si algo falla

// $_FILES['imagenes'] contiene el array de imágenes subidas
// Solo procesamos si se enviaron archivos
if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {

    // Recorrer cada imagen (máximo 3)
    $total = min(count($_FILES['imagenes']['name']), 3);

    for ($i = 0; $i < $total; $i++) {
        // Verificar que no hubo error al subir
        if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir imagen " . ($i + 1);
            continue; // saltar al siguiente
        }

        // Verificar que el tipo sea una imagen permitida
        $tipo_mime = $_FILES['imagenes']['type'][$i];
        if (!in_array($tipo_mime, $tipos_permitidos)) {
            $errores[] = "La imagen " . ($i + 1) . " no es un tipo de imagen válido";
            continue;
        }

        // Generar un nombre único para no sobreescribir archivos
        // uniqid() genera un ID único basado en la hora actual
        $extension = pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION);
        $nombre_unico = uniqid('preview_', true) . '.' . $extension;
        $ruta_completa = $carpeta_destino . $nombre_unico;

        // Mover el archivo temporal al destino
        if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $ruta_completa)) {
            // Construir la URL pública de la imagen
            // Detectamos la URL base del servidor para que funcione en cualquier máquina
            $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            // Obtenemos la ruta relativa desde la raíz web
            // Asumimos que el proyecto está en /Biblioteca_digital/
            $ruta_relativa = '/Biblioteca_digital/uploads/previews/' . $nombre_unico;
            $url = $protocolo . '://' . $host . $ruta_relativa;

            $urls[] = $url;
        } else {
            $errores[] = "No se pudo guardar la imagen " . ($i + 1);
        }
    }
} 

// Responder con JSON
echo json_encode([
    'exito'   => empty($errores),
    'urls'    => $urls,
    'errores' => $errores
]);