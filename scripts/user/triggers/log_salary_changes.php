<?php
require_once '/var/www/html/config/database.php';

$triggers = [];
$error = null;

// Get trigger information
try {
    $query = "SHOW TRIGGERS LIKE 'trg_salary_audit%'";
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
    $emp_id = $_POST['emp_id'] ?? '';
    $new_salary = $_POST['new_salary'] ?? '';
    
    if (!empty($emp_id) && !empty($new_salary)) {
        try {
            // First check current salary
            $check_query = "SELECT full_name, salary FROM Employee WHERE emp_id = ?";
            $check_stmt = $mysqli->prepare($check_query);
            $check_stmt->bind_param("i", $emp_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $employee = $result->fetch_assoc();
            $check_stmt->close();
            
            if (!$employee) {
                $error = "Employee not found.";
            } else {
                $old_salary = $employee['salary'];
                $employee_name = $employee['full_name'];
                
                // Update salary for testing (trigger will be activated)
                $stmt = $mysqli->prepare("UPDATE Employee SET salary = ? WHERE emp_id = ?");
                $stmt->bind_param("di", $new_salary, $emp_id);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $success = "Employee '$employee_name' salary updated from $" . number_format($old_salary, 2) . " to $" . number_format($new_salary, 2) . ". Trigger executed!";
                    } else {
                        $error = "Salary not changed (same value).";
                    }
                } else {
                    $error = "Salary could not be updated: " . $stmt->error;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Get existing employees
$employees = [];
try {
    $employees_query = "SELECT emp_id, full_name, position, salary FROM Employee ORDER BY emp_id";
    $employees_result = $mysqli->query($employees_query);
    if ($employees_result) {
        while ($row = $employees_result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
} catch (Exception $e) {
    // Empty array will remain on error
}

// Get recent salary change logs
$salary_logs = [];
try {
    $logs_query = "SELECT scl.log_id, scl.emp_id, e.full_name, scl.old_salary, scl.new_salary, scl.changed_at 
                   FROM SalaryChangeLog scl 
                   LEFT JOIN Employee e ON scl.emp_id = e.emp_id 
                   ORDER BY scl.changed_at DESC LIMIT 10";
    $logs_result = $mysqli->query($logs_query);
    if ($logs_result) {
        while ($row = $logs_result->fetch_assoc()) {
            $salary_logs[] = $row;
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
    <title>Salary Change Audit Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Salary Change Audit Trigger</h1>
        
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
                        <p><strong>Trigger Name:</strong> trg_salary_audit</p>
                        <p><strong>Table:</strong> Employee</p>
                        <p><strong>Event:</strong> AFTER UPDATE</p>
                        <p><strong>Description:</strong> Records salary changes to the SalaryChangeLog table.</p>
                        
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
                                <label for="emp_id" class="form-label">Employee</label>
                                <select class="form-control" id="emp_id" name="emp_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['emp_id']; ?>">
                                            <?php echo htmlspecialchars($employee['full_name'] . ' - ' . $employee['position'] . ' (Current: $' . number_format($employee['salary'], 2) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_salary" class="form-label">New Salary ($)</label>
                                <input type="number" step="0.01" class="form-control" id="new_salary" name="new_salary" required>
                                <small class="form-text text-muted">Enter a value different from the current salary</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Trigger</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($employees)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Current Employees</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Position</th>
                                        <th>Salary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo $employee['emp_id']; ?></td>
                                            <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                            <td>$<?php echo number_format($employee['salary'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($salary_logs)): ?>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Salary Change Records (Trigger Results)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee</th>
                                        <th>Old Salary</th>
                                        <th>New Salary</th>
                                        <th>Change Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salary_logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['log_id']; ?></td>
                                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'N/A'); ?></td>
                                            <td>$<?php echo number_format($log['old_salary'], 2); ?></td>
                                            <td>$<?php echo number_format($log['new_salary'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($log['changed_at']); ?></td>
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