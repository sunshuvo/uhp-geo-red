<?php
	require("session.php");
	require("function.php");
	
	$_SESSION["username"]=null;
	redirect_to("login.php");
?>