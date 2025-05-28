<?php
require_once '/var/www/html/config/database.php';

$procedures = [];
$error = null;

// Get procedure information
try {
    $query = "SHOW PROCEDURE STATUS WHERE Name = 'CreateNewReservation'";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $procedures[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Could not retrieve procedure information: " . $e->getMessage();
}

// Test data insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cus_id = $_POST['cus_id'] ?? '';
    $car_id = $_POST['car_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (!empty($cus_id) && !empty($car_id) && !empty($start_date) && !empty($end_date)) {
        try {
            // Call procedure for testing
            $stmt = $mysqli->prepare("CALL CreateNewReservation(?, ?, ?, ?)");
            $stmt->bind_param("iiss", $cus_id, $car_id, $start_date, $end_date);
            
            if ($stmt->execute()) {
                $success = "New reservation created successfully! Procedure executed.";
            } else {
                $error = "Procedure could not be executed: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Get existing customers
$customers = [];
try {
    $customers_query = "SELECT cus_id, full_name, phone FROM Customer ORDER BY cus_id";
    $customers_result = $mysqli->query($customers_query);
    if ($customers_result) {
        while ($row = $customers_result->fetch_assoc()) {
            $customers[] = $row;
        }
    }
} catch (Exception $e) {
    // Empty array will remain on error
}

// Get existing cars
$cars = [];
try {
    $cars_query = "SELECT car_id, plate_number, brand, model_year FROM Car ORDER BY car_id";
    $cars_result = $mysqli->query($cars_query);
    if ($cars_result) {
        while ($row = $cars_result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
} catch (Exception $e) {
    // Empty array will remain on error
}

// Get recent reservations
$reservations = [];
try {
    $reservations_query = "SELECT r.res_id, r.res_date, c.full_name as customer_name, 
                          car.plate_number, rp.start_date, rp.end_date
                          FROM Reservation r
                          LEFT JOIN CustomerReservation cr ON r.res_id = cr.res_id
                          LEFT JOIN Customer c ON cr.cus_id = c.cus_id
                          LEFT JOIN ReservationCar rc ON r.res_id = rc.res_id
                          LEFT JOIN Car car ON rc.car_id = car.car_id
                          LEFT JOIN ReservationRentalPeriod rrp ON r.res_id = rrp.res_id
                          LEFT JOIN RentalPeriod rp ON rrp.rent_id = rp.rent_id
                          ORDER BY r.res_id DESC LIMIT 10";
    $reservations_result = $mysqli->query($reservations_query);
    if ($reservations_result) {
        while ($row = $reservations_result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
} catch (Exception $e) {
    // Empty array will remain on error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Reservation Procedure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Create New Reservation Procedure</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Procedure Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Procedure Name:</strong> CreateNewReservation</p>
                        <p><strong>Parameters:</strong> cus_id, car_id, start_date, end_date</p>
                        <p><strong>Description:</strong> Creates a complete reservation process (reservation, rental period and relationships).</p>
                        
                        <?php if (!empty($procedures)): ?>
                            <h6 class="mt-3">Procedure Details:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($procedures as $procedure): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($procedure['Name']); ?></td>
                                                <td><?php echo htmlspecialchars($procedure['Type']); ?></td>
                                                <td><?php echo htmlspecialchars($procedure['Created']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                This procedure has not been created yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Procedure</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="cus_id" class="form-label">Customer</label>
                                <select class="form-control" id="cus_id" name="cus_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['cus_id']; ?>">
                                            <?php echo htmlspecialchars($customer['full_name'] . ' (' . $customer['phone'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="car_id" class="form-label">Car</label>
                                <select class="form-control" id="car_id" name="car_id" required>
                                    <option value="">Select Car</option>
                                    <?php foreach ($cars as $car): ?>
                                        <option value="<?php echo $car['car_id']; ?>">
                                            <?php echo htmlspecialchars($car['plate_number'] . ' - ' . $car['brand'] . ' (' . $car['model_year'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+5 days')); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Procedure</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($reservations)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Reservations (Procedure Results)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reservation ID</th>
                                        <th>Customer</th>
                                        <th>Car Plate</th>
                                        <th>Reservation Date</th>
                                        <th>Start</th>
                                        <th>End</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo $reservation['res_id']; ?></td>
                                            <td><?php echo htmlspecialchars($reservation['customer_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['plate_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['res_date']); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['start_date'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($reservation['end_date'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="../" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 