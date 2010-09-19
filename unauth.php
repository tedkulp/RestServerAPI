<?php
// This is for test if the value posted (POST method) by the client mach to the server, and give a response back to the client true/false 
#########################################################################
# Init
#########################################################################

include_once '../../include.php';
include_once 'includes/restserver/RestServer.class.php';
require_once('lib/common.functions.php');
include_dir(dirname(__FILE__).'/controller/');
include_dir(dirname(__FILE__).'/view/');
$rest = new RestServer();

#########################################################################
# Set map and execute
#########################################################################

$rest->addMap("POST",".*","AuthTestController::verify");

echo $rest->execute();
?>
