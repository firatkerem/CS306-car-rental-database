<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user"></i> User Panel</h1>
            <div>
                <a href="../admin/" class="btn btn-outline-secondary">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Triggers</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="triggers/check_rental_period.php">
                                    <i class="fas fa-calendar-check"></i> Rental Period Check
                                </a>
                                <small class="text-muted d-block">Responsible: Utku </small>
                            </li>
                            <li class="list-group-item">
                                <a href="triggers/damage_record_notification.php">
                                    <i class="fas fa-exclamation-triangle"></i> Damage Record Notification
                                </a>
                                <small class="text-muted d-block">Responsible: Omer </small>
                            </li>
                            <li class="list-group-item">
                                <a href="triggers/log_salary_changes.php">
                                    <i class="fas fa-money-bill-wave"></i> Salary Change Log
                                </a>
                                <small class="text-muted d-block">Responsible: Kerem </small>
                            </li>
                            <li class="list-group-item">
                                <a href="triggers/update_car_status_on_rental.php">
                                    <i class="fas fa-car"></i> Update Car Status on Rental
                                </a>
                                <small class="text-muted d-block">Responsible: Mustafa</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-database"></i> Stored Procedures</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="procedures/create_new_reservation.php">
                                    <i class="fas fa-plus-circle"></i> Create New Reservation
                                </a>
                                <small class="text-muted d-block">Responsible: Utku</small>
                            </li>
                            <li class="list-group-item">
                                <a href="procedures/get_branch_cars.php">
                                    <i class="fas fa-building"></i> Get Branch Cars
                                </a>
                                <small class="text-muted d-block">Responsible: Omer</small>
                            </li>
                            <li class="list-group-item">
                                <a href="procedures/get_car_damage_history.php">
                                    <i class="fas fa-history"></i> Car Damage History
                                </a>
                                <small class="text-muted d-block">Responsible: Kerem</small>
                            </li>
                            <li class="list-group-item">
                                <a href="procedures/employee_performance_report.php">
                                    <i class="fas fa-chart-line"></i> Employee Performance Report
                                </a>
                                <small class="text-muted d-block">Responsible: Mustafa</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Support Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-headset"></i> Support</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-6 offset-md-3">
                                <i class="fas fa-ticket-alt fa-3x text-primary mb-3"></i>
                                <h4>Create Support Ticket</h4>
                                <p class="text-muted">
                                    If you are experiencing any issues or need assistance, 
                                    you can create a ticket to contact our support team.
                                </p>
                                <a href="tickets/create.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus"></i> Create New Ticket
                                </a>
                                <br><br>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Your ticket will be reviewed by admin and you will receive a response.
                                </small>
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