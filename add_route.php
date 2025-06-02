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
    $route_name = $_POST["route_name"];
    $from_city = $_POST["from_city"];
    $to_city = $_POST["to_city"];
    $distance = $_POST["distance"];
    $duration = $_POST["duration"];
    $fare = $_POST["fare"];

    $stmt = $conn->prepare("INSERT INTO routes (route_name, from_city, to_city, distance, duration, fare) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssids", $route_name, $from_city, $to_city, $distance, $duration, $fare);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center'>✅ Route added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>❌ Error: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Route</title>
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
            max-width: 500px;
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
        .form-control:focus {
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
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <span class="me-2" style="font-size: 1.8rem;">🛣️</span> Add New Route
        </div>
        <div class="card-body p-4">
            <?= $message ?>
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="route_name" class="form-label fw-semibold">Route Name</label>
                    <input type="text" class="form-control" id="route_name" name="route_name" required placeholder="Eg: Dhaka to Chittagong" />
                </div>

                <div class="mb-3">
                    <label for="from_city" class="form-label fw-semibold">From City</label>
                    <input type="text" class="form-control" id="from_city" name="from_city" required placeholder="Eg: Dhaka" />
                </div>

                <div class="mb-3">
                    <label for="to_city" class="form-label fw-semibold">To City</label>
                    <input type="text" class="form-control" id="to_city" name="to_city" required placeholder="Eg: Chittagong" />
                </div>

                <div class="mb-3">
                    <label for="distance" class="form-label fw-semibold">Distance (km)</label>
                    <input type="number" class="form-control" id="distance" name="distance" min="1" required placeholder="Eg: 250" />
                </div>

                <div class="mb-3">
                    <label for="duration" class="form-label fw-semibold">Duration</label>
                    <input type="text" class="form-control" id="duration" name="duration" required placeholder="Eg: 5h 30m" />
                </div>

                <div class="mb-4">
                    <label for="fare" class="form-label fw-semibold">Fare (BDT)</label>
                    <input type="number" step="0.01" class="form-control" id="fare" name="fare" min="0" required placeholder="Eg: 1200.00" />
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow-sm">Add Route</button>
            </form>
        </div>
    </div>
</body>
</html>
