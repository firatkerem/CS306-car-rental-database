<?php
require_once '/var/www/html/config/database.php';

$procedures = [];
$result = null;
$error = null;

// Get procedure information
try {
    $query = "SHOW PROCEDURE STATUS WHERE Name = 'EmployeePerformanceReport'";
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
    $emp_id = $_POST['emp_id'] ?? '';
    
    if (!empty($emp_id)) {
        try {
            // Stored procedure call
            $stmt = $mysqli->prepare("CALL EmployeePerformanceReport(?)");
            $stmt->bind_param("i", $emp_id);
            
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
        $error = "Please select an employee.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance Report Procedure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Employee Performance Report Procedure</h1>
        
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
                        <p><strong>Procedure Name:</strong> EmployeePerformanceReport</p>
                        <p><strong>Parameters:</strong> emp_id (INT)</p>
                        <p><strong>Description:</strong> Retrieves employee profile information and branch details.</p>
                        
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
                                <label for="emp_id" class="form-label">Employee</label>
                                <select class="form-control" id="emp_id" name="emp_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['emp_id']; ?>">
                                            <?php echo htmlspecialchars($employee['full_name'] . ' - ' . $employee['position']); ?>
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
                        <h5>Procedure Results - Employee Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Full Name</th>
                                        <th>Position</th>
                                        <th>Salary</th>
                                        <th>Branch</th>
                                        <th>Start Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                                            <td>$<?php echo number_format($row['salary'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($row['branch'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($row['since_date'] ?? 'N/A'); ?></td>
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
                    No information found for this employee.
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