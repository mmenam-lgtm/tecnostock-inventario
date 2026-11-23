<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$busqueda = trim($_GET['buscar'] ?? '');

$sql = "SELECT p.*, c.nombre AS categoria 
        FROM productos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE p.estado = 1";
$params = [];

if ($busqueda !== '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)";
    $like = "%$busqueda%";
    $params = [$like, $like, $like];
}

$sql .= " ORDER BY p.id_producto DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">TecnoStock</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white">Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="movimientos.php" class="btn btn-outline-info btn-sm">Movimientos</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Catálogo de Productos</h2>
        <a href="producto_form.php" class="btn btn-primary">+ Nuevo Producto</a>
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-10">
            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, código o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-secondary">Buscar</button>
        </div>
    </form>

    <div class="table-responsive bg-white shadow-sm rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Estado Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No hay productos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): 
                        $bajoStock = $p['stock_actual'] < $p['stock_minimo'];
                    ?>
                        <tr class="<?= $bajoStock ? 'table-danger' : '' ?>">
                            <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($p['nombre']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($p['descripcion']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></td>
                            <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                            <td class="fw-bold"><?= $p['stock_actual'] ?></td>
                            <td><?= $p['stock_minimo'] ?></td>
                            <td>
                                <?php if ($bajoStock): ?>
                                    <span class="badge bg-danger">¡Stock Bajo!</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="producto_form.php?id=<?= $p['id_producto'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="desactivar.php?id=<?= $p['id_producto'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas desactivar este producto?');">Desactivar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>