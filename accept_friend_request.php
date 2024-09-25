<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $requestId = intval($_POST['request_id']);

    if (!empty($requestId)) {
        // Update the status of the friend request to 'accepted'
        $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ?");
        $stmt->bind_param("i", $requestId);
        if ($stmt->execute()) {
            // Get the friend ID from the request
            $stmt = $conn->prepare("SELECT userid, friend_id FROM friends WHERE id = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            // Add the friend to the friends list (if not already there)
            $friendId = $row['userid'];
            $userId = $row['friend_id'];

            $insertFriendSQL = "INSERT INTO friends (userid, friend_id, status) VALUES (?, ?, 'accepted')";
            $insertStmt = $conn->prepare($insertFriendSQL);
            $insertStmt->bind_param("ii", $friendId, $userId);
            $insertStmt->execute();

            // Notify both users
            $message = "Your friend request has been accepted.";
            $notifySQL = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notifyStmt = $conn->prepare($notifySQL);
            $notifyStmt->bind_param("is", $userId, $message);
            $notifyStmt->execute();

            $messageForRequester = "You have accepted a friend request.";
            $notifyStmt->bind_param("is", $friendId, $messageForRequester);
            $notifyStmt->execute();

            echo "Friend request accepted.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>
