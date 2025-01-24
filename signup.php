<?php
	$duser = 'root';
	$password = ''  ; //To be completed if you have set a password to root
	$database = 'localdata'  ; //To be completed to connect to a database. 
	$port = 3308;     //Default must be NULL to use default port
	$mysqli = new mysqli('localhost', $duser, $password, $database);

	if ($mysqli->connect_error) {
		die('Connect Error (' . $mysqli->connect_errno . ') '
				. $mysqli->connect_error);
	}
	
    $newUsername = $_POST['user'];
    $newPassword = $_POST['pwd']; 

	$result = $mysqli->query("SELECT * FROM users WHERE username = '$newUsername' AND password='$newPassword'");
	$my_array = $result->fetch_assoc();
		
	define("USER",$my_array["username"]);
	define("PASS",$my_array["password"]);
	
	if(isset($_POST["user"])&&isset($_POST["pwd"]))
	{
		$user = $_POST["user"];
		$pwd = $_POST["pwd"];
		//echo "user: ". $user ." pwd: " .$pwd . "<br>";
		//echo "USER: ". USER ." PASS: " .PASS . "<br>";
		if($user==USER && $pwd==PASS)
		{
			echo "User: '$user' already exist !!!" . "<br>";
		}
		else
		{
		  $sql = "INSERT INTO users (username, password) VALUES ('$user', '$pwd')";
		  if ($mysqli->query($sql) === TRUE) {
			  echo "'$user': New user created successfully";
		  } else {
			  echo "Error: " . $sql . "<br>" . $conn->error;
		  }
		} 
	}
?>