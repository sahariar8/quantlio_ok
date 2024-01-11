<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use DOMDocument;
use Config;
use App\Models\IcdCode;
use App\Models\TestDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use URL;


class PDFController extends Controller
{
    public $uuids = []; 
    public $singleUuid = '';
    public function getTGT()
    {
        try{
            $response = Http::asForm()->post(
                Config::get('nih.utsApiKey'), [
                'apikey' =>  config('nih.apikey'),
                ]
            );
            $ticketGeneratedURL = $response->getBody()->getContents();
            $dom = new DOMDocument();
            // Parse the HTML
            // The @ before the method call suppresses any warnings that
            // loadHTML might throw because of invalid HTML in the page.
            @$dom->loadHTML($ticketGeneratedURL);
    
            // Iterate over all the <form> tags
            foreach($dom->getElementsByTagName('form') as $input) {
                // Show the attribute action
                $ticketURL = $input->getAttribute('action');
            }
            return $ticketURL;
            
        }catch (\Illuminate\Http\Client\ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    public function getServiceTicket()
    {
        try{
            $ticketGeneratedURL = $this->getTGT();
            $response = Http::asForm()->post(
                $ticketGeneratedURL, [
                'service' => Config::get('nih.umlUrl'),
                ]
            );
            $serviceTicket = $response->getBody()->getContents();
            return $serviceTicket;
        }catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    public function getMeshCode($icd)
    {
        try{
            $serviceTicket =  $this->getServiceTicket();
            $response = Http::get(Config::get('nih.crosswalk').$icd.'?targetSource=MSH&ticket='.$serviceTicket);
            $content = $response->getBody()->getContents();
            $responseData = json_decode($content, true);
            return $responseData;
        }
        catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }


    public function getMetabolite($medicationUids){
        $metabolitesIdsArray = array();
        $metaMedicineIds = array();

        foreach($medicationUids as $key => $medications_uuid){
            $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medications_uuid);
            $responseData = $medications_name->getBody()->getContents();
            $resultSet = json_decode($responseData, true);
            $metabolitesIds = $resultSet['results'][0]['metabolites'];

            foreach($metabolitesIdsArray as $key => $values){

                if(is_array($values) && !empty($values)){
                    $metaMedicineIds = $this->getMetabolite($values);
                    $metaMedicineIds = ( !empty($metaMedicineIds) ) ? $metaMedicineIds : [];
                    if (!empty($metaMedicineIds)) {
                        $metaMedicineIds = array_merge($metabolitesIdsArray, $metaMedicineIds);
                    }    
                }
            }
        }
    }
    public function generatePDF()
    {
        $orderCode = 'NE22-0007351';
        $response = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareOrders').$orderCode);
        $responseData = $response->getBody()->getContents();
        $dataSet = json_decode($responseData, true);
        
        if(!empty($dataSet['results'][0])){
            $icdCodeArray = $dataSet['results'][0]['icd10_codes'];
            $receivedDate = $dataSet['results'][0]['received_date'];
            $accountName = $dataSet['results'][0]['account']['name'];
            $inHouseLabLocations = $dataSet['results'][0]['in_house_lab_locations'][0]['name'];
            $providerFirstName = $dataSet['results'][0]['provider']['user']['first_name'];
            $providerLastName = $dataSet['results'][0]['provider']['user']['last_name'];
            $patientFirstName = $dataSet['results'][0]['patient']['user']['first_name'];
            $patientLastName = $dataSet['results'][0]['patient']['user']['last_name'];
            $patientGender = $dataSet['results'][0]['patient']['gender'];
            $patientDOB = $dataSet['results'][0]['patient']['birth_date'];
            $patientPhone = $dataSet['results'][0]['patient']['phone_number'];
            $accession = $dataSet['results'][0]['accession_number'];
            $sampleName = $dataSet['results'][0]['test_panels'][0]['samples'][0]['clia_sample_type']['name'];
            $collected = $dataSet['results'][0]['test_panels'][0]['samples'][0]['collection_date'];
            $reported = date('Y-m-d', time());
            $testPanel  = $dataSet['results'][0]['test_panels'];
            $comments =  $dataSet['results'][0]['test_panels'][0]['samples'][0]['comments'];

            // Order Test Results based on order code
            $orderTestResults = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareTestResults').$orderCode);
            $responseData = $orderTestResults->getBody()->getContents();
            $resultData = json_decode($responseData, true);
            $rawResultData = $resultData['results'][0]['tests'];
            
            // Test Information from Order API
            foreach($testPanel as $keyValue => $resultValue){
                $testInformations[] = $resultValue['test_panel_type']['name'];
                if(is_array($testInformations)){
                    $testInformation = implode(' , ', $testInformations);
                }
            }
            // Prescribed Medications based on order code
            $medicationUids = $dataSet['results'][0]['medication_uuids']; 

            $date = date_create($patientDOB);
            $reportedDate = date_create($reported);
            $collectedDate = date_create($collected);
            $prescribedWithoutMetabolites = array();
            
            $this->fetch_all_uuids($medicationUids);
            $finalArray = [];
           
            // Prescribed medications with metabolites of metabolites
            foreach ($this->uuids as $uuid) { 
                $count = 0;
                $prescribedUuid = '';
                
                if ( str_contains($uuid, '++') ) {
                    $explodedUuid = explode('++', $uuid);
                    $prescribedUuid = $explodedUuid[0];
                    $uuid = $explodedUuid[1]; 

                } else {
                    $this->singleUuid = $uuid; 
                    $prescribedUuid = $uuid; // prescribed medication uuid
                }
                
                $name = $this->fetchNameFromUuid($uuid);
                $rawResult = $this->fetchOrderTestResults($orderCode);
            
                if ($prescribedUuid == $uuid) {
                    foreach($rawResult as $key => $arrayValue){
                        if($name == $arrayValue['test_type']){
                            $finalArray[$this->singleUuid][$name] = $arrayValue['result'];
                        }
                    }
                }else{
                    foreach($rawResult as $key => $arrayValue){
                        if($name == $arrayValue['test_type']){
                            $finalArray[$this->singleUuid][$name] = $arrayValue['result'];
                        }
                    }
                } 
            }

        if(!empty($medicationUids) && is_array($medicationUids))
        {
            $medicineNamesArray = array();
            $prescribedWithMetabolites = array();
            $metaboliteUidsArray = array();

        foreach($medicationUids as $key => $medications_uuid){
            $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medications_uuid);
            $responseData = $medications_name->getBody()->getContents();
            $resultSet = json_decode($responseData, true);
            $prescribedMedsArray[] = $resultSet['results'];
        }

        foreach($prescribedMedsArray as $key => $value){
            $medicineName = $value[0]['name'];
            array_push($medicineNamesArray,$medicineName);
            $commonNameArray = $value[0]['common_name'];
            $commonNames = implode(' , ', $commonNameArray);
            $medNamewithCommonNames[] = $medicineName . " ( " .$commonNames . " ) ";

            if(!empty($value[0]['metabolites'])){
                $newValue = $this->getMetabolite($medicationUids);

                foreach($metaboliteUidsArray as $key => $value){
                   if(is_array($value) && !empty($value)){
                    $metabolitesData = array();
                        foreach($value as $key => $id){
                            $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$id);
                            $responseData = $metaboliteName->getBody()->getContents();
                            $resultSet = json_decode($responseData, true);
                            array_push($metabolitesData,$resultSet['results']);
                        }
                        $metaboliteNames = array();
                        foreach($metabolitesData as $key => $values){
                            foreach($values as $targetKey => $targetValue){
                                $metaboliteName = $targetValue['name'];
                                array_push($metaboliteNames,$metaboliteName);
                            }
                        }
                        $prescribedWithMetabolites[$medicineName] = $metaboliteNames;
                    }
                }

            }else{
                $drugWithoutMetabolites = $value[0]['name'];
                $prescribedWithoutMetabolites[$drugWithoutMetabolites] = [];
            }
            $prescribedAndWithMetabolites = array_merge($prescribedWithMetabolites,$prescribedWithoutMetabolites);
        }
        $medications = implode(' , ', $medNamewithCommonNames);
        }
        
       
        //Filter prescribed and their metabolites to check if they are detected or not 
       
      
              // Array containing the list of ICD codes
        if(!empty($icdCodeArray)){
            foreach($icdCodeArray as $keys => $icd){
                $icdCodes[] = $icd['full_code'];
            }
            $meshCodeArray = array();
            $namesFromUml = array();
            $icdVariables = array();
            $resultArray = array();
            $umlMeshResult = array();
            $newResult = array();
            $icdCodeValueWithNames = array();
            if(!empty($icdCodes)){
              
                foreach($icdCodes as $key => $value){
                    $meshCodeData = $this->getMeshCode($value);
                    if(array_key_exists('error', $meshCodeData)){
                        Log::channel('error')->error('Description not found for '.$value); 
                        $icdVariable = str_replace( ".", '', $value);
                        array_push($icdVariables,$icdVariable);
                        foreach($icdVariables as $key => $icdValue){
                            $query    = IcdCode::where('icd','LIKE','%'.$icdValue.'%')->first();
                        } 
                        if(!empty($query->description)){
                            $icdDescription = $query->description;
                            $icdCodeValueWithName = $value ." " . $icdDescription;
                            $icdCodeValueWithNames[] = $icdCodeValueWithName;
                        }else{
                            $icdCodeValueWithNames[] = $value;
                        }
                    }else{
                    array_push($meshCodeArray,$meshCodeData);  // Array containing MeSH code information from UMLs 
                    $condition = true;  
                            foreach($meshCodeArray as $key => $values){
                                $arr = array();
                                $arr[] = $values['result'];
                            }
                                foreach($arr as $res){
                                    if($condition == true){
                                        if(!empty($res)){
                                            foreach($res as $key => $nameValue){
                                                // array_push($namesFromUml,$nameValue['name']);
                                                $namesFromUmls[$value][] = $nameValue['name'];
                                                foreach($namesFromUmls as $icdCode => $namesFromUml){
                                                    $combinedName = implode(",",$namesFromUml);
                                                    $icdCodeValueWithName = $icdCode ." " . $combinedName;
                                                    // array_push($icdCodeValueWithNames,$icdCodeValueWithName);
                                                    // $icdCodeValueWithNames[] = $icdCodeValueWithName;
                                                    $umlMeshResult[$value] = $icdCodeValueWithName;
                                                }
                                               
                                                
                                            }
                                            $icdCodeValueWithNames[] = $icdCodeValueWithName;
                                        }
                                    }
                                    $condition = false;
                                }

                                // if(!empty($values['result'])){
                                    
                                //     foreach($values['result'] as $nameKey => $nameValue){
                                //         array_push($namesFromUml,$nameValue['name']);
                                //     }
                                //     $combinedName = implode(",",$namesFromUml);
                                //     $icdCodeValueWithName = $value ." " . $combinedName;
                                //     $icdCodeValueWithNames[] = $icdCodeValueWithName;
                                //     $umlMeshResult[$value] = $icdCodeValueWithName;
                                
                                // }else{
                                //     $newResult[$value] = $values;
                                // }
                            
                        
                        
                    if(!array_key_exists($value,$umlMeshResult)){
                        $icdVariable = str_replace( ".", '', $value);
                        array_push($icdVariables,$icdVariable);
                        foreach($icdVariables as $key => $icdValue){
                            $query    = IcdCode::where('icd','LIKE','%'.$icdValue.'%')->first();
                        } 
                        if(!empty($query->description)){
                            $icdDescription = $query->description;
                            $icdCodeValueWithName = $value ." " . $icdDescription;
                            $icdCodeValueWithNames[] = $icdCodeValueWithName;
                        }else{
                            $icdCodeValueWithNames[] = $value;
                        }   
                    } 
                    }  
                }
                }
               
                if(!empty($icdCodeValueWithNames)){
                    $icdCodeValues = implode(",\n", $icdCodeValueWithNames);
                }else{
                    $icdCodeValues = [];
                }
            }else{
                Log::channel('error')->error('ICD codes not available');  
            }
          
            $tests = DB::table('test_details')->get();
            $testTypeArray = ['pH','Specific Gravity','Urine Creatinine'];
            $analyteInformations = array();
            $eiaInformations = array();
            $detectedMedicines = array();
            $notPrescribedDetected = array();
            $notDetectedMedicines = array();
            $notDetectednotPrescribed = array();
            $prescribedNotDetected = array();
            $prescribedDetected = array();
            $notPrescribedDetectedSorted = array();
            foreach($rawResultData as $key => $value){

                if ( in_array($value['test_type'],$testTypeArray)){
                    if($value['test_type'] == 'pH'){
                        $quantitativeResults = $value['result']['result_quantitative'];
                        $quantitativeResult = round($quantitativeResults, 2);
                    }elseif($value['test_type'] == 'Specific Gravity'){
                        $quantitativeResultSpecificGravitys = $value['result']['result_quantitative'];
                        $quantitativeResultSpecificGravity = round($quantitativeResultSpecificGravitys, 3);
                    }elseif($value['test_type'] == 'Urine Creatinine'){
                        $quantitativeResultCreatinines = $value['result']['result_quantitative'];
                        $quantitativeResultCreatinine = round($quantitativeResultCreatinines, 2);
                    }else{
                        $quantitativeResult = '';
                        $quantitativeResultSpecificGravity = '';
                        $quantitativeResultCreatinine = '';
                    }
                }else{

                    if($value['result']['result_qualitative'] == 'Negative' || $value['result']['result_qualitative'] == 'Positive'){
                        $testTypeSpecimen = $value['test_type'];
                        $eiaInformations[$testTypeSpecimen] = $value['result'];
                    }
                    elseif($value['result']['result_qualitative'] == 'Not Detected' || $value['result']['result_qualitative'] == 'Detected'){
                        $testType = $value['test_type'];
                        $analyteInformations[$testType] = $value['result'];
                        if($value['result']['result_qualitative'] == 'Detected'){
                            $detectedMedicines[$testType] = $value['result'];
                        }else{
                            $notDetectedMedicines[$testType] = $value['result'];
                        }
                    }else{
                        Log::channel('error')->error('PDF report is pending');  
                    }
                }  
            }
        
        foreach($notDetectedMedicines as $notDetectedName => $values){
            if(!in_array($notDetectedName,$medicineNamesArray)){
                $notDetectednotPrescribed[$notDetectedName] = $values;  
            }else{
                $prescribedNotDetected[$notDetectedName] = $values;  
            }
        } 
        $datas = array();
        
        foreach($finalArray as $uuid => $data){
            array_push($datas,$data);
            $prescribedMedicationName = $this->fetchNameFromUuid($uuid);
            if(array_key_exists($prescribedMedicationName,$data)){
               foreach($data as $name => $result){
                   if(!array_key_exists($name,$prescribedNotDetected)){
                        
                        foreach($tests as $resultValue){
                            if($resultValue->dendi_test_name == $name){
                                $class  = $resultValue->class;
                                $result['class'] = $class;  
                            }
                        }
                        $prescribedDetected[$name] = $result;
                   }
               }
            }else{
                $prescribedMedicationName = $this->fetchNameFromUuid($uuid);
                foreach($data as $name => $result){
                    if(!array_key_exists($name,$prescribedNotDetected)){
                        
                        foreach($tests as $resultValue){
                            if($resultValue->dendi_test_name == $name){
                                $class  = $resultValue->class;
                                $result['class'] = $class;  
                            }
                        }
                        $prescribedDetected[$name] = $result; 
                    }
                }
            }
        }
       
        /*---------------------------*/

        $groupPrescribedDetected = array();

        // foreach($prescribedDetected as $key => $prescribedDetectedResult){
        //     if(!empty($prescribedDetectedResult['class'])){
        //         $counts = array_count_values(
        //             array_column($prescribedDetectedResult, 'class')
        //         );
        //     }
        // }

        

        // foreach($prescribedDetected as $key => $prescribedDetectedResult) {
        //     if(isset($groupNotPrescribedDetected[$prescribedDetectedResult['class']])) {
        //         $groupNotPrescribedDetected[$prescribedDetectedResult['class']]++;
        //     } else {
        //         $groupNotPrescribedDetected[$prescribedDetectedResult['class']] = 1;
        //     }
        // }

        foreach($prescribedDetected as $key => $prescribedDetectedResult){
            if(isset($prescribedDetectedResult['class']) && !empty($prescribedDetectedResult['class'])){
                $class = $prescribedDetectedResult['class'];
                $groupPrescribedDetected[$class][$key] = $prescribedDetectedResult;
            }
        }
        $sortedPrescribedDetected = array();
        foreach($groupPrescribedDetected as $keys => $valuesSorted){
            foreach($valuesSorted as $key => $sortedListing){
                // array_push($sortedPrescribedDetected,$sortedListing);
                $sortedPrescribedDetected[$key] = $sortedListing;
            }
        }
    
    
/*---------------------------------*/
        
        
        foreach($detectedMedicines as $index => $value){
            if(!in_array($index,$medicineNamesArray) && !array_key_exists($index,$prescribedDetected)){
                foreach($tests as $resultValue){
                    if($resultValue->dendi_test_name == $index){
                        $descriptionTest  = $resultValue->description;
                        $value['description'] = $descriptionTest;
                    }
                }
                $notPrescribedDetected[$index] = $value;
            }
        }
        /*-------------------------------*/

        if (!empty($notPrescribedDetected)) {
            $notPrescribedDetected = $this->insertValueAtPosition($notPrescribedDetected);
        }
        
        // $noTestFoundArr = [];
        // $newArr = [];

        // foreach ($notPrescribedDetected as $testName => $value) {
        //     if(!empty($value['description']) && isset($value['description'])){
        //         $descriptionValue = strtolower($value['description']);
            
        //         $descriptionInArray = explode(' ', $descriptionValue);
    
        //         $result = array_values(array_uintersect($testNameArr, $descriptionInArray, 'strcasecmp')); // array_values used due to index getting changed from intersect
        //         // array_uintersect to ignore case sensitivity
                
        //         if ( !empty($result) ) {
                    
        //             $newArr[$result[0]] = $result[0];
        //             $noTestFoundArr[$testName] = $result[0];
                    
        //         } else if ( !in_array($testName, $newArr) ) {
    
        //             $noTestFoundArr[$testName] = !empty($result[0]) ? $result[0] : $testName;
        //         }
        //     }
        // }
        // $newArrWithNumericIndex = array_values($newArr);
        // echo "<pre>newArrWithNumericIndex ........"; print_r($newArrWithNumericIndex);
        // echo "<pre> noTestFoundArr.........."; print_r($noTestFoundArr);
        // echo "<pre> newArr.........."; print_r($newArr);

        // foreach($noTestFoundArr as $noTestFoundKey => $noTestFoundVal) {
        //     if ( in_array($noTestFoundVal, $newArr) ) {
        //         $key = array_search($noTestFoundVal, $newArrWithNumericIndex);
        //         echo ($key+1).'......';
        //         array_splice( $newArr, $key+1, 0,  $noTestFoundKey);
        //         echo "<pre> newArr1111.........."; print_r($newArr);
        //     } else {
        //         // array_push($newArr, $noTestFoundKey);
        //         // echo "<pre> newArr0000.........."; print_r($newArr);
        //     }
        // }

        // echo "<pre> newArr2222.........."; print_r($newArr); die();

        // foreach($newArr as $value){
        //     $newPrecribeArr[$value] = $notPrescribedDetected[$value];
        // }

        // echo "<pre>"; print_r($newArr);
        // echo "<pre>"; print_r($notPrescribedDetected);
        // echo "<pre>"; print_r($newPrecribeArr); die();
        // dd($newArr);
       
        /*--------------------------------------------------*/

        $commentsArray = array();
        foreach($comments as $k => $v){
            array_push($commentsArray,$v['comment']);
        }
        $CIdescriptions = array();
        foreach($commentsArray as $key => $value){
            $newDesc= explode(':',$value,2);
            array_push($CIdescriptions,$newDesc);
        }
        // $contraindicationNotTested = array();
        // foreach($medicineNamesArray as $key => $value){
        //     foreach($CIdescriptions as $name => $description){
        //         if($value == $description[0] && !array_key_exists($description[0],$prescribedDetected) && !array_key_exists($description[0],$notPrescribedDetected) && !array_key_exists($description[0],$prescribedNotDetected)){
        //             $contraindicationNotTested[$description[0]] = $description[1];
        //         }
        //     }
        // }
      
        $contraindicationNotTested = array();
        $concatDescription = array();
        
        $allSectionTest = array_keys(array_merge($prescribedNotDetected, $prescribedDetected, $notPrescribedDetected));
        $singleDesc = array();
        foreach($medicineNamesArray as $key => $value){
            
            foreach($CIdescriptions as $key1 => $list){
                $medName = $list[0];
                
                if($value == $medName && !array_key_exists($medName,$prescribedDetected) && !array_key_exists($medName,$notPrescribedDetected) && !array_key_exists($medName,$prescribedNotDetected)){
                    if( !isset($concatDescription[$medName]) ){
                        $concatDescription[$medName] = array($medName,'');
                    }
                    $found = false;
                    foreach ($allSectionTest as $testName) {
                        $testName = " " . $testName;
                        $medTestName = trim($testName);
                        
                        if ((str_contains(strtolower($list[1]), strtolower($testName))) == true) { 
                            $found = true;
                            $singleDesc[$medTestName][] = $list[1];
                        }
                    }

                    if ($found == false) {
                        $concatDescription[$medName][1] .= (empty($concatDescription[$medName][1]) ? '' : " ")."\n" . $list[1];
                    }
                }
            }
            $concatDescription = array_values($concatDescription); 
        }
        $metaboliteArray = array();
        foreach($finalArray as $uuid => $data){
            $prescribedMedicationName = $this->fetchNameFromUuid($uuid);
            foreach($concatDescription as $keys => $values){
                if($prescribedMedicationName == $values[0]){
                    foreach($data as $name => $result){
                        array_push($metaboliteArray,$name);
                        array_push($metaboliteArray,$values[1]);
                        $CIdescriptions[] = $metaboliteArray;
                    }
                }
            }   
        }
        
        foreach($concatDescription as $keys => $notTestedResult){
            if(!in_array($notTestedResult[1],$metaboliteArray)){
                $contraindicationNotTested[$notTestedResult[0]] = $notTestedResult[1];
            }
        }

        $labLocation = DB::table('lab_locations')->where('location', 'Newstar Medical Laboratories - Atlanta')->first();
        $labLocationTempe = DB::table('lab_locations')->where('location', 'Newstar Medical Laboratories - Tempe')->first();
        $collection = collect($notDetectednotPrescribed);
        
        $chunks = $collection->chunk(15);
        $chunks->all();
        $orders = DB::table('lis_orders_details')->get();

        $data = [
            'code' => $orderCode,
            'patientName' => $patientFirstName . ' ' . $patientLastName,
            'patientDOB' => date_format($date,"m/d/Y"),
            'patientGender' => $patientGender,
            'patientPhone' => $patientPhone,
            'account' => $accountName,
            'provider' => $providerFirstName . ' ' . $providerLastName,
            'accession' => $accession,
            'sample_type' => $sampleName,
            'reported' => date_format($reportedDate,"m/d/Y"),
            'collected' => date_format($collectedDate,"m/d/Y"),
            'phone' => $patientPhone,
            'in_house_lab_location' => $inHouseLabLocations,
            'testInformation' => $testInformation,
            'icdCode' => $icdCodeValues,
            'medications' => $medications,
            'quantitativeResult' => $quantitativeResult,
            'quantitativeResultSpecificGravity' => $quantitativeResultSpecificGravity,
            'quantitativeResultCreatinine' => $quantitativeResultCreatinine,
            'analyteInformations' => $analyteInformations,
            'eiaInformations' => $eiaInformations,
            'medicineNamesArray' => $medicineNamesArray,
            'notPrescribedDetected' => $notPrescribedDetected,
            'notDetectednotPrescribed' => $chunks,
            'prescribedNotDetected' => $prescribedNotDetected,
            'prescribedDetected' => $prescribedDetected,
            'CIdescriptions' => $CIdescriptions,
            'tests' => $tests,
            'receivedDate' => $receivedDate,
            'labLocation' => $labLocation,
            'contraindicationNotTested' => $contraindicationNotTested,
            'datas' => $datas,
            'orders' => $orders,
            'singleDesc' => $singleDesc,
        ]; 
        $pdf = PDF::loadView('generatePDF', $data)->setPaper('a1', 'portrait');        
           
        $pdf->getDomPDF()->set_option("enable_php", true);
        // $fileName = "generate-$orderCode.pdf";
        // file_put_contents("pdf/$fileName" , $pdf->output());

        // $fileUrl = URL::to('/') . "/pdf/$fileName";
        
        // Storage::disk('local')->put('public/pdf/generateNewPDF.pdf', $pdf->output());
        // $file_path = \Storage::url($filename);
        // $pdfReport = asset($file_path);

        // $record = $this->postReportToDendi($orderCode, $fileUrl);
        
        // return $pdf->download('generatePDF.pdf');
        // return $fileUrl;
        return view('generatePDF', $data);
        // }
    }
}
public function groupingClasses($prescribedDetected,$value) {

    
    return array_merge($sortedOrderNotPrescribedDetected, $notPrescribedDetected);
}

    public function insertValueAtPosition($notPrescribedDetected) {

        $listingNotPrescribedDetected = array();

        foreach($notPrescribedDetected as $key => $notPrescribedDetectedResult){
            if(isset($notPrescribedDetectedResult['description']) && !empty($notPrescribedDetectedResult['description'])){
                foreach($notPrescribedDetected as $keyName => $resultantValue) {
                    if(strpos($notPrescribedDetectedResult['description'], $keyName)) {
                        $listingNotPrescribedDetected[$keyName][$key] = $notPrescribedDetectedResult;
                    }
                }
            }
        }
         
        foreach($listingNotPrescribedDetected as $key => $value){
            $listingNotPrescribedDetected[$key][$key] = $notPrescribedDetected[$key];
        }


        $sortedOrderNotPrescribedDetected = array();
        foreach($listingNotPrescribedDetected as $key1 => $noPrescribedDetect){
            if(is_array($noPrescribedDetect)) {
                $rev = array_reverse($noPrescribedDetect);
                foreach($rev as $key2 => $setval){
                    $sortedOrderNotPrescribedDetected[$key2] = $setval;
                }
            }
        }

        if (!empty($sortedOrderNotPrescribedDetected)) {
            foreach ($sortedOrderNotPrescribedDetected as $key1 => $sortedOrderNotPrescribedDetectedPrescription) {
                unset($notPrescribedDetected[$key1]);
            }
        }

        return array_merge($sortedOrderNotPrescribedDetected, $notPrescribedDetected);
    }
  

    public function postReportToDendi($orderCode, $pdfReport)
    {
        $response = Http::withHeaders(['Authorization' => config('nih.token')])->post('https://newstar.dendisoftware.com/api/v1/reports', [
            'external_url' => $pdfReport,
            'code' => $orderCode,
        ]);
        $content = $response->getBody()->getContents();
        $response_data = json_decode($content, true);
        return $response_data;
    }

    public function fetch_all_uuids ($medicationUuids = []) {
        foreach ($medicationUuids as $medicationUuid) {
            array_push($this->uuids, $medicationUuid);
            $this->fetch_metabolite_uuid_recursive($medicationUuid);
        }
    }
    public function fetchNameFromUuid ($medicationUuid = '') {

        $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medicationUuid);
        $responseData = $metaboliteName->getBody()->getContents();
        $resultSet = json_decode($responseData, true);
        $uuidName = $resultSet['results'][0]['name'];

        return $uuidName;
    }
    public function fetchOrderTestResults ($orderCode = '') {

        $orderTestResults = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareTestResults').$orderCode);
        $responseData = $orderTestResults->getBody()->getContents();
        $resultData = json_decode($responseData, true);
        $rawResultData = $resultData['results'][0]['tests'];

        // $testTypeName = $rawResultData[0]['test_type'];
        // if($testTypeName == $name){
        //     return $testTypeName;
        // }
        return $rawResultData;
    }
    public function fetch_metabolite_uuid_recursive ($medicationUuid = '') {
        $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medicationUuid);
        $responseData = $medications_name->getBody()->getContents();
        $resultSet = json_decode($responseData, true);

        $metabolitesIds = $resultSet['results'][0]['metabolites'];

        if ( !empty($metabolitesIds) ) {
            foreach ($metabolitesIds as $metaboliteUuid) {

                // // push in $this->uuids array with (prescribedUuid ++ metaboliteUuid)
                 array_push($this->uuids, $medicationUuid . '++' . $metaboliteUuid);

                $this->fetch_metabolite_uuid_recursive($metaboliteUuid);
            }
        }
    }
}
