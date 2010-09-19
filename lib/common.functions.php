<?php

function include_dir($path) {

	if($dir = opendir($path)) {
		
		while (false !== ($file = readdir($dir))) {

			if($file == '..' || $file == '.' || $file == 'index.html' || $file == '.svn') continue;			
			include_once($path.$file);
		}
		
		closedir($dir);	
	}
}

?>