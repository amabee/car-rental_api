<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('../connection.php');

class Auth
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    // Customer Signup function
    public function signup($json)
    {
        $data = json_decode($json, true);

        // Get customer data
        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $driverLicense = $data['driver_license'];
        $password = $data['password'];

        // Check if the customer already exists by email
        $checkQuery = "SELECT * FROM customers WHERE email = :email";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return json_encode(array("error" => "Customer already exists"));
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert new customer into the database
            $insertQuery = "INSERT INTO customers (first_name, last_name, email, phone, driver_license, password) 
                            VALUES (:first_name, :last_name, :email, :phone, :driver_license, :password)";
            $stmt = $this->conn->prepare($insertQuery);
            $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':driver_license', $driverLicense, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

            if ($stmt->execute()) {
                return json_encode(array("success" => "Customer registered successfully"));
            } else {
                return json_encode(array("error" => "Failed to register customer"));
            }
        }
    }

    // Customer Login function
    public function login($json)
    {
        $data = json_decode($json, true);

        // Get the email and password
        $email = $data['email'];
        $password = $data['password'];

        // Retrieve the customer from the database
        $query = "SELECT * FROM customers WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            // Verify the password
            if (password_verify($password, $customer['password'])) {
                return json_encode(array("success" => "Login successful", "customer_id" => $customer['customer_id']));
            } else {
                return json_encode(array("error" => "Incorrect password"));
            }
        } else {
            return json_encode(array("error" => "Customer not found"));
        }
    }
}

// Initialize the Auth class
$auth = new Auth();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST["operation"]) && isset($_REQUEST["json"])) {
        $operation = $_REQUEST["operation"];
        $json = $_REQUEST["json"];

        switch ($operation) {
            case "login":
                echo $auth->login($json);
                break;

            case "signup":
                echo $auth->signup($json);
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
