<?php

class SupportController implements RestController
{

    /**
     * Contructor of RestServer
     * @param string $query Optional query to be treat as the URL
     * @return RestServer $rest;
    */
	public function execute(RestServer $rest)
	{
		return $rest;
	}

	/**
	* Handles incoming POST requests, and acts according to the _method parameter
	*
	*/
	public function postpage(RestServer $rest) {
	
		$get = $rest->getRequest()->getGet();
		switch($get['_method']) {
			case 'DELETE':
				return $this->deletepage($rest); // Not working i know! -Stikki-
				break;
			default:
				return null;
				break;
		}
	}
	
    /**
     * Contructor of RestServer
     * @return content types array in JSON format;
    */
	public function content_type(RestServer $rest) {

		global $gCms;
		$contentops =& $gCms->GetContentOperations();
		$type = $rest->getRequest()->getURI(3);
		$template_id = $rest->getRequest()->getURI(4);
		
		$contentobj = $contentops->CreateNewContent($type);		
		$contentobj->SetAddMode();
		
		if($template_id) {
		
			$contentobj->mTemplateId = $template_id; // SetTemplateId() not working for some reason.
		}
		
		$content_fields = array();
		foreach($contentobj->EditAsArray() as $item) {
		
			$label = $item[0];
			$input = $item[1];
			
			$tmp = array();
			if(stristr($input, 'input')) {
				
				$tmp[] = get_string_between($item[1],'type="','"');
			} 
			
			if(stristr($input,'select')) {
			
				$tmp[0] = 'select';			
			} 
			
			if(stristr($input, 'textarea')) {
						
				$tmp[0] = 'textarea';
			}
			
			$tmp[] = get_string_between($item[1],'name="','"');
			
			$tmp2 = array();
			$tmp2[] = $item[0];
			$tmp2[] = $tmp;
			
			$content_fields[] = $tmp2;
		
		}	
	
		$rest->getResponse()->setResponse(json_encode($content_fields));
		
		return $rest;
	
	}	

}

?>