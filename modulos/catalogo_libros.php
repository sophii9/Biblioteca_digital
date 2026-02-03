<?php 
error_reporting(E_ALL);

require '/config/conexion.php';

$obj = new BD_PDO();

$buscar = isset($_POST['txtbuscar']) ? $_POST['txtbuscar'] : '';
$filtro_genero = isset($_POST['filtro_genero']) ? $_POST['filtro_genero'] : '';

$sql = "SELECT l.id_libro, l.Titulo, l.ISBN, l.Año_Publicacion, l.Cantidad_Disponible, l.Precio,
               a.Nombre as Autor, e.Nombre as Editorial, g.Nombre as Genero, a.Nacionalidad
        FROM libros l
        INNER JOIN autores a ON l.id_autor = a.id_autor
        INNER JOIN editoriales e ON l.id_editorial = e.id_editorial
        INNER JOIN generos_literarios g ON l.id_genero = g.id_genero
        WHERE l.Titulo LIKE ?";

$params = ['%'.$buscar.'%'];

if($filtro_genero != '') {
    $sql .= " AND l.id_genero = ?";
    $params[] = $filtro_genero;
}

$sql .= " ORDER BY l.Titulo";

$datos = $obj->Ejecutar_Seguro($sql, $params);
$lista_generos = $obj->Listado("SELECT id_genero, Nombre FROM generos_literarios ORDER BY Nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Libros</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <nav class="navbar">
        <h1>Catálogo de Libros</h1>
        <a href="index.php" class="btn-back">← Volver al menú</a>
    </nav>
    
    <div class="container">
        <div class="header-section">
            <h1>Explora Nuestro Catálogo</h1>
            <p>Descubre nuestra colección de libros disponibles</p>
        </div>
        
        <div class="filters">
            <form method="post" action="catalogo_libros.php">
                <div class="filter-row">
                    <input type="text" name="txtbuscar" placeholder="Buscar por título..." 
                           value="<?php echo htmlspecialchars($buscar); ?>">
                    
                    <select name="filtro_genero">
                        <option value="">Todos los géneros</option>
                        <?php echo $lista_generos; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>
        </div>
        
        <?php if($datos && count($datos) > 0): ?>
            <div class="books-grid">
                <?php foreach($datos as $libro): ?>
                    <div class="book-card">
                        <div class="title"><?php echo htmlspecialchars($libro['Titulo']); ?></div>
                        <div class="author">por <?php echo htmlspecialchars($libro['Autor']); ?></div>
                        
                        <div class="book-info">
                            <div><span class="label">Género:</span> <?php echo $libro['Genero']; ?></div>
                            <div><span class="label">Año:</span> <?php echo $libro['Año_Publicacion']; ?></div>
                            <div><span class="label">Editorial:</span> <?php echo $libro['Editorial']; ?></div>
                            <div><span class="label">ISBN:</span> <?php echo $libro['ISBN'] ?: 'N/A'; ?></div>
                            <div><span class="label">Nacionalidad:</span> <?php echo $libro['Nacionalidad']; ?></div>
                        </div>
                        
                        <?php 
                        $cantidad = $libro['Cantidad_Disponible'];
                        if($cantidad > 10) {
                            echo '<span class="stock-badge stock-available">Disponible ('.$cantidad.' unidades)</span>';
                        } elseif($cantidad > 0) {
                            echo '<span class="stock-badge stock-low">Pocas unidades ('.$cantidad.')</span>';
                        } else {
                            echo '<span class="stock-badge stock-out">Agotado</span>';
                        }
                        ?>
                        
                        <div class="book-price">
                            $<?php echo number_format($libro['Precio'], 2); ?> MXN
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <div class="icon">📭</div>
                <h2>No se encontraron libros</h2>
                <p>Intenta con otros términos de búsqueda</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>