<?php

class AuthTestController implements RestController {

    public function execute(RestServer $rest) {
        return $rest;
    }

    public function verify(RestServer $rest) {
	
        global $gCms;
		$db = &$gCms->GetDb();
		$config = $gCms->GetConfig();
        $post = $rest->getRequest()->getPost();

		$query = 'SELECT count(*) FROM '.cms_db_prefix().'users WHERE username=? AND password=?';
		$AuthCheck = $db->GetOne($query,array($post['username'],md5($post['password'])));
				
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
						
		$rest->getResponse()->setResponse(json_encode($output));

        return $rest;
    }
}

?>
