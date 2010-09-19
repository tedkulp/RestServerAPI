<?php

class AuthTestController implements RestController {

    public function execute(RestServer $rest) {
        return $rest;
    }

    public function verify(RestServer $rest) {
        global $gCms;
		$config = $gCms->GetConfig();
        $post = $rest->getRequest()->getPost();
         //error_log(print_r($post));  
		$db = &$gCms->GetDb();
		$AuthCheck = $db->GetOne('select count(*) from '.cms_db_prefix().'users where username=? and password=?',
			array($post['username'],md5($post['password'])));
		
		
		$postedDomain = $post['cmsdomain'];
		$domain = $config['root_url'];
		$signatureID = md5($config['db_name'] . $config['root_url'] . $config['root_path']);
			
		$check = "false";
			if($AuthCheck > 0 && $postedDomain == $domain) {
			 $check = "true";
			 }
		$output = array(
						'sucess' => $check,
						'signature' => $signatureID
						); 
			 
		$rest->getResponse()->setResponse(json_decode($output));

        return $rest;
    }
}

?>
