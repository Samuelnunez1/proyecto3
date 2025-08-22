 <?php
$page_title = "Gestión de Pedidos - Restaurante Pro";
include 'header.php';
include 'db.php';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_pedido'])) {
        $mesa = $_POST['mesa'];
        $id_usuario = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("INSERT INTO pedidos (mesa, id_usuario) VALUES (?, ?)");
        $stmt->execute([$mesa, $id_usuario]);
        $id_pedido = $pdo->lastInsertId();
        
        header("Location: pedidos.php?editar=$id_pedido");
        exit();
    }
    
    if (isset($_POST['agregar_producto_pedido'])) {
        $id_pedido = $_POST['id_pedido'];
        $id_menu = $_POST['id_menu'];
        $cantidad = $_POST['cantidad'];
        $notas = $_POST['notas'];
        
        // Obtener precio del producto
        $stmt = $pdo->prepare("SELECT precio FROM menu WHERE id = ?");
        $stmt->execute([$id_menu]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        $precio_unitario = $producto['precio'];
        $subtotal = $precio_unitario * $cantidad;
        
        // Agregar producto al pedido
        $stmt = $pdo->prepare("INSERT INTO detalle_pedido (id_pedido, id_menu, cantidad, precio_unitario, subtotal, notas) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_pedido, $id_menu, $cantidad, $precio_unitario, $subtotal, $notas]);
        
        // Actualizar total del pedido
        $stmt = $pdo->prepare("UPDATE pedidos SET total = (SELECT SUM(subtotal) FROM detalle_pedido WHERE id_pedido = ?) WHERE id = ?");
        $stmt->execute([$id_pedido, $id_pedido]);
    }
    
    if (isset($_POST['actualizar_estado'])) {
        $id_pedido = $_POST['id_pedido'];
        $estado = $_POST['estado'];
        
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id_pedido]);
        
        header("Location: pedidos.php");
        exit();
    }
}

// Obtener pedidos
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$where = '';
if ($filtro_estado) {
    $where = "WHERE estado = '$filtro_estado'";
}

$stmt = $pdo->query("SELECT p.*, u.nombre as mesero FROM pedidos p JOIN usuarios u ON p.id_usuario = u.id $where ORDER BY p.fecha DESC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos del menú
$stmt = $pdo->query("SELECT * FROM menu WHERE disponible = TRUE ORDER BY categoria, nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener pedido para editar si se proporciona ID
$pedido_editar = null;
$detalles_pedido = [];
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT p.*, u.nombre as mesero FROM pedidos p JOIN usuarios u ON p.id_usuario = u.id WHERE p.id = ?");
    $stmt->execute([$_GET['editar']]);
    $pedido_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido_editar) {
        $stmt = $pdo->prepare("SELECT dp.*, m.nombre as producto FROM detalle_pedido dp JOIN menu m ON dp.id_menu = m.id WHERE dp.id_pedido = ?");
        $stmt->execute([$_GET['editar']]);
        $detalles_pedido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Cambiar estado del pedido
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['id'];
    $estado = $_GET['cambiar_estado'];
    
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);
    
    header('Location: pedidos.php');
    exit();
}
?>

<div class="page-container">
    <h2>Gestión de Pedidos</h2>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'lista')">Lista de Pedidos</button>
        <button class="tab-button" onclick="openTab(event, 'crear')">Crear Pedido</button>
        <?php if ($pedido_editar): ?>
        <button class="tab-button" onclick="openTab(event, 'editar')">Editando Pedido #<?php echo $pedido_editar['id']; ?></button>
        <?php endif; ?>
    </div>
    
    <div id="lista" class="tab-content active">
        <h3>Lista de Pedidos</h3>
        
        <div class="filtros">
            <form method="GET" class="filter-form">
                <label for="estado">Filtrar por estado:</label>
                <select id="estado" name="estado" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $filtro_estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="preparando" <?php echo $filtro_estado == 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                    <option value="listo" <?php echo $filtro_estado == 'listo' ? 'selected' : ''; ?>>Listo</option>
                    <option value="entregado" <?php echo $filtro_estado == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                    <option value="cancelado" <?php echo $filtro_estado == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </form>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mesa</th>
                    <th>Mesero</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $ped): ?>
                <tr>
                    <td><?php echo $ped['id']; ?></td>
                    <td><?php echo $ped['mesa']; ?></td>
                    <td><?php echo $ped['mesero']; ?></td>
                    <td>
                        <span class="estado-badge estado-<?php echo $ped['estado']; ?>">
                            <?php echo ucfirst($ped['estado']); ?>
                        </span>
                    </td>
                    <td>$<?php echo number_format($ped['total'], 2); ?></td>
                    <td><?php echo $ped['fecha']; ?></td>
                    <td>
                        <a href="pedidos.php?editar=<?php echo $ped['id']; ?>" class="btn-edit">Editar</a>
                        <a href="generar_ticket.php?id=<?php echo $ped['id']; ?>" target="_blank" class="btn-secondary">Ticket</a>
                        
                        <?php if ($ped['estado'] == 'pendiente'): ?>
                        <a href="pedidos.php?cambiar_estado=preparando&id=<?php echo $ped['id']; ?>" class="btn-warning">Preparar</a>
                        <?php elseif ($ped['estado'] == 'preparando'): ?>
                        <a href="pedidos.php?cambiar_estado=listo&id=<?php echo $ped['id']; ?>" class="btn-success">Listo</a>
                        <?php elseif ($ped['estado'] == 'listo'): ?>
                        <a href="pedidos.php?cambiar_estado=entregado&id=<?php echo $ped['id']; ?>" class="btn-primary">Entregar</a>
                        <?php endif; ?>
                        
                        <?php if ($ped['estado'] != 'cancelado' && $ped['estado'] != 'entregado'): ?>
                        <a href="pedidos.php?cambiar_estado=cancelado&id=<?php echo $ped['id']; ?>" class="btn-delete" onclick="return confirmarEliminacion('¿Cancelar este pedido?')">Cancelar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="crear" class="tab-content">
        <h3>Crear Nuevo Pedido</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="mesa">Número de Mesa:</label>
                <input type="number" id="mesa" name="mesa" min="1" max="50" required>
            </div>
            
            <div class="form-group">
                <button type="submit" name="crear_pedido" class="btn-primary">Crear Pedido</button>
            </div>
        </form>
    </div>
    
    <?php if ($pedido_editar): ?>
    <div id="editar" class="tab-content">
        <h3>Editando Pedido #<?php echo $pedido_editar['id']; ?> - Mesa <?php echo $pedido_editar['mesa']; ?></h3>
        
        <div class="pedido-info">
            <p><strong>Estado:</strong> <span class="estado-badge estado-<?php echo $pedido_editar['estado']; ?>"><?php echo ucfirst($pedido_editar['estado']); ?></span></p>
            <p><strong>Mesero:</strong> <?php echo $pedido_editar['mesero']; ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($pedido_editar['total'], 2); ?></p>
            <p><strong>Fecha:</strong> <?php echo $pedido_editar['fecha']; ?></p>
        </div>
        
        <h4>Agregar Producto al Pedido</h4>
        <form method="POST" class="form-grid">
            <input type="hidden" name="id_pedido" value="<?php echo $pedido_editar['id']; ?>">
            
            <div class="form-group">
                <label for="id_menu">Producto:</label>
                <select id="id_menu" name="id_menu" required>
                    <?php foreach ($productos as $prod): ?>
                    <option value="<?php echo $prod['id']; ?>" data-precio="<?php echo $prod['precio']; ?>">
                        <?php echo $prod['nombre']; ?> - $<?php echo number_format($prod['precio'], 2); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cantidad">Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
            </div>
            
            <div class="form-group">
                <label for="notas">Notas:</label>
                <textarea id="notas" name="notas" rows="2" placeholder="Especificaciones especiales..."></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" name="agregar_producto_pedido" class="btn-primary">Agregar Producto</button>
            </div>
        </form>
        
        <h4>Productos en el Pedido</h4>
        <?php if (count($detalles_pedido) > 0): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles_pedido as $det): ?>
                <tr>
                    <td><?php echo $det['producto']; ?></td>
                    <td><?php echo $det['cantidad']; ?></td>
                    <td>$<?php echo number_format($det['precio_unitario'], 2); ?></td>
                    <td>$<?php echo number_format($det['subtotal'], 2); ?></td>
                    <td><?php echo $det['notas']; ?></td>
                    <td>
                        <a href="eliminar_detalle_pedido.php?id=<?php echo $det['id']; ?>&pedido=<?php echo $pedido_editar['id']; ?>" class="btn-delete" onclick="return confirmarEliminacion()">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No hay productos en este pedido.</p>
        <?php endif; ?>
        
        <div class="pedido-actions">
            <form method="POST">
                <input type="hidden" name="id_pedido" value="<?php echo $pedido_editar['id']; ?>">
                
                <div class="form-group">
                    <label for="estado">Cambiar estado:</label>
                    <select id="estado" name="estado" required>
                        <option value="pendiente" <?php echo $pedido_editar['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="preparando" <?php echo $pedido_editar['estado'] == 'preparando' ? 'selected' : ''; ?>>Preparando</option>
                        <option value="listo" <?php echo $pedido_editar['estado'] == 'listo' ? 'selected' : ''; ?>>Listo</option>
                        <option value="entregado" <?php echo $pedido_editar['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelado" <?php echo $pedido_editar['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="actualizar_estado" class="btn-primary">Actualizar Estado</button>
                </div>
            </form>
            
            <a href="generar_ticket.php?id=<?php echo $pedido_editar['id']; ?>" target="_blank" class="btn-secondary">Generar Ticket PDF</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function openTab(evt, tabName) {
    const tabcontent = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    
    const tabbuttons = document.getElementsByClassName("tab-button");
    for (let i = 0; i < tabbuttons.length; i++) {
        tabbuttons[i].classList.remove("active");
    }
    
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>

<?php include 'footer.php'; ?