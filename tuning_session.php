<?php
session_start();

// Make sure user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$freq = "";
$temp = "";
$humidity = "";

// Set timeout duration (in seconds)
$timeout = 1; // Timeout after 5 seconds
$start_time = time(); // Get current time to track timeout

// Try to open /dev/rfcomm0
$handle = fopen('/dev/rfcomm0', 'r');
if ($handle) {
    // Loop until we have data or timeout
    while (time() - $start_time < $timeout) {
        // Read a line from the device
        $line = fgets($handle);
        if ($line !== false) {
            $line = trim($line);  // Clean up the data
            // Only keep numbers and periods in the frequency
            $freq = preg_replace('/[^0-9.]/', '', $line);
            break;  // Break the loop as we've read the data
        }
    }
    fclose($handle);  // Close the connection to rfcomm0
} else {
    $freq = "No data available";  // Handle error if file can't be opened
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

    // Updated SQL query with the correct table name 'logs'
    $stmt = $conn->prepare("INSERT INTO logs (user_id, frequency, temperature, humidity) VALUES (?, ?, ?, ?)");
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
    <meta http-equiv="refresh" content="1"> <!-- Refresh the page every 1 second to update frequency -->
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <h2>Current Frequency (from device): <?php echo $freq; ?></h2>

    <form method="post" action="">
        <input type="hidden" name="submit_tuning" value="1">
        <button type="submit">I'm Happy With This</button>
    </form>

    <?php if (isset($message)) echo "<p>$message</p>"; ?>
</body>
</html>