<?php
require_once '/var/www/html/config/database.php';

$triggers = [];
$error = null;

// Get trigger information
try {
    $query = "SHOW TRIGGERS LIKE 'trg_damage_notification%'";
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
    $damage_description = $_POST['damage_description'] ?? '';
    $damage_cost = $_POST['damage_cost'] ?? '';
    
    if (!empty($car_id) && !empty($damage_description) && !empty($damage_cost)) {
        try {
            // Add damage record for testing (trigger will be activated)
            $stmt = $mysqli->prepare("INSERT INTO DamageRecord (car_id, description, repair_cost, record_date) VALUES (?, ?, ?, CURDATE())");
            $stmt->bind_param("isd", $car_id, $damage_description, $damage_cost);
            
            if ($stmt->execute()) {
                $success = "Test damage record added. Trigger executed! Check the notifications table.";
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

// Get recent notifications
$notifications = [];
try {
    $notifications_query = "SELECT n.notification_id, n.car_id, n.message, n.created_at, c.plate_number 
                           FROM Notifications n 
                           LEFT JOIN Car c ON n.car_id = c.car_id 
                           ORDER BY n.created_at DESC LIMIT 10";
    $notifications_result = $mysqli->query($notifications_query);
    if ($notifications_result) {
        while ($row = $notifications_result->fetch_assoc()) {
            $notifications[] = $row;
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
    <title>Damage Record Notification Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Damage Record Notification Trigger</h1>
        
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
                        <p><strong>Trigger Name:</strong> trg_damage_notification</p>
                        <p><strong>Table:</strong> DamageRecord</p>
                        <p><strong>Event:</strong> AFTER INSERT</p>
                        <p><strong>Description:</strong> Adds a notification to the Notifications table when a new damage record is added.</p>
                        
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
                                <label for="damage_description" class="form-label">Damage Description</label>
                                <textarea class="form-control" id="damage_description" name="damage_description" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="damage_cost" class="form-label">Repair Cost ($)</label>
                                <input type="number" step="0.01" class="form-control" id="damage_cost" name="damage_cost" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Trigger</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($notifications)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Notifications (Trigger Results)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Car Plate</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr>
                                            <td><?php echo $notification['notification_id']; ?></td>
                                            <td><?php echo htmlspecialchars($notification['plate_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                            <td><?php echo htmlspecialchars($notification['created_at']); ?></td>
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