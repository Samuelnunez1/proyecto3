 <?php
$page_title = "Gestión Financiera - Restaurante Pro";
include 'header.php';
include 'db.php';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_registro'])) {
        $tipo = $_POST['tipo'];
        $concepto = $_POST['concepto'];
        $monto = $_POST['monto'];
        $fecha = $_POST['fecha'];
        $categoria = $_POST['categoria'];
        $descripcion = $_POST['descripcion'];
        
        $stmt = $pdo->prepare("INSERT INTO finanzas (tipo, concepto, monto, fecha, categoria, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipo, $concepto, $monto, $fecha, $categoria, $descripcion]);
        $mensaje = "Registro agregado correctamente";
    }
}

// Obtener registros financieros
$filtro_mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$where = "WHERE DATE_FORMAT(fecha, '%Y-%m') = '$filtro_mes'";

$stmt = $pdo->query("SELECT * FROM finanzas $where ORDER BY fecha DESC");
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_ingresos = 0;
$total_egresos = 0;

foreach ($registros as $reg) {
    if ($reg['tipo'] == 'ingreso') {
        $total_ingresos += $reg['monto'];
    } else {
        $total_egresos += $reg['monto'];
    }
}

$balance = $total_ingresos - $total_egresos;

// Obtener datos para gráficas
$stmt = $pdo->query("SELECT categoria, SUM(monto) as total FROM finanzas WHERE tipo = 'ingreso' AND DATE_FORMAT(fecha, '%Y-%m') = '$filtro_mes' GROUP BY categoria");
$ingresos_por_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT categoria, SUM(monto) as total FROM finanzas WHERE tipo = 'egreso' AND DATE_FORMAT(fecha, '%Y-%m') = '$filtro_mes' GROUP BY categoria");
$egresos_por_categoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-container">
    <h2>Gestión Financiera</h2>
    
    <?php if (isset($mensaje)): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Ingresos del Mes</h3>
            <p class="stat-number text-success">$<?php echo number_format($total_ingresos, 2); ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Egresos del Mes</h3>
            <p class="stat-number text-danger">$<?php echo number_format($total_egresos, 2); ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Balance</h3>
            <p class="stat-number <?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                $<?php echo number_format($balance, 2); ?>
            </p>
        </div>
    </div>
    
    <div class="filtros">
        <form method="GET" class="filter-form">
            <label for="mes">Filtrar por mes:</label>
            <input type="month" id="mes" name="mes" value="<?php echo $filtro_mes; ?>" onchange="this.form.submit()">
        </form>
    </div>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab(event, 'registros')">Registros</button>
        <button class="tab-button" onclick="openTab(event, 'agregar')">Agregar Registro</button>
        <button class="tab-button" onclick="openTab(event, 'graficas')">Gráficas</button>
    </div>
    
    <div id="registros" class="tab-content active">
        <h3>Registros Financieros - <?php echo date('F Y', strtotime($filtro_mes)); ?></h3>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Categoría</th>
                    <th>Monto</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?php echo $reg['fecha']; ?></td>
                    <td>
                        <span class="badge <?php echo $reg['tipo'] == 'ingreso' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo ucfirst($reg['tipo']); ?>
                        </span>
                    </td>
                    <td><?php echo $reg['concepto']; ?></td>
                    <td><?php echo $reg['categoria']; ?></td>
                    <td class="<?php echo $reg['tipo'] == 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                        $<?php echo number_format($reg['monto'], 2); ?>
                    </td>
                    <td><?php echo $reg['descripcion']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="agregar" class="tab-content">
        <h3>Agregar Registro Financiero</h3>
        <form method="POST" class="form-grid">
            <div class="form-group">
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <option value="ingreso">Ingreso</option>
                    <option value="egreso">Egreso</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="concepto">Concepto:</label>
                <input type="text" id="concepto" name="concepto" required>
            </div>
            
            <div class="form-group">
                <label for="monto">Monto:</label>
                <input type="number" id="monto" name="monto" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="categoria">Categoría:</label>
                <input type="text" id="categoria" name="categoria" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" name="agregar_registro" class="btn-primary">Agregar Registro</button>
            </div>
        </form>
    </div>
    
    <div id="graficas" class="tab-content">
        <h3>Análisis Financiero - <?php echo date('F Y', strtotime($filtro_mes)); ?></h3>
        
        <div class="charts-container">
            <div class="chart-card">
                <h4>Ingresos por Categoría</h4>
                <canvas id="ingresosChart"></canvas>
            </div>
            
            <div class="chart-card">
                <h4>Egresos por Categoría</h4>
                <canvas id="egresosChart"></canvas>
            </div>
        </div>
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

// Gráfica de ingresos por categoría
const ingresosCtx = document.getElementById('ingresosChart').getContext('2d');
const ingresosChart = new Chart(ingresosCtx, {
    type: 'pie',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['categoria'] . "'"; }, $ingresos_por_categoria)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $ingresos_por_categoria)); ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(255, 99, 132, 0.7)'
            ]
        }]
    }
});

// Gráfica de egresos por categoría
const egresosCtx = document.getElementById('egresosChart').getContext('2d');
const egresosChart = new Chart(egresosCtx, {
    type: 'pie',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['categoria'] . "'"; }, $egresos_por_categoria)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_map(function($item) { return $item['total']; }, $egresos_por_categoria)); ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(255, 205, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(54, 162, 235, 0.7)'
            ]
        }]
    }
});
</script>

<?php include 'footer.php'; ?