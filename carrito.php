<?php
// Configurar opciones de sesión antes de iniciarla
session_set_cookie_params([
    'lifetime' => 1800, // 30 minutos de vida de la cookie
    'secure' => true,   // Solo enviar cookies sobre HTTPS
    'httponly' => true, // Evitar acceso a la cookie desde JavaScript
    'samesite' => 'Strict' // Evitar ataques CSRF
]);

// Iniciar la sesión
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32)); // Generar token CSRF
}

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Verificación del token CSRF
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['token'])) {
    http_response_code(403);
    echo json_encode(["error" => "Token CSRF no válido"]);
    exit();
}

// Agregar un paquete al carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['paquete_id'])) {
    $paquete = $_POST['paquete_id'];
    if (!in_array($paquete, $_SESSION['carrito'])) {
        $_SESSION['carrito'][] = $paquete;
    }
    echo json_encode(["success" => true, "message" => "Agregado al carrito"]);
    exit();
}

// Eliminar un paquete del carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_paquete'])) {
    $paquete = $_POST['eliminar_paquete'];
    $_SESSION['carrito'] = array_filter($_SESSION['carrito'], fn($p) => $p !== $paquete);
    echo json_encode(["success" => true, "message" => "Eliminado del carrito"]);
    exit();
}

// Mostrar contenido del carrito
if (isset($_GET['ver_carrito'])) {
    echo json_encode($_SESSION['carrito']);
    exit();
}

// Cerrar sesión de forma segura
if (isset($_GET['cerrar_sesion'])) {
    // Destruir todas las variables de sesión
    $_SESSION = [];

    // Si se desea destruir la sesión completamente, borrar también la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir la sesión
    session_destroy();

    echo json_encode(["success" => true, "message" => "Sesión cerrada correctamente"]);
    exit();
}

    // Evitar el uso de array_filter() en la eliminación del carrito array_filter() conserva las claves del array, lo que puede causar inconsistencias en el índice. En su lugar, usa array_values() para reindexar.
?>

