<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$freq = "";
$temp = "";
$humidity = "";

// Timeout settings for serial read
$timeout = 5;
$start_time = time();

// Read frequency from device
$handle = @fopen('/dev/rfcomm0', 'r');
if ($handle) {
    stream_set_blocking($handle, 0);
    while (time() - $start_time < $timeout) {
        $line = fgets($handle);
        if ($line !== false) {
            $line = trim($line);
            $freq = preg_replace('/[^0-9.]/', '', $line);
            break;
        }
    }
    fclose($handle);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Run sensor script
    $output = shell_exec("python3 /home/hami/Project/bme280_read.py");
    if ($output) {
        $output_lines = explode("\n", trim($output));
        foreach ($output_lines as $line) {
            if (str_starts_with($line, "Temperature:")) {
                $temp = trim(str_replace("Temperature:", "", $line));
            }
            if (str_starts_with($line, "Humidity:")) {
                $humidity = trim(str_replace("Humidity:", "", $line));
            }
        }
    }

    // Save to DB
    $conn = new mysqli("localhost", "php", "0s@48X+_tDL,E)cDC@n>9)UM7Lh:eY", "TunerDB");
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
    } else {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, note_frequency, temperature, humidity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $freq, $temp, $humidity);
        if ($stmt->execute()) {
            $message = "Tuning data saved successfully.";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tuning Session</title>
    <meta http-equiv="refresh" content="1">
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <h2>Current Frequency (from device): <?php echo htmlspecialchars($freq); ?></h2>

    <form method="post" action="">
        <input type="hidden" name="submit_tuning" value="1">
        <button type="submit">I'm Happy With This</button>
    </form>

    <?php if (isset($message)) echo "<p>" . htmlspecialchars($message) . "</p>"; ?>
</body>
</html>