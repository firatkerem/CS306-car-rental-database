<?php
// Set UTF-8 output header
header('Content-Type: text/html; charset=utf-8');

require_once '/var/www/html/config/mongodb.php';
require_once '/var/www/html/config/database.php';

$ticketId = $_GET['id'] ?? '';
$ticket = null;
$error = null;
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
        $stmt = $mysqli->prepare("SELECT 'Customer' as type, full_name, phone as info, email FROM Customer WHERE full_name = ?");
        $stmt->bind_param("s", $ticket['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $userInfo = $result->fetch_assoc();
        $stmt->close();
        
        // If not found, search in employee table
        if (!$userInfo) {
            $stmt = $mysqli->prepare("SELECT 'Employee' as type, full_name, position as info, email FROM Employee WHERE full_name = ?");
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
    <title>Admin - Ticket Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-ticket-alt"></i> Ticket Details</h1>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Ticket List
                        </a>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($ticket): ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
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
                                        Ticket ID: <code><?php echo substr((string)$ticket['_id'], -8); ?></code>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <h6><i class="fas fa-align-left"></i> Description:</h6>
                                    <div class="border rounded p-3 bg-light mb-4">
                                        <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                                    </div>
                                    
                                    <?php if (!empty($ticket['responses'])): ?>
                                        <h6><i class="fas fa-comments"></i> Responses (<?php echo count($ticket['responses']); ?>):</h6>
                                        <?php 
                                        // Convert MongoDB BSONArray to PHP array
                                        $responses = iterator_to_array($ticket['responses']);
                                        foreach ($responses as $index => $response): 
                                        ?>
                                            <div class="border rounded p-3 mb-3 <?php echo $index % 2 === 0 ? 'bg-light' : 'bg-white'; ?>">
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
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            No responses have been given to this ticket yet.
                                        </div>
                                    <?php endif; ?>
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
                                            <td><code><?php echo $ticket['_id']; ?></code></td>
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
                                                echo $date->format('d.m.Y H:i:s');
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>
                                                <?php 
                                                $date = $ticket['updated_at']->toDateTime();
                                                echo $date->format('d.m.Y H:i:s');
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
                                            <?php if (!empty($userInfo['email'])): ?>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td>
                                                        <a href="mailto:<?php echo htmlspecialchars($userInfo['email']); ?>">
                                                            <?php echo htmlspecialchars($userInfo['email']); ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2">
                                                    <small class="text-muted">User details not found</small>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-cogs"></i> Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <?php if ($ticket['status'] !== 'closed'): ?>
                                            <a href="respond.php?id=<?php echo $ticket['_id']; ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-reply"></i> Respond
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-list"></i> Ticket List
                                        </a>
                                        
                                        <a href="../" class="btn btn-outline-primary">
                                            <i class="fas fa-home"></i> Admin Panel
                                        </a>
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
</body>
</html> 