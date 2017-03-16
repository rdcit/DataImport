<?php
require_once 'includes/connection.inc.php';

require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);

$action = $_GET['action'];
$_SESSION['task']= $action;




?>

<br/>
<form action="uploadxmls.php" method="post" enctype="multipart/form-data">

<table id="uploader">

<tr><td>Please choose an ODM XML file:</td><td> <input type="file" name="uploadFile"></td></tr>
<?php if($_SESSION['task'] == "difference"){?>
<tr><td>Please choose another ODM XML file:</td><td> <input type="file" name="uploadFile2"></td></tr>
<?php }?>
<tr><td>Action:</td><td><b><?php echo $action;?> </b><input type="hidden" name="actionType" value="<?php echo $action;?>"></td></tr>
<tr><td><input type="submit" value="Upload Files" class="easyui-linkbutton" data-options="iconCls:'icon-add'"></td></tr>
</table>
</form> 



<?php 
require_once 'includes/html_bottom.inc.php';
?>