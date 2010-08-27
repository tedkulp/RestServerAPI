<?php
if (!isset($gCms)) exit;
?>
<h1>Syntax</h1>
<ul>
<li>GET <a href="<?php echo $gCms->config['root_url']?>/modules/restserverapi/index.php?q=/pages"><?php echo $gCms->config['root_url']?>/modules/restserverapi/index.php?q=/pages</a> - return a list of pages in JSON format</li>
<li>GET <a href="<?php echo $gCms->config['root_url']?>/modules/restserverapi/index.php?q=/page/15"><?php echo $gCms->config['root_url']?>/modules/restserverapi/index.php?q=/page/page_id</a> - return a page detail in JSON format</li>
<li>Not yet implemented. POST <?php echo $gCms->config['root_url']?>/modules/restserverapi/index.php?q=/page - insert/update a page</li>
</ul>

<h2>Authentication</h2>
<p>Uses Basic Authentication. Any CMSMS Admin User's username/password will give access. Actual CMSMS permissions will be added.</p>
