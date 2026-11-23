<?php
session_start();
require 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if ($correo === '' || $clave === '') {
        $error = 'Por favor, completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND estado = 1 LIMIT 1");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && $clave === $usuario['clave']) {
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-3">TecnoStock</h3>
        <p class="text-center text-muted mb-4">Control de Inventario</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="formLogin">
            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="correo" id="correo" class="form-control" required placeholder="admin@tecnostock.cl">
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" id="clave" class="form-control" required placeholder="123456">
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>

    <script>
        document.getElementById('formLogin').addEventListener('submit', function(e) {
            const correo = document.getElementById('correo').value.trim();
            const clave = document.getElementById('clave').value.trim();
            if (!correo || !clave) {
                e.preventDefault();
                alert('Debes ingresar tu correo y contraseña.');
            }
        });
    </script>
</body>
</html>