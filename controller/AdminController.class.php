<?php

class AdminController implements RestController {

    public function execute(RestServer $rest) {
        return $rest;
    }
	
    public function listpages(RestServer $rest) {
	
        global $gCms;
		$db = &$gCms->GetDb();
		
		$query = 'SELECT content_id, content_name, type FROM '.cms_db_prefix().'content';
		$pages = $db->GetArray($query);
		
		$json = new Services_JSON();
        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
        $rest->getResponse()->addHeader("Content-Description: File Transfer");
		$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=pages.json");
        $rest->getResponse()->setResponse($json->encode($pages));

        return $rest;
    }

    public function viewpage(RestServer $rest) {

        global $gCms;
		$db = &$gCms->GetDb();		
        $id = $rest->getRequest()->getURI(2); // Second part of the URI

		$query = 'SELECT * FROM '.cms_db_prefix().'content WHERE content_id=?';
		$row = $db->GetRow($query, array($id));
		
		if ($row) {
		
			$json = new Services_JSON();
	        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
	        $rest->getResponse()->addHeader("Content-Description: File Transfer");
			$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=page.json");
		    $rest->getResponse()->setResponse($json->encode($row));	

		} else {
		
	        $rest->getResponse()->addHeader("HTTP/1.1 404 NOT FOUND");
	        $rest->getResponse()->setResponse("Page not found"); 	
		}
		
		//echo $rest->getParameter('user_id');
        return $rest;
    }
	
    /** 
    * REQUIRED POST: title, menutext, content_en
    * OPTIONAL POST: parent_id	
    */
	public function addpage(RestServer $rest) {
	
		global $gCms;
		$db = &$gCms->GetDb();
		$contentops =& $gCms->GetContentOperations();
		$post = $rest->getRequest()->getPost();


/*
		$userid = $rest->getParameter('user_id');
		$page_secure = get_site_preference('page_secure',0);
		$page_cachable = ((get_site_preference('page_cachable',"1")=="1")?true:false);
		$active = ((get_site_preference('page_active',"1")=="1")?true:false);
		$showinmenu = ((get_site_preference('page_showinmenu',"1")=="1")?true:false);
		$metadata = get_site_preference('page_metadata');
*/		


		$userid = $rest->getParameter('user_id');
		$page_secure = 0;
		$page_cachable = true;
		$active = true;
		$showinmenu = true;
		$metadata = '';		
		$content_type = 'content';
		$error = false;
	
/*	
		$existingtypes = $contentops->ListContentTypes();	
		
		if (isset($post["content_type"])) {
		
			$content_type = $post["content_type"];
			
		} else {
		
			if (isset($existingtypes) && count($existingtypes) > 0) {
			
				$content_type = 'content';

			} else {
			
				$error = "No content types loaded!";
		
			}
		}
*/


		$parent_id = get_preference($userid, 'default_parent', -2);
		if (isset($post['parent_id'])) $parent_id = $post["parent_id"];

		$contentobj = $contentops->CreateNewContent($content_type);
		$contentobj->SetAddMode();
		$contentobj->SetOwner($userid);
		$contentobj->SetCachable($page_cachable);
		$contentobj->SetActive($active);
		$contentobj->SetShowInMenu($showinmenu);
		$contentobj->SetLastModifiedBy($userid);

		$templateops =& $gCms->GetTemplateOperations();
		$dflt = $templateops->LoadDefaultTemplate();
		if(isset($dflt)) {
		
			$contentobj->SetTemplateId($dflt->id);
		}
  
		$contentobj->SetMetadata($metadata);
		$contentobj->SetPropertyValue('content_en', get_site_preference('defaultpagecontent')); // why?

		if ($parent_id!=-1) $contentobj->SetParentId($parent_id);
		$contentobj->SetPropertyValue('searchable', get_site_preference('page_searchable',1));
		$contentobj->SetPropertyValue('extra1', get_site_preference('page_extra1',''));
		$contentobj->SetPropertyValue('extra2', get_site_preference('page_extra2',''));
		$contentobj->SetPropertyValue('extra3', get_site_preference('page_extra3',''));
		$tmp = get_site_preference('additional_editors');
		$tmp2 = array();
		if(!empty($tmp)) {
			
			$tmp2 = explode(',',$tmp);
		}
		
		$contentobj->SetAdditionalEditors($tmp2);
		$contentobj->FillParams($post);

		//$error = $contentobj->ValidateData();
		if ($error === FALSE)
		{
			$contentobj->Save();
			$contentops->SetAllHierarchyPositions();
			//audit($contentobj->Id(), $contentobj->Name(), 'Added Content');

		}		
	
			$json = new Services_JSON();
	        $rest->getResponse()->addHeader("Content-Type: application/json; charset=utf-8");
	        $rest->getResponse()->addHeader("Content-Description: File Transfer");
			$rest->getResponse()->addHeader("Content-Disposition: attachment; filename=addpage.json");
		    $rest->getResponse()->setResponse($json->encode($error));		
	
		return $rest;
	}

}

?>
