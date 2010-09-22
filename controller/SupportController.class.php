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
				return $this->deletepage($rest);
				break;
			default:
				return null;
				break;
		}
	}

}

?>