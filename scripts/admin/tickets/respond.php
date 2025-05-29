<?php
// Set UTF-8 output header
header('Content-Type: text/html; charset=utf-8');

require_once '/var/www/html/config/mongodb.php';
require_once '/var/www/html/config/database.php';

$ticketId = $_GET['id'] ?? '';
$ticket = null;
$error = null;
$success = null;
$userInfo = null;

// Get ticket from MongoDB
if (!empty($ticketId)) {
    try {
        $ticket = $ticketsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($ticketId)]);
        if (!$ticket) {
            $error = "Ticket not found.";
        }
    } catch (Exception $e) {
        $error = "MongoDB error: " . $e->getMessage();
    }
} else {
    $error = "Ticket ID not specified.";
}

// Get user information from MySQL
if ($ticket) {
    try {
        // First search in customer table
        $stmt = $mysqli->prepare("SELECT 'Customer' as type, full_name, phone as info FROM Customer WHERE full_name = ?");
        $stmt->bind_param("s", $ticket['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userInfo = $result->fetch_assoc();
        $stmt->close();
        
        // If not found, search in employee table
        if (!$userInfo) {
            $stmt = $mysqli->prepare("SELECT 'Employee' as type, full_name, position as info FROM Employee WHERE full_name = ?");
            $stmt->bind_param("s", $ticket['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            $userInfo = $result->fetch_assoc();
            $stmt->close();
        }
    } catch (Exception $e) {
        // User info will not be available on MySQL error
    }
}

// Add response process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ticket) {
    $response = trim($_POST['response'] ?? '');
    $newStatus = $_POST['status'] ?? $ticket['status'];
    $adminName = trim($_POST['admin_name'] ?? 'Admin');
    
    if (!empty($response)) {
        try {
            $newResponse = [
                'message' => $response,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'author' => $adminName
            ];
            
            $updateResult = $ticketsCollection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($ticketId)],
                [
                    '$push' => ['responses' => $newResponse],
                    '$set' => [
                        'status' => $newStatus,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($updateResult->getModifiedCount() > 0) {
                $success = "Response added successfully and ticket status updated.";
                // Reload ticket
                $ticket = $ticketsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($ticketId)]);
                // Clear form
                $_POST = [];
            } else {
                $error = "Response could not be added.";
            }
        } catch (Exception $e) {
            $error = "MongoDB error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a response message.";
    }
}

// Helper functions
function getPriorityBadge($priority) {
    switch ($priority) {
        case 'urgent': return 'bg-danger';
        case 'high': return 'bg-warning text-dark';
        case 'medium': return 'bg-info';
        case 'low': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'open': return 'bg-success';
        case 'in_progress': return 'bg-warning text-dark';
        case 'closed': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getPriorityText($priority) {
    switch ($priority) {
        case 'urgent': return 'Urgent';
        case 'high': return 'High';
        case 'medium': return 'Medium';
        case 'low': return 'Low';
        default: return ucfirst($priority);
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'open': return 'Open';
        case 'in_progress': return 'In Progress';
        case 'closed': return 'Closed';
        default: return ucfirst($status);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Respond to Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-reply"></i> Respond to Ticket</h1>
                    <div>
                        <a href="view.php?id=<?php echo $ticketId; ?>" class="btn btn-outline-info">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Ticket List
                        </a>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($ticket): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Ticket Summary -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>
                                            <i class="fas fa-file-alt"></i>
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </h5>
                                        <div>
                                            <span class="badge <?php echo getPriorityBadge($ticket['priority']); ?>">
                                                <?php echo getPriorityText($ticket['priority']); ?>
                                            </span>
                                            <span class="badge <?php echo getStatusBadge($ticket['status']); ?>">
                                                <?php echo getStatusText($ticket['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Ticket ID: <code><?php echo substr((string)$ticket['_id'], -8); ?></code> | 
                                        User: <strong><?php echo htmlspecialchars($ticket['username']); ?></strong>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <h6><i class="fas fa-align-left"></i> Original Message:</h6>
                                    <div class="border rounded p-3 bg-light">
                                        <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Previous Responses -->
                            <?php if (!empty($ticket['responses'])): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5><i class="fas fa-history"></i> Previous Responses (<?php echo count($ticket['responses']); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        // Convert MongoDB BSONArray to PHP array
                                        $responses = iterator_to_array($ticket['responses']);
                                        $reversedResponses = array_reverse($responses);
                                        foreach ($reversedResponses as $index => $response): 
                                        ?>
                                            <div class="border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong>
                                                            <i class="fas fa-user-tie"></i>
                                                            <?php echo htmlspecialchars($response['author']); ?>
                                                        </strong>
                                                        <span class="badge bg-primary ms-2">Admin</span>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i>
                                                        <?php 
                                                        $date = $response['created_at']->toDateTime();
                                                        echo $date->format('d.m.Y H:i:s');
                                                        ?>
                                                    </small>
                                                </div>
                                                <div class="mt-2">
                                                    <?php echo nl2br(htmlspecialchars($response['message'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Response Form -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-edit"></i> Add New Response</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="admin_name" class="form-label">Admin Name</label>
                                            <input type="text" class="form-control" id="admin_name" name="admin_name" 
                                                   value="<?php echo htmlspecialchars($_POST['admin_name'] ?? 'Support Team'); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="response" class="form-label">Response Message *</label>
                                            <textarea class="form-control" id="response" name="response" rows="6" 
                                                      placeholder="Write your response to the user here..." required><?php echo htmlspecialchars($_POST['response'] ?? ''); ?></textarea>
                                            <div class="form-text">Provide a detailed response that will help the user.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Update Ticket Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="open" <?php echo ($ticket['status'] === 'open') ? 'selected' : ''; ?>>
                                                    Open - More information needed
                                                </option>
                                                <option value="in_progress" <?php echo ($ticket['status'] === 'in_progress') ? 'selected' : ''; ?>>
                                                    In Progress - Working on the issue
                                                </option>
                                                <option value="closed" <?php echo ($ticket['status'] === 'closed') ? 'selected' : ''; ?>>
                                                    Closed - Issue resolved
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" class="btn btn-success btn-lg">
                                                <i class="fas fa-paper-plane"></i> Send Response
                                            </button>
                                            <a href="view.php?id=<?php echo $ticketId; ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Ticket Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-info-circle"></i> Ticket Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td><code><?php echo substr((string)$ticket['_id'], -8); ?></code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadge($ticket['status']); ?>">
                                                    <?php echo getStatusText($ticket['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Priority:</strong></td>
                                            <td>
                                                <span class="badge <?php echo getPriorityBadge($ticket['priority']); ?>">
                                                    <?php echo getPriorityText($ticket['priority']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>
                                                <?php 
                                                $date = $ticket['created_at']->toDateTime();
                                                echo $date->format('d.m.Y H:i');
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Response Count:</strong></td>
                                            <td><?php echo count($ticket['responses'] ?? []); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- User Information -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-user"></i> User Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                        </tr>
                                        <?php if ($userInfo): ?>
                                            <tr>
                                                <td><strong>Type:</strong></td>
                                                <td>
                                                    <span class="badge <?php echo $userInfo['type'] === 'Customer' ? 'bg-info' : 'bg-warning text-dark'; ?>">
                                                        <?php echo htmlspecialchars($userInfo['type']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Info:</strong></td>
                                                <td><?php echo htmlspecialchars($userInfo['info']); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Quick Response Templates -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-magic"></i> Quick Responses</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="insertTemplate('Hello,\n\nThank you for your inquiry. We are reviewing the issue and will get back to you as soon as possible.\n\nBest regards,\nSupport Team')">
                                            <i class="fas fa-clock"></i> Review Message
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm" 
                                                onclick="insertTemplate('Hello,\n\nYour issue has been resolved. Please check and let us know if you need any further assistance.\n\nBest regards,\nSupport Team')">
                                            <i class="fas fa-check"></i> Resolution Message
                                        </button>
                                        <button type="button" class="btn btn-outline-info btn-sm" 
                                                onclick="insertTemplate('Hello,\n\nTo better understand your issue, we need additional information. Please provide the following details:\n\n- \n- \n- \n\nThank you,\nSupport Team')">
                                            <i class="fas fa-question"></i> Information Request
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function insertTemplate(template) {
            const responseTextarea = document.getElementById('response');
            responseTextarea.value = template;
            responseTextarea.focus();
        }
    </script>
</body>
</html> 