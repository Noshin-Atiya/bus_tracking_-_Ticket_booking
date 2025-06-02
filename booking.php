<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();

// Get routes for dropdown
$routes_query = "SELECT * FROM routes ORDER BY route_name";
$routes_result = $conn->query($routes_query);

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_ticket'])) {
    $bus_id = $_POST['bus_id'];
    $route_id = $_POST['route_id'];
    $passenger_name = sanitize($_POST['passenger_name']);
    $passenger_phone = sanitize($_POST['passenger_phone']);
    $seat_numbers = sanitize($_POST['seat_numbers']);
    $journey_date = $_POST['journey_date'];
    
    // ✅ NEW: Check if seats are already booked
    $seat_array = explode(',', $seat_numbers);
    $booked_seats = [];
    
    foreach ($seat_array as $seat) {
        $seat = trim($seat);
        $check_query = "SELECT passenger_name FROM bookings 
                       WHERE bus_id = ? AND journey_date = ? 
                       AND FIND_IN_SET(?, REPLACE(seat_numbers, ' ', '')) > 0 
                       AND status = 'confirmed'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("iss", $bus_id, $journey_date, $seat);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing_booking = $check_result->fetch_assoc();
            $booked_seats[] = $seat . " (booked by " . $existing_booking['passenger_name'] . ")";
        }
    }
    
    // If any seat is already booked, show error
    if (!empty($booked_seats)) {
        $error = "❌ Sorry! These seats are already booked: " . implode(', ', $booked_seats) . ". Please select different seats.";
    } else {
        // Continue with normal booking process
        // Get bus fare
        $fare_query = "SELECT r.fare FROM routes r JOIN buses b ON r.id = b.route_id WHERE b.id = ?";
        $fare_stmt = $conn->prepare($fare_query);
        $fare_stmt->bind_param("i", $bus_id);
        $fare_stmt->execute();
        $fare_result = $fare_stmt->get_result();
        $fare_row = $fare_result->fetch_assoc();
        
        $seat_count = count($seat_array);
        $total_fare = $fare_row['fare'] * $seat_count;
        
        // Insert booking
        $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, bus_id, route_id, passenger_name, passenger_phone, seat_numbers, total_fare, booking_date, journey_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
        $booking_stmt->bind_param("iiisssds", $_SESSION['user_id'], $bus_id, $route_id, $passenger_name, $passenger_phone, $seat_numbers, $total_fare, $journey_date);
        
        if ($booking_stmt->execute()) {
            $booking_id = $conn->insert_id;
            $success = "✅ Booking confirmed! Your booking ID is: " . $booking_id . ". Seats: " . $seat_numbers;
        } else {
            $error = "❌ Booking failed! Please try again.";
        }
    }
}

// Get buses for selected route
if (isset($_GET['route_id'])) {
    $route_id = $_GET['route_id'];
    $buses_query = "SELECT b.*, r.route_name, r.from_city, r.to_city, r.fare FROM buses b JOIN routes r ON b.route_id = r.id WHERE b.route_id = ? AND b.status = 'active'";
    $buses_stmt = $conn->prepare($buses_query);
    $buses_stmt->bind_param("i", $route_id);
    $buses_stmt->execute();
    $buses_result = $buses_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket - BD Bus Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-bus"></i> BD Bus Track
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a class="nav-link" href="dashboard.php">Dashboard</a>
    <a class="nav-link" href="tracking.php">
        <i class="fas fa-satellite-dish"></i> Live Tracking
    </a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-ticket-alt"></i> Book Your Ticket</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Route Selection -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Step 1: Select Route</h5>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-8">
                            <select name="route_id" class="form-select" required>
                                <option value="">Select a route</option>
                                <?php while ($route = $routes_result->fetch_assoc()): ?>
                                    <option value="<?php echo $route['id']; ?>" <?php echo (isset($_GET['route_id']) && $_GET['route_id'] == $route['id']) ? 'selected' : ''; ?>>
                                        <?php echo $route['from_city'] . ' → ' . $route['to_city'] . ' (৳' . $route['fare'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Search Buses</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Available Buses -->
        <?php if (isset($buses_result) && $buses_result->num_rows > 0): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5>Step 2: Select Bus</h5>
            </div>
            <div class="card-body">
                <?php while ($bus = $buses_result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6><?php echo $bus['bus_name']; ?></h6>
                                <small class="text-muted"><?php echo $bus['bus_number']; ?></small>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-info"><?php echo $bus['bus_type']; ?></span>
                            </div>
                            <div class="col-md-2">
                                <small>Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?></small>
                            </div>
                            <div class="col-md-2">
                                <small>Arrival: <?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></small>
                            </div>
                            <div class="col-md-2">
                                <strong>৳<?php echo $bus['fare']; ?></strong>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-sm btn-success" onclick="selectBus(<?php echo $bus['id']; ?>, '<?php echo $bus['bus_name']; ?>', <?php echo $bus['fare']; ?>)">
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <div class="card" id="bookingForm" style="display: none;">
            <div class="card-header">
                <h5>Step 3: Passenger Details</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" id="selected_bus_id" name="bus_id">
                    <input type="hidden" name="route_id" value="<?php echo isset($_GET['route_id']) ? $_GET['route_id'] : ''; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="passenger_name" class="form-label">Passenger Name</label>
                                <input type="text" class="form-control" id="passenger_name" name="passenger_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="passenger_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="passenger_phone" name="passenger_phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                           <div class="mb-3">
    <label for="journey_date" class="form-label">Journey Date</label>
    <input type="date" class="form-control" id="journey_date" name="journey_date" 
           min="<?php echo date('Y-m-d'); ?>" required onchange="checkSeatAvailability()">
</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="seat_numbers" class="form-label">Seat Numbers (comma separated)</label>
                                <input type="text" class="form-control" id="seat_numbers" name="seat_numbers" placeholder="e.g., A1, A2" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="alert alert-info">
                            <strong>Selected Bus:</strong> <span id="selected_bus_name"></span><br>
                            <strong>Fare per seat:</strong> ৳<span id="selected_bus_fare"></span>
                        </div>
                    </div>
                    
                    <button type="submit" name="book_ticket" class="btn btn-success btn-lg">
                        <i class="fas fa-credit-card"></i> Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectBus(busId, busName, fare) {
            document.getElementById('selected_bus_id').value = busId;
            document.getElementById('selected_bus_name').textContent = busName;
            document.getElementById('selected_bus_fare').textContent = fare;
            document.getElementById('bookingForm').style.display = 'block';
            document.getElementById('bookingForm').scrollIntoView();
        }
        // Check seat availability when bus and date are selected
function checkSeatAvailability() {
    const busId = document.getElementById('selected_bus_id').value;
    const journeyDate = document.getElementById('journey_date').value;
    
    if (!busId || !journeyDate) return;
    
    // Show loading
    const seatInput = document.getElementById('seat_numbers');
    if (seatInput) {
        seatInput.placeholder = "Checking availability...";
        
        // Fetch booked seats
        fetch(`check_seats.php?bus_id=${busId}&journey_date=${journeyDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.booked_seats && data.booked_seats.length > 0) {
                    seatInput.placeholder = `Available seats. Booked: ${data.booked_seats.join(', ')}`;
                } else {
                    seatInput.placeholder = "All seats available. e.g., A1, A2";
                }
            })
            .catch(error => {
                seatInput.placeholder = "e.g., A1, A2";
            });
    }
}

// Add event listener when page loads
document.addEventListener('DOMContentLoaded', function() {
    const journeyDateInput = document.getElementById('journey_date');
    if (journeyDateInput) {
        journeyDateInput.addEventListener('change', checkSeatAvailability);
    }
});
    </script>
</body>
</html>