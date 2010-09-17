<?php
// This is for test if the value posted (POST method) by the client mach to the server, and give a response back to the client true/false 
include_once '../../include.php';
global $gCms;
include_once 'includes/diogok-restserver-b1e641c/RestServer.class.php';
include_once 'includes/AuthTestController.class.php';

$rest = new RestServer();
$rest->addMap("POST",".*","AuthTestController::verify");

echo $rest->execute();
?>
