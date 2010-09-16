<?php

include_once '../../include.php';
global $gCms;
include_once 'includes/diogok-restserver-b1e641c/RestServer.class.php';
include_once 'includes/AuthTestController.class.php';

$rest = new RestServer();
$rest->addMap("POST",".*","AuthTestController::verify");

echo $rest->execute();
?>
