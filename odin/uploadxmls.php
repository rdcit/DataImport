<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
//add the session id to the datafile's name
$target_dir = "uploads/";
$file1 = $target_dir . basename( $_FILES["uploadFile"]["name"]);
$file2 = $target_dir . basename( $_FILES["uploadFile2"]["name"]);

if(substr($file1, -4) === ".xml" && substr($file1, -4) === ".xml"){
	$firstXml = false;
	$secondXml = false;
	
	if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $file1)) {
		echo "<p>The file ". basename( $_FILES["uploadFile"]["name"]). " has been uploaded.<br/></p>";
		
		$_SESSION['xmlFile']=$_FILES["uploadFile"]["name"];
		$firstXml = true;
	} 
	
	if($_SESSION['task'] == "difference"){
		if (move_uploaded_file($_FILES["uploadFile2"]["tmp_name"], $file2)) {
			echo "<p>The file ". basename( $_FILES["uploadFile2"]["name"]). " has been uploaded.<br/></p>";
			$_SESSION['xmlFile2']=$_FILES["uploadFile2"]["name"];
			$secondXml = true;
		}
		
	}
	else{
		$secondXml = true;
	}
	
	
	
	if($firstXml && $secondXml){
		echo '<p><a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a> or <a href="xmldiff.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue</a></p>';
		
	}
	
	else {
		echo "<p>Sorry, there was an error uploading your file.<br/>";
		echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
	}
	
	
}
else{
	echo "<p>Only xml files can be uploaded. Please select two xml files.<br/>";
	echo '<a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';
	
	
}



//var_dump($_FILES);

?>


 
<?php 
require_once 'includes/html_bottom.inc.php';
?> 
 