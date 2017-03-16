<?php
require_once 'classes/OpenClinicaSoapWebService.php';
require_once 'classes/OpenClinicaODMFunctions.php';
require_once 'classes/PHPExcel.php';
require_once 'settings/settings.inc.php';

// connect to webservices

$client = new OpenClinicaSoapWebService ( $ocWsInstanceURL, $user, $password );

class SpecialValueBinder extends PHPExcel_Cell_DefaultValueBinder implements PHPExcel_Cell_IValueBinder
{
	public function bindValue(PHPExcel_Cell $cell, $value = null)
	{

		$value = PHPExcel_Shared_String::SanitizeUTF8($value);
		$cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
		return true;
	}
}


/**  Tell PHPExcel that we want to use our Special Value Binder  **/
PHPExcel_Cell::setValueBinder( new SpecialValueBinder() );




// read the xls data file
$inputFileName = 'datafile/eventgod_data.csv';

try {
	$inputFileType = PHPExcel_IOFactory::identify ( $inputFileName );
	$objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
	$objPHPExcel = $objReader->load ( $inputFileName );
} catch ( Exception $e ) {
	die ( 'Error loading file "' . pathinfo ( $inputFileName, PATHINFO_BASENAME ) . '": ' . $e->getMessage () );
}

// Get worksheet dimensions
$sheet = $objPHPExcel->getSheet ( 0 );
$highestRow = $sheet->getHighestRow ();
$highestColumn = $sheet->getHighestColumn ();

$excelData = array ();
// Read all rows
for($row = 1; $row <= $highestRow; $row ++) {

	$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, FALSE, FALSE, FALSE);
	array_push($excelData, $rowData[0]);
	//array_push ( $excelData, $rowdata );
}

//var_dump($excelData);


echo "<br/><br/>Attempting to schedule events from file...<br/><br/>
		<table><thead><tr><td>Subject</td><td>Occ. in OC</td><td>Occ. in csv</td><td>Action/Result</td></tr></thead><tbody>";

$dbh = new PDO("pgsql:dbname=$db;host=$dbhost", $dbuser, $dbpass );

$logFile = "sql.log";
	
	for($i = 1; $i < $highestRow; $i ++) {
		$subjectID = trim($excelData[$i][0]);
		$eventOID = trim($excelData[$i][1]);
		$eventRepKey = intval($excelData[$i][2]);
		$eventStartDate = trim($excelData[$i][3]);
		$studyProtocolId = trim($excelData[$i][4]);
		$studySiteId = trim($excelData[$i][5]);
		$ocEventLocation = "";
		$eventStartTime="00:01";
		$ocEventEndDate = null;
		$ocEventEndTime = null;
		
		$dateFormat = "/(^([0-9]{2})\\/([0-9]{2})\\/([0-9]{4})$)/";
		//if date is UK standard format
		if (preg_match($dateFormat,$eventStartDate)){
			$dateParts = explode("/",$eventStartDate);
				
			//transform the date to YYYY-MM-DD format
			$eventStartDate_final = trim($dateParts[2]."-".$dateParts[1]."-".$dateParts[0]);
		}
		else{
			$eventStartDate_final = trim($eventStartDate);
		}
		
		
		$sql =	"SELECT max(study_event.sample_ordinal) as last_event
					FROM
					public.study_subject
					INNER JOIN
					public.study_event ON study_subject.study_subject_id = study_event.study_subject_id
					INNER JOIN public.study_event_definition 
				    ON  study_event.study_event_definition_id = study_event_definition.study_event_definition_id
					AND study_subject.label = :subjectid	
				    AND study_event_definition.oc_oid = :eventoid";
		
		//file_put_contents($logFile, $sql."\n");
		
		$sth = $dbh->prepare($sql);
		$sth->bindParam(':subjectid',$subjectID);
		$sth->bindParam(':eventoid',$eventOID);
		
		$sth->execute();
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		
		$eventOccurrenceOnServer=$result['last_event'];
		
		if ($eventOccurrenceOnServer == ""  || $eventOccurrenceOnServer==null){
			$eventOccurrenceOnServer=0;
		}
		
		$eventOccurrenceOnServer = intval($eventOccurrenceOnServer);
		$message='Skip scheduling';
      	if($studySiteId == "") {
		$studySiteId = null;
	}
      
		if($eventRepKey==0 || $eventRepKey-$eventOccurrenceOnServer==1){
		
		
			$schedule = $client->eventSchedule($subjectID, $eventOID,
					$ocEventLocation, $eventStartDate_final, $eventStartTime, $ocEventEndDate,
					$ocEventEndTime, $studyProtocolId, $studySiteId);
		
			if ($schedule->xpath('//v1:result')[0]=='Success'){
				$message = 'New occurrence has been scheduled.';
			}
			else{
				$message = $schedule->xpath('//v1:error')[0];
			}
		}
		
		echo "<tr><td>".$subjectID."</td><td>".$eventOccurrenceOnServer."</td><td>".$eventRepKey."</td><td>".$message."</td></tr>";
		
	}

echo "</tbody></table><br/><br/>";
/* 


 */
echo "Creating ODM XML...<br/><br/>";
//creating the ODM XML

$getMetadata_server = $client->studyGetMetadata($studyProtocolId);

$odmMetaRaw_server = $getMetadata_server->xpath('//v1:odm');

$odmMeta_server = simplexml_load_string($odmMetaRaw_server[0]);
$odmMeta_server->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);

$studyOID = $odmMeta_server->Study->attributes()->OID;

if(strlen($studySiteId)>0){
	
	foreach($odmMeta_server->Study as $studies){
		if ($studies->GlobalVariables->ProtocolName == $studyProtocolId." - ".$studySiteId){
			$siteOID = $studies->attributes()->OID;
		}
	}
	
}

if (isset($siteOID) && strlen($siteOID)>0){
	$study = $siteOID;
}
else{
	$study = $studyOID;
}

$odmXML = new ocODMclinicalData($study, 1, array());
$subjectOIDMap = array();
for ($i=1;$i<sizeof($excelData);$i++){
	for ($j=6;$j<sizeof($excelData[$i]);$j++){
		
		$oidParts = explode("##",$excelData[0][$j]);
		
		$event = $oidParts[0];
		$form = $oidParts[1];
		$group = $oidParts[2];
		$item = $oidParts[3];
		$formstatus = "complete";
		$eventOccurrence = $excelData[$i][2];
		$value = $excelData[$i][$j];
		$studysubject = $excelData[$i][0];
		
		if(!isset($subjectOIDMap[$studysubject])){
			
			$isStudySubject = $client->subjectIsStudySubject($studyProtocolId, $studySiteId, $studysubject);
			$subject = null;
			if ($isStudySubject->xpath('//v1:result')[0]=='Success'){
				$subject = (string)$isStudySubject->xpath('//v1:subjectOID')[0];
					
				$subjectOIDMap[$studysubject] = array("subjID"=>$studysubject,"oid"=>$subject);
			}
		}
		else{
			$subject = $subjectOIDMap[$studysubject]['oid'];
		}
		
		
		if (strpos($value,'::') !== FALSE){
			//this is a repeating item value
			$groupData = explode('::',$value);
			for($k=1;$k<sizeof($groupData);$k++){
				if (!empty($groupData[$k])){
		
					$odmXML->add_subject($subject, $event, $eventOccurrence, $form, $formstatus, $group,$k, $item, $groupData[$k]);
				}
			}
		
		}
		else{
			//this is not a repeating item value
			//adding the value to the odmXML object.
		
			$odmXML->add_subject($subject, $event, $eventOccurrence, $form, $formstatus, $group,1, $item, $value);
		}
		
		
		
	}
	
	
}


//create the xml file for the study
$xml = ocODMtoXML(array($odmXML));


$xmlName = "eventgod.xml";

//$xml->saveXML("savedxmls/".$xmlName);
file_put_contents('savedxmls/'.$xmlName,$xml);
file_put_contents('subjmap.txt', print_r($subjectOIDMap,true));

if(is_file('savedxmls/'.$xmlName)){
	echo '<a href="savedxmls/'.$xmlName.'">XML created successfully</a><br/><br/>';
	echo '<a href="index.php">Go back to main menu</a>';
}
else{
	echo 'There was an error while creating the XML file.';
}




?>