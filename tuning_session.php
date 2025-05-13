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