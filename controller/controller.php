<?php
require_once 'db.php'; // Ensure this file contains the PDO connection

class Controller
{
    public $connection;
    public $statement;
    public $isConnectionSuccess;
    public $connectionError;

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_SERVER. ";dbname=" . DB_NAME;
          
            $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->isConnectionSuccess = true;
        } catch (PDOException $e) {
            $this->connectionError = "<script defer> console.log('" . $e->getMessage() . "')</script>";
        }
    }

    public function setStatement($query)
    {
        if ($this->isConnectionSuccess) {
            $this->statement = $this->connection->prepare($query);
        } else {
            $this->sendJsonResponse(["error" => "Database connection failed"], 500);
        }
    }

    protected function sendJsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>