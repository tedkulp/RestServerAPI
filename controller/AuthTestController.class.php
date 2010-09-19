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
		
		$domain = $config['root_url'];
		$postedDomain = $post['cmsdomain'];
			
			$check = "false";
			if($AuthCheck > 0 && $postedDomain == $domain) {
			 $check = "true";
			 }
			 $output = array('sucess' => $check); 
			 
		$json = new Services_JSON();
        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
        $rest->getResponse()->addHeader("Content-Description: File Transfer");
		$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=authTest.json");
        $rest->getResponse()->setResponse($json->encode($output));

        return $rest;
    }
}

?>
