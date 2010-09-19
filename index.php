<?php

#########################################################################
# Init
#########################################################################

require_once('../../include.php');
require_once('lib/restserver/RestServer.class.php');
require_once('lib/common.functions.php');
include_dir(dirname(__FILE__).'/controller/');
include_dir(dirname(__FILE__).'/view/');
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_GET['q'];
$rest = new RestServer($path);

#########################################################################
# Login
#########################################################################

$ra = $rest->getAuthenticator();
$ra->setRealm('CMSMS Mobile Admin');
$ra->requireAuthentication(true);
$user = $ra->GetUser();
$passwd = $ra->GetPassword();
$db = &$gCms->GetDb();

$query = 'SELECT user_id FROM '.cms_db_prefix().'users WHERE username=? AND password=?';
$user_id = $db->GetOne($query, array($user,md5($passwd)));

if ($user_id) {

	$rest->setParameter('user_id',$user_id);
	$ra->setAuthenticated(true);
}

#########################################################################
# Set map and execute
#########################################################################

$rest->addMap("GET","/?pages","AdminController::listpages");
$rest->addMap("POST","/?pages","AdminController::addpage");
$rest->addMap("GET","/?pages/[0-9]*","AdminController::viewpage");

echo $rest->execute();
?>
