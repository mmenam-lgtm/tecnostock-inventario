<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$id = $_GET['id'] ?? null;
$producto = [
    'codigo' => '', 'nombre' => '', 'descripcion' => '',
    'precio' => '', 'stock_actual' => 0, 'stock_minimo' => 5, 'id_categoria' => ''
];
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($encontrado) $producto = $encontrado;
}

$categorias = $pdo->query("SELECT * FROM categorias WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? -1);
    $stock_actual = (int)($_POST['stock_actual'] ?? -1);
    $stock_minimo = (int)($_POST['stock_minimo'] ?? -1);
    $id_categoria = $_POST['id_categoria'] ?? '';

    if ($codigo === '' || $nombre === '' || $id_categoria === '') {
        $error = 'Completa los campos obligatorios.';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio y los valores de stock no pueden ser negativos.';
    } else {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE productos SET codigo=?, nombre=?, descripcion=?, precio=?, stock_actual=?, stock_minimo=?, id_categoria=? WHERE id_producto=?");
                $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$codigo, $nombre, $descripcion, $precio, $stock_actual, $stock_minimo, $id_categoria]);
            }
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Error: El código de producto ya existe. Debe ser único.';
            } else {
                $error = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Editar' : 'Nuevo' ?> Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 600px;">
    <div class="card shadow p-4">
        <h3 class="mb-3"><?= $id ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="formProducto">
            <div class="mb-3">
                <label class="form-label">Código (único)</label>
                <input type="text" name="codigo" class="form-control" required value="<?= htmlspecialchars($producto['codigo']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($producto['nombre']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <select name="id_categoria" class="form-select" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= $producto['id_categoria'] == $c['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" required value="<?= htmlspecialchars($producto['precio']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock Actual</label>
                    <input type="number" min="0" name="stock_actual" id="stock_actual" class="form-control" required value="<?= htmlspecialchars($producto['stock_actual']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Stock Mínimo</label>
                    <input type="number" min="0" name="stock_minimo" id="stock_minimo" class="form-control" required value="<?= htmlspecialchars($producto['stock_minimo']) ?>">
                </div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <a href="index.php" class="btn btn-secondary">Volver</a>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('formProducto').addEventListener('submit', function(e) {
    const precio = parseFloat(document.getElementById('precio').value);
    const stock = parseInt(document.getElementById('stock_actual').value);
    const minimo = parseInt(document.getElementById('stock_minimo').value);

    if (precio < 0 || stock < 0 || minimo < 0) {
        e.preventDefault();
        alert('El precio, el stock actual y el stock mínimo no pueden ser negativos.');
    }
});
</script>
</body>
</html>