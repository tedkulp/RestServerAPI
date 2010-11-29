<?php

#########################################################################
# Init
#########################################################################

$CMS_ADMIN_PAGE=1;

require_once('../../include.php');
require_once('../../lib/classes/class.user.inc.php'); // Useless? Explain this to me. -Stikki-
require_once('lib/restserver/RestServer.class.php');
require_once('lib/common.functions.php');
require_once('lib/misc.functions.php');
include_dir(dirname(__FILE__).'/controller/');
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : $_GET['q'];
$rest = new RestServer($path);

if(isset($_SESSION[CMS_USER_KEY]) && !isset($_GET[CMS_SECURE_PARAM_NAME])){
	
	$_GET[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
}	

#########################################################################
# Login
#########################################################################

global $gCms;
$ra = $rest->getAuthenticator();
$ra->setRealm('CMSMS Mobile Admin');
$ra->requireAuthentication(true);
$user = $ra->GetUser();
$passwd = $ra->GetPassword();

$userops =& $gCms->GetUserOperations();
$oneuser =& $userops->LoadUserByUsername($user, $passwd, true, true);

if (isset($oneuser) && $oneuser == true) {

	generate_user_object($oneuser->id);
	$_SESSION['login_user_id'] = $oneuser->id;
	$_SESSION['login_user_username'] = $oneuser->username;
	$default_cms_lang = get_preference($oneuser->id, 'default_cms_language');

	if ($default_cms_lang != '') {
	
		$_SESSION['login_cms_language'] = $default_cms_lang;
	}
	
	audit($oneuser->id, $oneuser->username, 'User Login');
	Events::SendEvent('Core', 'LoginPost', array('user' => &$oneuser));	

	$rest->setParameter('user_id', get_userid());
	$ra->setAuthenticated(true);
}

#########################################################################
# Set map and execute
#########################################################################

// AdminController
$rest->addMap("GET","/?pages","AdminController::listpages");
$rest->addMap("POST","/?pages","AdminController::addpage");
$rest->addMap("GET","/?pages/[0-9]*","AdminController::viewpage");
$rest->addMap("DELETE","/?pages/[0-9]*","AdminController::deletepage");
$rest->addMap("POST","/?pages/[0-9]*","AdminController::postpage");

// SupportController
$rest->addMap("GET","/?get/contenttype/[A-Za-z]*","SupportController::content_type");
$rest->addMap("GET","/?get/contenttype/[A-Za-z]*/[0-9]*","SupportController::content_type");

global $gCms;
$ops = $gCms->GetModuleOperations();
$modules = $ops->get_modules_with_capability('restserver');
if (!empty($modules))
{
	foreach ($modules as $module_obj)
	{
		$module_obj->AddRestMap(&$rest);
	}
}

echo $rest->execute();
?>
