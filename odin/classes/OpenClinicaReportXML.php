<?php
require_once 'OpenClinicaODMFunctions.php';
require_once 'OpenClinicaSoapWebService.php';
class OpenClinicaReportXML{
	protected $xmlData;
	protected $events = array();
	protected $forms = array();
	protected $groups = array();
	protected $items = array();
	protected $subjects = array();

	
	public function __construct(){
				
	}
	
	public function load($xmlfile){
		
		$this->xmlData = simplexml_load_file($xmlfile);
		$this->xmlData->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		
		//loading events
		foreach ($this->xmlData->Study->MetaDataVersion->StudyEventDef as $eventDefs){
			$eventId = (string)$eventDefs->attributes()->OID;
			$eventName = (string)$eventDefs->attributes()->Name;
			$refs = array();
			$eventRepeating = (string)$eventDefs->attributes()->Repeating;
			foreach ($eventDefs->FormRef as $formRefs){
				$formRef = (string)$formRefs->attributes()->FormOID;
				$refs[] = $formRef;
			}
			$this->events[$eventId]=array("name"=>$eventName,"repeating"=>$eventRepeating, "refs"=>$refs);
		}
		
		//loading forms
		foreach ($this->xmlData->Study->MetaDataVersion->FormDef as $formDefs){
			$formId = (string)$formDefs->attributes()->OID;
			$formName = (string)$formDefs->attributes()->Name;
			$refs = array();
			foreach ($formDefs->ItemGroupRef as $igRefs){
				$igRef = (string)$igRefs->attributes()->ItemGroupOID;
				$refs[] = $igRef;
			}
			$this->forms[$formId]= array ("name"=>$formName,"refs"=>$refs);
		}
		
		//loading groups
		foreach ($this->xmlData->Study->MetaDataVersion->ItemGroupDef as $igDefs){
			$igId = (string)$igDefs->attributes()->OID;
			$igName = (string)$igDefs->attributes()->Name;
			$refs = array();
			foreach ($igDefs->ItemRef as $iRefs){
				$iRef = (string)$iRefs->attributes()->ItemOID;
				$refs[] = $iRef;
			}
			$this->groups[$igId]= array ("name"=>$igName,"refs"=>$refs);
		}
		
		//loading items
		foreach ($this->xmlData->Study->MetaDataVersion->ItemDef as $iDefs){
			$iId = (string)$iDefs->attributes()->OID;
			$iName = (string)$iDefs->attributes()->Name;
			$namespaces = $iDefs->getNameSpaces(true);
			$OpenClinica = $iDefs->children($namespaces['OpenClinica']);
			$fOID = array();
			foreach ($OpenClinica as $oc){
				$subelement = $oc->children($namespaces['OpenClinica']);
				foreach ($subelement as $sube){
					$subattr = $sube->attributes();
					$fOID[] = (string)$subattr['FormOID'];
				}
			}
		
			$this->items[$iId]= array ("name"=>$iName,"foid"=>$fOID);
		}
		
		//loading subjects

		
		
	}
	
	public function getEvents(){
		return $this->events;
	}
	
	
	public function createXMLDiff($xmlfile){
		$datacomposition = array();
		
		//load values from previous xml
		foreach($this->xmlData->ClinicalData as $ClinicalDataNode){
			$studyOID = (string)$ClinicalDataNode->attributes()->StudyOID;
			
			foreach($ClinicalDataNode->SubjectData as $SubjectDataNode){
				$subjectOID = (string)$SubjectDataNode->attributes()->SubjectKey;
					
				foreach($SubjectDataNode->StudyEventData as $StudyEventDataNode){
					$eventOID = (string)$StudyEventDataNode->attributes()->StudyEventOID;
					$eventOccurrence = $StudyEventDataNode->attributes()->StudyEventRepeatKey;
			
					foreach($StudyEventDataNode->FormData as $FormDataNode){
						$formOID = (string)$FormDataNode->attributes()->FormOID;
							
						foreach($FormDataNode->ItemGroupData as $ItemGroupDataNode){
							$groupOID = (string)$ItemGroupDataNode->attributes()->ItemGroupOID;
							$groupRepeatKey = $ItemGroupDataNode->attributes()->ItemGroupRepeatKey;
			
							foreach($ItemGroupDataNode->ItemData as $ItemDataNode){
								$itemOID = (string)$ItemDataNode->attributes()->ItemOID;
								$itemValue = (string)$ItemDataNode->attributes()->Value;
									
								$composition = $studyOID."::".$subjectOID."::".$eventOID."::".$eventOccurrence."::".$formOID.
								"::".$groupOID."::".$groupRepeatKey."::".$itemOID;
								
								$datacomposition[$composition] = $itemValue;
							}
						}
					}
				}
			}
		}

		//reading the newer XML
		
		$newerXML = simplexml_load_file($xmlfile);
		$newerXML->registerXPathNamespace('odm', OpenClinicaSoapWebService::NS_ODM);
		
		$odmFinal = array();

		echo '<table><thead><tr><td>Item</td><td>'.$_SESSION['xmlFile'].'</td><td>'.$_SESSION['xmlFile2'].'</td></tr></thead><tbody>';
		
		foreach($newerXML->ClinicalData as $ClinicalDataNode){
			$studyOID = (string)$ClinicalDataNode->attributes()->StudyOID;
			$odmXML = new ocODMclinicalData($studyOID, 1, array());
			
			foreach($ClinicalDataNode->SubjectData as $SubjectDataNode){
				$subjectOID = (string)$SubjectDataNode->attributes()->SubjectKey;
					
				foreach($SubjectDataNode->StudyEventData as $StudyEventDataNode){
					$eventOID = (string)$StudyEventDataNode->attributes()->StudyEventOID;
					$eventOccurrence = $StudyEventDataNode->attributes()->StudyEventRepeatKey;
						
					foreach($StudyEventDataNode->FormData as $FormDataNode){
						$formOID = (string)$FormDataNode->attributes()->FormOID;
							
						foreach($FormDataNode->ItemGroupData as $ItemGroupDataNode){
							$groupOID = (string)$ItemGroupDataNode->attributes()->ItemGroupOID;
							$groupRepeatKey = $ItemGroupDataNode->attributes()->ItemGroupRepeatKey;
								
							foreach($ItemGroupDataNode->ItemData as $ItemDataNode){
								$itemOID = (string)$ItemDataNode->attributes()->ItemOID;
								$itemValue = (string)$ItemDataNode->attributes()->Value;
									
								$composition = $studyOID."::".$subjectOID."::".$eventOID."::".$eventOccurrence."::".$formOID.
								"::".$groupOID."::".$groupRepeatKey."::".$itemOID;
		
								if(isset($datacomposition[$composition])){
									if($datacomposition[$composition] != $itemValue){
										echo '<tr><td>'.$subjectOID.' '.$itemOID.'</td><td>'.$datacomposition[$composition].
										'</td><td>'.$itemValue.'</td></tr>';
											
										$odmXML->add_subject($subjectOID,$eventOID,$eventOccurrence,$formOID,'initial data entry', 
												$groupOID,$groupRepeatKey,$itemOID,$itemValue);
										
									}
								}
								
								else{
									echo '<tr><td>'.$subjectOID.' '.$itemOID.'</td><td></td><td>'.$itemValue.'</td></tr>';
										
									$odmXML->add_subject($subjectOID,$eventOID,$eventOccurrence,$formOID,'initial data entry',
											$groupOID,$groupRepeatKey,$itemOID,$itemValue);
										
								}
							}
						}
					}
				}
			}
			$odmFinal[]=$odmXML;
		}		
		
		echo '</tbody></table>';
		
		return ocODMtoXML($odmFinal);
		
		
		
		
	}
	
public function printXMLStats(){
		
		$file1Studies = 0;
		$file1Subjects = 0;
		$file1Events = 0;
		$file1Forms = 0;
		$file1Data = 0;
		

		
		
		//load values from previous xml
		foreach($this->xmlData->ClinicalData as $ClinicalDataNode){
			$file1Studies++;
				
			foreach($ClinicalDataNode->SubjectData as $SubjectDataNode){
				$file1Subjects++;
					
				foreach($SubjectDataNode->StudyEventData as $StudyEventDataNode){
					$file1Events++;
						
					foreach($StudyEventDataNode->FormData as $FormDataNode){
						$file1Forms++;
							
						foreach($FormDataNode->ItemGroupData as $ItemGroupDataNode){
								
							foreach($ItemGroupDataNode->ItemData as $ItemDataNode){
								$file1Data++;
							}
						}
					}
				}
			}
		}
		
		
		echo '<table><thead><tr><td></td><td>'.$_SESSION['xmlFile'].'</td></tr></thead>';
		echo '<tbody>';
		
		echo '<tr><td>Study nodes</td><td>'.$file1Studies.'</td></tr>';

		echo '<tr><td>Subjects</td><td>'.$file1Subjects.'</td></tr>';
		
		echo '<tr><td>Events</td><td>'.$file1Events.'</td></tr>';
		
		echo '<tr><td>Forms</td><td>'.$file1Forms.'</td></tr>';
		
		echo '<tr><td>Data points</td><td>'.$file1Data.'</td></tr>';
		
		echo '</tbody></table>';
	}
	
}


?>