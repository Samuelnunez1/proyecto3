 $host = "sql200.infinityfree.com"; // Cambia por tu host
$user = "if0_39764921"; // Usuario de BD
$pass = "do5lM4IqFy2Vs6"; // Contraseña de BD
$db = "if0_39764921_restaurante"; // Nombre de BD


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?