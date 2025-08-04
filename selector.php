<?php
session_start();
require_once 'db.php';

// 1. Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Si se envía el formulario, establecer la empresa en la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empresa_id'])) {
    $empresa_id = $_POST['empresa_id'];
    $is_superuser = $_SESSION['is_superuser'] ?? false;

    // Si es superusuario, puede acceder a cualquier empresa.
    // Si no, se valida que tenga acceso.
    $sql = $is_superuser
        ? 'SELECT id, nombre FROM empresas WHERE id = ?'
        : 'SELECT e.id, e.nombre FROM empresas e JOIN usuario_empresa ue ON e.id = ue.empresa_id WHERE ue.usuario_id = ? AND ue.empresa_id = ?';

    $stmt = $conn->prepare($sql);

    if ($is_superuser) {
        $stmt->bind_param("i", $empresa_id);
    } else {
        $stmt->bind_param("ii", $_SESSION['user_id'], $empresa_id);
    }

    $stmt->execute();
    $empresa = $stmt->get_result()->fetch_assoc();

    if ($empresa) {
        $_SESSION['empresa_id'] = $empresa['id'];
        $_SESSION['empresa_nombre'] = $empresa['nombre'];
        header('Location: index.php');
        exit;
    } else {
        // Si el usuario intenta acceder a una empresa no autorizada, se muestra un error.
        $error = "Acceso no autorizado a la empresa seleccionada.";
    }
}

// 3. Obtener la lista de empresas a las que el usuario tiene acceso
$stmt = $conn->prepare(
    'SELECT e.id, e.nombre FROM empresas e ' .
    'JOIN usuario_empresa ue ON e.id = ue.empresa_id ' .
    'WHERE ue.usuario_id = ?'
);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$empresas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Si el usuario solo tiene acceso a una empresa Y NO es superusuario, seleccionarla automáticamente y redirigir
if (count($empresas) === 1 && !($_SESSION['is_superuser'] ?? false)) {
    $_SESSION['empresa_id'] = $empresas[0]['id'];
    $_SESSION['empresa_nombre'] = $empresas[0]['nombre'];
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: var(--solutek-background-grey);
        }
        .selector-container {
            max-width: 500px;
            width: 100%;
            padding: 40px;
            background-color: var(--solutek-card-background);
            border-radius: 8px;
            box-shadow: 0 8px 15px var(--solutek-shadow);
            text-align: center;
        }
        .selector-container h2 {
            margin-bottom: 25px;
            color: var(--solutek-dark-blue);
        }
        .form-select {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: var(--solutek-light-blue);
            border-color: var(--solutek-light-blue);
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <h2>Seleccionar Empresa</h2>
        <p>Elige la empresa con la que deseas trabajar.</p>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($empresas) > 0): ?>
            <form method="POST" action="selector.php">
                <select name="empresa_id" class="form-select form-select-lg" required>
                    <option value="" disabled selected>-- Elige una empresa --</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo htmlspecialchars($empresa['id']); ?>">
                            <?php echo htmlspecialchars($empresa['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-lg">Acceder</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                No tienes acceso a ninguna empresa. Por favor, contacta a un administrador.
            </div>
            <a href="logout.php" class="btn btn-secondary">Cerrar Sesión</a>
        <?php endif; ?>
    </div>
</body>
</html>
