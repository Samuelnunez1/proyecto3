 <?php
$page_title = "Gestión de Empleados - Restaurante Pro";
include 'header.php';
include 'db.php';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_empleado'])) {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $rol = $_POST['rol'];
        
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $email, $password, $rol]);
        $mensaje = "Empleado agregado correctamente";
    }
    
    if (isset($_POST['registrar_asistencia'])) {
        $id_usuario = $_POST['id_usuario'];
        $tipo = $_POST['tipo'];
        
        if ($tipo === 'entrada') {
            $stmt = $pdo->prepare("INSERT INTO asistencias (id_usuario, fecha, hora_entrada) VALUES (?, CURDATE(), CURTIME())");
            $stmt->execute([$id_usuario]);
            $mensaje = "Entrada registrada correctamente";
        } else {
            $stmt = $pdo->prepare("UPDATE asistencias SET hora_salida = CURTIME(), horas_trabajadas = TIMESTAMPDIFF(HOUR, hora_entrada, CURTIME()) WHERE id_usuario = ? AND fecha = CURDATE()");
            $stmt->execute([$id_usuario]);
            $mensaje = "Salida registrada correctamente";
        }
    }
}

// Obtener lista de empleados
$stmt = $pdo->query("SELECT * FROM usuarios WHERE activo = TRUE ORDER BY nombre");
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de asistencias
$stmt = $pdo->query("SELECT a.*, u.nombre FROM asistencias a JOIN usuarios u ON a.id_usuario = u.id ORDER BY a.fecha DESC, a.hora_entrada DESC LIMIT 50");
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-container">
    <h2>Gestión de Empleados</h2>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'empleados')">Empleados</button>
        <button class="tab-button" onclick="openTab(event, 'asistencias')">Registro de Asistencias</button>
        <button class="tab-button" onclick="openTab(event, 'historial')">Historial</button>
    </div>
    
    <div id="empleados" class="tab-content active">
        <h3>Agregar Nuevo Empleado</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="mesero">Mesero</option>
                    <option value="cocinero">Cocinero</option>
                    <option value="cajero">Cajero</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="agregar_empleado" class="btn-primary">Agregar Empleado</button>
            </div>
        </form>
        
        <h3>Lista de Empleados</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empleados as $emp): ?>
                <tr>
                    <td><?php echo $emp['id']; ?></td>
                    <td><?php echo $emp['nombre']; ?></td>
                    <td><?php echo $emp['email']; ?></td>
                    <td><?php echo $emp['rol']; ?></td>
                    <td><?php echo $emp['fecha_creacion']; ?></td>
                    <td>
                        <a href="editar_empleado.php?id=<?php echo $emp['id']; ?>" class="btn-edit">Editar</a>
                        <a href="eliminar_empleado.php?id=<?php echo $emp['id']; ?>" class="btn-delete" onclick="return confirmarEliminacion()">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="asistencias" class="tab-content">
        <h3>Registrar Asistencia</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="id_usuario">Empleado:</label>
                <select id="id_usuario" name="id_usuario" required>
                    <?php foreach ($empleados as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>"><?php echo $emp['nombre']; ?> (<?php echo $emp['rol']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de registro:</label>
                <select id="tipo" name="tipo" required>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="registrar_asistencia" class="btn-primary">Registrar Asistencia</button>
            </div>
        </form>
    </div>
    
    <div id="historial" class="tab-content">
        <h3>Historial de Asistencias</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Fecha</th>
                    <th>Hora Entrada</th>
                    <th>Hora Salida</th>
                    <th>Horas Trabajadas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asistencias as $asist): ?>
                <tr>
                    <td><?php echo $asist['nombre']; ?></td>
                    <td><?php echo $asist['fecha']; ?></td>
                    <td><?php echo $asist['hora_entrada']; ?></td>
                    <td><?php echo $asist['hora_salida'] ?? '-'; ?></td>
                    <td><?php echo $asist['horas_trabajadas'] ?? '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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