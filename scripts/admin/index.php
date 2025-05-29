<?php
require_once '/var/www/html/config/database.php';
require_once '/var/www/html/config/mongodb.php';

// Demo tickets (instead of MongoDB)
$tickets = [
    [
        '_id' => '1',
        'username' => 'testuser1',
        'subject' => 'Login Problem',
        'message' => 'I cannot log into the system, can you help me?',
        'created_at' => '2024-05-25 10:30:00',
        'comments' => ['First comment', 'Second comment'],
        'status' => true
    ],
    [
        '_id' => '2',
        'username' => 'testuser2',
        'subject' => 'Password Reset',
        'message' => 'I forgot my password, how can I reset it?',
        'created_at' => '2024-05-24 15:45:00',
        'comments' => ['Admin response'],
        'status' => true
    ],
    [
        '_id' => '3',
        'username' => 'admin',
        'subject' => 'System Update',
        'message' => 'I want to get information about the system update.',
        'created_at' => '2024-05-23 09:15:00',
        'comments' => [],
        'status' => true
    ]
];

// Get ticket statistics from MongoDB
$ticketStats = [
    'total' => 0,
    'open' => 0,
    'in_progress' => 0,
    'closed' => 0,
    'urgent' => 0
];

try {
    // Total ticket count
    $ticketStats['total'] = $ticketsCollection->countDocuments();
    
    // Status-based counts
    $ticketStats['open'] = $ticketsCollection->countDocuments(['status' => 'open']);
    $ticketStats['in_progress'] = $ticketsCollection->countDocuments(['status' => 'in_progress']);
    $ticketStats['closed'] = $ticketsCollection->countDocuments(['status' => 'closed']);
    
    // Urgent tickets
    $ticketStats['urgent'] = $ticketsCollection->countDocuments(['priority' => 'urgent']);
    
} catch (Exception $e) {
    // Default values will remain on MongoDB error
    $mongoError = $e->getMessage();
}

// Get recent tickets
$recentTickets = [];
try {
    $cursor = $ticketsCollection->find(
        [],
        [
            'sort' => ['created_at' => -1],
            'limit' => 5
        ]
    );
    foreach ($cursor as $ticket) {
        $recentTickets[] = $ticket;
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
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Panel</h1>
            <div>
                <a href="../user/" class="btn btn-outline-secondary">
                    <i class="fas fa-user"></i> User Panel
                </a>
            </div>
        </div>
        
        <?php if (isset($mongoError)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>MongoDB Connection Warning:</strong> <?php echo htmlspecialchars($mongoError); ?>
            </div>
        <?php endif; ?>
        
        <!-- Ticket Statistics -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h3><i class="fas fa-chart-bar"></i> Ticket Statistics</h3>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ticket-alt fa-2x text-primary mb-2"></i>
                        <h4 class="card-title text-primary"><?php echo $ticketStats['total']; ?></h4>
                        <p class="card-text">Total Tickets</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-folder-open fa-2x text-success mb-2"></i>
                        <h4 class="card-title text-success"><?php echo $ticketStats['open']; ?></h4>
                        <p class="card-text">Open</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-cog fa-2x text-warning mb-2"></i>
                        <h4 class="card-title text-warning"><?php echo $ticketStats['in_progress']; ?></h4>
                        <p class="card-text">In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-secondary mb-2"></i>
                        <h4 class="card-title text-secondary"><?php echo $ticketStats['closed']; ?></h4>
                        <p class="card-text">Closed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h4 class="card-title text-danger"><?php echo $ticketStats['urgent']; ?></h4>
                        <p class="card-text">Urgent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body">
                        <a href="tickets/" class="btn btn-primary btn-lg">
                            <i class="fas fa-cogs"></i><br>
                            Ticket Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-clock"></i> Recent Tickets</h5>
                <a href="tickets/" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list"></i> View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentTickets)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i>
                        No tickets have been created yet.
                        <br><br>
                        <a href="../user/tickets/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create First Ticket
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentTickets as $ticket): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <i class="fas fa-ticket-alt"></i>
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            <strong>User:</strong> <?php echo htmlspecialchars($ticket['username']); ?><br>
                                            <?php echo htmlspecialchars(substr($ticket['description'], 0, 100)) . (strlen($ticket['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            $date = $ticket['created_at']->toDateTime();
                                            echo $date->format('d.m.Y H:i');
                                            ?>
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <span class="badge bg-<?php 
                                            echo $ticket['priority'] === 'urgent' ? 'danger' : 
                                                ($ticket['priority'] === 'high' ? 'warning' : 
                                                ($ticket['priority'] === 'medium' ? 'info' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                        <br>
                                        <span class="badge bg-<?php 
                                            echo $ticket['status'] === 'open' ? 'success' : 
                                                ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                        ?> mt-1">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                        <br>
                                        <div class="btn-group btn-group-sm mt-2">
                                            <a href="tickets/view.php?id=<?php echo $ticket['_id']; ?>" 
                                               class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($ticket['status'] !== 'closed'): ?>
                                                <a href="tickets/respond.php?id=<?php echo $ticket['_id']; ?>" 
                                                   class="btn btn-outline-success" title="Respond">
                                                    <i class="fas fa-reply"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Access -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-rocket"></i> Quick Access</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="tickets/" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-list"></i><br>
                                    All Tickets
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="tickets/index.php?status=open" class="btn btn-outline-success w-100 mb-2">
                                    <i class="fas fa-folder-open"></i><br>
                                    Open Tickets
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="tickets/index.php?priority=urgent" class="btn btn-outline-danger w-100 mb-2">
                                    <i class="fas fa-exclamation-triangle"></i><br>
                                    Urgent Tickets
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../user/tickets/create.php" class="btn btn-outline-info w-100 mb-2">
                                    <i class="fas fa-plus"></i><br>
                                    Create New Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 