-- Create Database
CREATE DATABASE bus_tracking_bd;
USE bus_tracking_bd;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bus Routes Table
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(100) NOT NULL,
    from_city VARCHAR(50) NOT NULL,
    to_city VARCHAR(50) NOT NULL,
    distance INT NOT NULL,
    duration VARCHAR(20) NOT NULL,
    fare DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buses Table
CREATE TABLE buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    bus_name VARCHAR(100) NOT NULL,
    route_id INT,
    total_seats INT DEFAULT 40,
    bus_type ENUM('AC', 'Non-AC') DEFAULT 'Non-AC',
    current_lat DECIMAL(10, 8) DEFAULT NULL,
    current_lng DECIMAL(11, 8) DEFAULT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- Bookings Table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    bus_id INT,
    route_id INT,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_phone VARCHAR(15) NOT NULL,
    seat_numbers VARCHAR(100) NOT NULL,
    total_fare DECIMAL(10,2) NOT NULL,
    booking_date DATE NOT NULL,
    journey_date DATE NOT NULL,
    status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bus_id) REFERENCES buses(id),
    FOREIGN KEY (route_id) REFERENCES routes(id)
);

-- Insert Sample Data
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin User', 'admin@bustrack.bd', '01700000000', MD5('admin123'), 'admin'),
('Rahul Ahmed', 'rahul@email.com', '01711111111', MD5('user123'), 'user'),
('Fatima Khan', 'fatima@email.com', '01722222222', MD5('user123'), 'user');

INSERT INTO routes (route_name, from_city, to_city, distance, duration, fare) VALUES
('Dhaka-Chittagong Express', 'Dhaka', 'Chittagong', 264, '5h 30m', 450.00),
('Dhaka-Sylhet Highway', 'Dhaka', 'Sylhet', 247, '5h 00m', 400.00),
('Dhaka-Rajshahi Route', 'Dhaka', 'Rajshahi', 256, '4h 45m', 380.00),
('Chittagong-Cox\'s Bazar', 'Chittagong', 'Cox\'s Bazar', 152, '3h 30m', 250.00);

INSERT INTO buses (bus_number, bus_name, route_id, total_seats, bus_type, departure_time, arrival_time, current_lat, current_lng) VALUES
('DH-1234', 'Green Line Paribahan', 1, 40, 'AC', '08:00:00', '13:30:00', 23.8103, 90.4125),
('DH-5678', 'Shyamoli NR Travels', 2, 36, 'AC', '09:00:00', '14:00:00', 23.8103, 90.4125),
('DH-9012', 'Hanif Enterprise', 3, 40, 'Non-AC', '07:30:00', '12:15:00', 23.8103, 90.4125),
('CH-3456', 'Soudia Transport', 4, 32, 'AC', '10:00:00', '13:30:00', 22.3569, 91.7832);