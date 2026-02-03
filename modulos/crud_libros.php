<?php 
require_once '/config/conexion.php';

if(isset($_POST['add'])){
    $t = $_POST['titulo']; $a = $_POST['autor']; $p = $_POST['precio'];
    mysqli_query($con, 
    "INSERT INTO Libros (
    titulo, 
    autor, 
    precio_reposicion
    ) 
    VALUES ('$t', '$a', '$p')");
}
if(isset($_GET['del'])){
    mysqli_query($con, "DELETE FROM Libros WHERE id_libro = ".$_GET['del']);
}

// BDA: Operación Matemática (SUM) y Texto (UPPER)
$res_mate = mysqli_query($con, "SELECT SUM(precio_reposicion) as total FROM Libros");
$total = mysqli_fetch_assoc($res_mate);
$res_libros = mysqli_query($con, "SELECT id_libro, UPPER(titulo) as titulo, autor, precio_reposicion FROM Libros");
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
<body class="container p-4">
    <h3>Inventario de Libros (Valor Total: $<?php echo $total['total']; ?>)</h3>
    <form method="POST" class="row g-2 mb-3">
        <div class="col"><input name="titulo" class="form-control" placeholder="Título"></div>
        <div class="col"><input name="autor" class="form-control" placeholder="Autor"></div>
        <div class="col"><input name="precio" class="form-control" placeholder="Precio"></div>
        <div class="col"><button name="add" class="btn btn-primary">Añadir</button></div>
    </form>
    <table class="table border">
        <?php while($l = mysqli_fetch_assoc($res_libros)): ?>
        <tr><td><?php echo $l['titulo']; ?></td><td>$<?php echo $l['precio_reposicion']; ?></td>
        <td><a href="?del=<?php echo $l['id_libro']; ?>" class="btn btn-danger btn-sm">X</a></td></tr>
        <?php endwhile; ?>
    </table>
</body>
</html>