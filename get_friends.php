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

// Prepare the SQL statement to retrieve friends
$stmt = $conn->prepare("SELECT u.userid, u.username 
                        FROM friends AS f 
                        JOIN users AS u ON (f.user_one = u.userid OR f.user_two = u.userid) 
                        WHERE (f.user_one = ? OR f.user_two = ?) 
                        AND u.userid != ?");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]));
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();

// Fetch the friends
$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = [
        'userid' => $row['userid'],
        'username' => $row['username']
    ];
}

// Close the statement
$stmt->close();

// Return the friends as JSON
echo json_encode($friends);

// Close the database connection
$conn->close();
?>
