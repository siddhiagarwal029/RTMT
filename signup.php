<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Basic validation
    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirmPassword)) {

        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '<script type="text/javascript">
                alert("Invalid email format!");
                window.location.href = "signup.html";
              </script>';
            exit();
        }

        // Check if passwords match
        if ($password !== $confirmPassword) {
            echo '<script type="text/javascript">
                alert("Passwords do not match!");
                window.location.href = "signup.html";
              </script>';
            exit();
        }

        // Check if username or email already exists in the database
        $checkUserSQL = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($checkUserSQL);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User with same username or email already exists
            echo '<script type="text/javascript">
                alert("Username or email already exists. Please choose another.");
                window.location.href = "signup.html";
              </script>';
            exit();
        }

        // Hash the password before saving it to the database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $insertSQL = "INSERT INTO users (username, email, password) VALUES (?,?,?)";
        $stmt = $conn->prepare($insertSQL);

        if ($stmt === false) {
            echo '<script type="text/javascript">
                alert("Database Error. Please try again later.");
              </script>';
            exit();
        }

        $stmt->bind_param("sss", $username, $email, $hashed_password);

        // Execute the query and check for success
        if ($stmt->execute()) {
            echo '<script type="text/javascript">
                alert("Successfully registered!");
                window.location.href = "login.html"; // Redirect to login page
              </script>';
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // If any field is missing
        echo '<script type="text/javascript">
            alert("Please fill out all the fields.");
            window.location.href = "signup.html";
          </script>';
        exit();
    }
}

$conn->close();
?>
