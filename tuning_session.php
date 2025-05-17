<?php
session_start();

if (!isset($_SESSION["username"])) {
    // Not logged in
    header("Location: login.php"); // Redirect back to login
    exit;
}

$username = $_SESSION["username"];

#code is incomplete but would refresh the frequency, and allow user to hit a button to send frequency,temp and humidity to logs table
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tuning Session</title>
    <meta http-equiv="refresh" content="1"> <!-- Refresh every second -->
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <h2>Current Frequency: <?php echo $freq; ?></h2>

    <form method="post" action="">
        <input type="hidden" name="submit_tuning" value="1">
        <button type="submit">I'm Happy With This</button>
    </form>

    <?php if (isset($message)) echo "<p>$message</p>"; ?>
</body>
</html>
