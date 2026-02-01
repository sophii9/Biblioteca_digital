<?php 
require_once '../config/conexion.php';
if(isset($_POST['add'])){
    $l = $_POST['l']; $u = $_POST['u']; $f = $_POST['f'];
    mysqli_query($con, "INSERT INTO Prestamos (id_libro, id_usuario, fecha_salida, fecha_limite) VALUES ('$l', '$u', CURDATE(), '$f')");
}

// BDA: INNER JOIN y DATEDIFF (Fechas)
$sql = "SELECT P.id_prestamo, U.nombre, U.telefono, L.titulo, DATEDIFF(CURDATE(), P.fecha_limite) as mora 
        FROM Prestamos P 
        INNER JOIN Usuarios U ON P.id_usuario = U.id_usuario 
        INNER JOIN Libros L ON P.id_libro = L.id_libro";
$res = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
<body class="container p-4">
    <h3>Préstamos y Alertas WhatsApp</h3>
    <form method="POST" class="row g-2 mb-3">
        <div class="col"><input name="l" placeholder="ID Libro"></div>
        <div class="col"><input name="u" placeholder="ID Usuario"></div>
        <div class="col"><input type="date" name="f"></div>
        <div class="col"><button name="add" class="btn btn-warning">Prestar</button></div>
    </form>
    <table class="table">
        <?php while($row = mysqli_fetch_assoc($res)): ?>
        <tr><td><?php echo $row['nombre']; ?></td><td><?php echo $row['titulo']; ?></td>
        <td><?php echo ($row['mora'] > 0) ? "Mora: ".$row['mora'] : "OK"; ?></td>
        <td><a href="https://wa.me/<?php echo $row['telefono']; ?>?text=Devuelve:<?php echo $row['titulo']; ?>" class="btn btn-success btn-sm">Avisar</a></td></tr>
        <?php endwhile; ?>
    </table>
</body>
</html>