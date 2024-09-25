<?php
session_start();

// Include database connection file
include 'db_connection.php';

// Enable error reporting for debugging (remove this in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

// Get the logged-in user's ID
$sender_id = $_SESSION['user_id'];

// Get the recipient user ID from the request (assuming it is sent via POST)
if (isset($_POST['recipient_id'])) {
    $recipient_id = intval($_POST['recipient_id']); // Ensure it's an integer

    // Prepare the SQL statement to insert a friend request
    $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
        exit();
    }

    // Bind parameters and execute
    $stmt->bind_param("ii", $sender_id, $recipient_id);

    if ($stmt->execute()) {
        // Send a success response
        echo json_encode(["status" => "success", "message" => "Friend request sent."]);
    } else {
        // Send an error response if the execution fails
        echo json_encode(["status" => "error", "message" => "Error sending friend request: " . $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    // Send an error response if no recipient ID is provided
    echo json_encode(["status" => "error", "message" => "No recipient ID provided."]);
}

// Close the database connection
$conn->close();
?>
