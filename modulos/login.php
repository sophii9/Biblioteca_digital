<?php 
session_start();
error_reporting(E_ALL);
require 'bd/conexion_bd.php';

// Redirigir si ya está logueado
if(isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit();
}

$mensaje_error = "";

if(isset($_POST['btnlogin'])) {
    // Validación 1: Campos no vacíos
    if(empty($_POST['txtemail']) || empty($_POST['txtpassword'])) {
        $mensaje_error = "Por favor complete todos los campos";
    }
    // Validación 2: Formato de email válido
    elseif(!filter_var($_POST['txtemail'], FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del email no es válido";
    }
    else {
        $obj = new BD_PDO();
        $usuario = $obj->VerificarLogin($_POST['txtemail'], $_POST['txtpassword']);
        
        if($usuario) {
            // Iniciar sesión
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre_usuario'] = $usuario['Nombre'];
            $_SESSION['email'] = $usuario['Email'];
            $_SESSION['id_tipo'] = $usuario['id_tipo'];
            $_SESSION['tipo_usuario'] = $usuario['TipoUsuario'];
            
            header('Location: index.php');
            exit();
        } else {
            $mensaje_error = "Credenciales incorrectas o usuario inactivo";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca Digital</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
    </style>
    
    <script>
        // Validación 3: JavaScript - Validación en tiempo real
        function validarFormulario() {
            var email = document.getElementById('txtemail').value;
            var password = document.getElementById('txtpassword').value;
            
            if(email.trim() === '' || password.trim() === '') {
                alert('Por favor complete todos los campos');
                return false;
            }
            
            // Validación 4: Longitud mínima de contraseña
            if(password.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>📚 Biblioteca Digital</h1>
            <p>Ingrese sus credenciales para continuar</p>
        </div>
        
        <?php if($mensaje_error != ""): ?>
            <div class="error-message">
                <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php" onsubmit="return validarFormulario()">
            <div class="form-group">
                <label for="txtemail">Correo Electrónico</label>
                <input type="email" id="txtemail" name="txtemail" placeholder="correo@ejemplo.com" required>
            </div>
            
            <div class="form-group">
                <label for="txtpassword">Contraseña</label>
                <input type="password" id="txtpassword" name="txtpassword" placeholder="Ingrese su contraseña" required>
            </div>
            
            <button type="submit" name="btnlogin" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="credentials-info">
            <strong>Credenciales de prueba:</strong>
            Admin: admin@biblioteca.com / admin123<br>
            Visitante: visitante@biblioteca.com / visitante123
        </div>
    </div>
</body>
</html>