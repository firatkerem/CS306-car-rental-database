<?php
// Set UTF-8 output header
header('Content-Type: text/html; charset=utf-8');

require_once '/var/www/html/config/mongodb.php';
require_once '/var/www/html/config/database.php';

$success = null;
$error = null;

// Get users from MySQL
$users = [];
try {
    // Get customers
    $customers_query = "SELECT cus_id, full_name, phone FROM Customer ORDER BY full_name";
    $customers_result = $mysqli->query($customers_query);
    if ($customers_result) {
        while ($row = $customers_result->fetch_assoc()) {
            $users[] = [
                'id' => 'customer_' . $row['cus_id'],
                'name' => $row['full_name'],
                'type' => 'Customer',
                'info' => $row['phone']
            ];
        }
    }
    
    // Get employees
    $employees_query = "SELECT emp_id, full_name, position FROM Employee ORDER BY full_name";
    $employees_result = $mysqli->query($employees_query);
    if ($employees_result) {
        while ($row = $employees_result->fetch_assoc()) {
            $users[] = [
                'id' => 'employee_' . $row['emp_id'],
                'name' => $row['full_name'],
                'type' => 'Employee',
                'info' => $row['position']
            ];
        }
    }
    
} catch (Exception $e) {
    $error = "MySQL user error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    if (!empty($username) && !empty($subject) && !empty($description)) {
        try {
            // Add new ticket to MongoDB
            $ticket = [
                'username' => $username,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'status' => 'open',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'responses' => []
            ];
            
            $result = $ticketsCollection->insertOne($ticket);
            
            if ($result->getInsertedCount() > 0) {
                $success = "Ticket created successfully! Ticket ID: " . substr((string)$result->getInsertedId(), -8);
                // Clear form
                $_POST = [];
            } else {
                $error = "Ticket could not be created.";
            }
        } catch (Exception $e) {
            $error = "MongoDB error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Support Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4 text-center">Create Support Ticket</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <br><small class="text-muted">Your ticket will be reviewed by admin and you will receive a response.</small>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-ticket-alt"></i> Ticket Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Select User *</label>
                                <select class="form-select" id="username" name="username" required>
                                    <option value="">Select user...</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['name']); ?>" 
                                                <?php echo ($_POST['username'] ?? '') === $user['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?> 
                                            (<?php echo htmlspecialchars($user['type']); ?> - <?php echo htmlspecialchars($user['info']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                                       placeholder="Briefly summarize your ticket subject" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority Level</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low - General questions</option>
                                    <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium - Standard support</option>
                                    <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High - Important issue</option>
                                    <option value="urgent" <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent - Critical issue</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Detailed Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="6" 
                                          placeholder="Describe your issue or request in detail..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <div class="form-text">The more detailed your description, the faster we can find a solution.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane"></i> Create Ticket
                                </button>
                                <a href="../" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Home
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle"></i> Information</h6>
                        <ul class="small mb-0">
                            <li>Your ticket will be reviewed by admin after creation</li>
                            <li>Response time may vary based on priority level</li>
                            <li>Select "Urgent" priority level for critical issues</li>
                            <li>Contact admin for information about your ticket status</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html> 