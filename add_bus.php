<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "bus_tracking_bd";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_name = $_POST["bus_name"];
    $bus_number = $_POST["bus_number"];
    $route_id = $_POST["route_id"];
    $total_seats = $_POST["total_seats"];
    $bus_type = $_POST["bus_type"];
    $departure_time = $_POST["departure_time"];
    $arrival_time = $_POST["arrival_time"];
    $status = $_POST["status"];

    $stmt = $conn->prepare("INSERT INTO buses (bus_name, bus_number, route_id, total_seats, bus_type, departure_time, arrival_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisssss", $bus_name, $bus_number, $route_id, $total_seats, $bus_type, $departure_time, $arrival_time, $status);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center'>✅ Bus added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>❌ Error: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();
}

// Fetch routes for dropdown
$routes_result = $conn->query("SELECT id, route_name FROM routes");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Bus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 0 25px rgba(0,0,0,0.15);
            max-width: 900px; /* Boro kore dilam */
            width: 100%;
            background-color: #fff;
        }
        .card-header {
            background: #764ba2;
            color: #fff;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            text-align: center;
            font-weight: 700;
            font-size: 1.5rem;
            padding: 1.25rem;
            letter-spacing: 1.5px;
            user-select: none;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 8px #764ba2;
            border-color: #764ba2;
        }
        button.btn-primary {
            background-color: #667eea;
            border: none;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 50px;
            transition: background-color 0.3s ease;
        }
        button.btn-primary:hover {
            background-color: #5a67d8;
        }
        .alert {
            border-radius: 0.75rem;
        }
        /* Grid layout for form */
        form .row > div {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <span class="me-2" style="font-size: 1.8rem;">🚌</span> Add New Bus
        </div>
        <div class="card-body p-4">
            <?= $message ?>
            <form method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <label for="bus_name" class="form-label fw-semibold">Bus Name</label>
                        <input type="text" class="form-control" id="bus_name" name="bus_name" required placeholder="Eg: Green Line Express" />
                    </div>

                    <div class="col-md-6">
                        <label for="bus_number" class="form-label fw-semibold">Bus Number</label>
                        <input type="text" class="form-control" id="bus_number" name="bus_number" required placeholder="Eg: DHA-1234" />
                    </div>

                    <div class="col-md-6">
                        <label for="route_id" class="form-label fw-semibold">Route</label>
                        <select class="form-select" id="route_id" name="route_id" required>
                            <option value="" selected disabled>Choose a route</option>
                            <?php while ($row = $routes_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id']) ?>"><?= htmlspecialchars($row['route_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="total_seats" class="form-label fw-semibold">Total Seats</label>
                        <input type="number" class="form-control" id="total_seats" name="total_seats" min="1" required placeholder="Eg: 40" />
                    </div>

                    <div class="col-md-6">
                        <label for="bus_type" class="form-label fw-semibold">Bus Type</label>
                        <select class="form-select" id="bus_type" name="bus_type" required>
                            <option value="AC">AC</option>
                            <option value="Non-AC" selected>Non-AC</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="departure_time" class="form-label fw-semibold">Departure Time</label>
                        <input type="time" class="form-control" id="departure_time" name="departure_time" required />
                    </div>

                    <div class="col-md-6">
                        <label for="arrival_time" class="form-label fw-semibold">Arrival Time</label>
                        <input type="time" class="form-control" id="arrival_time" name="arrival_time" required />
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow-sm mt-3">Add Bus</button>
            </form>
        </div>
    </div>
</body>
</html>
