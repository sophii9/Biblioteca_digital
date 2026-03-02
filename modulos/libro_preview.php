<?php
/**
 * get_libro_preview.php
 * 
 * Endpoint que recibe un id_libro por GET y devuelve en JSON
 * los datos necesarios para mostrar la Vista Previa:
 *   - titulo, autor, descripcion, precio, categoria
 *   - imagen_url, imagen2_url, imagen3_url
 *
 * Lo llama el JavaScript del catálogo con fetch()
 * cuando el usuario hace clic en "Vista Previa".
 *
 * Ejemplo de uso:
 *   fetch('libro_preview.php?id=3')
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../config/config.php';

// Validar que llegó el parámetro id y que es un número
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'ID de libro inválido']);
    exit;
}

$id = (int) $_GET['id']; // castear a entero evita inyección SQL

$db = new Conexion($opciones);

// Traer solo las columnas que necesitamos para la vista previa
$query = $db->select("Libros", "id_libro, titulo, autor, descripcion, categoria, precio, imagen_url, imagen2_url, imagen3_url");
$query->where("id_libro", "=", $id);
$resultado = $query->execute();

if (empty($resultado)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Libro no encontrado']);
    exit;
}

$libro = $resultado[0];

// Construir el array de imágenes: solo incluimos las que no están vacías
// así el frontend no muestra imágenes rotas
$imagenes = [];
if (!empty($libro['imagen_url']))  $imagenes[] = $libro['imagen_url'];
if (!empty($libro['imagen2_url'])) $imagenes[] = $libro['imagen2_url'];
if (!empty($libro['imagen3_url'])) $imagenes[] = $libro['imagen3_url'];

echo json_encode([
    'exito'       => true,
    'id'          => $libro['id_libro'],
    'titulo'      => $libro['titulo'],
    'autor'       => $libro['autor'],
    'descripcion' => $libro['descripcion'] ?? 'Sin descripción disponible.',
    'categoria'   => $libro['categoria'],
    'precio'      => $libro['precio'],
    'imagenes'    => $imagenes   // array de 0 a 3 URLs
]);