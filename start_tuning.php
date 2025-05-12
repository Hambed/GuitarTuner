<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Connect to DB
    $conn = new mysqli("localhost", "php", "0s@48X+_tDL,E)cDC@n>9)UM7Lh:eY", "TunerDB");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $password_hash);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $user_id;

            // Send username to tuner over BT
            shell_exec("echo \"$username\" > /dev/rfcomm0");

            header("Location: tuning_session.php");
            exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head><title>Start Tuning</title></head>
<body>
<h1>Login to Start Tuning</h1>
<form method="post" action="">
    <label>Username:</label><input type="text" name="username" required><br>
    <label>Password:</label><input type="password" name="password" required><br>
    <button type="submit">Start</button>
</form>
</body>
</html>