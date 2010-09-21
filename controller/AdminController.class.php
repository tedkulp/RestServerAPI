<?php

class AdminController implements RestController
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
     * Returns listing of CMSMS pages
     * @return RestServer $rest;
    */	
	public function listpages(RestServer $rest)
	{
		global $gCms;
		$db = &$gCms->GetDb();

		$query = 'SELECT * FROM '.cms_db_prefix().'content order by hierarchy';
		$pages = $db->GetArray($query);
		
		$rest->getResponse()->setResponse(json_encode($pages));

		return $rest;
	}

    /**
     * Returns data of one of CMSMS page
     * @return RestServer $rest;
    */		
	public function viewpage(RestServer $rest)
	{
		global $gCms;
		$db = &$gCms->GetDb();		
		$id = $rest->getRequest()->getURI(2); // Second part of the URI

		$query = 'SELECT * FROM '.cms_db_prefix().'content WHERE content_id=?';
		$row = $db->GetRow($query, array($id));

		if ($row)
		{
			$rest->getResponse()->setResponse(json_encode($row));	
		}
		else
		{
			$rest->getResponse()->addHeader("HTTP/1.1 404 NOT FOUND");
			$rest->getResponse()->setResponse("Page not found"); 	
		}

		//echo $rest->getParameter('user_id');
		return $rest;
	}

    /**
     * Adds one CMSMS page
	 * @POST required: title, menutext, content_en
	 * @POST optional: showinmenu, active, secure, cachable, searchable(FP), target, alias, metadata(FP), pagedata(FP), image, 
					   thumbnail, titleatribute, accesskey, tabindex, disable_wysiwyg(FP), extra1, extra2, extra3, 
					   additional_editors, template_id
     * @return RestServer $rest;
    */		
	public function addpage(RestServer $rest)
	{
		global $gCms;
		$db = &$gCms->GetDb();
		$contentops =& $gCms->GetContentOperations();
		$templateops =& $gCms->GetTemplateOperations();		
		$post = $rest->getRequest()->getPost();

		$userid = $rest->getParameter('user_id');
		$alias = isset($post['alias']) ? munge_string_to_url($post['alias'],true) : munge_string_to_url($post['menutext'],true);
		$page_secure = isset($post['secure']) ? $post['secure'] : get_site_preference('page_secure',0); // Values: 0/1
		$page_cachable = isset($post['cachable']) ? $post['cachable'] : ((get_site_preference('page_cachable',"1")=="1")?true:false); // Values: 0/1
		$active = isset($post['active']) ? $post['active'] : ((get_site_preference('page_active',"1")=="1")?true:false); // Values: 0/1
		$showinmenu = isset($post['showinmenu']) ? $post['showinmenu'] : ((get_site_preference('page_showinmenu',"1")=="1")?true:false); // Values: 0/1
		$metadata = isset($post['metadata']) ? $post['metadata'] : get_site_preference('page_metadata');
		$template_id = isset($post['template_id']) ? $post['template_id'] : $templateops->LoadDefaultTemplate()->id;
/*
		$extra1 = isset($post['extra1']) ? $post['extra1'] : get_site_preference('page_extra1','');
		$extra2 = isset($post['extra2']) ? $post['extra2'] : get_site_preference('page_extra2','');
		$extra3 = isset($post['extra3']) ? $post['extra3'] : get_site_preference('page_extra3','');
*/		
		$error = false;

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
		
		$parent_id = -1;
		if (isset($post['parent_id'])) $parent_id = $post["parent_id"];

		$contentobj = $contentops->CreateNewContent($content_type);
		$contentobj->SetAddMode();
		$contentobj->SetOwner($userid);
		$contentobj->SetCachable($page_cachable);
		$contentobj->SetAlias($alias);
		$contentobj->SetActive($active);
		$contentobj->SetShowInMenu($showinmenu);
		$contentobj->SetLastModifiedBy($userid);
		$contentobj->SetParentId($parent_id);
		$contentobj->SetMetadata($metadata);
		$contentobj->SetTemplateId($template_id);
	
		$contentobj->SetPropertyValue('content_en', get_site_preference('defaultpagecontent'));	// Why?
/*
		$contentobj->SetPropertyValue('extra1', $extra1);
		$contentobj->SetPropertyValue('extra2', $extra2);
		$contentobj->SetPropertyValue('extra3', $extra3);
*/
		
		$additional_editors = isset($post['additional_editors']) ? $post['additional_editors'] : get_site_preference('additional_editors'); // Value: string
	
		$tmp2 = array();
		if(!empty($additional_editors)) {
		
			$tmp2 = explode(',',$additional_editors);
		}

		$contentobj->SetAdditionalEditors($tmp2);
		$contentobj->FillParams($post);
	
		$error = $contentobj->ValidateData();
		if ($error === FALSE) {
		
			$contentobj->Save();		
			$contentops->SetAllHierarchyPositions();
			audit($contentobj->Id(), $contentobj->Name(), 'Added Content');
		}

		$rest->getResponse()->setResponse(json_encode($post));

		return $rest;
	}


	/**
	* handles incoming POST requests, and acts according to the _method parameter
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
	
    /**
     * Adds one CMSMS page
	 * @DELETE string $page_id required	 
     * @return RestServer $rest;
    */		
	public function deletepage(RestServer $rest) {
	
		global $gCms;	
		$contentops =& $gCms->GetContentOperations();		
		$hierManager =& $gCms->GetHierarchyManager();	
			
		$userid = $rest->getParameter('user_id');
		$contentid = $rest->getRequest()->getURI(2); // Second part of the URI
		$mypages = author_pages($userid);

		$error = false;
		$access = (check_permission($userid, 'Remove Pages') && (check_ownership($userid,$contentid) || quick_check_authorship($contentid,$mypages))) || check_permission($userid, 'Manage All Content');

		if ($access)
		{
			$node = &$hierManager->getNodeById($contentid);
			if ($node)
			{
				$contentobj =& $node->getContent(true);
				$childcount = 0;
				$parentid = -1;
				if (isset($node->parentNode))
				{
					$parent =& $node->parentNode;
					if (isset($parent))
					{
						$parentContent =& $parent->getContent();
						if (isset($parentContent))
						{
							$parentid = $parentContent->Id();
							$childcount = $parent->getChildrenCount();
						}
					}
				}

				if ($contentobj)
				{
					$title = $contentobj->Name();
		
					#Check for children
					if ($contentobj->HasChildren())
					{
						$error = 'Page has children'; // TODO: Trought $lang
					}
		
					#Check for default
					if ($contentobj->DefaultContent())
					{
						$error = 'Page is default'; // TODO: Trought $lang
					}
				
					if($error === false) {
					
						$contentobj->Delete();
						$contentops->SetAllHierarchyPositions();
						
						#See if this is the last child... if so, remove
						#the expand for it
					/*	if ($childcount == 1 && $parentid > -1)
						{
							toggleexpand($parentid, true);
						}
						
						#Do the same with this page as well
						toggleexpand($contentid, true);
						
						audit($contentid, $title, 'Deleted Content');
					*/	
						$contentops->ClearCache();
				
					}
				

				}
			}
		}	
	
		$rest->getResponse()->setResponse(json_encode($error));	

	}

/*	
	private function toggleexpand($contentid, $collapse = false)
	{
		$userid = get_userid();
		$openedArray=array();
		if (get_preference($userid, 'collapse', '') != '')
		{
			$tmp  = explode('.',get_preference($userid, 'collapse'));
			foreach ($tmp as $thisCol)
			{
				$colind = substr($thisCol,0,strpos($thisCol,'='));
				$openedArray[$colind] = 1;
			}
		}
		if ($collapse)
		{
			$openedArray[$contentid] = 0;
		}
		else
		{
			$openedArray[$contentid] = 1;
		}
		$cs = '';
		foreach ($openedArray as $key=>$val)
		{
			if ($val == 1)
			{
				$cs .= $key.'=1.';
			}
		}
		set_preference($userid, 'collapse', $cs);
	}	
*/	
	
	
}

?>
