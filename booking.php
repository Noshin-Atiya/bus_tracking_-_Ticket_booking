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
    $bus_id = (int)$_POST['bus_id'];
    $route_id = (int)$_POST['route_id'];
    $passenger_name = sanitize($_POST['passenger_name']);
    $passenger_phone = sanitize($_POST['passenger_phone']);
    $seat_numbers = sanitize($_POST['seat_numbers']);
    $journey_date = $_POST['journey_date'];
    
    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $journey_date)) {
        $error = "❌ Invalid date format";
    } else {
        // Check if seats are already booked
        $seat_array = array_map('trim', explode(',', $seat_numbers));
        $booked_seats = [];
        
        foreach ($seat_array as $seat) {
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
        
        if (!empty($booked_seats)) {
            $error = "❌ Sorry! These seats are already booked: " . implode(', ', $booked_seats) . ". Please select different seats.";
        } else {
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
            $booking_stmt = $conn->prepare("INSERT INTO bookings (user_id, bus_id, route_id, passenger_name, passenger_phone, seat_numbers, total_fare, booking_date, journey_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'confirmed')");
            $booking_stmt->bind_param("iiisssds", $_SESSION['user_id'], $bus_id, $route_id, $passenger_name, $passenger_phone, $seat_numbers, $total_fare, $journey_date);
            
            if ($booking_stmt->execute()) {
                $booking_id = $conn->insert_id;
                $success = "✅ Booking confirmed! Your booking ID is: " . $booking_id . ". Seats: " . $seat_numbers;
                
                // Clear selected seats from session if needed
                unset($_SESSION['selected_seats']);
            } else {
                $error = "❌ Booking failed! Please try again.";
            }
        }
    }
}

// Get buses for selected route
if (isset($_GET['route_id'])) {
    $route_id = (int)$_GET['route_id'];
    $buses_query = "SELECT b.*, r.route_name, r.from_city, r.to_city, r.fare 
                    FROM buses b 
                    JOIN routes r ON b.route_id = r.id 
                    WHERE b.route_id = ? AND b.status = 'active'";
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
    <style>
        .btn-seat {
            width: 100%;
            background-color: white;
            border: 1px solid #dee2e6;
            color: #212529;
            padding: 8px 0;
        }
        
        .btn-seat:hover {
            background-color: #f8f9fa;
        }
        
        .btn-seat.selected {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        
        .btn-seat.booked {
            background-color: #dc3545;
            color: white;
            border-color: #dc3545;
            cursor: not-allowed;
        }
        
        .seat-grid .row {
            margin-bottom: 8px;
        }
        
        .seat-grid .badge {
            width: 100%;
            padding: 8px 0;
        }
        
        #selectedSeatsDisplay {
            min-height: 40px;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .card {
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-bus"></i> BD Bus Ticket Book & Track
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="http://localhost:3000">
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
                                        <?php echo htmlspecialchars($route['from_city'] . ' → ' . $route['to_city'] . ' (৳' . $route['fare'] . ')'); ?>
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
                                <h6><?php echo htmlspecialchars($bus['bus_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($bus['bus_number']); ?></small>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-info"><?php echo htmlspecialchars($bus['bus_type']); ?></span>
                            </div>
                            <div class="col-md-2">
                                <small>Departure: <?php echo date('h:i A', strtotime($bus['departure_time'])); ?></small>
                            </div>
                            <div class="col-md-2">
                                <small>Arrival: <?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></small>
                            </div>
                            <div class="col-md-2">
                                <strong>৳<?php echo htmlspecialchars($bus['fare']); ?></strong>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-sm btn-success" onclick="selectBus(<?php echo $bus['id']; ?>, '<?php echo addslashes($bus['bus_name']); ?>', <?php echo $bus['fare']; ?>)">
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
                    <input type="hidden" name="route_id" value="<?php echo isset($_GET['route_id']) ? (int)$_GET['route_id'] : ''; ?>">
                    
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
                                <label for="seat_numbers" class="form-label">Seat Numbers</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="seat_numbers" name="seat_numbers" required readonly>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#seatSelectionModal">
                                        <i class="fas fa-chair"></i> Select Seats
                                    </button>
                                </div>
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

    <!-- Seat Selection Modal -->
    <div class="modal fade" id="seatSelectionModal" tabindex="-1" aria-labelledby="seatSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="seatSelectionModalLabel">Select Your Seats</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="seat-grid mb-4 text-center">
                        <div class="row mb-2">
                            <div class="col-2"></div>
                            <div class="col-2"><span class="badge bg-secondary">1</span></div>
                            <div class="col-2"><span class="badge bg-secondary">2</span></div>
                            <div class="col-2"><span class="badge bg-secondary">3</span></div>
                            <div class="col-2"><span class="badge bg-secondary">4</span></div>
                            <div class="col-2"><span class="badge bg-secondary">5</span></div>
                        </div>
                        
                        <!-- Row Ex -->
                        <div class="row mb-2">
                            <div class="col-2"><span class="badge bg-secondary">Ex</span></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="Ex1">1</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="Ex2">2</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="Ex3">3</button></div>
                            <div class="col-2"></div>
                            <div class="col-2"></div>
                        </div>
                        
                        <!-- Rows A-I -->
                        <?php 
                        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
                        foreach ($rows as $row): ?>
                        <div class="row mb-2">
                            <div class="col-2"><span class="badge bg-secondary"><?= $row ?></span></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="<?= $row ?>1">1</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="<?= $row ?>2">2</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="<?= $row ?>3">3</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="<?= $row ?>4">4</button></div>
                            <div class="col-2"></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Row J -->
                        <div class="row">
                            <div class="col-2"><span class="badge bg-secondary">J</span></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="J1">1</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="J2">2</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="J3">3</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="J4">4</button></div>
                            <div class="col-2"><button type="button" class="btn btn-seat" data-seat="J5">5</button></div>
                        </div>
                    </div>
                    
                    <div class="selected-seats mb-4">
                        <strong>Selected Seats:</strong>
                        <div id="selectedSeatsDisplay" class="p-2">No seats selected</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">GO BACK</button>
                    <button type="button" class="btn btn-success" id="confirmSeatsBtn">DONE</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seat Selection Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle seat selection
            document.querySelectorAll('.btn-seat').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!this.classList.contains('booked')) {
                        this.classList.toggle('selected');
                        updateSelectedSeats();
                    }
                });
            });

            // Update selected seats display
            function updateSelectedSeats() {
                const selectedSeats = Array.from(document.querySelectorAll('.btn-seat.selected'))
                    .map(seat => seat.dataset.seat);
                const display = document.getElementById('selectedSeatsDisplay');
                
                if (selectedSeats.length > 0) {
                    display.textContent = selectedSeats.join(', ');
                    display.style.color = '#212529';
                } else {
                    display.textContent = 'No seats selected';
                    display.style.color = '#6c757d';
                }
            }

            // Confirm seats selection
            document.getElementById('confirmSeatsBtn').addEventListener('click', function() {
                const selectedSeats = Array.from(document.querySelectorAll('.btn-seat.selected'))
                    .map(seat => seat.dataset.seat);
                
                if (selectedSeats.length > 0) {
                    document.getElementById('seat_numbers').value = selectedSeats.join(', ');
                    bootstrap.Modal.getInstance(document.getElementById('seatSelectionModal')).hide();
                } else {
                    alert('Please select at least one seat');
                }
            });

            // Check seat availability when date changes
            function checkSeatAvailability() {
                const busId = document.getElementById('selected_bus_id').value;
                const journeyDate = document.getElementById('journey_date').value;
                
                if (!busId || !journeyDate) return;
                
                fetch(`check_seats.php?bus_id=${busId}&journey_date=${journeyDate}`)
                    .then(response => response.json())
                    .then(data => {
                        // Reset all seats first
                        document.querySelectorAll('.btn-seat').forEach(btn => {
                            btn.classList.remove('booked', 'selected');
                            btn.disabled = false;
                        });
                        
                        // Mark booked seats as disabled and red
                        if (data.booked_seats && data.booked_seats.length > 0) {
                            const bookedSeats = data.booked_seats;
                            
                            document.querySelectorAll('.btn-seat').forEach(btn => {
                                const seatNumber = btn.dataset.seat;
                                if (bookedSeats.includes(seatNumber)) {
                                    btn.classList.add('booked');
                                    btn.disabled = true;
                                    btn.classList.remove('selected');
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error checking seat availability:', error);
                    });
            }

            // Initialize the modal with seats when shown
            document.getElementById('seatSelectionModal').addEventListener('show.bs.modal', function() {
                checkSeatAvailability();
            });
        });

        function selectBus(busId, busName, fare) {
            document.getElementById('selected_bus_id').value = busId;
            document.getElementById('selected_bus_name').textContent = busName;
            document.getElementById('selected_bus_fare').textContent = fare;
            document.getElementById('bookingForm').style.display = 'block';
            document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
            
            // Clear previous selections
            document.getElementById('seat_numbers').value = '';
            document.getElementById('journey_date').value = '';
        }
    </script>
</body>
</html>