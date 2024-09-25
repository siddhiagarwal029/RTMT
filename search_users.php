<?php
// Database connection
include 'db_connection.php';

if (isset($_POST['query'])) {
    $searchQuery = $_POST['query'];

    // Prevent SQL injection
    $stmt = $conn->prepare("SELECT userid, username FROM users WHERE username LIKE ?");
    $searchTerm = "%$searchQuery%";
    $stmt->bind_param('s', $searchTerm);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['username']) . " 
                        <button onclick='addFriend(" . intval($row['userid']) . ")'>Add Friend</button>
                      </li>";
            }
        } else {
            echo "<li>No users found</li>";
        }
    } else {
        echo "<li>Error in query execution</li>";
    }

    $stmt->close();
}
?>
