<?php

include_once '../../include.php';
global $gCms;
include_once 'includes/diogok-restserver-b1e641c/RestServer.class.php';
include_once 'includes/AdminController.class.php';


$rest = new RestServer($_GET['q']) ;
$ra = $rest->getAuthenticator();
$ra->setRealm('CMSMS Mobile Admin');
$ra->requireAuthentication(true);
$u = $ra->GetUser();
$p = $ra->GetPassword();
$db = &$gCms->GetDb();

$res = $db->Execute('select user_id from '.cms_db_prefix().'users where username=? and password=?',
	array($u,md5($p)));
if ($res && $usr=$res->FetchRow())
	{
	$rest->setParameter('user_id',$usr['user_id']);
	$ra->setAuthenticated(true);
	}

$rest->addMap("GET","/?pages","AdminController::listing");
$rest->addMap("POST","/?pages","AdminController::insert");
$rest->addMap("GET","/?page/[0-9]*","AdminController::view");


echo $rest->execute();
?>
