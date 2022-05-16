<?php
	date_default_timezone_set("Asia/Dhaka");
	ini_set('session.gc_maxlifetime', 0);
	require("session.php");
	require("function.php");
	if(!isset($_SESSION["username"])){redirect_to("login.php");}
	$dbtable="uhp-switch";
	$connect=mysql_conn();

require_once "phptelnet.php";

//NMS Selection
function uhpapi_active_nms($nms){
	$data= <<<DATA
{
    "object": "system",
    "action": "core",
    "params": {"cmd": "status"}
}
DATA;
	$url="http://".$nms."/jsonapi/?token=3RzM2jnm7s32wKOtXm9pLGQhdLhCxwkpaWx9tQMvjjfIWhzkNf2u94U9ZKig2h0K";
	$crl = curl_init($url);
	curl_setopt($crl, CURLOPT_POST, 1);
	curl_setopt($crl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($crl);
	$decode=json_decode($result, true);
	$nms_report=$decode["result"];
	$role = $nms_report["redundancy_role"];
	return($role);
}

if(uhpapi_active_nms("192.168.168.162")=="master"){$GLOBALS["nms"] = "192.168.168.162"; $active_nms="NMS-1"; }
if(uhpapi_active_nms("192.168.168.161")=="master"){$GLOBALS["nms"] = "192.168.168.161"; $active_nms="NMS-2"; }
if(uhpapi_active_nms("192.168.168.160")=="master"){$GLOBALS["nms"] = "192.168.168.160"; $active_nms="NMS-3"; }
//NMS Selection

//Modem Reboot Function
function reboot($modem){

	$telnet = new PHPTelnet();

	// if the first argument to Connect is blank,
	// PHPTelnet will connect to the local host via 127.0.0.1
	$result = $telnet->Connect($modem,'uhp','uhpnms');

	if ($result == 0) {
	$telnet->DoCommand('reboot', $result);
	$telnet->DoCommand('y', $result);
	// NOTE: $result may contain newlines
	//echo $result;
		// say Disconnect(0); to break the connection without explicitly logging out
	$telnet->Disconnect();
	}
}
//Modem Reboot Function

//Modem update Function
function uhpapi_update_pass($id, $pass){
	$data= <<<DATA
{
    "object": "modem",
    "action": "update",
    "id": "$id",
    "params": {"password": "$pass", "lic_types": ["1185","6146"]}
}
DATA;
	$url="http://".$GLOBALS["nms"]."/jsonapi/?token=3RzM2jnm7s32wKOtXm9pLGQhdLhCxwkpaWx9tQMvjjfIWhzkNf2u94U9ZKig2h0K";
	$crl = curl_init($url);
	curl_setopt($crl, CURLOPT_POST, 1);
	curl_setopt($crl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($crl);
}
//Modem update Function

//Action Taken based on Submit//
if(isset($_POST["fault"]) || isset($_POST["clear"])){
	if(isset($_POST["id"])){
		if(isset($_POST["fault"])){
			foreach($_POST["id"] as $j => $item){
				foreach($item as $id => $ip){
					uhpapi_update_pass($id, "test1");
					if($ip!=NULL) { reboot($ip);}
					//echo $id."=".$ip."<br>";
				}
			}
		}
		if(isset($_POST["clear"])){
			foreach($_POST["id"] as $j => $item){
				foreach($item as $id => $ip){
					uhpapi_update_pass($id, "test");
					//echo $id."=".$ip."<br>";
				}
			}
		}
		
	} 
	else{echo "Select atleast One modem to submit";}
}
//Action Taken based on Submit//

//UHP API Call//
function uhpapi($section,$action, $id){
	$data= array("object"=>"$section", "action"=>"$action", "id"=>$id);
	$postdata = json_encode($data);
	$url="http://".$GLOBALS["nms"]."/jsonapi/?token=3RzM2jnm7s32wKOtXm9pLGQhdLhCxwkpaWx9tQMvjjfIWhzkNf2u94U9ZKig2h0K";
	$crl = curl_init($url);
	curl_setopt($crl, CURLOPT_POST, 1);
	curl_setopt($crl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($crl);
	return($result);
}
//UHP API Call//

//Controller List Generate
$controller_list_decode=json_decode(uhpapi("controller","list",""), true);
$controller_list=$controller_list_decode["data"];

foreach($controller_list as $controller_list) {
	$con_name = $controller_list["name"];
	$con_id = $controller_list["id"];
	
	$controller_info_decode=json_decode(uhpapi("controller","select",$con_id), true);
	$controller_info=$controller_info_decode["data"];
	
	$connected = $controller_info["name"];
	$modem_id = $controller_info["modem_id"];
	
	$controller_status[] = array("id"=>$con_id, "name"=>$con_name, "modem_id"=>$modem_id);
}
//Controller List Generate

//Modem List Generate
$modem_list_decode=json_decode(uhpapi("modem","list",""), true);
$modem_list=$modem_list_decode["data"];

foreach($modem_list as $modem_list) {
	$mod_name = $modem_list["name"];
	$mod_id = $modem_list["id"];
	
	$modem_info_decode=json_decode(uhpapi("modem","select",$mod_id), true);
	$modem_info=$modem_info_decode["data"];
	
	$connected = $modem_info["connected"];
	$rffeed_id = $modem_info["rffeed_id"];
	$is_active = $modem_info["status"];
	$password = $modem_info["password"];
	$ip_addr = $modem_info["ip_addr"];
	
	$controller=NULL;
	$controller_id=NULL;
	foreach($controller_status as $i => $item){
		if($item["modem_id"]==$mod_id){
			$controller=$item["name"];
			$controller_id=$item["id"];
		}
	}
	
	$modem_status[] = array("id"=>$mod_id, "name"=>$mod_name, "rffeed_id"=>$rffeed_id, "connected"=>$connected, "password"=>$password, "controller"=>$controller, "ip_addr"=>$ip_addr, "is_fault"=>$is_active, "controller_id"=>$controller_id);
	
}
//Modem List Generate
?>


<html>

<head>
<title>UHP-Redundency</title>
	<!-- Bootstart CSS Links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">

</head>
<body>

<?php 


?>
<main>
<div class="container">	
	<br>
	<div class="row">
		<div class="col-6 col-md-6 text-left">
			<h2>Redundency Controls : <?php echo $active_nms; ?></h2>
		</div>
		<div class="col-6 col-md-6 text-end">
			<a class="btn btn-warning" href="logout.php">Logout</a>
		</div>
	</div>
	<br>
	<div class="row text-center d-flex justify-content-center">
		<div class="col-10 col-md-10">
			
			<table class="table table-sm table-hover table-bordered text-center table-striped ">
			  <thead>
			    <tr>
				  <th colspan="9" class="text-center"><h4>List of Devices at Gazipur</h4></th>
				</tr>
				<tr>
				  <th scope="col">SL</th>
				  <th scope="col">Modem Name</th>
				  <th scope="col">Location</th>
				  <th scope="col">Connected</th>
				  <th scope="col">Status</th>
				  <th scope="col">Password</th>
				  <th scope="col">Controller</th>
				  <th scope="col">IP Address</th>
				  <th scope="col">Action</th>
				</tr>
			  </thead>
			  <form METHOD="POST" ACTION="index.php">
			  <tbody>
				<?php foreach($modem_status as $i => $item){ if ($item["rffeed_id"]==1){?>
				<tr>
				  <th scope="row"><?php echo $i+1; ?></th>
				  <td><a href="http://<?php echo $GLOBALS["nms"]; ?>/#/modem/<?php echo $item["id"];?>/update/"  target="_blank"><?php echo $item["name"]; ?></a></td>
				  <td><?php echo "Gazipur"; ?></td>
				  <td><?php if($item["connected"]==1){ echo "<div style=\"color:green\">Yes</div>";} else { echo "<div style=\"color:red\">No</div>";} ?></td>
				  <td><?php if($item["is_fault"]==0){ echo "<div style=\"color:green\">IDLE</div>";} elseif($item["is_fault"]==1) { echo "<div style=\"color:blue\">ACTIVE</div>";} elseif($item["is_fault"]==2) { echo "<div style=\"color:red\">FAULT</div>";} ?></td>
				  <td><?php echo $item["password"]; ?></td>
				  <td><a href="http://<?php echo $GLOBALS["nms"]; ?>/#/controller_dashboard/<?php echo $item["controller_id"];?>/"  target="_blank"><?php echo "<div style=\"color:blue\">".$item["controller"]."</div>"; ?></a></td>
				  <td><a href="http://uhp:uhpnms@<?php echo $item["ip_addr"]; ?>"  target="_blank"><?php echo $item["ip_addr"]; ?></a></td>
				  <td><input type="checkbox" name="id[][<?php echo $item["id"]; ?>]" value="<?php if($item["controller_id"]!=NULL) {echo $item["ip_addr"];} ?>"></td>
				</tr>
				<?php }} ?>
				<tr>
				  <td colspan="4" class="text-center">
					<div class="button">
						<input type="submit" class="btn btn-danger" name="fault" value="Create Fault" onclick="return confirm('Creating Fault: Are you sure?')">
					</div>
				  </td>
				  <td colspan="5" class="text-center">
					<div class="button">
						<input type="submit" class="btn btn-primary" name="clear" value="Clear Fault" onclick="return confirm('Clearing: Are you sure?')">
					</div>
				  </td>
				</tr>
			  </tbody>
			  </form>
			</table>
		</div>
	</div>

<br><br>

	<div class="row text-center d-flex justify-content-center">
		<div class="col-10 col-md-10">
			
			<table class="table table-sm table-hover table-bordered text-center table-striped ">
			  <thead>
			    <tr>
				  <th colspan="9" class="text-center"><h4>List of Devices at Betbunia</h4></th>
				</tr>
				<tr>
				  <th scope="col">SL</th>
				  <th scope="col">Modem Name</th>
				  <th scope="col">Location</th>
				  <th scope="col">Connected</th>
				  <th scope="col">Status</th>
				  <th scope="col">Password</th>
				  <th scope="col">Controller</th>
				  <th scope="col">IP Address</th>
				  <th scope="col">Action</th>
				</tr>
			  </thead>
			  <form METHOD="POST" ACTION="index.php">
			  <tbody>
				<?php foreach($modem_status as $i => $item){ if ($item["rffeed_id"]==2){?>
				<tr>
				  <th scope="row"><?php echo $i+1; ?></th>
				  <td><a href="http://<?php echo $GLOBALS["nms"]; ?>/#/modem/<?php echo $item["id"];?>/update/"  target="_blank"><?php echo $item["name"]; ?></a></td>
				  <td><?php echo "Betbunia"; ?></td>
				  <td><?php if($item["connected"]==1){ echo "<div style=\"color:green\">Yes</div>";} else { echo "<div style=\"color:red\">No</div>";} ?></td>
				  <td><?php if($item["is_fault"]==0){ echo "<div style=\"color:green\">IDLE</div>";} elseif($item["is_fault"]==1) { echo "<div style=\"color:blue\">ACTIVE</div>";} elseif($item["is_fault"]==2) { echo "<div style=\"color:red\">FAULT</div>";} ?></td>
				  <td><?php echo $item["password"]; ?></td>
				  <td><a href="http://<?php echo $GLOBALS["nms"]; ?>/#/controller_dashboard/<?php echo $item["controller_id"];?>/"  target="_blank"><?php echo "<div style=\"color:blue\">".$item["controller"]."</div>"; ?></a></td>
				  <td><a href="http://uhp:uhpnms@<?php echo $item["ip_addr"]; ?>"  target="_blank"><?php echo $item["ip_addr"]; ?></a></td>
				  <td><input type="checkbox" name="id[][<?php echo $item["id"]; ?>]" value="<?php if($item["controller_id"]!=NULL) {echo $item["ip_addr"];} ?>"></td>
				</tr>
				<?php }} ?>
				<tr>
				  <td colspan="4" class="text-center">
					<div class="button">
						<input type="submit" class="btn btn-danger" name="fault" value="Create Fault" onclick="return confirm('Creating Fault: Are you sure?')">
					</div>
				  </td>
				  <td colspan="5" class="text-center">
					<div class="button">
						<input type="submit" class="btn btn-primary" name="clear" value="Clear Fault" onclick="return confirm('Clearing: Are you sure?')">
					</div>
				  </td>
				</tr>
			  </tbody>
			  </form>
			</table>
		</div>
	</div>
</div>
</main>
<?php require("footer.php"); ?>
</body>
</html>