<?php
require_once '/var/www/html/config/database.php';

$procedures = [];
$result = null;
$error = null;

// Get procedure information
try {
    $query = "SHOW PROCEDURE STATUS WHERE Name = 'GetCarDamageHistory'";
    $proc_result = $mysqli->query($query);
    
    if ($proc_result) {
        while ($row = $proc_result->fetch_assoc()) {
            $procedures[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Could not retrieve procedure information: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = $_POST['car_id'] ?? '';
    
    if (!empty($car_id)) {
        try {
            // Stored procedure call
            $stmt = $mysqli->prepare("CALL GetCarDamageHistory(?)");
            $stmt->bind_param("i", $car_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $success = "Procedure executed successfully!";
            } else {
                $error = "Procedure could not be executed: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please select a car.";
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Damage History Procedure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Car Damage History Procedure</h1>
        
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
                        <p><strong>Procedure Name:</strong> GetCarDamageHistory</p>
                        <p><strong>Parameters:</strong> car_id (INT)</p>
                        <p><strong>Description:</strong> Lists the damage history of the specified car.</p>
                        
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
                            
                            <button type="submit" class="btn btn-primary">Test Procedure</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Procedure Results - Damage History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Damage ID</th>
                                        <th>Description</th>
                                        <th>Repair Cost</th>
                                        <th>Record Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['damage_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td>$<?php echo number_format($row['repair_cost'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['record_date']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($result): ?>
            <div class="mt-4">
                <div class="alert alert-info">
                    No damage records found for this car.
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