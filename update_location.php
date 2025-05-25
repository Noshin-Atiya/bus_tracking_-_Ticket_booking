<?php
require_once 'config/database.php';

// This file would be called by GPS devices or mobile apps to update bus locations
// For demo purposes, we'll create a simple interface

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_id = $_POST['bus_id'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE buses SET current_lat = ?, current_lng = ? WHERE id = ?");
    $stmt->bind_param("ddi", $latitude, $longitude, $bus_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Location updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update location']);
    }
    
    $conn->close();
    exit;
}

// Demo interface for updating locations
$conn = getConnection();
$buses_query = "SELECT id, bus_name, bus_number FROM buses WHERE status = 'active'";
$buses_result = $conn->query($buses_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Bus Location - BD Bus Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Update Bus Location (Demo)</h2>
        <div class="card">
            <div class="card-body">
                <form id="locationForm">
                    <div class="mb-3">
                        <label for="bus_id" class="form-label">Select Bus</label>
                        <select class="form-select" id="bus_id" name="bus_id" required>
                            <option value="">Choose a bus</option>
                            <?php while ($bus = $buses_result->fetch_assoc()): ?>
                                <option value="<?php echo $bus['id']; ?>">
                                    <?php echo $bus['bus_name'] . ' (' . $bus['bus_number'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="any" class="form-control" id="latitude" name="latitude" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="any" class="form-control" id="longitude" name="longitude" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Location</button>
                    <button type="button" class="btn btn-secondary" onclick="getCurrentLocation()">Use Current Location</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        document.getElementById('locationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_location.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Location updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating location.');
            });
        });
    </script>
</body>
</html>