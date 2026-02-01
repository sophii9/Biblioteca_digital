<?php 
require_once '../config/conexion.php';
if(isset($_POST['add'])){
    $p = $_POST['p']; $lat = $_POST['lat']; $lon = $_POST['lon'];
    mysqli_query($con, "INSERT INTO Ubicaciones (id_prestamo, latitud, longitud) VALUES ('$p', '$lat', '$lon')");
}

// BDA: LEFT JOIN (Ver préstamos aunque no tengan coordenadas aún)
$sql = "SELECT P.id_prestamo, Ub.latitud, Ub.longitud FROM Prestamos P LEFT JOIN Ubicaciones Ub ON P.id_prestamo = Ub.id_prestamo";
$res = mysqli_query($con, $sql);
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
<body class="container p-4 text-center">
    <h3>Ubicaciones (Geolocalización)</h3>
    <form method="POST" class="row g-2 mb-3">
        <input name="p" placeholder="ID Préstamo">
        <input name="lat" placeholder="Latitud">
        <input name="lon" placeholder="Longitud">
        <button name="add" class="btn btn-info">Guardar Coordenadas</button>
    </form>
    <?php while($m = mysqli_fetch_assoc($res)): if($m['latitud']): ?>
        <iframe width="100%" height="200" src="https://maps.google.com/maps?q=<?php echo $m['latitud']; ?>,<?php echo $m['longitud']; ?>&output=embed"></iframe>
    <?php endif; endwhile; ?>
</body>
</html>