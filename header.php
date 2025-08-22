 <?php
// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Restaurante Pro'; ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <h1>Restaurante Pro</h1>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="pedidos.php">Pedidos</a></li>
                    <li><a href="menu.php">Menú</a></li>
                    <li><a href="empleados.php">Empleados</a></li>
                    <li><a href="finanzas.php">Finanzas</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <span>Hola, <?php echo $_SESSION['user_name']; ?> (<?php echo $_SESSION['user_role']; ?>)</span>
                <a href="logout.php" class="btn-logout">Cerrar sesión</a>
            </div>
        </div>
    </header>
    
    <main class="main-content"