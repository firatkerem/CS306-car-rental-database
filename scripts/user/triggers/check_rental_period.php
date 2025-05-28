<?php
require_once '/var/www/html/config/database.php';

$triggers = [];
$error = null;

// Get trigger information
try {
    $query = "SHOW TRIGGERS LIKE 'trg_check_rental_period%'";
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
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (!empty($start_date) && !empty($end_date)) {
        try {
            // Add rental period for testing (trigger will be activated)
            $stmt = $mysqli->prepare("INSERT INTO RentalPeriod (start_date, end_date) VALUES (?, ?)");
            $stmt->bind_param("ss", $start_date, $end_date);
            
            if ($stmt->execute()) {
                $success = "Test rental period added. Trigger executed!";
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

// Get existing rental periods
$rental_periods = [];
try {
    $query = "SELECT rent_id, start_date, end_date FROM RentalPeriod ORDER BY rent_id DESC LIMIT 10";
    $result = $mysqli->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rental_periods[] = $row;
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
    <title>Rental Period Check Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Rental Period Check Trigger</h1>
        
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
                        <p><strong>Trigger Name:</strong> trg_check_rental_period</p>
                        <p><strong>Table:</strong> RentalPeriod</p>
                        <p><strong>Event:</strong> BEFORE INSERT/UPDATE</p>
                        <p><strong>Description:</strong> Checks that the end date must be after the start date.</p>
                        
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
                        <?php else: ?>
                            <div class="alert alert-warning">
                                This trigger has not been created yet.
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
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+5 days')); ?>" required>
                                <small class="form-text text-muted">If you enter a date before the start date, the trigger will throw an error</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Trigger</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($rental_periods)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Rental Periods</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Duration (Days)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rental_periods as $period): ?>
                                        <tr>
                                            <td><?php echo $period['rent_id']; ?></td>
                                            <td><?php echo htmlspecialchars($period['start_date']); ?></td>
                                            <td><?php echo htmlspecialchars($period['end_date']); ?></td>
                                            <td>
                                                <?php 
                                                $start = new DateTime($period['start_date']);
                                                $end = new DateTime($period['end_date']);
                                                $diff = $start->diff($end);
                                                echo $diff->days;
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