<?php

if($_SERVER["REQUEST_METHOD"] == "POST")
{
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    //Check entry
    if(empty(trim($username)) || empty($password))
    {
        echo"Username and Password are Required.";
    }
    
    elseif(strlen($username)>11)
    {
        echo "Username must be 11 Characters or Less";
    }
    else
    {
        //Hash the password
        $hashed_password = password_hash(password: $password, algo: PASSWORD_DEFAULT);

        //Connect to Database
        $conn = new mysqli("localhost","<Your_php_user>","your_php_password","TunerDB");

        if($conn->connect_error)
        {
            die("Connection Failed: " . $conn->connect_error);
        }

        //insert data to TunerDB
        $stmt = $conn->prepare(query:"INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss",$username,$hashed_password);

        if($stmt->execute())
        {
            echo "User Created Successfully.";
            header("Location: login.php?status=account_created");
            exit;
        }
        else
        {
            echo "Error: ". $stmnt->error;
        }

        $stmt->close();
        $conn->close();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Create User</title>
</head>
<body>
    <h1>Create A New User</h1>
    <form method="post" action="create_user.php">
        <label>Username:</label>
        <input type="text" name="username" maxlength="11" required><br><br>
        <label>Password:</label>
        <input type="password" name="password" required><br><br>
        <button type="submit">Create User</button>
    </form>
</body>
</html>
