 <?php
$page_title = "Gestión de Menú - Restaurante Pro";
include 'header.php';
include 'db.php';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_producto'])) {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $categoria = $_POST['categoria'];
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        
        // Manejar carga de imagen
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagen = $target_dir . uniqid() . '.' . $file_extension;
            
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen);
        }
        
        $stmt = $pdo->prepare("INSERT INTO menu (nombre, descripcion, precio, categoria, disponible, imagen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $precio, $categoria, $disponible, $imagen]);
        $mensaje = "Producto agregado correctamente";
    }
    
    if (isset($_POST['actualizar_producto'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $categoria = $_POST['categoria'];
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        
        // Si se sube una nueva imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagen = $target_dir . uniqid() . '.' . $file_extension;
            
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen);
            
            $stmt = $pdo->prepare("UPDATE menu SET nombre=?, descripcion=?, precio=?, categoria=?, disponible=?, imagen=? WHERE id=?");
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $disponible, $imagen, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE menu SET nombre=?, descripcion=?, precio=?, categoria=?, disponible=? WHERE id=?");
            $stmt->execute([$nombre, $descripcion, $precio, $categoria, $disponible, $id]);
        }
        
        $mensaje = "Producto actualizado correctamente";
    }
}

// Obtener productos del menú
$stmt = $pdo->query("SELECT * FROM menu ORDER BY categoria, nombre");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener producto para editar si se proporciona ID
$producto_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $producto_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header('Location: menu.php');
    exit();
}
?>

<div class="page-container">
    <h2>Gestión de Menú</h2>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'productos')">Productos</button>
        <button class="tab-button" onclick="openTab(event, 'agregar')"><?php echo $producto_editar ? 'Editar' : 'Agregar'; ?> Producto</button>
    </div>
    
    <div id="productos" class="tab-content active">
        <h3>Lista de Productos</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Categoría</th>
                    <th>Disponible</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $prod): ?>
                <tr>
                    <td><?php echo $prod['id']; ?></td>
                    <td>
                        <?php if ($prod['imagen']): ?>
                        <img src="<?php echo $prod['imagen']; ?>" alt="<?php echo $prod['nombre']; ?>" class="product-img-small">
                        <?php else: ?>
                        <span class="no-image">Sin imagen</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $prod['nombre']; ?></td>
                    <td><?php echo substr($prod['descripcion'], 0, 50) . '...'; ?></td>
                    <td>$<?php echo number_format($prod['precio'], 2); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $prod['categoria'])); ?></td>
                    <td><?php echo $prod['disponible'] ? 'Sí' : 'No'; ?></td>
                    <td>
                        <a href="menu.php?editar=<?php echo $prod['id']; ?>" class="btn-edit">Editar</a>
                        <a href="menu.php?eliminar=<?php echo $prod['id']; ?>" class="btn-delete" onclick="return confirmarEliminacion()">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="agregar" class="tab-content">
        <h3><?php echo $producto_editar ? 'Editar' : 'Agregar'; ?> Producto</h3>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <?php if ($producto_editar): ?>
            <input type="hidden" name="id" value="<?php echo $producto_editar['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo $producto_editar['nombre'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?php echo $producto_editar['descripcion'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo $producto_editar['precio'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="categoria">Categoría:</label>
                <select id="categoria" name="categoria" required>
                    <option value="entrada" <?php echo (isset($producto_editar['categoria']) && $producto_editar['categoria'] == 'entrada') ? 'selected' : ''; ?>>Entrada</option>
                    <option value="plato_fuerte" <?php echo (isset($producto_editar['categoria']) && $producto_editar['categoria'] == 'plato_fuerte') ? 'selected' : ''; ?>>Plato Fuerte</option>
                    <option value="postre" <?php echo (isset($producto_editar['categoria']) && $producto_editar['categoria'] == 'postre') ? 'selected' : ''; ?>>Postre</option>
                    <option value="bebida" <?php echo (isset($producto_editar['categoria']) && $producto_editar['categoria'] == 'bebida') ? 'selected' : ''; ?>>Bebida</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen:</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
                <?php if (isset($producto_editar['imagen']) && $producto_editar['imagen']): ?>
                <div class="current-image">
                    <img src="<?php echo $producto_editar['imagen']; ?>" alt="Imagen actual" class="product-img-small">
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="disponible" class="checkbox-label">
                    <input type="checkbox" id="disponible" name="disponible" value="1" <?php echo (isset($producto_editar['disponible']) && $producto_editar['disponible']) ? 'checked' : ''; ?>>
                    <span>Disponible</span>
                </label>
            </div>
            
            <div class="form-group">
                <?php if ($producto_editar): ?>
                <button type="submit" name="actualizar_producto" class="btn-primary">Actualizar Producto</button>
                <a href="menu.php" class="btn-secondary">Cancelar</a>
                <?php else: ?>
                <button type="submit" name="agregar_producto" class="btn-primary">Agregar Producto</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
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