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
	 * @POST string $title required
	 * @POST string $menutext required
	 * @POST string $content_en required
	 * @POST string $parent_id optional		 
     * @return RestServer $rest;
    */		
	public function addpage(RestServer $rest)
	{
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

		if (isset($post["content_type"]))
		{
			$content_type = $post["content_type"];
		}
		else
		{
			if (isset($existingtypes) && count($existingtypes) > 0)
			{
				$content_type = 'content';
			}
			else
			{
				$error = "No content types loaded!";
			}
		}
		*/

		//$contentops->LoadContentType($content_type);
		
		$parent_id = -1;
		if (isset($post['parent_id'])) $parent_id = $post["parent_id"];

		$contentobj = $contentops->CreateNewContent($content_type);
		$contentobj->SetAddMode();
		$contentobj->SetOwner($userid);
		$contentobj->SetCachable($page_cachable);
		$contentobj->SetActive($active);
		$contentobj->SetShowInMenu($showinmenu);
		$contentobj->SetLastModifiedBy($userid);
		$contentobj->SetParentId($parent_id);
		
		$templateops =& $gCms->GetTemplateOperations();
		$dflt = $templateops->LoadDefaultTemplate();
		
		if(isset($dflt))
		{
			$contentobj->SetTemplateId($dflt->id);
		}

		$contentobj->SetMetadata($metadata);
		$contentobj->SetPropertyValue('content_en', get_site_preference('defaultpagecontent')); // why?

		if ($parent_id != -1) $contentobj->SetParentId($parent_id);
	
		$contentobj->SetPropertyValue('searchable', get_site_preference('page_searchable',1));
		$contentobj->SetPropertyValue('extra1', get_site_preference('page_extra1',''));
		$contentobj->SetPropertyValue('extra2', get_site_preference('page_extra2',''));
		$contentobj->SetPropertyValue('extra3', get_site_preference('page_extra3',''));
		$tmp = get_site_preference('additional_editors');
	
		$tmp2 = array();
		if(!empty($tmp))
		{
			$tmp2 = explode(',',$tmp);
		}

		$contentobj->SetAdditionalEditors($tmp2);
		$contentobj->FillParams($post);

		$error = $contentobj->ValidateData();
		if ($error === FALSE)
		{
			$contentobj->Save();		
			$contentops->SetAllHierarchyPositions();
			audit($contentobj->Id(), $contentobj->Name(), 'Added Content');
		}

		$rest->getResponse()->setResponse(json_encode($error));

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
