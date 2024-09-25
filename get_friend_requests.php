<?php
session_start();

// Include database connection file
include 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Prepare the SQL statement to retrieve friend requests
$stmt = $conn->prepare("SELECT sender_id, username FROM friend_requests 
                        JOIN users ON friend_requests.sender_id = users.userid 
                        WHERE receiver_id = ?");
$stmt->bind_param("i", $user_id);

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]));
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Fetch the friend requests
$friend_requests = [];
while ($row = $result->fetch_assoc()) {
    $friend_requests[] = [
        'userid' => $row['sender_id'], // Assuming this is the sender's ID
        'username' => $row['username']  // The username of the sender
    ];
}

// Close the statement
$stmt->close();

// Return the friend requests as JSON
echo json_encode($friend_requests);

// Close the database connection
$conn->close();
?>
