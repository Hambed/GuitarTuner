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
$serial_device = '/dev/rfcomm0'; // Define the serial device

// Function to read frequency from the serial device
function readFrequency($device, $timeout) {
    $start_time = time();
    $frequency = "";
    $handle = @fopen($device, 'r');
    if ($handle) {
        stream_set_blocking($handle, 0); // Non-blocking read
        while (time() - $start_time < $timeout) {
            $line = fgets($handle, 10); // Read up to 10 bytes (including newline)
            if ($line !== false) {
                $line = trim($line);
                $frequency = preg_replace('/[^0-9.]/', '', $line); // Extract only numbers and dots
                if (strlen($frequency) <= 6 && $frequency !== "") { //check length
                    break; // Exit the loop if we got a valid frequency
                }
            }
            usleep(10000); // Sleep for 10ms to reduce CPU load
        }
        fclose($handle);
    } else {
        error_log("Failed to open serial device: " . $device);
    }
    return $frequency;
}

// Read frequency from device
$freq = readFrequency($serial_device, $timeout);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Run sensor script and get the JSON output
    $command = "source /home/hami/Project/venv/bin/activate && python3 /home/hami/Project/bme280_read.py";
    $output = shell_exec($command);

    if ($output === null) {
        error_log("Error: shell_exec failed to execute the Python script.");
        $message = "Error: Failed to retrieve sensor data.";
        $temp = null; // Set to null to prevent using old values
        $humidity = null;
    } else {

        // Decode the JSON output
        $data = json_decode($output, true);

        if ($data === null && $output !== "") {
           error_log("Error: json_decode failed. Output from python script: " . $output);
           $message = "Error: Invalid sensor data format.";
           $temp = null;
           $humidity = null;
        }
        else if ($data) {
            if (isset($data["temperature"])) {
                $temp = floatval($data["temperature"]); // Ensure it's a float
            } else {
                error_log("Temperature not found in BME280 output.");
                $temp = null;
            }
            if (isset($data["humidity"])) {
                $humidity = floatval($data["humidity"]); // Ensure it's a float
            } else {
                error_log("Humidity not found in BME280 output.");
                $humidity = null;
            }
        }
        else{
            $message = "Error: No sensor data received.";
            $temp = null;
            $humidity = null;
        }
    }
    // Save to DB
    $conn = new mysqli("localhost","[YOUR_PHP_USER]","[YOUR_PHP_PASSWORD]","TunerDB");
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        $message = "Database connection error."; // set message
    } else {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, note_frequency, temperature, humidity) VALUES (?, ?, ?, ?)");
        //check if prepare was successful
        if($stmt){
            $stmt->bind_param("issd", $user_id, $freq, $temp, $humidity); // Make sure the temperature and humidity are treated as doubles
            if ($stmt->execute()) {
                $message = "Tuning data saved successfully.";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        else{
            $message = "Error in preparing the sql statement: " . $conn->error;
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tuning Session</title>
    <meta http-equiv="refresh" content="1"> </head>
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
