<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$conn = getConnection();

// Get statistics
$stats = [];

// Total buses
$buses_query = "SELECT COUNT(*) as total FROM buses";
$buses_result = $conn->query($buses_query);
$stats['total_buses'] = $buses_result->fetch_assoc()['total'];

// Total routes
$routes_query = "SELECT COUNT(*) as total FROM routes";
$routes_result = $conn->query($routes_query);
$stats['total_routes'] = $routes_result->fetch_assoc()['total'];

// Total bookings today
$bookings_query = "SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = CURDATE()";
$bookings_result = $conn->query($bookings_query);
$stats['today_bookings'] = $bookings_result->fetch_assoc()['total'];

// Total revenue today
$revenue_query = "SELECT SUM(total_fare) as total FROM bookings WHERE DATE(created_at) = CURDATE()";
$revenue_result = $conn->query($revenue_query);
$stats['today_revenue'] = $revenue_result->fetch_assoc()['total'] ?: 0;

// Recent bookings
$recent_bookings_query = "SELECT b.*, u.name as user_name, bus.bus_name, r.route_name 
                          FROM bookings b 
                          JOIN users u ON b.user_id = u.id 
                          JOIN buses bus ON b.bus_id = bus.id 
                          JOIN routes r ON b.route_id = r.id 
                          ORDER BY b.created_at DESC 
                          LIMIT 10";
$recent_bookings_result = $conn->query($recent_bookings_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BD Bus Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.html">
                <i class="fas fa-bus"></i> BD Bus Track - Admin
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="buses.php">Manage Buses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="routes.php">Manage Routes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">Bookings</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="navbar-text me-3">Admin: <?php echo $_SESSION['user_name']; ?></span>
                    <a class="nav-link" href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['total_buses']; ?></h4>
                                <p>Total Buses</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-bus fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['total_routes']; ?></h4>
                                <p>Total Routes</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-route fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo $stats['today_bookings']; ?></h4>
                                <p>Today's Bookings</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>৳<?php echo number_format($stats['today_revenue']); ?></h4>
                                <p>Today's Revenue</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-money-bill fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <a href="add_bus.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Add Bus
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="add_route.php" class="btn btn-success w-100">
                                    <i class="fas fa-plus"></i> Add Route
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="buses.php" class="btn btn-info w-100">
                                    <i class="fas fa-bus"></i> Manage Buses
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="routes.php" class="btn btn-warning w-100">
                                    <i class="fas fa-route"></i> Manage Routes
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="bookings.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-list"></i> All Bookings
                                </a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="reports.php" class="btn btn-dark w-100">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Recent Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Bus</th>
                                <th>Route</th>
                                <th>Journey Date</th>
                                <th>Fare</th>
                                <th>Status</th>
                                <th>Booked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo $booking['user_name']; ?></td>
                                <td><?php echo $booking['bus_name']; ?></td>
                                <td><?php echo $booking['route_name']; ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['journey_date'])); ?></td>
                                <td>৳<?php echo number_format($booking['total_fare']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo ucfirst($booking['status']); ?></span>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>