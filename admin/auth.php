<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('../connection.php');

class AdminAuth
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    // Admin Signup function
    public function signup($json)
    {
        $data = json_decode($json, true);

        // Get admin data
        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $username = $data['username'];
        $password = $data['password'];

        // Check if the admin already exists by username
        $checkQuery = "SELECT * FROM admin WHERE username = :username";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return json_encode(array("error" => "Admin already exists"));
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert new admin into the database
            $insertQuery = "INSERT INTO admin (firstname, lastname, username, password) 
                            VALUES (:firstname, :lastname, :username, :password)";
            $stmt = $this->conn->prepare($insertQuery);
            $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
            $stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return json_encode(array("success" => "Admin registered successfully"));
            } else {
                return json_encode(array("error" => "Failed to register admin"));
            }
        }
    }

    // Admin Login function
    public function login($json)
    {
        $data = json_decode($json, true);

        // Get the username and password
        $username = $data['username'];
        $password = $data['password'];

        // Retrieve the admin from the database
        $query = "SELECT user_id, firstname, lastname, username, password, created_at FROM admin WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            // Verify the password
            if (password_verify($password, $admin['password'])) {
                return json_encode(array("success" => $admin, ));
            } else {
                return json_encode(array("error" => "Incorrect password"));
            }
        } else {
            return json_encode(array("error" => "Admin not found"));
        }
    }
}

// Initialize the AdminAuth class
$adminAuth = new AdminAuth();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST["operation"]) && isset($_REQUEST["json"])) {
        $operation = $_REQUEST["operation"];
        $json = $_REQUEST["json"];

        switch ($operation) {
            case "login":
                echo $adminAuth->login($json);
                break;

            case "signup":
                echo $adminAuth->signup($json);
                break;

            default:
                echo json_encode(array("error" => "No such operation here"));
                break;
        }
    } else {
        echo json_encode(array("error" => "Missing Parameters"));
    }
} else {
    echo json_encode(array("error" => "Invalid Request Method"));
}

?>