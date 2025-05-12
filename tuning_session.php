<?php
session_start();

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // or wherever your login page is
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$freq = "";
$temp = "";
$humidity = "";

// Read latest line from Bluetooth (rfcomm0)
$lines = @file('/dev/rfcomm0');
if ($lines !== false && count($lines) > 0) {
    $lastLine = trim(end($lines));
    // Assuming frequency is a 6-char string, newline-terminated
    $freq = htmlspecialchars($lastLine); // Safe to echo in HTML
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get temperature and humidity from the sensor script
    $output = shell_exec("python3 /home/hami/Project/bme280_read.py");
    $output_lines = explode("\n", trim($output));
    foreach ($output_lines as $line) {
        if (str_starts_with($line, "Temperature:")) {
            $temp = trim(str_replace("Temperature:", "", $line));
        }
        if (str_starts_with($line, "Humidity:")) {
            $humidity = trim(str_replace("Humidity:", "", $line));
        }
    }

    // Save to DB
    $conn = new mysqli("localhost", "php", "0s@48X+_tDL,E)cDC@n>9)UM7Lh:eY", "TunerDB");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $stmt = $conn->prepare("INSERT INTO log (user_id, frequency, temperature, humidity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $freq, $temp, $humidity);

    if ($stmt->execute()) {
        $message = "Tuning data saved successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
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