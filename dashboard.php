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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Inter', sans-serif;
        }

        .navbar {
            background-color: #0d6efd !important;
        }

        .navbar .nav-link,
        .navbar .navbar-brand,
        .navbar .navbar-text {
            color: #ffffff !important;
        }

        .card.bg-primary {
            background-color: #4e73df !important;
        }

        .card.bg-success {
            background-color: #1cc88a !important;
        }

        .card.bg-info {
            background-color: #36b9cc !important;
        }

        .card.bg-warning {
            background-color: #f6c23e !important;
            color: #212529 !important;
        }

        .card.action-card {
            transition: 0.3s ease;
            border-radius: 12px;
            border: none;
        }

        .card.action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        table thead {
            background-color: #e3e6f0;
        }

        table tbody tr:hover {
            background-color: #f1f7ff;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.html">
                <i class="fas fa-bus"></i> BD Bus Track - Admin
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-ticket-alt"></i> Bookings
                        </a>
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
        <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4><?php echo $stats['total_buses']; ?></h4>
                            <p class="mb-0">Total Buses</p>
                        </div>
                        <i class="fas fa-bus fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4><?php echo $stats['total_routes']; ?></h4>
                            <p class="mb-0">Total Routes</p>
                        </div>
                        <i class="fas fa-route fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4><?php echo $stats['today_bookings']; ?></h4>
                            <p class="mb-0">Today's Bookings</p>
                        </div>
                        <i class="fas fa-ticket-alt fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4>৳<?php echo number_format($stats['today_revenue']); ?></h4>
                            <p class="mb-0">Today's Revenue</p>
                        </div>
                        <i class="fas fa-money-bill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <!-- Add Bus -->
                            <div class="col-md-4 col-sm-6 mb-3">
                                <a href="add_bus.php" class="text-decoration-none">
                                    <div class="card action-card bg-primary text-white h-100">
                                        <div class="card-body">
                                            <i class="fas fa-bus fa-2x mb-2"></i>
                                            <h6>Add Bus</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Add Route -->
                            <div class="col-md-4 col-sm-6 mb-3">
                                <a href="add_route.php" class="text-decoration-none">
                                    <div class="card action-card bg-success text-white h-100">
                                        <div class="card-body">
                                            <i class="fas fa-route fa-2x mb-2"></i>
                                            <h6>Add Route</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- All Bookings -->
                            <div class="col-md-4 col-sm-6 mb-3">
                                <a href="bookings.php" class="text-decoration-none">
                                    <div class="card action-card bg-info text-white h-100">
                                        <div class="card-body">
                                            <i class="fas fa-list fa-2x mb-2"></i>
                                            <h6>All Bookings</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Recent Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Booking Assistant</th>
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
                                <td><span class="badge bg-success"><?php echo ucfirst($booking['status']); ?></span></td>
                                <td><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
