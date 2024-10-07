<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('../connection.php');

class CustomerProcess
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }


    public function getAllCars()
    {
        $query = "SELECT car_id, make, model, year, license_plate, price_per_day, status, car_image, created_at 
                  FROM cars";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cars as &$car) {
            switch ($car['status']) {
                case 'available':
                    $car['rental_status'] = 'Available';
                    break;
                case 'rented':
                    $car['rental_status'] = 'Rented';
                    break;
                case 'maintenance':
                    $car['rental_status'] = 'In Maintenance';
                    break;
                default:
                    $car['rental_status'] = 'Unknown';
            }
        }

        return json_encode(["success" => $cars]);
    }


    public function getAvailableCars()
    {
        $query = "SELECT car_id, make, model, year, license_plate, price_per_day, status, car_image, created_at FROM cars WHERE status = 'available'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode(["success" => $cars]);
    }

    public function getTopCustomers()
    {
        $query = "
            SELECT bookings.customer_id, customers.first_name, customers.last_name, COUNT(bookings.booking_id) as bookings 
            FROM bookings 
            JOIN customers ON bookings.customer_id = customers.customer_id 
            WHERE YEAR(bookings.created_at) = YEAR(CURDATE()) 
            GROUP BY bookings.customer_id 
            ORDER BY bookings DESC 
            LIMIT 10
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode(["success" => $topCustomers]);
    }

    public function createBooking($json)
    {
        $data = json_decode($json, true);

        $carId = $data['car_id'];
        $customerId = $data['customer_id'];
        $rentalStart = $data['rental_start'];
        $rentalEnd = $data['rental_end'];
        $totalPrice = $data['total_price'];
        $status = $data['status'];
        $booking_source = $data['booking_source'];

        // Insert booking into the database
        $insertQuery = "INSERT INTO bookings (car_id, customer_id, rental_start, rental_end, total_price, status, booking_source) 
                        VALUES (:car_id, :customer_id, :rental_start, :rental_end, :total_price, :status, :booking_source)";
        $stmt = $this->conn->prepare($insertQuery);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);
        $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $stmt->bindParam(':rental_start', $rentalStart, PDO::PARAM_STR);
        $stmt->bindParam(':rental_end', $rentalEnd, PDO::PARAM_STR);
        $stmt->bindParam(':total_price', $totalPrice, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':booking_source', $booking_source, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Update the car status to "rented"
            $updateCarStatusQuery = "UPDATE cars SET status = 'rented' WHERE car_id = :car_id";
            $updateStmt = $this->conn->prepare($updateCarStatusQuery);
            $updateStmt->bindParam(':car_id', $carId, PDO::PARAM_INT);
            $updateStmt->execute(); // Execute the update

            return json_encode(array("success" => "Booking created successfully"));
        } else {
            return json_encode(array("error" => "Failed to create booking"));
        }
    }

    public function getMyBookings($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['customer_id'])) {
            return json_encode(array("error" => "Invalid User ID"));
        }

        $customer_id = $data['customer_id'];

        $query = "SELECT `booking_id`, bookings.car_id, `customer_id`, `rental_start`, `rental_end`, `total_price`, bookings.status, bookings.created_at, `booking_source`, cars.make, cars.model FROM `bookings` JOIN cars ON bookings.car_id = cars.car_id
        WHERE `customer_id` = :customer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":customer_id", $customer_id);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode(["success" => $bookings]);
    }


}

$customerProcess = new CustomerProcess();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_REQUEST["operation"]) && isset($_REQUEST["json"])) {
        $operation = $_REQUEST["operation"];
        $json = $_REQUEST["json"];

        switch ($operation) {
            case "getAvailableCars":
                echo $customerProcess->getAvailableCars();
                break;
            case "getAllCars":
                echo $customerProcess->getAllCars();
                break;
            case "getTopCustomers":
                echo $customerProcess->getTopCustomers();
                break;
            case "createBooking":
                echo $customerProcess->createBooking($json);
                break;

            case "getMyBookings":
                echo $customerProcess->getMyBookings($json);
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