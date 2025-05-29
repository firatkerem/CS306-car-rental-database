<?php
require_once '/var/www/html/config/database.php';

$procedures = [];
$result = null;
$error = null;

// Get procedure information
try {
    $query = "SHOW PROCEDURE STATUS WHERE Name = 'GetBranchCars'";
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
    $branch_id = $_POST['branch_id'] ?? '';
    
    if (!empty($branch_id)) {
        try {
            // Stored procedure call
            $stmt = $mysqli->prepare("CALL GetBranchCars(?)");
            $stmt->bind_param("i", $branch_id);
            
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
        $error = "Please select a branch.";
    }
}

// Get existing branches
$branches = [];
try {
    $branches_query = "SELECT branch_id, location, phone FROM Branch ORDER BY branch_id";
    $branches_result = $mysqli->query($branches_query);
    if ($branches_result) {
        while ($row = $branches_result->fetch_assoc()) {
            $branches[] = $row;
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
    <title>Branch Cars Procedure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Branch Cars Procedure</h1>
        
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
                        <p><strong>Procedure Name:</strong> GetBranchCars</p>
                        <p><strong>Parameters:</strong> branch_id (INT)</p>
                        <p><strong>Description:</strong> Lists cars in the specified branch.</p>
                        
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
                                <label for="branch_id" class="form-label">Branch</label>
                                <select class="form-control" id="branch_id" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>">
                                            <?php echo htmlspecialchars($branch['location'] . ' (' . $branch['phone'] . ')'); ?>
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
                        <h5>Procedure Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Car ID</th>
                                        <th>Plate Number</th>
                                        <th>Brand</th>
                                        <th>Model Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['car_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['plate_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($row['model_year']); ?></td>
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
                    No cars found in this branch.
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