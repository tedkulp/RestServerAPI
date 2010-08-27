<?php
// cribbed from Marius Rugan's example for Diogo Souza da Silva's RestServer
require_once( dirname(__FILE__).'/json.php' );

class AdminController implements RestController {

    public function execute(RestServer $rest) {
        return $rest;
    }

    public function listing(RestServer $rest) {
        global $gCms;
		$db = &$gCms->GetDb();
		$res = $db->Execute('select content_id, content_name, type from '.cms_db_prefix().'content');
		$pages = array();
		while ($res && $row=$res->FetchRow())
			{
			array_push($pages,$row);
			}

		$json = new Services_JSON();
        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
        $rest->getResponse()->addHeader("Content-Description: File Transfer");
		$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=pages.json");
        $rest->getResponse()->setResponse($json->encode($pages));

        return $rest;
    }

    public function view(RestServer $rest) {
        // If an ID is specified
        $id = $rest->getRequest()->getURI(2); // Second part of the URI
        global $gCms;
		$db = &$gCms->GetDb();
		$res = $db->Execute('select * from '.cms_db_prefix().'content where content_id=?', array($id));
		if ($res && $page=$res->FetchRow())
			{
			$json = new Services_JSON();
	        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
	        $rest->getResponse()->addHeader("Content-Description: File Transfer");
			$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=page.json");
		    $rest->getResponse()->setResponse($json->encode($page));	
			}
		else
			{
	        $rest->getResponse()->addHeader("HTTP/1.1 404 NOT FOUND");
	        $rest->getResponse()->setResponse("Page not found"); 	
			}
        return $rest;
    }

    public function insert(RestServer $rest) {
        $post = $rest->getRequest()->getPost();
        // Go for the database
        $pdo = new PDO("mysql:localhost","user","pass");
        $pdo->query("insert into users (name) values ('".$post['name']."')");
        $id = $pdo->lastInsertId();
        $rest->getResponse()->addHeader("HTTP/1.1 201 CREATED");
        $rest->getResponse()->addHeader("Content-Type: text/plain");
        $rest->getResponse()->setResponse($id);
        return $rest; 
    }

}

?>
