<?php


$target_dir = "datafile/";
$target_dir = $target_dir . basename( "eventgod_data.csv");
$uploadOk=1;

//var_dump($_FILES);

?>




<?php
/* 
// Only XLSX files allowed
if (!($_FILES["uploadFile"]["type"] == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")) {
   echo "Sorry, only XLSX files are allowed.";
    $uploadOk = 0;
}
 */


// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "<p>Sorry, your file was not uploaded.<br/>";

// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $target_dir)) {
        echo "<p>The file ". basename( $_FILES["uploadFile"]["name"]). " has been uploaded.<br/><br/>";
    	echo '<a href="run.php">Click here to run the script</a></p>';
    } else {
        echo "<p>Sorry, there was an error uploading your file.<br/>";

    }
}
?> 
 
