<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $conn = getConnection();
    
    // প্রথমে চেক করুন যে বুকিংটি এই ইউজারের কিনা
    $check_query = "SELECT user_id FROM bookings WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $booking = $check_result->fetch_assoc();
        
        if ($booking['user_id'] == $_SESSION['user_id']) {
            // ডিলিট কুয়েরি
            $delete_query = "DELETE FROM bookings WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $booking_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success_message'] = "Booking #" . $booking_id . " has been cancelled successfully!";
            } else {
                $_SESSION['error_message'] = "Error cancelling booking. Please try again.";
            }
        } else {
            $_SESSION['error_message'] = "You are not authorized to cancel this booking.";
        }
    } else {
        $_SESSION['error_message'] = "Booking not found.";
    }
    
    header("Location: dashboard.php");
    exit();
}
?>