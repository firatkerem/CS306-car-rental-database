<?php
require_once '/var/www/html/config/database.php';

$triggers = [];
$error = null;

// Get trigger information
try {
    $query = "SHOW TRIGGERS LIKE 'trg_check_insurance%'";
    $result = $mysqli->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $triggers[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Could not retrieve trigger information: " . $e->getMessage();
}

// Test data insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = $_POST['car_id'] ?? '';
    $policy_num = $_POST['policy_num'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (!empty($car_id) && !empty($policy_num) && !empty($start_date) && !empty($end_date)) {
        try {
            // Add insurance record for testing (trigger will be activated)
            $stmt = $mysqli->prepare("INSERT INTO CarInsurance (car_id, policy_num, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $car_id, $policy_num, $start_date, $end_date);
            
            if ($stmt->execute()) {
                $success = "Test insurance record added. Trigger executed!";
            } else {
                $error = "Test record could not be added: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
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

// Get recent insurance records
$insurances = [];
try {
    $insurance_query = "SELECT ci.ins_id, ci.car_id, c.plate_number, ci.policy_num, ci.start_date, ci.end_date 
                       FROM CarInsurance ci 
                       LEFT JOIN Car c ON ci.car_id = c.car_id 
                       ORDER BY ci.ins_id DESC LIMIT 10";
    $insurance_result = $mysqli->query($insurance_query);
    if ($insurance_result) {
        while ($row = $insurance_result->fetch_assoc()) {
            $insurances[] = $row;
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
    <title>Insurance Date Check Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Insurance Date Check Trigger</h1>
        
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
                        <h5>Trigger Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Trigger Name:</strong> trg_check_insurance</p>
                        <p><strong>Table:</strong> CarInsurance</p>
                        <p><strong>Event:</strong> BEFORE INSERT/UPDATE</p>
                        <p><strong>Description:</strong> Checks that the insurance end date must be after the start date.</p>
                        
                        <?php if (!empty($triggers)): ?>
                            <h6 class="mt-3">Existing Triggers:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Trigger</th>
                                            <th>Event</th>
                                            <th>Table</th>
                                            <th>Timing</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($triggers as $trigger): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($trigger['Trigger']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Event']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Table']); ?></td>
                                                <td><?php echo htmlspecialchars($trigger['Timing']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Trigger</h5>
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
                            
                            <div class="mb-3">
                                <label for="policy_num" class="form-label">Policy Number</label>
                                <input type="text" class="form-control" id="policy_num" name="policy_num" 
                                       value="TEST-<?php echo rand(1000, 9999); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                                <small class="form-text text-muted">If you enter a date before the start date, the trigger will throw an error</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Trigger</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($insurances)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Insurance Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Car Plate</th>
                                        <th>Policy No</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($insurances as $insurance): ?>
                                        <tr>
                                            <td><?php echo $insurance['ins_id']; ?></td>
                                            <td><?php echo htmlspecialchars($insurance['plate_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($insurance['policy_num']); ?></td>
                                            <td><?php echo htmlspecialchars($insurance['start_date']); ?></td>
                                            <td><?php echo htmlspecialchars($insurance['end_date']); ?></td>
                                            <td>
                                                <?php 
                                                $end_date = new DateTime($insurance['end_date']);
                                                $today = new DateTime();
                                                if ($end_date > $today) {
                                                    echo '<span class="badge bg-success">Active</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">Expired</span>';
                                                }
                                                ?>
                                            </td>
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