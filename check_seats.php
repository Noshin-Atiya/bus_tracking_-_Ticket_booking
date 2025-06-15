<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Validate input parameters
if (!isset($_GET['bus_id']) || !isset($_GET['journey_date'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$bus_id = (int)$_GET['bus_id'];
$journey_date = $_GET['journey_date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $journey_date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

$conn = getConnection();

try {
    // Get all booked seats for this bus and date
    $query = "SELECT seat_numbers FROM bookings 
              WHERE bus_id = ? AND journey_date = ? AND status = 'confirmed'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $bus_id, $journey_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_seats = [];
    while ($row = $result->fetch_assoc()) {
        // Clean and process seat numbers
        $seats = array_map('trim', explode(',', $row['seat_numbers']));
        $seats = array_filter($seats); // Remove empty values
        $booked_seats = array_merge($booked_seats, $seats);
    }
    
    // Remove duplicates and sort
    $booked_seats = array_unique($booked_seats);
    sort($booked_seats);
    
    echo json_encode([
        'status' => 'success',
        'bus_id' => $bus_id,
        'journey_date' => $journey_date,
        'booked_seats' => $booked_seats,
        'total_booked' => count($booked_seats)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>