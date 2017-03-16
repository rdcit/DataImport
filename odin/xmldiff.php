<?php 

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
require_once 'classes/OpenClinicaReportXML.php';

if($_SESSION['task'] != "stats" && $_SESSION['task'] != "difference"){
	
	echo "<p>Missing task!<br/>";
		echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
	
		require_once 'includes/html_bottom.inc.php';
		die();
}


if(!$_SESSION['xmlFile']){

	echo "<p>Missing xml file!<br/>";
	echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';

	require_once 'includes/html_bottom.inc.php';
	die();
}




if ($_SESSION['task'] =="stats"){
	
	$xml_server = new OpenClinicaReportXML();
	$xml_server->load('uploads/'.$_SESSION['xmlFile']);
	$xml_server->printXMLStats('uploads/'.$_SESSION['xmlFile2']);
	
}
else if($_SESSION['task'] =="difference"){
	
	$xml_server = new OpenClinicaReportXML();
	$xml_server->load('uploads/'.$_SESSION['xmlFile']);
	
	$diffXML = $xml_server->createXMLDiff('uploads/'.$_SESSION['xmlFile2']);
	$diffName = 'uploads/difference_'.$_SESSION['importid'].'.xml';
	
	//var_dump($diffXML);
	file_put_contents($diffName,$diffXML);
	
	echo'<p>Creating ODM xml ...</p>';
	echo '<p><button type="button" onclick="location.href=\'download.php?type=diff&id='.$_SESSION['importid'].'\'">Download diff XML</button></p>';
	
	
}


require_once 'includes/html_bottom.inc.php';
?>