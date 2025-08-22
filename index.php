 <?php
$page_title = "Dashboard - Restaurante Pro";
include 'header.php';
include 'db.php';

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha) = CURDATE()");
$pedidos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT SUM(total) as total FROM pedidos WHERE DATE(fecha) = CURDATE() AND estado = 'entregado'");
$ventas_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = TRUE");
$empleados_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener datos para gráfica de ventas de la semana
$ventas_semana = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE DATE(fecha) = ? AND estado = 'entregado'");
    $stmt->execute([$fecha]);
    $ventas_semana[] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}
?>

<div class="dashboard">
    <h2>Dashboard</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Pedidos Hoy</h3>
            <p class="stat-number"><?php echo $pedidos_hoy; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Ventas Hoy</h3>
            <p class="stat-number">$<?php echo number_format($ventas_hoy, 2); ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Empleados Activos</h3>
            <p class="stat-number"><?php echo $empleados_activos; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Mesas Ocupadas</h3>
            <p class="stat-number"><?php 
                $stmt = $pdo->query("SELECT COUNT(DISTINCT mesa) as mesas FROM pedidos WHERE estado IN ('pendiente', 'preparando')");
                echo $stmt->fetch(PDO::FETCH_ASSOC)['mesas'];
            ?></p>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart-card">
            <h3>Ventas de la Semana</h3>
            <canvas id="ventasChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3>Productos Populares</h3>
            <canvas id="productosChart"></canvas>
        </div>
    </div>
</div>

<script>
// Gráfica de ventas de la semana
const ventasCtx = document.getElementById('ventasChart').getContext('2d');
const ventasChart = new Chart(ventasCtx, {
    type: 'bar',
    data: {
        labels: ['<?php echo date("D", strtotime("-6 days")); ?>', 
                 '<?php echo date("D", strtotime("-5 days")); ?>', 
                 '<?php echo date("D", strtotime("-4 days")); ?>', 
                 '<?php echo date("D", strtotime("-3 days")); ?>', 
                 '<?php echo date("D", strtotime("-2 days")); ?>', 
                 '<?php echo date("D", strtotime("-1 days")); ?>', 
                 '<?php echo date("D"); ?>'],
        datasets: [{
            label: 'Ventas $',
            data: [<?php echo implode(', ', $ventas_semana); ?>],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Obtener datos de productos populares mediante AJAX
fetch('get_popular_products.php')
    .then(response => response.json())
    .then(data => {
        const productosCtx = document.getElementById('productosChart').getContext('2d');
        const productosChart = new Chart(productosCtx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ]
                }]
            }
        });
    });
</script>

<?php include 'footer.php'; ?