<?php
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();
    
require_once('GrepGithubContributors_Plugin.php');
$aPlugin = new GrepGithubContributors_Plugin();
$aPlugin->uninstall();