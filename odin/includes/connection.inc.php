<?php

@ini_set("session.use_cookies", 1);
@ini_set("session.use_only_cookies", 1);
@ini_set('session.gc_maxlifetime', 7200);
@session_set_cookie_params(7200);
//@ini_set("display_errors", E_ALL);
session_start(); 
error_reporting(E_ERROR | E_WARNING | E_PARSE);
if ( !function_exists('get_magic_quotes_gpc') || get_magic_quotes_gpc() ) {
 $_GET = array_map('own_strip', $_GET);
 $_POST = array_map('own_strip', $_POST);
}
function own_strip($elem) {
 if ( is_array($elem) ) {
   return array_map("own_strip", $elem);
 }
 return stripslashes($elem);
}

function redirect($url_and_query) {
 // This is the proper browser redirecting
 header("HTTP 302 Found");
 // Location string must be properly urlencoded
 header("Location: ".$url_and_query);
 die();
}

function is_logged_in(){
	if (!$_SESSION['user_id']){
		redirect("login.php");
		die();
	}
}

function is_logged_out(){
	if ($_SESSION['user_id']){
		redirect("index.php");
		die();
	}
}

function removeFiles($path,$timeago) {
	$files = array();
	$index = array();
	if ($handle = opendir($path)) {

		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if(is_dir($path.'/'.$file))
					removeFiles($path.'/'.$file,$timeago);
				else if(filemtime( $path.'/'.$file ) < $timeago) {
					unlink($path.'/'.$file);
					//echo 'Deleted '.$path.'/'.$file."\n";
				}
			}
		}
		closedir($handle);
	}
}
