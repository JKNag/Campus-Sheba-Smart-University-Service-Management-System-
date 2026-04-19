<?php
// config/database.php
<<<<<<< HEAD
$host     = 'localhost';
$dbname   = 'campus_sheba';
=======
$host = 'localhost';
$dbname = 'campus_sheba';
>>>>>>> d0e0a54232ff112c6b2de5a9005bff7a4384372e
$username = 'root';
$password = '';

try {
<<<<<<< HEAD
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (PDOException $e) {
    die("<h3 style='font-family:sans-serif;color:red;padding:30px'>
        ❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "
        <br><br><small>Check your credentials in config/database.php</small>
    </h3>");
}
?>
=======
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
>>>>>>> d0e0a54232ff112c6b2de5a9005bff7a4384372e
