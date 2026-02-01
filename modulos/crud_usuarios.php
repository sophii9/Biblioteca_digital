<?php 
require_once '../config/conexion.php';if(isset($_POST['add'])){
    $n = $_POST['nombre']; $t = $_POST['tel'];
    mysqli_query($con, "INSERT INTO Usuarios (nombre, telefono) VALUES ('$n', '$t')");
}

// BDA: Subconsulta (Libros que NO han sido prestados)
$sql_sub = "SELECT titulo FROM Libros WHERE id_libro NOT IN (SELECT id_libro FROM Prestamos)";
$res_sub = mysqli_query($con, $sql_sub);
$res_user = mysqli_query($con, "SELECT * FROM Usuarios");
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
<body class="container p-4">
    <h3>Usuarios y Libros Libres (Subconsulta)</h3>
    <form method="POST" class="row g-2 mb-3">
        <div class="col"><input name="nombre" class="form-control" placeholder="Nombre"></div>
        <div class="col"><input name="tel" class="form-control" placeholder="WhatsApp (Ej: 521...)"></div>
        <div class="col"><button name="add" class="btn btn-success">Registrar</button></div>
    </form>
    <h5>Libros nunca prestados:</h5>
    <ul><?php while($s = mysqli_fetch_assoc($res_sub)) echo "<li>".$s['titulo']."</li>"; ?></ul>
</body>
</html>