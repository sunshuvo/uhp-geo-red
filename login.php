<?php
	date_default_timezone_set("Asia/Dhaka");
	require("session.php");
	require("function.php");
	$username="";

	$connect=mysql_conn();
	
	function passcheck($username,$password){
		$login_qry  = "select * from user where ";
		$login_qry .= "user='$username'";
		
		$res_login = mysqli_query(mysql_conn(),$login_qry);
		$result=mysqli_fetch_array($res_login);
		
		
		if($password==$result["password"]){return true;}
		else{return false;}
	}

    if(isset($_POST["username"])){
		$username = $_POST["username"];
		$password = $_POST["password"];
		


		if(passcheck($username,$password)){
			$_SESSION["username"] = findentry($username,"user");
			redirect_to("index.php");

		}
		else {echo "<h2>Wrong Credential.... Please try Again</h2>";};
	}

	//if($connect){echo "Mysql Connected...";}
	//else{die();}

?>


<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>UHP Manual Switch Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">

</head>

	

<body>
	<!--Heading Start-->
	<header>
		<div class="container">
			<div class="login_logo">
				<img src="img/uhp.jpeg" alt="">
			</div>
			
		</div>
	</header>
	<!--Heading End-->
	
		<!--Login Form Start-->
	<div class="container">
	<a class="nav-link" href="logout.php">Logout</a>
	
		<div class="row fs-3 fw-bold">
			<form action="login.php" method="post">
				<div class="col-12">
					<div class="row my-3">
						<div class="col-4"><label for="user">Username:</label></div>
						<div class="col-8"><input class="form-control" id="user" type="text" name="username" value="<?php echo $username;?>" placeholder="Enter Username"></div>
					</div>
				</div>
				<div class="col-12 my-3">
					<div class="row">
						<div class="col-4"><label for="password">Password:</label></div>
						<div class="col-8"><input class="form-control" id="password" type="password" name="password" value="" placeholder="Enter password"></div>
					</div>
				</div>
				<div class="col-12 text-center my-3">
					<input class="btn btn-success btn-lg fs-3 fw-bold" type="submit" name="submit" value="Login"><br><br><br>
				</div>
			</form>
		</div>
	</div>
	<!--Login Form End-->
	
	<!--Footer Start-->
	<!--Footer End-->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
</body>
</html>