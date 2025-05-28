<?php
// Set UTF-8 output header
header('Content-Type: text/html; charset=utf-8');

require_once '/var/www/html/config/mongodb.php';
require_once '/var/www/html/config/database.php';

$error = null;
$tickets = [];
$stats = [
    'total' => 0,
    'open' => 0,
    'in_progress' => 0,
    'closed' => 0,
    'urgent' => 0,
    'high' => 0
];

// Filter parameters
$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$user_filter = $_GET['user'] ?? '';

try {
    // Create filter
    $filter = [];
    if (!empty($status_filter)) {
        $filter['status'] = $status_filter;
    }
    if (!empty($priority_filter)) {
        $filter['priority'] = $priority_filter;
    }
    if (!empty($user_filter)) {
        $filter['username'] = ['$regex' => $user_filter, '$options' => 'i'];
    }
    
    // Get tickets
    $options = ['sort' => ['created_at' => -1], 'limit' => 100];
    $cursor = $ticketsCollection->find($filter, $options);
    foreach ($cursor as $ticket) {
        $tickets[] = $ticket;
    }
    
    // Calculate statistics
    $allTickets = $ticketsCollection->find();
    foreach ($allTickets as $ticket) {
        $stats['total']++;
        $stats[$ticket['status']] = ($stats[$ticket['status']] ?? 0) + 1;
        if ($ticket['priority'] === 'urgent' || $ticket['priority'] === 'high') {
            $stats[$ticket['priority']]++;
        }
    }
    
} catch (Exception $e) {
    $error = "MongoDB error: " . $e->getMessage();
}

// Get unique users
$users = [];
try {
    $pipeline = [
        ['$group' => ['_id' => '$username']],
        ['$sort' => ['_id' => 1]]
    ];
    $result = $ticketsCollection->aggregate($pipeline);
    foreach ($result as $doc) {
        if (!empty($doc['_id'])) {
            $users[] = $doc['_id'];
        }
    }
} catch (Exception $e) {
    // Empty array will remain on error
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
    <title>Admin - Ticket Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-ticket-alt"></i> Ticket Management</h1>
                    <div>
                        <a href="../" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Admin Panel
                        </a>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo $stats['total']; ?></h5>
                                <p class="card-text small">Total Tickets</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success"><?php echo $stats['open'] ?? 0; ?></h5>
                                <p class="card-text small">Open</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo $stats['in_progress'] ?? 0; ?></h5>
                                <p class="card-text small">In Progress</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-secondary"><?php echo $stats['closed'] ?? 0; ?></h5>
                                <p class="card-text small">Closed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-danger"><?php echo $stats['urgent'] ?? 0; ?></h5>
                                <p class="card-text small">Urgent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning"><?php echo $stats['high'] ?? 0; ?></h5>
                                <p class="card-text small">High Priority</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="">All Priorities</option>
                                    <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" 
                                       value="<?php echo htmlspecialchars($user_filter); ?>" 
                                       placeholder="Search username...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php if (!empty($status_filter) || !empty($priority_filter) || !empty($user_filter)): ?>
                            <div class="mt-2">
                                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Ticket List -->
                <div class="card">
                    <div class="card-header">
                        <h5>
                            <i class="fas fa-list"></i> Tickets 
                            <span class="badge bg-primary"><?php echo count($tickets); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> 
                                <?php if (!empty($status_filter) || !empty($priority_filter) || !empty($user_filter)): ?>
                                    No tickets found matching the filters.
                                <?php else: ?>
                                    No tickets have been created yet.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Subject</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?php echo substr((string)$ticket['_id'], -8); ?></code>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ticket['username']); ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($ticket['subject']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars(substr($ticket['description'], 0, 80)); ?>
                                                            <?php if (strlen($ticket['description']) > 80): ?>...<?php endif; ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo getPriorityBadge($ticket['priority']); ?>">
                                                        <?php echo getPriorityText($ticket['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadge($ticket['status']); ?>">
                                                        <?php echo getStatusText($ticket['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                        $date = $ticket['created_at']->toDateTime();
                                                        echo $date->format('d.m.Y H:i');
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                        $date = $ticket['updated_at']->toDateTime();
                                                        echo $date->format('d.m.Y H:i');
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view.php?id=<?php echo $ticket['_id']; ?>" 
                                                           class="btn btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($ticket['status'] !== 'closed'): ?>
                                                            <a href="respond.php?id=<?php echo $ticket['_id']; ?>" 
                                                               class="btn btn-outline-success" title="Respond">
                                                                <i class="fas fa-reply"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 