<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

$receiver_id = $_SESSION['user_id'];
$sender_id = $_POST['sender_id']; // Assuming you send sender_id in the POST request

// Check if the friend request exists
$stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Friend request not found."]);
    exit();
}

// Proceed with accepting the request
// Add to friends table
$stmt = $conn->prepare("INSERT INTO friends (user_one, user_two) VALUES (?, ?), (?, ?)");
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id); // Add both directions
$stmt->execute();

// Delete the friend request
$stmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();

echo json_encode(["status" => "success", "message" => "Friend request accepted."]);
$stmt->close();
$conn->close();
?>
