<?php
define('DB_SERVER', '192.168.10.10');
define('DB_USERNAME', 'oamsun');
define('DB_PASSWORD', 'Oams@UN');
define('DB_NAME', 'unmg-workplace');

try {
   
} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}

