<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include('../connection.php');

class AdminProc
{
    private $conn;

    public function __construct()
    {
        $this->conn = DatabaseConnection::getInstance()->getConnection();
    }

    // CRUD for Cars

    // Create new car
    public function createCar($json)
    {
        $data = json_decode($json, true);

        $make = $data['make'];
        $model = $data['model'];
        $year = $data['year'];
        $licensePlate = $data['license_plate'];
        $pricePerDay = $data['price_per_day'];
        $status = $data['status'];

        // Handle image upload
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
            $imageName = $_FILES['car_image']['name'];
            $targetDir = '../CAR_IMAGES/';
            $targetFile = $targetDir . basename($imageName);

            // Check if image is a valid image
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                // Move the uploaded file
                if (move_uploaded_file($_FILES['car_image']['tmp_name'], $targetFile)) {
                    // Insert into database
                    $insertQuery = "INSERT INTO cars (make, model, year, license_plate, price_per_day, status, car_image) 
                                    VALUES (:make, :model, :year, :license_plate, :price_per_day, :status, :car_image)";
                    $stmt = $this->conn->prepare($insertQuery);
                    $stmt->bindParam(':make', $make, PDO::PARAM_STR);
                    $stmt->bindParam(':model', $model, PDO::PARAM_STR);
                    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                    $stmt->bindParam(':license_plate', $licensePlate, PDO::PARAM_STR);
                    $stmt->bindParam(':price_per_day', $pricePerDay, PDO::PARAM_STR);
                    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                    $stmt->bindParam(':car_image', $imageName, PDO::PARAM_STR);

                    if ($stmt->execute()) {
                        return json_encode(array("success" => "Car created successfully"));
                    } else {
                        return json_encode(array("error" => "Failed to create car"));
                    }
                } else {
                    return json_encode(array("error" => "Failed to upload image"));
                }
            } else {
                return json_encode(array("error" => "Invalid image format"));
            }
        } else {
            return json_encode(array("error" => "Image not provided or upload error"));
        }
    }


    // Read all cars
    public function readCars()
    {
        $query = "SELECT car_id, make, model, year, license_plate, price_per_day, status, car_image, created_at FROM cars";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    public function getListOfUsers()
    {
        $query = "SELECT `customer_id`, `first_name`, `last_name`, `email`,`phone`, `driver_license` FROM `customers` WHERE 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode(["success" => $users]);
    }


    // Read a single car
    public function readCar($json)
    {
        $data = json_decode($json, true);
        if (!isset($data['car_id'])) {
            return json_encode(["error" => "Car ID is not present"]);
        }

        $query = "SELECT car_id, make, model, year, license_plate, price_per_day, status, created_at FROM cars WHERE car_id = :car_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':car_id', $data['car_id'], PDO::PARAM_INT);
        $stmt->execute();
        $car = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($car) {
            return json_encode($car);
        } else {
            return json_encode(array("error" => "Car not found"));
        }
    }

    // Update car information
    public function updateCar($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['car_id'])) {
            return json_encode(["error" => "Car ID not present"]);
        }

        $carId = $data['car_id'];
        $make = $data['make'];
        $model = $data['model'];
        $year = $data['year'];
        $licensePlate = $data['license_plate'];
        $pricePerDay = $data['price_per_day'];
        $status = $data['status'];
        $imageName = null;

        // Handle image upload if provided
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
            $imageName = $_FILES['car_image']['name'];
            $targetDir = 'Car_Image/';
            $targetFile = $targetDir . basename($imageName);

            // Check if image is a valid image
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                // Move the uploaded file
                if (!move_uploaded_file($_FILES['car_image']['tmp_name'], $targetFile)) {
                    return json_encode(array("error" => "Failed to upload image"));
                }
            } else {
                return json_encode(array("error" => "Invalid image format"));
            }
        }

        $updateQuery = "UPDATE cars SET make = :make, model = :model, year = :year, license_plate = :license_plate, price_per_day = :price_per_day, status = :status" .
            ($imageName ? ", car_image = :car_image" : "") .
            " WHERE car_id = :car_id";

        $stmt = $this->conn->prepare($updateQuery);
        $stmt->bindParam(':make', $make, PDO::PARAM_STR);
        $stmt->bindParam(':model', $model, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->bindParam(':license_plate', $licensePlate, PDO::PARAM_STR);
        $stmt->bindParam(':price_per_day', $pricePerDay, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);

        if ($imageName) {
            $stmt->bindParam(':car_image', $imageName, PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            return json_encode(array("success" => "Car updated successfully"));
        } else {
            return json_encode(array("error" => "Failed to update car"));
        }
    }

    // Delete a car
    public function deleteCar($json)
    {
        $data = json_decode($json, true);

        if (!isset($data['car_id'])) {
            return json_encode(["error" => "Car ID not present"]);
        }

        $carId = $data["car_id"];

        $deleteQuery = "DELETE FROM cars WHERE car_id = :car_id";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bindParam(':car_id', $carId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return json_encode(array("success" => "Car deleted successfully"));
        } else {
            return json_encode(array("error" => "Failed to delete car"));
        }
    }



    // CRUD for Bookings

    // Create new booking
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

    // Read all bookings
    public function readBookings()
    {
        $query = "SELECT 
                    bookings.booking_id, 
                    bookings.car_id, 
                    bookings.customer_id, 
                    bookings.rental_start, 
                    bookings.rental_end, 
                    bookings.total_price, 
                    bookings.status, 
                    bookings.created_at,
                    customers.first_name, 
                    customers.last_name, 
                    customers.email, 
                    customers.phone, 
                    customers.driver_license 
                  FROM bookings
                  JOIN customers ON bookings.customer_id = customers.customer_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode(array("success" => $bookings));
    }


    // Read a single booking
    public function readBooking($bookingId)
    {
        $query = "SELECT booking_id, car_id, customer_id, rental_start, rental_end, total_price, status, created_at FROM bookings WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            return json_encode(array("success" => $booking));
        } else {
            return json_encode(array("error" => "Booking not found"));
        }
    }

    // Update booking information
    public function updateBooking($json)
    {
        $data = json_decode($json, true);
        $status = $data['status'];
        $bookingId = $data['booking_id'];

        // Start a transaction
        $this->conn->beginTransaction();

        try {
            // Update the booking status
            $updateQuery = "UPDATE bookings SET status = :status WHERE booking_id = :booking_id";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update booking");
            }

            // Get the car_id from the booking
            $carQuery = "SELECT car_id FROM bookings WHERE booking_id = :booking_id";
            $carStmt = $this->conn->prepare($carQuery);
            $carStmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
            $carStmt->execute();
            $carId = $carStmt->fetchColumn();

            if ($status === 'completed' || $status === 'canceled') {
                $updateCarQuery = "UPDATE cars SET status = 'available' WHERE car_id = :car_id";
                $carStmt = $this->conn->prepare($updateCarQuery);
                $carStmt->bindParam(':car_id', $carId, PDO::PARAM_INT);

                if (!$carStmt->execute()) {
                    throw new Exception("Failed to update car status");
                }
            }

            // Commit the transaction
            $this->conn->commit();

            return json_encode(array("success" => "Booking updated successfully"));

        } catch (Exception $e) {
            // Rollback the transaction if an error occurred
            $this->conn->rollBack();
            return json_encode(array("error" => $e->getMessage()));
        }
    }

    // Delete a booking
    public function deleteBooking($bookingId)
    {
        $deleteQuery = "DELETE FROM bookings WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return json_encode(array("success" => "Booking deleted successfully"));
        } else {
            return json_encode(array("error" => "Failed to delete booking"));
        }
    }

    public function getIncomeAndBookings()
    {
        // Current year and month
        $currentYear = date('Y');
        $currentMonth = date('m');

        // Query to get the total bookings, annual income, monthly income, number of cars, and cars under maintenance
        $query = "
        SELECT 
            (SELECT IFNULL(SUM(total_price), 0) FROM bookings WHERE YEAR(rental_start) = :currentYear) AS annual_income,
            (SELECT IFNULL(SUM(total_price), 0) FROM bookings WHERE YEAR(rental_start) = :currentYear AND MONTH(rental_start) = :currentMonth) AS monthly_income,
            (SELECT COUNT(booking_id) FROM bookings WHERE YEAR(rental_start) = :currentYear) AS total_bookings,
            (SELECT COUNT(car_id) FROM cars) AS total_cars,
            (SELECT COUNT(car_id) FROM cars WHERE status = 'maintenance') AS cars_under_maintenance
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
        $stmt->bindParam(':currentMonth', $currentMonth, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_encode(array("success" => $result));
        } else {
            return json_encode(array("error" => "Failed to retrieve data"));
        }
    }



}

// Initialize the AdminProc class
$adminProc = new AdminProc();

if ($_SERVER["REQUEST_METHOD"] == "GET" || $_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "PUT" || $_SERVER["REQUEST_METHOD"] == "DELETE") {
    if (isset($_REQUEST["operation"]) && isset($_REQUEST["json"])) {
        $operation = $_REQUEST["operation"];
        $json = $_REQUEST["json"];

        switch ($operation) {
            // Cars Operations
            case "createCar":
                echo $adminProc->createCar($json);
                break;

            case "getCars":
                echo $adminProc->readCars();
                break;

            case "getAvailableCars":
                echo $adminProc->getAvailableCars();
                break;

            case "getListOfUsers":
                echo $adminProc->getListOfUsers();
                break;

            case "getCar":
                echo $adminProc->readCar($json);
                break;

            case "updateCar":
                echo $adminProc->updateCar($json);
                break;

            case "deleteCar":
                echo $adminProc->deleteCar($json);
                break;

            // Bookings Operations
            case "createBooking":
                echo $adminProc->createBooking($json);
                break;

            case "readBookings":
                echo $adminProc->readBookings();
                break;

            case "updateBooking":
                echo $adminProc->updateBooking($json);
                break;

            case "getIncomeAndBookings":
                echo $adminProc->getIncomeAndBookings();
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