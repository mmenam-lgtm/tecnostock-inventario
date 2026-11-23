<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $id_usuario = $_SESSION['id_usuario'];

    if ($cantidad <= 0) {
        $error = 'La cantidad debe ser mayor a cero.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT stock_actual FROM productos WHERE id_producto = ? FOR UPDATE");
            $stmt->execute([$id_producto]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prod) throw new Exception('Producto no encontrado.');

            $nuevo_stock = $prod['stock_actual'];
            if ($tipo === 'salida') {
                if ($cantidad > $nuevo_stock) {
                    throw new Exception('Operación rechazada: la salida dejaría el stock bajo cero.');
                }
                $nuevo_stock -= $cantidad;
            } elseif ($tipo === 'entrada') {
                $nuevo_stock += $cantidad;
            } else {
                throw new Exception('Tipo de movimiento inválido.');
            }

            $stmtMov = $pdo->prepare("INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmtMov->execute([$tipo, $cantidad, $observacion, $id_producto, $id_usuario]);

            $stmtUpd = $pdo->prepare("UPDATE productos SET stock_actual = ? WHERE id_producto = ?");
            $stmtUpd->execute([$nuevo_stock, $id_producto]);

            $pdo->commit();
            $mensaje = 'Movimiento registrado y stock actualizado correctamente.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$productos = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$movimientos = $pdo->query("
    SELECT m.*, p.codigo, p.nombre AS producto, u.nombre AS usuario 
    FROM movimientos m
    JOIN productos p ON m.id_producto = p.id_producto
    JOIN usuarios u ON m.id_usuario = u.id_usuario
    ORDER BY m.fecha_hora DESC LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-light btn-sm">Volver al Catálogo</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm p-3">
                <h4 class="mb-3">Registrar Movimiento</h4>
                <?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <form method="POST" id="formMovimiento">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <select name="id_producto" class="form-select" required>
                            <option value="">Seleccione un producto...</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id_producto'] ?>">
                                    <?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre'] . ' (Stock: ' . $p['stock_actual'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Movimiento</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (+)</option>
                            <option value="salida">Salida (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" min="1" name="cantidad" id="cantidad" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <input type="text" name="observacion" class="form-control" placeholder="Ej: Compra a proveedor / Venta">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Movimiento</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm p-3">
                <h4 class="mb-3">Últimos Movimientos</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cant.</th>
                                <th>Usuario</th>
                                <th>Obs.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td><?= $m['fecha_hora'] ?></td>
                                    <td><?= htmlspecialchars($m['codigo'] . ' - ' . $m['producto']) ?></td>
                                    <td>
                                        <span class="badge <?= $m['tipo'] === 'entrada' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= ucfirst($m['tipo']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= $m['cantidad'] ?></strong></td>
                                    <td><?= htmlspecialchars($m['usuario']) ?></td>
                                    <td><?= htmlspecialchars($m['observacion']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formMovimiento').addEventListener('submit', function(e) {
    const cant = parseInt(document.getElementById('cantidad').value);
    if (isNaN(cant) || cant <= 0) {
        e.preventDefault();
        alert('La cantidad del movimiento debe ser mayor a cero.');
    }
});
</script>
</body>
</html>