<?php
require_once 'config/database.php';

$conn = getConnection();

// Get all active buses with their current locations
$buses_query = "SELECT b.*, r.route_name, r.from_city, r.to_city 
                FROM buses b 
                JOIN routes r ON b.route_id = r.id 
                WHERE b.status = 'active' 
                ORDER BY r.route_name";
$buses_result = $conn->query($buses_query);

// Handle AJAX request for bus location update
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_location' && isset($_GET['bus_id'])) {
    $bus_id = $_GET['bus_id'];
    $location_query = "SELECT current_lat, current_lng, bus_name FROM buses WHERE id = ?";
    $stmt = $conn->prepare($location_query);
    $stmt->bind_param("i", $bus_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $location = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode($location);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Bus - BD Bus Track</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
        .bus-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .bus-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .bus-card.selected {
            border: 2px solid #007bff;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="fas fa-bus"></i> BD Bus Track
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['user_name']; ?></span>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Bus List -->
            <div class="col-md-4">
                <h4><i class="fas fa-list"></i> Active Buses</h4>
                <div class="mb-3">
                    <input type="text" id="searchBus" class="form-control" placeholder="Search buses...">
                </div>
                
                <div id="busList">
                    <?php while ($bus = $buses_result->fetch_assoc()): ?>
                    <div class="card bus-card mb-2" onclick="trackBus(<?php echo $bus['id']; ?>, '<?php echo $bus['bus_name']; ?>', <?php echo $bus['current_lat']; ?>, <?php echo $bus['current_lng']; ?>)">
                        <div class="card-body p-3">
                            <h6 class="card-title mb-1"><?php echo $bus['bus_name']; ?></h6>
                            <small class="text-muted"><?php echo $bus['bus_number']; ?></small>
                            <p class="card-text mb-1">
                                <small><i class="fas fa-route"></i> <?php echo $bus['from_city'] . ' → ' . $bus['to_city']; ?></small>
                            </p>
                            <span class="badge bg-success">Active</span>
                            <span class="badge bg-info"><?php echo $bus['bus_type']; ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Map -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-map-marker-alt"></i> Live Bus Tracking</h5>
                        <small class="text-muted">Click on a bus from the list to track its location</small>
                    </div>
                    <div class="card-body p-0">
                        <div id="map"></div>
                    </div>
                </div>
                
                <!-- Bus Info Panel -->
                <div id="busInfo" class="card mt-3" style="display: none;">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Bus Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Bus Name:</strong> <span id="infoBusName"></span></p>
                                <p><strong>Bus Number:</strong> <span id="infoBusNumber"></span></p>
                                <p><strong>Route:</strong> <span id="infoBusRoute"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Location:</strong> <span id="infoBusLocation"></span></p>
                                <p><strong>Last Updated:</strong> <span id="infoLastUpdate"></span></p>
                                <p><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([23.8103, 90.4125], 7); // Center on Dhaka
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var currentMarker = null;
        var selectedBusId = null;

        // Track specific bus
        function trackBus(busId, busName, lat, lng) {
            // Remove previous marker
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            
            // Highlight selected bus card
            document.querySelectorAll('.bus-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            selectedBusId = busId;
            
            // Add new marker
            if (lat && lng) {
                currentMarker = L.marker([lat, lng]).addTo(map);
                currentMarker.bindPopup(`<b>${busName}</b><br>Current Location`).openPopup();
                map.setView([lat, lng], 12);
                
                // Update bus info panel
                updateBusInfo(busId, busName);
            } else {
                alert('Location not available for this bus');
            }
        }

        // Update bus information panel
        function updateBusInfo(busId, busName) {
            // This would typically fetch more detailed info from the server
            document.getElementById('infoBusName').textContent = busName;
            document.getElementById('infoLastUpdate').textContent = new Date().toLocaleString();
            document.getElementById('busInfo').style.display = 'block';
        }

        // Search functionality
        document.getElementById('searchBus').addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            var busCards = document.querySelectorAll('.bus-card');
            
            busCards.forEach(function(card) {
                var busName = card.querySelector('.card-title').textContent.toLowerCase();
                var busNumber = card.querySelector('.text-muted').textContent.toLowerCase();
                
                if (busName.includes(searchTerm) || busNumber.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Auto-refresh bus location every 30 seconds
        setInterval(function() {
            if (selectedBusId) {
                fetch(`tracking.php?ajax=get_location&bus_id=${selectedBusId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.current_lat && data.current_lng) {
                            if (currentMarker) {
                                map.removeLayer(currentMarker);
                            }
                            currentMarker = L.marker([data.current_lat, data.current_lng]).addTo(map);
                            currentMarker.bindPopup(`<b>${data.bus_name}</b><br>Updated Location`).openPopup();
                            document.getElementById('infoLastUpdate').textContent = new Date().toLocaleString();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }, 30000);
    </script>
</body>
</html>