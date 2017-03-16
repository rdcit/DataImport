<html>
<head>
<title>DataLoader</title>
</head>
<body>

<?php
if (!file_exists('datafile')) {
	//create datafile directory if not exists
	mkdir('datafile', 0755, true);
}

if (!file_exists('savedxmls')) {
	//create savedxmls directory if not exists
	mkdir('savedxmls', 0755, true);
}


//checking permissions to directories
$perm_err = array();
if (!is_writable("datafile/")){
	$perm_err[] = "Dataloader must have write permission to its datafile directory.";
}
if (!is_writable("savedxmls/")){
	$perm_err[] = "Dataloader must have write permission to its savedxmls directory.";
}


if(!empty($perm_err)){
	foreach ($perm_err as $pe){
		echo $pe."<br/>";
	}
	die();
}



?>
<br/>

<form action="upload.php" method="post" enctype="multipart/form-data">
<table id="uploader">
<tr><td>Please choose a CSV file to upload:</td><td> <input type="file" name="uploadFile"></td></tr>
<tr><td><input type="submit" value="Upload File" class="easyui-linkbutton" data-options="iconCls:'icon-add'"></td></tr>
</table>
  </form> 
</body>
</html>