<?php

class RestServerAPI extends CMSModule
{
	function RestServerAPI()
	{
	$this->CMSModule();
	}
	
    function GetName()
  {
    return 'RestServerAPI';
  }
  function GetVersion()
  {
    return '0.1';
  }
  function GetAuthor()
  {
    return 'SjG';
  }
  function GetAuthorEmail()
  {
    return 'sjg@cmsmodules.com';
  }
  function HasAdmin()
  {
    return true;
  }
  function MinimumCMSVersion()
  {
    return "1.7";
  }
} //end class
?>
