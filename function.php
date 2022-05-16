<?php

function redirect_to($page){
	header("Location: $page");
}

function mysql_conn(){
	$dbhost="localhost";
	$dbuser="root";
	$dbpass="";
	$dbname="vms";
	
	$connect= mysqli_connect($dbhost,$dbuser,$dbpass,$dbname);
	return($connect);
}

function findentry($username,$field){
	$login_qry  = "select * from user where ";
	$login_qry .= "user='$username'";
	
	$res_login = mysqli_query(mysql_conn(),$login_qry);
	
	if($res_login){
		while($user=mysqli_fetch_assoc($res_login)){
			$found=$user["$field"];
		}
		//mysqli_free_result($res_login);
		return($found);
	}
}


?>