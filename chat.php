<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['userid'])) {
    die("User not logged in.");
}

$userid = $_SESSION['userid']; // The ID of the logged-in user

// Query to get the list of friends
$query = "
    SELECT u.userid, u.username 
    FROM users u
    JOIN friends f ON (f.user_one = ? AND f.user_two = u.userid) 
                   OR (f.user_two = ? AND f.user_one = u.userid)
";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $userid, $userid);
$stmt->execute();
$result = $stmt->get_result();

$friends = [];

while ($row = $result->fetch_assoc()) {
    $friends[] = $row;
}

// Return friends in JSON format
echo json_encode($friends);

$stmt->close();
$conn->close();




?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App - Friend Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>
<body>

    <!-- Chat Container -->
    <div class="chat-container">
        <!-- Sidebar with Friends List -->
        <div class="sidebar">
            <h2>Friends</h2>
            <div id="friendsList">

            </div>


            <h2>Friend Requests</h2>
            <ul id="incoming-requests">
                <!-- Incoming friend requests will be dynamically added here -->
            </ul>

            <h4>Search for Friends</h4>
            <input type="text" id="searchUserInput" placeholder="Search for a user..." oninput="searchUsers()">
            <ul id="search-results">
                <!-- Search results will be dynamically added here -->
            </ul>
        </div>

        <!-- Chat Section -->
        <div class="chat-section">
            <div class="chat-header">
                <h3 id="chat-with">Chat with...</h3>
            </div>
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be dynamically inserted here -->
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Type your message..." onkeypress="sendMessage(event)">
                <button onclick="loadIncomingRequests()">Send</button>
            </div>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', (event) => {
        loadFriends(); // Load friends when the page is ready
        loadIncomingRequests(); // Load friend requests when the page is ready
    });

    function loadFriends() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "get_friends.php", true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            try {
                const friends = JSON.parse(xhr.responseText);
                const friendsList = document.getElementById("friendsList");
                friendsList.innerHTML = ""; // Clear existing friends

                // Check if friends data is returned correctly
                if (Array.isArray(friends) && friends.length > 0) {
                    friends.forEach(friend => {
                        const friendDiv = document.createElement("div");
                        friendDiv.classList.add("friend");
                        friendDiv.innerText = friend.username;

                        // Set up click event to select a friend
                        friendDiv.onclick = function () {
                            selectFriend(friend.username);
                        };

                        friendsList.appendChild(friendDiv);
                    });
                } else {
                    friendsList.innerHTML = "<div>No friends found</div>";
                }
            } catch (e) {
                console.error("Error parsing JSON:", e);
            }
        } else {
            console.error("Error loading friends:", xhr.status, xhr.statusText);
        }
    };
    xhr.send();
}


function selectFriend(username) {
    const chatHeader = document.getElementById("chat-header");
    chatHeader.innerHTML = `<h3>Chat with ${username}</h3>`;
}

        // Example function to handle adding a friend
function addFriend(recipientId) {
    if (!recipientId) {
        alert("No recipient ID provided.");
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "send_friend_request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            alert(response.message); // Show response message
        } else {
            console.error("Error sending friend request.");
        }
    };

    // Sending the recipient_id to the PHP file
    xhr.send(`recipient_id=${recipientId}`);
}

        
        // Function to search users and display results
        function searchUsers() {
            const searchQuery = document.getElementById('searchUserInput').value;

            if (searchQuery.length > 0) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'search_users.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        console.log("Search response:", xhr.responseText); // Debugging
                        const searchResults = document.getElementById('search-results');
                        searchResults.innerHTML = xhr.responseText; // Display response inside the results list
                    } else {
                        console.error('Error fetching search results:', xhr.status, xhr.statusText);
                    }
                };
                
                xhr.send('query=' + encodeURIComponent(searchQuery));
            } else {
                document.getElementById('search-results').innerHTML = ''; // Clear results if query is too short
            }
        }
    


        // Function to load the user's friends
function loadFriendsList() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_friends.php', true);

    xhr.onload = function () {
        if (xhr.status === 200) {
            const friends = JSON.parse(xhr.responseText);
            const friendsList = document.getElementById('friends-list');
            friendsList.innerHTML = ''; // Clear current list

            if (friends.length > 0) {
                friends.forEach(friend => {
                    const listItem = document.createElement('li');
                    listItem.textContent = friend.username;
                    friendsList.appendChild(listItem);
                });
            } else {
                friendsList.innerHTML = '<li>No friends found</li>';
            }
        } else {
            console.error('Error loading friends:', xhr.status, xhr.statusText);
        }
    };

    xhr.send();
}

function loadIncomingRequests() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_friend_requests.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const requests = JSON.parse(xhr.responseText);
                    const requestList = document.getElementById('incoming-requests'); // Ensure this ID matches your HTML
                    requestList.innerHTML = ''; // Clear current list

                    if (requests.length > 0) {
                        requests.forEach(request => {
                            const listItem = document.createElement('li');
                            listItem.innerHTML = `
                                <span>${request.username}</span>
                                <div class="actions">
                                    <button onclick="respondToRequest(${request.userid}, 'accept')" class="btn btn-success">Accept</button>
                                    <button onclick="respondToRequest(${request.userid}, 'decline')" class="btn btn-danger">Decline</button>
                                </div>
                            `;
                            requestList.appendChild(listItem);
                        });
                    } else {
                        requestList.innerHTML = '<li>No pending requests</li>';
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                }
            } else {
                console.error('Error loading incoming requests:', xhr.status, xhr.statusText);
            }
        };
        xhr.send();
    }


function respondToRequest(senderId, action) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "respond_to_request.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            const response = JSON.parse(xhr.responseText);
            alert(response.message);
            loadIncomingRequests(); // Reload friend requests after responding
        };
        xhr.send(`sender_id=${senderId}&action=${action}`);
    }




// Function to send a friend request
function sendFriendRequest(friendId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'send_friend_request.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            alert(xhr.responseText); // Notify the user of the result
        } else {
            console.error('Error sending friend request:', xhr.status, xhr.statusText);
        }
    };

    // Send the friend ID to the server
    xhr.send('friend_id=' + encodeURIComponent(friendId));
}



function acceptRequest(requestId) {
    console.log("Accepting request:", requestId); // Debugging statement
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "respond_to_request.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        console.log("Response from server:", xhr.responseText); // Log the response
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.status === "success") {
                // Handle success (e.g., remove the request from the UI)
            } else {
                // Handle error
                alert(response.message);
            }
        } catch (error) {
            console.error("Error parsing JSON:", error);
        }
    };
    xhr.send("action=accept&request_id=" + requestId);
}

      
    </script>
</body>
</html>


<style>
    /* Friend Requests Section Styles */
    .btn {
    padding: 5px 10px;
    margin: 5px;
    cursor: pointer;
}

.btn-success {
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
}

#friend-requests {
    display: none; /* Hidden by default, shown when clicking the icon */
    position: absolute;
    top: 60px; /* Adjust based on the position of your icon */
    right: 10px;
    width: 300px;
    background-color: #ffffff;
    border: 1px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 10px;
    z-index: 100; /* Ensure it appears above other content */
    max-height: 400px; /* Add a max-height with overflow for long lists */
    overflow-y: auto;
}

#friend-requests h3 {
    margin: 0;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

#friend-requests ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

#friend-requests li {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 16px;
    color: #333;
    position: relative;
}

#friend-requests li:last-child {
    border-bottom: none;
}

#friend-requests .actions {
    display: flex;
    gap: 10px;
}

#friend-requests .actions i {
    cursor: pointer;
    font-size: 16px;
    transition: color 0.3s ease;
}

#friend-requests .actions .accept {
    color: #4caf50; /* Green color for accept */
}

#friend-requests .actions .accept:hover {
    color: #388e3c; /* Darker green on hover */
}

#friend-requests .actions .decline {
    color: #f44336; /* Red color for decline */
}

#friend-requests .actions .decline:hover {
    color: #d32f2f; /* Darker red on hover */
}

        /* Additional styles for friend requests */
        #friend-request-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            transition: opacity 0.3s ease;
        }

        #friend-requests-container {
            position: fixed;
            top: 70px;
            right: 20px;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            display: none; /* Hidden by default */
            z-index: 1000;
        }

        #friend-requests-container h3 {
            margin: 0;
            padding: 15px;
            background-color: rgb(30, 175, 30);
            color: white;
            text-align: center;
            font-size: 18px;
        }

        #friend-requests {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #friend-requests li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        #friend-requests li button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            color: #333;
        }

        #friend-requests li button:hover {
            color: #00bfa5;
        }

        #friend-requests li button:active {
            transform: scale(0.95);
        }

        .friends-list {
        display: flex;
        flex-direction: column;
        margin: 20px;
    }
    .friend {
        padding: 10px;
        margin: 5px;
        background-color: green;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .friend:hover {
        background-color: #e0e0e0;
    }

        /* Existing chat styles */
        .chat-container {
            display: flex;
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 25%;
            background: linear-gradient(45deg, #1de9b6, #00bfa5);
            color: white;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
            transition: all 0.4s ease;
        }

        .sidebar h2 {
            font-size: 28px;
            margin-bottom: 52px;
            text-align: center;
            animation: slideIn 0.8s ease;
        }

        #friends-list {
            list-style-type: none;
            padding: 0;
            margin-top: 20px;
        }

        #friends-list li {
            padding: 15px;
            background-color: rgb(30, 175, 30);
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            text-align: center;
            font-size: 18px;
        }

        #friends-list li:hover {
            background-color: rgba(255, 255, 255, 0.4);
            transform: translateX(10px);
        }

        #friends-list li:active {
            transform: scale(0.98);
        }

        .chat-section {
            width: 75%;
            display: flex;
            flex-direction: column;
            background-color: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px;
            background-color: rgb(30, 175, 30);
            color: white;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            position: relative;
            z-index: 10;
            animation: fadeIn 0.8s ease;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            background-color: #f1f8e9;
            overflow-y: auto;
            position: relative;
            z-index: 5;
            animation: slideIn 0.5s ease;
            transition: background-color 0.3s ease;
        }

        .message {
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }

        .message.sent {
            text-align: right;
        }

        .message.received {
            text-align: left;
        }

        .message .text {
            display: inline-block;
            padding: 15px;
            border-radius: 20px;
            max-width: 60%;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .message.sent .text {
            background-color: #714af0;
            color: white;
            animation: bounceIn 0.6s ease;
        }

        .message.received .text {
            background-color: #c8e6c9;
            color: black;
            animation: bounceIn 0.6s ease;
        }

        .chat-input {
            display: flex;
            padding: 20px;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.5s ease;
        }

        .chat-input input {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid rgb(30, 175, 30);
            border-radius: 30px;
            margin-right: 10px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .chat-input input:focus {
            border-color: #00bfa5;
            box-shadow: 0 0 10px rgb(30, 175, 30);
        }

        .chat-input button {
            background-color: rgb(30, 175, 30);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .chat-input button:hover {
            background-color: rgb(30, 175, 30);
            transform: translateY(-2px);
        }

        .chat-input button:active {
            transform: scale(0.98);
        }

        @keyframes slideIn {
            0% {
                transform: translateY(50px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            0% {
                transform: translateY(30px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background-color: rgb(30, 175, 30);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background-color: rgb(30, 175, 30);
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background-color: rgb(30, 175, 30);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100px;
            }

            .chat-section {
                width: calc(100% - 100px);
            }

            #friends-list li {
                font-size: 14px;
                padding: 10px;
            }

            .chat-header {
                font-size: 18px;
            }

            .chat-input input {
                font-size: 14px;
            }

            .chat-input button {
                font-size: 14px;
                padding: 8px 16px;
            }
        }
    </style>