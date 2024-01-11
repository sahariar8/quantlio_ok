<?php

// app/Services/YourService.php

namespace App\Services;

use App\Services\MetaboliteService;
use App\Services\RxcuiService;
use Illuminate\Support\Str;

use Illuminate\Support\Collection;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use DOMDocument;
use Exception;
use Response;
use Config;
use App\Models\OrderHistory;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Handle;
use Illuminate\Http\Client\ConnectionException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\WebhookPayload;
use App\Models\OrderDetail;
use App\Models\IcdCode;
use App\Models\LabLocation;
use App\Models\DrugInteraction;
use Illuminate\Support\Facades\Storage;
use URL;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use App\Models\OrderTestClassSection;

use function GuzzleHttp\Promise\exception_for;
use function PHPUnit\Framework\isEmpty;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Replace;
use App\Models\OrderCodeQueue;

class PDFGenerationService
{

    protected RxcuiService $rxcuiService;
    protected MetaboliteService $metaboliteService;

    public $showLog = 0;

    public function __construct()
    {
        $this->rxcuiService = new RxcuiService();
        $this->metaboliteService = new MetaboliteService();
    }

    public function getDataSetFrom_StratusApi($orderCode)
    {
        $response = Http::accept('application/json')
            ->withHeaders(['Authorization' => 'Basic c2FsdnVzX3N0cmF0dXNkeF8xMTo4NmM3ZDE2NC03YWQz'])
            ->get("https://testapi.stratusdx.net/interface/result/" . $orderCode);
        $response_data = $response->getBody()->getContents();
        $dataSet = json_decode($response_data, true);

        return $dataSet;
    }

    public function getDataSetFrom_mock($orderCode)
    {
        $filePath = storage_path('./../MockData/mock.json');
        $jsonData = File::get($filePath);
        $dataSet = json_decode($jsonData, true);

        return $dataSet;
    }

    public function dump_array($label, $arrayVal, $priority = 0)
    {
        if ($this->showLog == 1 || $priority == 1) {
            $currentDateTime = now();
            dump($currentDateTime->format('Y-m-d H:i:s'));

            dump($label);
            if (!blank($arrayVal) && !empty($arrayVal) && filled($arrayVal) &&  is_array($arrayVal) && count($arrayVal) > 0) {
                dump($arrayVal);
            } else if ($arrayVal instanceof Collection) {
                dump($arrayVal->all());
            } else {
                dump($arrayVal);
            }
        }
    }

    public function Log_scheduler_info($messageToPrint)
    { 
        if (env('LOG_SCHEDULE_INFO') == 'yes') 
        {
            // Log::channel('scheduler_info')->info(
            //     $_pdfGenerationService->Log_scheduler_info(

            Log::channel('scheduler_info')->info($messageToPrint);
        }
    }

    public function dump_currentTime()
    {
        $currentDateTime2 = now();
        dump($currentDateTime2);
    }

    public function getPdfDataForStratus($orderCode, $show_log = 0)
    {
        // Will print log or not
        $this->showLog = $show_log;

        $response = [
            'content' => null,
            'message' => "",
            'status' => "",
        ];

        try {
            ini_set('max_execution_time', '0');
            $uuids = [];

            // $this->Log_scheduler_info(
            $this->Log_scheduler_info('-> pdf generation - called: getDataSetFrom_StratusApi');
            //$dataSet = $this->getDataSetFrom_mock($orderCode);
            $dataSet = $this->getDataSetFrom_StratusApi($orderCode);
            $this->Log_scheduler_info('-> pdf generation - response: getDataSetFrom_StratusApi : ' . json_encode($dataSet));
            $this->dump_array('dataSet', $dataSet);
            if ($dataSet["status"] == "failed") {
                Log::channel('error')->error('DataSet not found for orderCode : ' . $orderCode);

                $response = [
                    'content' => null,
                    'message' => "requested message does not exist",
                    'status' => "500",
                ];

                return $response;
            }

            if (!empty($dataSet)) {
                $icdCodeArray = $dataSet['diagnosis_codes'];
                $accountName = $dataSet['location']['name'];
                $inHouseLabLocations = $dataSet['organization']['name'];
                $providerNpi = $dataSet['provider']['npi'];
                $receivedDate = $dataSet['order']['received_datetime'];
                $providerFirstName = $dataSet['provider']['first_name'];
                $providerLastName = $dataSet['provider']['last_name'];
                $patientFirstName = $dataSet['patient']['first_name'];
                $patientLastName = $dataSet['patient']['last_name'];
                $patientGender = $dataSet['patient']['gender_code'];
                $patientDOB = $dataSet['patient']['dob'];
                $patientPhone = $dataSet['patient']['home_phone'] ?? $dataSet['patient']['mobile_phone'];
                $accession = $dataSet['order']['accession_id'];
                $sampleName = $dataSet['panels'][0]['sample_type'];
                $collected = $dataSet['order']['collected_datetime'];
                $reported = date('Y-m-d', time());
                $testPanel  = $dataSet['panels'];
                $medications = $dataSet['medications'];
                $state = $dataSet['patient']['address_state_code'];

                OrderDetail::firstOrCreate(['order_code' => $orderCode]);
                OrderHistory::create(['order_code' => $orderCode]);
                OrderHistory::where('order_code', $orderCode)
                    ->update([
                        'in_house_lab_location' => $inHouseLabLocations,
                        'medications' => $medications,
                        'account_name' => $accountName,
                        'provider_name' => $providerFirstName . ' ' . $providerLastName,
                        'patient_name' => $patientFirstName . ' ' . $patientLastName,
                        'accession' => $accession
                    ]);
                $patientAge = DB::table('order_details')
                    ->selectRaw("TIMESTAMPDIFF(YEAR, `patient_DOB`, current_date) AS age")
                    ->where('order_code', $orderCode)->get();
                $labLocations = DB::table('lab_locations')->get();

                OrderDetail::where('order_code', $orderCode)
                    ->update([
                        'state' => $state,
                        'patient_DOB' => $patientDOB,
                        'account_name' => $accountName,
                        'patient_name' => $patientFirstName . ' ' . $patientLastName,
                        'patient_gender' => $patientGender,
                        'provider_name' => $providerFirstName . ' ' . $providerLastName,
                        'provider_npi' => $providerNpi,
                        'patient_age' => $patientAge[0]->age,
                        'reported_date' => $reported
                    ]);

                foreach ($labLocations as $labLocation) {
                    if ($inHouseLabLocations == $labLocation->location) {
                        OrderDetail::where('order_code', $orderCode)
                            ->update([
                                'location_id' => $labLocation->id
                            ]);
                    }
                }

                $this->Log_scheduler_info('-> pdf generation - start for meshcode : ');

                /**
                 *  Crosswalk between ICD and MeSH code
                 *  Array containing MeSH codes
                 **/

                $meshCodeArray = array();
                $icdCodes = array();
                $icdCodeValues = array();
                $icdToMesh = array();
                $icdToMeshCodes = array();
                $icdCodeValueWithNames = array();

                // TODO: Order Test Results with Order Code throws Error
                // TODO: Test Results are coming from the Stratus API. Use that?
                // $testResults = $this->getOrderTestResults($orderCode);
                // Done Previous TODO;

                $this->dump_array("testPanel", $testPanel);

                $testResults = $this->orderTestResultsFromPanels($testPanel);

                $this->dump_array("testResults__after format", $testResults);
                $this->Log_scheduler_info('-> pdf generation - icdCodeArray: ' . json_encode($icdCodeArray));

                // Array containing the list of ICD codes
                //if(!empty($icdCodeArray)) //jafar
                if (!blank($icdCodeArray) && !empty($icdCodeArray) && filled($icdCodeArray) &&  is_array($icdCodeArray) && count($icdCodeArray) > 0) {
                    $this->dump_array("icdCodeArray", $icdCodeArray);

                    foreach ($icdCodeArray as $icd) {
                        $icdCodes[] = $icd['code'];
                    }

                    $this->dump_array("icdCodes___123", $icdCodes);

                    // if(!empty($icdCodes) && is_array($icdCodes))
                    if (!blank($icdCodes) && !empty($icdCodes) && filled($icdCodes) &&  is_array($icdCodes) && count($icdCodes) > 0) {
                        foreach ($icdCodes as $icdCode) {
                            $meshCodeData = $this->getMeshCode($icdCode);
                            //@@$this->dump_array("icdCode", $icdCode);
                            $this->dump_array("meshCodeData_icd to Mesh__from_api", $meshCodeData);

                            if (array_key_exists('error', $meshCodeData) || empty($meshCodeData['result'])) {
                                Log::channel('error')->error('MeSH code not found for ' . $icdCode);
                                $icdVariable = str_replace(".", '', $icdCode);
                                $query = IcdCode::where('icd', 'LIKE', '%' . $icdVariable . '%')->first();
                                if (!empty($query)) {
                                    $meshCode = $query->mesh; // ICD-MeSH from database
                                    $icdToMesh[] = $meshCode;
                                    if (!empty($query->description)) {
                                        $icdDescription = $query->description;
                                        $icdCodeValueWithName = $icdCode . " " . $icdDescription;
                                        $icdCodeValueWithNames[] = $icdCodeValueWithName;
                                    }
                                } else {
                                    $icdCodeValueWithNames[] = $icdCode;
                                    Log::channel('icdToMesh_notfound')->error('MeSH code not found for ' . $icdCode);
                                }

                                //@@$this->dump_array("icdToMesh___Not Found", $icdToMesh);
                                //@@$this->dump_array("icdCodeValueWithNames___Not Found", $icdCodeValueWithNames);

                            } else {
                                $meshCodeArray[] = $meshCodeData;  // Array containing MeSH code information from UMLs
                                $condition = true;
                                foreach ($meshCodeArray as $key => $mesh) {
                                    $meshArray = array();
                                    $meshArray[] = $mesh['result'];
                                    if (!empty($mesh['result'])) {
                                        $meshCode = $mesh['result'][0];
                                        $meshFromUml[] = $meshCode['ui'];
                                    }
                                }
                                foreach ($meshFromUml as $key => $meshValue) {
                                    $icdToMesh[] = $meshValue;
                                }

                                //@@$this->dump_array("icdToMesh_inner", $icdToMesh);

                                foreach ($meshArray as $resultName) {
                                    if ($condition) {
                                        if (!empty($resultName)) {
                                            foreach ($resultName as $key => $nameValue) {

                                                $namesFromUmls[$icdCode][] = $nameValue['name'];
                                                foreach ($namesFromUmls as $icdCode => $namesFromUml) {
                                                    $combinedName = implode(",", $namesFromUml);
                                                    $icdCodeValueWithName = $icdCode . " " . $combinedName;
                                                    $umlMeshResult[$icdCode] = $icdCodeValueWithName;
                                                }
                                            }
                                            $icdCodeValueWithNames[] = $icdCodeValueWithName;
                                        } else {
                                            $icdCodeValueWithNames[] = $icdCode;
                                        }
                                    }
                                    $condition = false;
                                }
                            }
                        }
                    }
                    if (!empty($icdCodeValueWithNames)) {
                        $icdCodeValues = implode(",\n", $icdCodeValueWithNames);
                    } else {
                        $icdCodeValues = [];
                    }
                    $icdToMeshCodes = implode(",", $icdToMesh);
                } else {
                    Log::channel('error')->error('ICD codes not available');
                    $this->dump_array('ICD codes not available', []);
                }

                $this->dump_array('icdToMesh____before loop', $icdToMesh);
                $this->Log_scheduler_info('-> pdf generation - icdToMesh____before loop: ' . json_encode($icdToMesh));

                // Array containing contraindication conditions (list of test names) for MeSH codes from UMLs
                $meshConditions = array();
                //if(is_array($icdToMesh) && !empty($icdToMesh)) //jafar
                if (!blank($icdToMesh) && !empty($icdToMesh) && filled($icdToMesh) &&  is_array($icdToMesh) && count($icdToMesh) > 0) {
                    $this->dump_array("icdToMesh_foreach", $icdToMesh);

                    foreach ($icdToMesh as $code) {
                        $conditionsResponseFromNihApi = $this->getConditions($code);
                        //@@$this->dump_array("icdToMesh_code: get condition", $code);
                        //@@$this->dump_array("conditionsResponseFromNihApi", $conditionsResponseFromNihApi);

                        if (isset($conditionsResponseFromNihApi['drugMemberGroup']['drugMember'])) {
                            $conditionsResponseFromNih = $conditionsResponseFromNihApi['drugMemberGroup']['drugMember'];
                            $conditionsArray = array();

                            foreach ($conditionsResponseFromNih as $list) {
                                if (isset($list['minConcept']['name'])) {
                                    $conditionsArray[] = $list['minConcept']['name'];
                                    $meshConditions[$code] = $conditionsArray;
                                }
                            }
                        }
                    }
                }

                $this->Log_scheduler_info('-> pdf generation - meshConditions: ' . json_encode($meshConditions));
                $this->dump_array("meshConditions", $meshConditions);
                //@@$this->dump_array("medications", $medications);
                $prescribedMedications = $this->medicationNamesFromMedications($medications);
                $prescribedMedications_as_prescribed = $prescribedMedications;

                $this->dump_array("prescribedMedications", $prescribedMedications);
                //  Filter the positive detected medications from Order test Results Stratus API
                // TODO: The response has field "result_flag": "Positive". We can take only those.
                // TODO: What about "" and "Normal"?

                //@@$this->dump_array("testResults__Count", []);
                //@@$this->dump_array(count($testResults), []);
                //@@$this->dump_array("testResults__abc", $testResults);

                foreach ($testResults as $result) {
                    // TODO: This needs to be changed from 'Positive' to 'Detected'
                    if ($result["result_flag"] == 'Detected') {
                        $detectedTest = strtolower($result['test_description']);
                        $prescribedMedications[] = $detectedTest;
                    }
                }

                $this->Log_scheduler_info('-> pdf generation - testResults: ' . json_encode($testResults));

                //@@$this->dump_array("prescribedMedications__2", $prescribedMedications);

                //ddd($prescribedMedications);

                OrderDetail::where('order_code', $orderCode)
                    ->update([
                        'icd_codes' => $icdCodes,
                        'prescribed_medications' => $prescribedMedications,
                        'account_name' => $accountName,
                        'provider_name' => $providerFirstName,
                        'provider_npi' => $providerNpi,
                        'order_test_result' => $testResults
                    ]);

                $medicationRxcuis = array();
                $boxedWarningData = array();
                $boxedWarningResults = array();

                $madicationNameArray =  array();
                $this->dump_array("prescribedMedications__2345", $prescribedMedications);
                $this->Log_scheduler_info('-> pdf generation - prescribedMedications: ' . json_encode($prescribedMedications));
                // Get RxCUI and Boxed Warning for the list of medications
                if (!blank($prescribedMedications) && !empty($prescribedMedications) && filled($prescribedMedications) &&  is_array($prescribedMedications) && count($prescribedMedications) > 0) {
                    // foreach ($prescribedMedications as $key => $value) {
                    //     $medicationRxcuiValue = $this->getRxcui($value);
                    //     $boxedWarningValue = $this->getBoxedWarning($value);

                    //     if(!empty($boxedWarningValue)) {
                    //         $boxedWarningResults[] = $boxedWarningValue;
                    //     } else {
                    //         Log::channel('error')->error('Boxed warning not found for '.$value);
                    //     }

                    //     if(!empty($medicationRxcuiValue)) {
                    //         $medicationRxcuis[] =  $medicationRxcuiValue;
                    //     } else {
                    //         Log::channel('error')->error('Failed to get rxcui '.$value);
                    //     }
                    // }

                    //@@$this->dump_array("prescribedMedications__Inner__2345", $prescribedMedications);
                    foreach ($prescribedMedications as $key => $value) {
                        $boxedWarningValue = $this->getBoxedWarning($value);

                        if (!empty($boxedWarningValue)) {
                            $boxedWarningResults[] = $boxedWarningValue;
                        } else {
                            Log::channel('error')->error('Boxed warning not found for ' . $value);
                        }
                    }

                    if (!blank($prescribedMedications) && !empty($prescribedMedications) && filled($prescribedMedications) &&  is_array($prescribedMedications) && count($prescribedMedications) > 0) {
                        $this->dump_array("Rxcuis__calling", $prescribedMedications);

                        $medicationWithRxcuisArray = $this->rxcuiService->getAllRxcuiCodesByMedication($prescribedMedications);
                        foreach ($prescribedMedications as $key => $prescibedMedicineName) {
                            if (!empty($medicationWithRxcuisArray[$prescibedMedicineName]["rxcuiCode"])) {
                                $medicationRxcuis[] =  $medicationWithRxcuisArray[$prescibedMedicineName]["rxcuiCode"];
                            } else {
                                Log::channel('error')->error('Failed to get rxcui ' . $value);
                            }
                        }
                    }

                    //@@$this->dump_array("prescribedMedications__890", $prescribedMedications);

                }

                $this->dump_array("boxedWarningResults", $boxedWarningResults);
                $this->dump_array("medicationRxcuis", $medicationRxcuis);
                $this->Log_scheduler_info('-> pdf generation - boxedWarningResults: ' . json_encode($boxedWarningResults));
                $this->Log_scheduler_info('-> pdf generation - medicationRxcuis: ' . json_encode($medicationRxcuis));

                //if(!empty($medicationRxcuis)) 
                //if(true) // TODO: ducktap. if no medication found, then also generate pdf.
                if (true) {
                    // Boxed Warning response specific to drug name
                    foreach ($boxedWarningResults as $bw_key => $bw_value) {
                        $arrVal = $bw_value['boxed_warning'][0];
                        $open_fda = $bw_value['openfda'];
                        if (!empty($open_fda)) {
                            $boxedWarningData[$bw_key]['substance_name'] = $bw_value['openfda']['substance_name'];
                        }
                        $boxedWarningData[$bw_key]['boxed_warning'] =  $arrVal;
                    }

                    $this->dump_array("boxedWarningData", $boxedWarningData);
                    $this->Log_scheduler_info('-> pdf generation - boxedWarningData: ' . json_encode($boxedWarningData));

                    // Filter the medicines $prescribedMedications (prescribed + positive detected medication) from calculated conditions( $meshConditions )
                    $resultArray_mesh_data = array();
                    $arrayResult_CI_Data_On_icdMesh = array();
                    $condition = true;
                    $contraindicationComments = array();
                    // $orderId = OrderDetail::select('id')->where('order_code', $orderCode)->get(); // TODO: This is unnecessary. Remove?

                    // ddd($meshConditions);

                    $this->dump_array("meshConditions__loop", $meshConditions);
                    $this->Log_scheduler_info('-> pdf generation - meshConditions: ' . json_encode($meshConditions));

                    //if(!empty($meshConditions)){
                    if (!blank($meshConditions) && !empty($meshConditions) && filled($meshConditions) &&  is_array($meshConditions) && count($meshConditions) > 0) {
                        $this->dump_array("meshConditions", $meshConditions);

                        foreach ($meshConditions as $keys => $values) {
                            foreach ($values as $key => $value) // TODO: $key is used as a key in both inner and outer loop. Change name?
                            {
                                if (in_array(strtolower($value), $prescribedMedications)) {
                                    if ($condition) {
                                        $this->dump_array("meshCodeArray", $meshCodeArray);

                                        foreach ($meshCodeArray as $key => $mesh) {
                                            $resultArray_mesh_data[] = $mesh['result'];
                                            foreach ($resultArray_mesh_data as $nkeys => $nvalues) {
                                                if (isset($nvalues[$key])) {
                                                    if ($keys == $nvalues[$key]['ui']) {
                                                        $arrayResult_CI_Data_On_icdMesh[$key]['CI_with'] = $nvalues[0]['name'];
                                                        $values = array(
                                                            'prescribed_test' => $value,
                                                            'conditions' => $nvalues[0]['name'],
                                                            'order_id' => $orderCode
                                                        );
                                                        DB::table('conditions')->insert($values);
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $condition = false;

                                    $this->dump_array("icdCodes", $icdCodes);

                                    foreach ($icdCodes as $key => $valueCode) {
                                        $meshCodeData = $this->getMeshCode($valueCode);
                                        $this->dump_array("valueCode_get Mesh code:", $valueCode);

                                        if (
                                            array_key_exists('error', $meshCodeData)
                                            || empty($meshCodeData['result'])
                                        ) {
                                            $this->dump_array("meshCodeData result:___ got Data", $meshCodeData);
                                            Log::channel('error')->error('MeSH code not found for ' . $valueCode);

                                            $icdVariable = str_replace(".", '', $valueCode);

                                            if (!empty($icdVariable)) {
                                                $query    = IcdCode::where('icd', 'LIKE', '%' . $icdVariable . '%')->first();
                                                if (!empty($query->description)) {
                                                    $arrayResult_CI_Data_On_icdMesh[$value] = "[CI]" . $query->description;
                                                    $values = array(
                                                        'prescribed_test' => $value,
                                                        'conditions' => $query->description,
                                                        'order_id' => $orderCode
                                                    );
                                                    DB::table('conditions')->insert($values);
                                                }
                                            }
                                        } else {
                                            $this->dump_array("meshCodeData result __Not Found", []);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $this->Log_scheduler_info('-> pdf generation - arrayResult_CI_Data_On_icdMesh: ' . json_encode($arrayResult_CI_Data_On_icdMesh));
                    $this->Log_scheduler_info('-> pdf generation - resultArray_mesh_data: ' . json_encode($resultArray_mesh_data));

                    $this->dump_array("arrayResult_CI_Data :: ", $arrayResult_CI_Data_On_icdMesh);
                    $this->dump_array("resultArray", $resultArray_mesh_data);
                    //@@$this->dump_array("medicationRxcuis___before_unique", $medicationRxcuis );
                    $responseResult_ddi_data = array();
                    $medicationRxcuis = array_unique($medicationRxcuis);
                    $rxcuis_string = implode('+', $medicationRxcuis);  //  returns a string from the elements of an array

                    $ddi_row_data = $this->getDDI($rxcuis_string);
                    $descriptionArray = array();

                    //@@$this->dump_array("medicationRxcuis___array_1__after_unique", $medicationRxcuis );
                    //@@$this->dump_array("rxcuis_string:", $rxcuis_string );
                    //@@$this->dump_array($rxcuis_string,[]);
                    $this->dump_array("ddi_row_data get data:___123", $ddi_row_data);
                    $this->Log_scheduler_info('-> pdf generation - ddi_row_data: ' . json_encode($ddi_row_data));

                    // Focus: jafar (drug-drug interactions)
                    // Response array containing drug-drug interactions with both drug names and description
                    //if(!empty($ddi_row_data))
                    if (!blank($ddi_row_data) && !empty($ddi_row_data) && filled($ddi_row_data) &&  is_array($ddi_row_data) && count($ddi_row_data) > 0) {
                        if (array_key_exists("fullInteractionTypeGroup", $ddi_row_data)) {
                            $type = $ddi_row_data['fullInteractionTypeGroup'][0]['fullInteractionType'];
                            $detected_med_uuids = array();

                            $tests_table_test_details = DB::table('test_details')->get();
                            $keywords = DB::table('keywords')->get();

                            /**
                             * not planning  to use these 2 $prescribedTest, $interactionComment
                             * todo delete
                             */
                            // $prescribedTest = DB::table('drug_interactions')->select('prescribed_test')->get();
                            // $interactionComment = DB::table('drug_interactions')->select('description')->get();

                            $this->Log_scheduler_info('-> pdf generation - type: ' . json_encode($type));
                            $this->dump_array("type:___123", $type);

                            foreach ($type as $key => $value) {
                                $drugName = $value['interactionPair'][0]['interactionConcept'][0]['minConceptItem']['name'];
                                $drugInteractedWith = $value['interactionPair'][0]['interactionConcept'][1]['minConceptItem']['name'];
                                $description = $value['interactionPair'][0]['description'];

                                $descriptionArray['drug_interacted_with'] = $drugInteractedWith;
                                $descriptionArray['description'] = $description;
                                $responseResult_ddi_data[$drugName][] = $descriptionArray;

                                //** Refactored: jafar */

                                // $tests_table_test_details = DB::table('test_details')->get();
                                // $prescribedTest = DB::table('drug_interactions')->select('prescribed_test')->get();
                                // $interactionComment = DB::table('drug_interactions')->select('description')->get();

                                /**
                                 * find drug class from test(drug name)
                                 */
                                $testDetail = $tests_table_test_details->where('dendi_test_name', ucwords($drugName));
                                $drugClassName = '';
                                if (!$testDetail->isEmpty()) {
                                    $drugClassName = $testDetail->first()->class;
                                }

                                $this->dump_array("keywords", $keywords);

                                $filterd = $keywords->filter(function ($value, $key) use ($description) {
                                    return str_contains($description, $value->primary_keyword)
                                        && str_contains($description, $value->secondary_keyword);
                                });

                                $this->dump_array("keyword filter", $filterd->first());

                                $keyword = '';
                                if (!$filterd->isEmpty()) {
                                    $keyword = $filterd->first()->resultant_keyword;
                                }

                                $values = array(
                                    'prescribed_test' => $drugName,
                                    'interacted_with' => $drugInteractedWith,
                                    'description' => $description,
                                    'order_id' => $orderCode,
                                    'drug_class' => $drugClassName,
                                    'keyword' => $keyword
                                );

                                $this->dump_array("drug interaction update", $values);

                                DB::table('drug_interactions')->insert($values);

                                //** Refactored: jafar */

                                // $keywords = DB::table('keywords')->get();
                                // foreach($prescribedTest as $prescribedTestName){
                                //     foreach($tests_table_test_details as $result){
                                //         if($result->dendi_test_name == ucwords($prescribedTestName->prescribed_test)){
                                //             $query = DrugInteraction::where('prescribed_test', $prescribedTestName->prescribed_test)->update(['drug_class' => $result->class]);
                                //         }
                                //     }
                                // }
                                // foreach($interactionComment as $description){
                                //     foreach($keywords as $keyword){
                                //         if(str_contains($description->description, $keyword->primary_keyword) && str_contains($description->description, $keyword->secondary_keyword)){
                                //             $query = DrugInteraction::where('description', $description->description)->update(['keyword' => $keyword->resultant_keyword]);
                                //         }
                                //     }
                                // }
                            }

                            //** Refactored: jafar : above code taken here outoff loop` */


                            //TODO: a lot here, to refactor to update performance
                            // foreach($prescribedTest as $prescribedTestName){
                            //     foreach($tests_table_test_details as $result){
                            //         if($result->dendi_test_name == ucwords($prescribedTestName->prescribed_test)){
                            //             $query = DrugInteraction::where('prescribed_test', $prescribedTestName->prescribed_test)->update(['drug_class' => $result->class]);
                            //         }
                            //     }
                            // }
                            // foreach($interactionComment as $description){
                            //     foreach($keywords as $keyword){
                            //         if(str_contains($description->description, $keyword->primary_keyword) && str_contains($description->description, $keyword->secondary_keyword)){
                            //             $query = DrugInteraction::where('description', $description->description)->update(['keyword' => $keyword->resultant_keyword]);
                            //         }
                            //     }
                            // }

                        } else {
                            Log::channel('error')->error('No drug-drug interactions found');
                            $this->dump_array("No drug-drug interactions found ", []);
                        }
                    } else {
                        Log::channel('error')->error('No data found (NO -ddi_row_data)');
                        $this->dump_array("NO -ddi_row_data- data ", []);
                    }

                    $this->dump_array("next step____", []);
                    $this->dump_array("responseResult____321", $responseResult_ddi_data);
                    $this->Log_scheduler_info('-> pdf generation - responseResult_ddi_data: ' . json_encode($responseResult_ddi_data));

                    // ddd($responseResult_ddi_data);

                    OrderDetail::where('order_code', $orderCode)
                        ->update([
                            'drug_drug_interactions' => $responseResult_ddi_data,
                            'contraindicated_conditions' => $arrayResult_CI_Data_On_icdMesh,
                            'boxed_warnings' => $boxedWarningData,
                            'report_status' => '0'
                        ]);

                    // Test Information from Order API
                    if (!blank($testPanel) && !empty($testPanel) && filled($testPanel) &&  is_array($testPanel) && count($testPanel) > 0) {
                        //@@$this->dump_array("testPanel", $testPanel);

                        foreach ($testPanel as $tPanel) {
                            $testInformations[] = $tPanel['panel_name'];
                            if (is_array($testInformations)) {
                                $testInformation = implode(' , ', $testInformations);
                            }
                        }
                    }

                    $panelTestResult = array();
                    $medicineNamesArray = array();
                    //TODO: jafar - For test purpose only. Remove below code.
                    $medicineNamesArray = $prescribedMedications;
                    $this->dump_array("medicineNamesArray:", $medicineNamesArray);
                    $this->Log_scheduler_info('-> pdf generation - medicineNamesArray: ' . json_encode($medicineNamesArray));

                    if (str_contains($testInformation, 'SafeDrugs') || true) // TODO: Added this true for Stratus. Need to remove.
                    {
                        if (str_contains($testInformation, '4 Panel Urine Screen') || true) { // TODO: '4 Panel Urine Screen' not found in Panel Name
                            // TODO: Need Test Panels
                            $panelTestsArray = [
                                'Amphetamines',
                                'Amphetamine Scr',
                                'Barbiturates',
                                'Barbiturates Scr',
                                'Benzodiazepines Scr',
                                'THC Scr',
                                'Benzodiazepines',
                                'Buprenorphines',
                                'Buprenorphines Scr',
                                'Opiates',
                                'Opiates Group',
                                'Cocaine Metabolite Scr',
                                'Opiates Scr',
                                'Oxycodone Scr',
                                'PCP Scr',
                                'Buprenorphine Scr',
                                'EDDP (Metabolite for Methadone) Scr',
                                'Creatinine',
                                'Oxidant Scr',
                                'Specific Gravity',
                                'Synthetic Urine'
                            ];

                            foreach ($testResults as $testResult) {
                                if (in_array($testResult['test_description'], $panelTestsArray)) {
                                    $panelTestName = $testResult['test_description'];
                                    $panelTestResult[$panelTestName] = $testResult['result_display'];
                                }
                            }
                        }

                        $this->dump_array("panelTestResult", $panelTestResult);

                        // TODO: Panel Test Results are all Negative
                        $finalArray = [];
                        // ddd($panelTestResult);

                        /*
                        // Prescribed Medications based on order code
                        $medicationUids = $dataSet['medications'];
                        $prescribedWithoutMetabolites = array();

                        $this->fetchAllUuids($uuids,  $medicationUids);
                        // $finalArray = [];

                        // Prescribed medications with metabolites of metabolites
                        foreach ($uuids as $uuid) {
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

                            foreach($testResults as $key => $arrayValue){
                                if($name == ucwords($arrayValue['test_type'])
                                    || $name == $arrayValue['test_type']){
                                    $finalArray[$this->singleUuid][$name] = $arrayValue['result'];
                                }
                            }
                        }

                        if(!empty($medicationUids) && is_array($medicationUids))
                        {
                            $medicineNamesArray = array();
                            $prescribedWithMetabolites = array();
                            $metaboliteUidsArray = array(); // TODO: Not used anywhere. Remove?

                            foreach($medicationUids as $key => $prescribedMedicationId) {
                                // TODO: Use medications[index]["name"] for this?
                                $medications_name = Http::accept('application/json')
                                    ->withHeaders(['Authorization' => config('nih.token')])
                                    ->get(Config::get('nih.dendiSoftwareId').$prescribedMedicationId['code']);
                                $responseData = $medications_name->getBody()->getContents();
                                $resultSet = json_decode($responseData, true);
                                $medicineName = $resultSet['name'];
                                $prescribedMedsArray[$medicineName] = $resultSet['metabolites'];
                            }

                            foreach($prescribedMedsArray as $medicineName => $metabolitesArray) {
                                $medicineNamesArray[] = $medicineName;

                                if(!empty($metabolitesArray) && is_array($metabolitesArray)) {
                                    foreach($metabolitesArray as $key => $metaboliteId) {
                                        $metabolitesData = array();
                                        $metaboliteName = Http::accept('application/json')
                                            ->withHeaders(['Authorization' => config('nih.token')])
                                            ->get(Config::get('nih.dendiSoftwareId').$metaboliteId);
                                        $responseData = $metaboliteName->getBody()->getContents();
                                        $resultSet = json_decode($responseData, true);
                                        $metabolitesData[] = $resultSet['name'];
                                        $prescribedWithMetabolites[$medicineName] = $metabolitesData;
                                    }
                                } else {
                                    $prescribedWithoutMetabolites[$medicineName] = [];
                                }
                                // TODO: this variable is not used anywhere. Remove?
                                $prescribedAndWithMetabolites = array_merge($prescribedWithMetabolites,$prescribedWithoutMetabolites);
                            }
                            if(!empty($medicineNamesArray)){
                                $medications = implode(' , ', $medicineNamesArray);
                            }
                        } else {
                            $medications = 'None';
                        }*/


                        /************ start  Alternet of above code, as "Stratus" doesn't has any "uuid"    ***************/
                        $finalArray = [];
                        $prescribedWithMetabolites = array();   // Never used for output
                        $metaboliteUidsArray = array(); // TODO: Not used anywhere. Remove?

                        //*********************** The array Need ***************************/
                        // $prescribedMedsArray[$medicineName] = $resultSet['metabolites'];  //ok
                        // $medicineNamesArray[] = $medicineName; //ok
                        // $finalArray[$this->singleUuid][$name] = $arrayValue['result'];
                        // $medications = implode(' , ', $medicineNamesArray);   // ??

                        $this->Log_scheduler_info('-> pdf generation - prescribedMedications: ' . json_encode($prescribedMedications));

                        $prescribedMedsArray = array();
                        if (!blank($prescribedMedications) && !empty($prescribedMedications) && filled($prescribedMedications) &&  is_array($prescribedMedications) && count($prescribedMedications) > 0) {
                            $medicationWithMetaboitesArray = $this->metaboliteService->getMetaboliteByTest($prescribedMedications);
                            foreach ($prescribedMedications as $key => $prescibedMedicineName) {
                                $medicineNamesArray[] = $prescibedMedicineName;
                                $prescribedMedsArray[$prescibedMedicineName] = $medicationWithMetaboitesArray[$prescibedMedicineName]["metabolite"];
                                $prescribedWithMetabolites[$prescibedMedicineName] = $medicationWithMetaboitesArray[$prescibedMedicineName]["metabolite"];

                                //Focus: jafar
                            }
                        }

                        $this->dump_array("medicineNamesArray__890", $medicineNamesArray);
                        $this->dump_array("prescribedMedsArray__890", $prescribedMedsArray);

                        /************  END Alternet of above code, as "Stratus" doesn't has any "uuid"    ***************/

                        $tests_table_test_details = DB::table('test_details')->get();

                        $testTypeArray = [
                            // 'pH',
                            'Specific Gravity',
                            'Creatinine',
                            'Urine Creatinine'
                        ];
                        $analyteInformations = array();
                        $eiaInformations = array();
                        $detectedMedicines = array();
                        $notPrescribedDetected = array();
                        $notDetectedMedicines = array();
                        $notDetectednotPrescribed_old = array();
                        $prescribedNotDetected = array();
                        $prescribedDetected = [];
                        $notPrescribedDetectedSorted = array();
                        $methData = array();
                        $quantitativeResult = '';
                        $quantitativeResultSpecificGravity = '';
                        $quantitativeResultCreatinine = '';


                        $this->dump_array("testResults_1111", $testResults);

                        foreach ($testResults as $key => $value) {

                            if (in_array($value['test_description'], $testTypeArray)) {
                                if ($value['test_description'] == 'pH') {
                                    $quantitativeResults = $value['result_display'];
                                    $quantitativeResult = round($quantitativeResults, 2);
                                } elseif ($value['test_description'] == 'Specific Gravity') {
                                    $quantitativeResultSpecificGravitys = $value['result_display'];
                                    $quantitativeResultSpecificGravity = round($quantitativeResultSpecificGravitys, 3);
                                } elseif (
                                    $value['test_description'] == 'Urine Creatinine'
                                    || $value['test_description'] == 'Creatinine'
                                ) {
                                    $quantitativeResultCreatinines = $value['result_display'];
                                    $quantitativeResultCreatinine = round($quantitativeResultCreatinines, 2);
                                } else {
                                    $quantitativeResult = '';
                                    $quantitativeResultSpecificGravity = '';
                                    $quantitativeResultCreatinine = '';
                                }
                            } else {
                                // TODO: result flag is empty sometimes
                                if (
                                    $value['result_flag'] == 'Negative'
                                    || $value['result_flag'] == 'Positive'
                                ) {
                                    $testTypeSpecimen = $value['test_type'];
                                    $eiaInformations[$testTypeSpecimen] = $value;
                                } elseif (
                                    $value['result_flag'] == 'Not Detected'
                                    || $value['result_flag'] == 'Detected'
                                ) {
                                    // TODO: Stratus API does not have Detected and Not Detected
                                    $testType = $value['test_type'];
                                    $analyteInformations[$testType] = $value;

                                    //TODO: Note: to match with Stratus api data, refector like way. //jafar
                                    // if ($value['test_type'] == 'D-Methamphetamine %'
                                    //     || $value['test_type'] == 'L-Methamphetamine %')
                                    // {

                                    if (
                                        Str::contains(Str::lower($value['test_type']), Str::lower('Methamphetamine'))
                                        || Str::contains($value['test_type'], 'Methamphetamine')
                                    ) {
                                        $methData[$value['test_type']] = $value;
                                    }

                                    if ($value['result_flag'] == 'Detected') {
                                        $detectedMedicines[$testType] = $value;
                                    } else {
                                        $notDetectedMedicines[$testType] = $value;
                                    }
                                } else {
                                    //NOTE: $value['result_flag'] == ''  -- there are some value like blank, 
                                    // hence commenting below code 
                                    Log::channel('error')->error('PDF report is pending-- result_flag has some blank value');
                                    // return response(['message' => 'PDF report is pending'], 200);
                                }
                            }
                        }

                        //@@$this->dump_array("testResults", $testResults);

                        $condition = true;
                        $prescribedNotPrescribedTests = array();


                        // ddd($medicineNamesArray);
                        $this->dump_array("notDetectedMedicines", $notDetectedMedicines);
                        $this->dump_array("finalArray", $finalArray);

                        foreach ($notDetectedMedicines as $notDetectedName => $values) {
                            if (!empty($medicineNamesArray) && !empty($finalArray)) {
                                // This code will only run once. The $condition=false is immediately after this block.
                                if ($condition) {
                                    foreach ($finalArray as $uuid => $data) {
                                        foreach ($data as $testName => $testResult) {
                                            $prescribedNotPrescribedTests[] = $testName;
                                        }
                                    }
                                }
                                $condition = false;
                                // TODO: It seems this whole if block is unnecessary.
                                // TODO: Array index is immediately overwritten before accessing. Remove?
                                if (
                                    !in_array($notDetectedName, $medicineNamesArray)
                                    && !in_array($notDetectedName, $prescribedNotPrescribedTests)
                                ) {
                                    $notDetectednotPrescribed_old[$notDetectedName] = $values;
                                }
                            }
                            $notDetectednotPrescribed_old[$notDetectedName] = $values;
                        }
                        $this->dump_array("prescribedNotPrescribedTests", $prescribedNotPrescribedTests);
                        $this->dump_array("notDetectednotPrescribed_old", $notDetectednotPrescribed_old);

                        $groupPrescribedDetected = array();
                        $groupNotPrescribedDetected = array();
                        $groupPrescribedNotDetected = array();
                        $sortedPrescribedDetected = array();
                        $sortedNotPrescribedDetected = array();
                        $sortedPrescribedNotDetected = array();
                        $prescribedTestDetected = array();
                        $prescribedNotTestDetected = array();

                        $detectedPrescribed = array();
                        $detectedNotPrescribed = array();
                        $notDetectedPrescribed = array();
                        $notDetectedNotPrescribed_new_variable = array(); //Important NOTE: this array is making null here, as new logic for "Stratus" start here..

                        //@@$this->dump_array("testResults", $testResults);

                        foreach ($testResults as $testResult) {
                            if ($testResult["result_medication"] == "COMPLIANT") {
                                $detectedPrescribed[] = $testResult;
                            } else if ($testResult["result_medication"] == "NON-COMPLIANT") {
                                $notDetectedPrescribed[] = $testResult;
                            } else if ($testResult["result_medication"] == "NON-COMPLIANT NP" || $testResult["result_medication"] == "NON-COMPLIANT NP\t") {
                                $detectedNotPrescribed[] = $testResult;
                            } else if ($testResult["result_medication"] == "") {
                                //ducktap: jafar
                                $notDetectedNotPrescribed_new_variable[$testResult["test_type"]] = $testResult;
                            }
                        }

                        $this->dump_array("detectedPrescribed__Count", count($detectedPrescribed));
                        $this->dump_array("notDetectedPrescribed__Count", count($notDetectedPrescribed));
                        $this->dump_array("detectedNotPrescribed__Count", count($detectedNotPrescribed));
                        $this->dump_array("notDetectedNotPrescribed__Count", count($notDetectedNotPrescribed_new_variable));

                        //@@$this->dump_array("detectedPrescribed", $detectedPrescribed);
                        //@@$this->dump_array("notDetectedPrescribed", $notDetectedPrescribed);  
                        //@@$this->dump_array("detectedNotPrescribed", $detectedNotPrescribed); // Not Prescribed Detected
                        //@@$this->dump_array("notDetectedNotPrescribed__1", $notDetectedNotPrescribed_new_variable);

                        // TODO: Prescribed & Detected Logic. @Jafar bhaia need help here
                        foreach ($detectedPrescribed as $value) {
                            $isTestNameInDB = 0;
                            foreach ($tests_table_test_details as $test) {
                                if (strtolower($test->dendi_test_name) == strtolower($value["test_type"])) {
                                    $value["class"] = $test->class;
                                    $value["id"] = $test->id;
                                    $value["section"] = 'prescribedDetected';
                                    $detectedPrescribed[$value["test_type"]] = $value;

                                    $isTestNameInDB = 1;
                                }
                            }
                            if ($isTestNameInDB == 0) {
                                $value["class"] = "N/A";
                                $value["id"] = random_int(1, 99999);
                                $value["section"] = 'prescribedDetected';
                                $detectedPrescribed[$value["test_type"]] = $value;

                                Log::channel('testnamenotfound')->error('Test Name Not at Database: ' . $value["test_type"]);
                            }
                        }
                        foreach ($detectedNotPrescribed as $value) {
                            $isTestNameInDB = 0;
                            foreach ($tests_table_test_details as $test) {
                                if (strtolower($test->dendi_test_name) == strtolower($value["test_type"])) {
                                    $value["class"] = $test->class;
                                    $value["id"] = $test->id;
                                    $value["section"] = 'notPrescribedDetected';
                                    $detectedNotPrescribed[$value["test_type"]] = $value;

                                    $isTestNameInDB = 1;
                                }
                            }
                            if ($isTestNameInDB == 0) {
                                $value["class"] = "N/A";
                                $value["id"] = random_int(1, 99999);
                                $value["section"] = 'prescribedDetected';
                                $detectedNotPrescribed[$value["test_type"]] = $value;

                                Log::channel('testnamenotfound')->error('Test Name Not at Database: ' . $value["test_type"]);
                            }
                        }
                        foreach ($notDetectedPrescribed as $value) {
                            $isTestNameInDB = 0;
                            foreach ($tests_table_test_details as $test) {
                                if (strtolower($test->dendi_test_name) == strtolower($value["test_type"])) {
                                    $value["class"] = $test->class;
                                    $value["id"] = $test->id;
                                    $value["section"] = 'prescribedNotDetected';
                                    $notDetectedPrescribed[$value["test_type"]] = $value;

                                    $isTestNameInDB = 1;
                                }
                            }
                            if ($isTestNameInDB == 0) {
                                $value["class"] = "N/A";
                                $value["id"] = random_int(1, 99999);
                                $value["section"] = 'prescribedDetected';
                                $notDetectedPrescribed[$value["test_type"]] = $value;

                                Log::channel('testnamenotfound')->error('Test Name Not at Database: ' . $value["test_type"]);
                            }
                        }
                        /*
                            * // TODO: Is this not needed?
                            foreach ($notDetectedNotPrescribed_new_variable as $key => $value)
                            {
                                if(strtolower($test->dendi_test_name) == strtolower($value["test_type"]))
                                {
                                    $value["class"] = $test->class;
                                    $value["id"] = $test->id;
                                    $value["section"] = 'notPrescribedNotDetected';
                                    $notDetectedNotPrescribed_new_variable[$key] = $value;
                                }
                            }
                            */


                        $prescribedDetected = $detectedPrescribed;
                        $notPrescribedDetected = $detectedNotPrescribed;
                        $prescribedNotDetected = $notDetectedPrescribed;
                        $chunks = $notDetectedNotPrescribed_new_variable;

                        $this->dump_array("prescribedDetected__2", $prescribedDetected);
                        $this->dump_array("notPrescribedDetected__2", $notPrescribedDetected);
                        $this->dump_array("prescribedNotDetected__2", $prescribedNotDetected);
                        $this->dump_array("notDetectedNotPrescribed__2", $notDetectedNotPrescribed_new_variable);

                        $this->dump_array("notDetectedNotPrescribed__Count", count($notDetectedNotPrescribed_new_variable));

                        //@@$nd_np_ducktap = $notDetectedNotPrescribed_new_variable;

                        // ddd($notPrescribedDetected);


                        /*
                        foreach($finalArray as $uuid => $individualArray) {
                            $valueCount = count($individualArray);
                            foreach ($individualArray as $finalTestName => $finalTestValue ) {
                                if ($finalTestValue['result_qualitative'] == 'Detected') {
                                    $prescribedTestDetected[$uuid] = $finalArray[$uuid];
                                }
                            }
                        }
                        */

                        /*
                        foreach($prescribedTestDetected as $testId => $testResult) {
                            foreach($testResult as $testName => $testData) {
                                foreach($tests_table_test_details as $resultValue) {
                                    if(ucwords($resultValue->dendi_test_name) == $testName
                                        || $resultValue->dendi_test_name == $testName) {
                                        $class  = $resultValue->class;
                                        $testData['class'] = $class;
                                        $id  = $resultValue->id;
                                        $testData['id'] = $id;
                                        $testData['section'] = 'prescribedDetected';
                                    }
                                }
                                $prescribedDetected[$testName] = $testData;
                            }
                        }


                        $finalArrayKeys = array_keys($finalArray);
                        $prescribedDetectedKeys = array_keys($prescribedTestDetected);
                        $notPrescribedDetectedKeys = array_diff($finalArrayKeys, $prescribedDetectedKeys);

                        foreach ($notPrescribedDetectedKeys as $notPrescribedDetectedKey) {
                            $prescribedNotTestDetected[$notPrescribedDetectedKey] = $finalArray[$notPrescribedDetectedKey];
                        }
                        foreach($prescribedNotTestDetected as $testId => $testResult) {
                            foreach($testResult as $testName => $testData) {
                                foreach($tests_table_test_details as $resultValue) {
                                    if(ucwords($resultValue->dendi_test_name) == $testName
                                        || $resultValue->dendi_test_name == $testName) {
                                        $class  = $resultValue->class;
                                        $testData['class'] = $class;
                                        $id  = $resultValue->id;
                                        $testData['id'] = $id;
                                        $testData['section'] = 'prescribedNotDetected';
                                    }
                                }
                                $prescribedNotDetected[$testName] = $testData;
                            }
                        }
                        */

                        foreach ($prescribedDetected as $key => $prescribedDetectedResult) {
                            if (!empty($prescribedDetectedResult['class'])) {
                                $class = $prescribedDetectedResult['class'];
                                $groupPrescribedDetected[$class][$key] = $prescribedDetectedResult;
                            }
                        }

                        $this->dump_array("groupPrescribedDetected", $groupPrescribedDetected);

                        foreach ($groupPrescribedDetected as $class => $valuesSorted) {
                            foreach ($valuesSorted as $key => $sortedListing) {
                                $sortedPrescribedDetected[$key] = $sortedListing;
                                $orderId = DB::table('order_details')
                                    ->where('order_code', $orderCode)
                                    ->value('id');
                                if (
                                    $sortedListing['section'] == "prescribedDetected"
                                    && in_array(ucwords($key), $medicineNamesArray)
                                ) {
                                    OrderTestClassSection::create([
                                        'order_id' => $orderId,
                                        'section_id' => 1,
                                        'test_class' => $class,
                                        'test' => $key
                                    ]);
                                }
                            }
                        }
                        $this->dump_array("sortedPrescribedDetected__1", $sortedPrescribedDetected);

                        $this->dump_array("detectedMedicines__1", $detectedMedicines);
                        $this->dump_array("medicineNamesArray__1", $medicineNamesArray);

                        foreach ($detectedMedicines as $index => $value) {
                            if (!empty($medicineNamesArray)) {
                                if (
                                    !in_array(ucwords($index), $medicineNamesArray)
                                    && !array_key_exists($index, $prescribedDetected)
                                ) {
                                    foreach ($tests_table_test_details as $resultValue) {
                                        if (ucwords($resultValue->dendi_test_name) == ucwords($index)) {
                                            $descriptionTest  = $resultValue->description;
                                            $value['description'] = $descriptionTest;
                                            $class  = $resultValue->class;
                                            $id  = $resultValue->id;
                                            $value['class'] = $class;
                                            $value['id'] = $id;
                                            $value['section'] = 'notPrescribedDetected';
                                        }
                                    }
                                    $notPrescribedDetected[$index] = $value;
                                }
                            } else {
                                foreach ($tests_table_test_details as $resultValue) {
                                    if (ucwords($resultValue->dendi_test_name) == ucwords($index)) {
                                        $descriptionTest  = $resultValue->description;
                                        $value['description'] = $descriptionTest;
                                        $class  = $resultValue->class;
                                        $id  = $resultValue->id;
                                        $value['class'] = $class;
                                        $value['id'] = $id;
                                        $value['section'] = 'notPrescribedDetected';
                                    }
                                }
                                $notPrescribedDetected[$index] = $value;
                            }
                        }

                        //$this->dump_array("notPrescribedDetected___Before call function", $notPrescribedDetected);

                        if (!empty($notPrescribedDetected)) {
                            $notPrescribedDetected = $this->insertValueAtPosition($notPrescribedDetected);
                        }
                        //$this->dump_array("notPrescribedDetected__after_called_insertValueAtPosition", $notPrescribedDetected);


                        foreach ($notPrescribedDetected as $key => $notPrescribedDetectedResult) {
                            if (!empty($notPrescribedDetectedResult['class'])) {
                                $class = $notPrescribedDetectedResult['class'];
                                $groupNotPrescribedDetected[$class][$key] = $notPrescribedDetectedResult;
                            }
                        }

                        //$this->dump_array("groupNotPrescribedDetected", $groupNotPrescribedDetected);

                        foreach ($groupNotPrescribedDetected as $class => $valuesSorted) {
                            foreach ($valuesSorted as $key => $sortedListing) {
                                $sortedNotPrescribedDetected[$key] = $sortedListing;
                                $orderId = DB::table('order_details')
                                    ->where('order_code', $orderCode)
                                    ->value('id');
                                if ($sortedListing['section'] == "notPrescribedDetected") {
                                    OrderTestClassSection::create([
                                        'order_id' => $orderId,
                                        'section_id' => 2,
                                        'test_class' => $class,
                                        'test' => $key
                                    ]);
                                }
                            }
                        }

                        //$this->dump_array("sortedNotPrescribedDetected__22", $sortedNotPrescribedDetected);

                        if (!empty($prescribedNotDetected)) {
                            $prescribedNotDetected = $this->insertValueAtPosition($prescribedNotDetected);
                        }

                        foreach ($prescribedNotDetected as $key => $prescribedNotDetectedResult) {
                            if (!empty($prescribedNotDetectedResult['class'])) {
                                $class = $prescribedNotDetectedResult['class'];
                                $groupPrescribedNotDetected[$class][$key] = $prescribedNotDetectedResult;
                            }
                        }

                        //$this->dump_array("groupPrescribedNotDetected", $groupPrescribedNotDetected);

                        foreach ($groupPrescribedNotDetected as $class => $valuesSorted) {
                            foreach ($valuesSorted as $key => $sortedListing) {
                                $sortedPrescribedNotDetected[$key] = $sortedListing;
                                $orderId = DB::table('order_details')
                                    ->where('order_code', $orderCode)
                                    ->value('id');
                                if (
                                    $sortedListing['section'] == "prescribedNotDetected"
                                    && in_array(ucwords($key), $medicineNamesArray)
                                ) {
                                    OrderTestClassSection::create([
                                        'order_id' => $orderId,
                                        'section_id' => 3,
                                        'test_class' => $class,
                                        'test' => $key
                                    ]);
                                }
                            }
                        }

                        $comment = array();
                        $allSectionTest = array_keys(
                            array_merge(
                                $sortedPrescribedNotDetected,
                                $sortedPrescribedDetected,
                                $sortedNotPrescribedDetected
                            )
                        );

                        $this->dump_array("allSectionTest", $allSectionTest);
                        $this->dump_array("medicineNamesArray", $medicineNamesArray);
                        $this->dump_array("responseResult", $responseResult_ddi_data);

                        $testWithDescription = array();

                        if (!empty($medicineNamesArray)) {
                            foreach ($medicineNamesArray as $key => $value) {
                                foreach ($responseResult_ddi_data as $keyResultName => $valueDataResult) {
                                    foreach ($valueDataResult as $keyData => $valueData) {
                                        if (
                                            ucwords($value) == ucwords($keyResultName)
                                            && !array_key_exists(ucwords($keyResultName), $sortedPrescribedDetected)
                                            && !array_key_exists(ucwords($keyResultName), $sortedPrescribedNotDetected)
                                            && !array_key_exists(ucwords($keyResultName), $sortedNotPrescribedDetected)
                                        ) {
                                            if (array_key_exists($valueData['drug_interacted_with'], $responseResult_ddi_data)) {
                                                $comment[$value][] = $valueData['description'];
                                            }
                                        }
                                    }
                                }
                            }
                            foreach ($allSectionTest as $testName) {
                                foreach ($comment as $drugNameKey => $descriptionResults) {
                                    foreach ($descriptionResults as $descriptionResult) {
                                        if (str_contains($descriptionResult, $testName)) {
                                            $testWithDescription[$testName][$drugNameKey] = $descriptionResult;
                                        }
                                    }
                                }
                            }

                            $this->dump_array("comment", $comment);
                            $this->dump_array("testWithDescription", $testWithDescription);
                        }

                        $drugInteractedWithArray_ddi_data = array();
                        $dataResult_Interaction_keywords = array();
                        $generalComments_table_comments = DB::table('comments')->get();
                        $sections = DB::table('comments_has_sections')->get();

                        $this->dump_array("generalComments__table", $generalComments_table_comments);
                        $this->dump_array("sections__table", $sections);

                        $this->dump_array("responseResult", $responseResult_ddi_data);

                        if (!empty($responseResult_ddi_data)) {
                            $keywords = DB::table('keywords')->distinct()->get();
                            $primaryKeywords = DB::table('keywords')
                                ->distinct('primary_keyword')
                                ->pluck('primary_keyword');

                            $this->dump_array("primaryKeywords", $primaryKeywords);

                            foreach ($responseResult_ddi_data as $drugName => $values) {
                                foreach ($values as $nameKey => $resultDataValues) {
                                    $drugNameValue = ucwords($drugName); // TODO: @Jafar bhaia, this variable is unnecessary. Remove?
                                    $drugInteractedWith = ucwords($resultDataValues['drug_interacted_with']);

                                    foreach ($keywords as $result) {
                                        if (
                                            str_contains($resultDataValues['description'], $result->primary_keyword)
                                            && str_contains($resultDataValues['description'], $result->secondary_keyword)
                                        ) {
                                            $drugInteractedWithArray_ddi_data[$drugName][$result->resultant_keyword][] = $drugInteractedWith;
                                        }
                                    }

                                    if (!($this->strposa($resultDataValues['description'], $primaryKeywords))) {
                                        $drugInteractedWithArray_ddi_data[$drugName]['Other'][] = $resultDataValues['description'];
                                    }
                                }
                            }

                            $this->dump_array("drugInteractedWithArray", $drugInteractedWithArray_ddi_data);

                            // DDIs with prescribed medications - check $responseResult_ddi_data("drug_interacted_with") for prescribed medications

                            $this->dump_array("testWithDescription", $testWithDescription);
                            $this->dump_array("keywords", $keywords);

                            if (!empty($testWithDescription)) {
                                foreach ($testWithDescription as $drugName => $values) {
                                    foreach ($values as $testNameKey => $resultDataValue) {
                                        foreach ($keywords as $result) {
                                            if (
                                                str_contains($resultDataValue, $result->primary_keyword)
                                                || str_contains($resultDataValue, strtolower($result->primary_keyword))
                                            ) {
                                                $drugInteractedWithArray_ddi_data[strtolower($drugName)][$result->resultant_keyword][] = $testNameKey;
                                            }
                                        }
                                        if (!($this->strposa($resultDataValue, $primaryKeywords))) {
                                            $drugInteractedWithArray_ddi_data[strtolower($drugName)]['Other'][] = $resultDataValue;
                                        }
                                    }
                                }
                            }

                            $sortedKeywordsInteractions = array();
                            $drugInteractionsWithKeyword = array();

                            // ddd($drugInteractedWithArray_ddi_data);
                            $this->dump_array("drugInteractedWithArray__2", $drugInteractedWithArray_ddi_data);

                            foreach ($drugInteractedWithArray_ddi_data as $testName => $data) {
                                if (array_key_exists("Other", $data)) {
                                    $b[$testName]['Other'] = $data['Other'];
                                    unset($data['Other']);
                                    $drugInteractionsWithKeyword[$testName] = $data;
                                    foreach ($drugInteractionsWithKeyword as $k => $v) {
                                        foreach ($b as $key => $result) {
                                            if ($k == $key) {
                                                $sortedKeywordsInteractions[$testName] = array_merge($v, $result);
                                            }
                                        }
                                    }
                                } else {
                                    $sortedKeywordsInteractions[$testName] = $data;
                                }
                            }

                            $this->dump_array("sortedKeywordsInteractions", $sortedKeywordsInteractions);

                            foreach ($sortedKeywordsInteractions as $testName => $data) {
                                foreach ($data as $keyword => $keyResult) {
                                    if ($keyword != "Other") {
                                        $dataResult_Interaction_keywords[$testName][$keyword] = implode(",", $keyResult);
                                    } else {
                                        $dataResult_Interaction_keywords[$testName][$keyword] = implode(" ", $keyResult);
                                    }
                                }
                            }

                            $this->dump_array("dataResult", $dataResult_Interaction_keywords);
                        }
                        foreach ($dataResult_Interaction_keywords as $key => $valueDatas) {
                            $output = implode(' - ', array_map(
                                function ($v, $k) {
                                    return sprintf("(%s) %s", $k, $v);
                                },
                                $valueDatas,
                                array_keys($valueDatas)
                            ));
                            $contraindicationComments[$key] = "[DDI] " . $output;
                        }
                        $this->dump_array("contraindicationComments", $contraindicationComments);

                        $collection = collect($notDetectedNotPrescribed_new_variable); // collect($notDetectednotPrescribed_old);
                        $chunks = $collection->chunk(12);
                        $chunks->all(); // TODO: @Jafar bhaia, Is this code needed?

                        $this->dump_array("notDetectednotPrescribed___old_11", $notDetectednotPrescribed_old);
                        $this->dump_array("notDetectedNotPrescribed_new_variable", $notDetectedNotPrescribed_new_variable);

                        $this->dump_array("chunks___abc123", $chunks);

                        $orders = DB::table('order_details')
                            ->select('order_code', 'report_status')
                            ->get();

                        $metforminText = "if GFR <30 mL/min";

                        $panelTests = DB::table('panel_tests')->get();
                        $dateTimeNow = new DateTime();
                        $patientDOBDate = DateTime::createFromFormat("Ymd", $patientDOB);
                        $collectedDate = DateTime::createFromFormat("YmdHis", $collected);
                        // $medicationsList = implode(", ", $prescribedMedications); // previous code.
                        $medicationsList = implode(", ", $prescribedMedications_as_prescribed);

                        $this->dump_array("panelTests", $panelTests);
                        $this->dump_array("medicationsList", $medicationsList);

                        $this->dump_array("notDetectedNotPrescribed__Count__16", count($notDetectedNotPrescribed_new_variable));

                        $this->Log_scheduler_info('-> before data set ');

                        $data = [
                            'code' => $orderCode,
                            'patientName' => $patientFirstName . ' ' . $patientLastName,
                            'patientDOB' => date_format($patientDOBDate, "m/d/Y"),
                            'patientGender' => $patientGender,
                            'patientPhone' => $patientPhone,
                            'account' => $accountName,
                            'provider' => $providerFirstName . ' ' . $providerLastName,
                            'accession' => $accession,
                            'sample_type' => $sampleName,
                            'reported' => date_format($dateTimeNow, "m/d/Y"),
                            'collected' => date_format($collectedDate, "m/d/Y"),
                            'phone' => $patientPhone,
                            'in_house_lab_location' => $inHouseLabLocations,
                            'testInformation' => $testInformation,
                            'icdCode' => $icdCodeValues,
                            'medications' => $medicationsList,
                            'quantitativeResult' => $quantitativeResult,
                            'quantitativeResultSpecificGravity' => $quantitativeResultSpecificGravity,
                            'quantitativeResultCreatinine' => $quantitativeResultCreatinine,
                            'analyteInformations' => $analyteInformations,
                            'eiaInformations' => $eiaInformations,
                            'notPrescribedDetected' => $sortedNotPrescribedDetected,
                            'notDetectednotPrescribed' => $chunks,
                            'prescribedNotDetected' => $sortedPrescribedNotDetected,
                            'sortedPrescribedDetected' => $sortedPrescribedDetected,
                            'tests' => $tests_table_test_details,
                            'generalComments' => $generalComments_table_comments,
                            'orders' => $orders,
                            'receivedDate' => date("m/d/Y", strtotime($receivedDate)),
                            'labLocations' => $labLocations,
                            'contraindicationComments' => $contraindicationComments,
                            'methData' => $methData,
                            'metforminText' => $metforminText,
                            'panelTests' => $panelTests,
                            'panelTestResult' => $panelTestResult,
                            'icdToMeshCodes' => $icdToMeshCodes,
                            'arrayResult' => $arrayResult_CI_Data_On_icdMesh,
                            'sections' => $sections,
                        ];

                        $this->dump_array("data output", $data);
                        $this->Log_scheduler_info('-> data ' . json_encode($data));

                        // return $data;
                        $response = [
                            'content' => $data,
                            'message' => "Success",
                            'status' => "200",
                        ];
                    } else {
                        // return response(['message' => 'Not a careview profile'], 200);
                        $response = [
                            'content' => null,
                            'message' => "Not a careview profile",
                            'status' => "200",
                        ];
                    }
                }
            } else {
                // return response()->json(['message' => 'No results for this order code'],200);
                $response = [
                    'content' => null,
                    'message' => "No results for this order code",
                    'status' => "200",
                ];
            }

            // //Unset Data
            // $data='';
            // $response = '';
            // unset($response);
            // unset($data);

            //GC collection
            gc_collect_cycles();

            return $response;
        } catch (Exception $ex) {
            $response = [
                'content' => $ex->getMessage(),
                'message' => "No results for this order code",
                'status' => "500",
            ];

            $this->Log_scheduler_info('-> exception method ' . $ex->getMessage());

            return $response;
        }
        finally {
            $data='';
            $response = '';
            //Unset Data
            unset($response);
            unset($data);

            //GC collection
            gc_collect_cycles();
        }
    }

    public function uploadToS3($fileName)
    {
        // $s3 = new S3Client([
        //     'version' => 'latest',
        //     'region' => 'us-east-1', // Replace with your S3 region
        //     'credentials' => [
        //         'key' => 'AKIASAR7MZIVKQJD2GRD',
        //         'secret' => 'qv1LGnlb+opVV051J7UeR/ydJ69foKmaI3xLIcTT',
        //     ],
        // ]);

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'), // Replace with your S3 region
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $bucket = env('AWS_BUCKET');  //'stratus-report'; // Replace with your S3 bucket name
        $filePath = $fileName; // Destination path in S3

        try {
            $result = $s3->putObject([
                'Bucket' => $bucket,
                'Key' => $filePath,
                'Body' => fopen(public_path() . '/pdf//' . $fileName, 'r'),
                //'ACL' => 'public-read', // Adjust ACL as needed public_path()."/pdf/$fileName"
            ]);

            return true;
        } catch (\Exception $exception) {
            Log::channel('scheduler_info')->error('$$$-> Exception: pdf to S3 upload: ' . $exception->getMessage());
            // Handle the exception (e.g., log the error)
            //return redirect()->back()->with('error', 'File upload failed. Please try again.');
            // return Response::json(['error' => $exception->getMessage()], 500)->header('Accept', 'application/json'); 
        }

        return false;
    }

    /**
     * API to register the user
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        try {
            if ($request->isMethod('POST')) {
                $rules = [
                    'username' => 'unique:users|required',
                    'email'    => 'unique:users|required',
                    'password' => 'required|max:10|min:6',
                ];
                $input     = $request->only('username', 'email', 'password');
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    return response()->json(['success' => false, 'error' => $validator->messages()]);
                }
                $username = $request->username;
                $email    = $request->email;
                $password = $request->password;
                $user     = User::create(['username' => $username, 'email' => $email, 'password' => Hash::make($password)]);
                return response(['message' => 'User succesfully created'], 201);
            }
        } catch (\Exception $ex) {
            Log::channel('error')->error('Method Not Allowed');
            return response(['message' => 'The POST method is supported for this route'], 405);
        }
    }

    /**
     * Generating access token for authentication via login
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        try {
            if ($request->isMethod('POST')) {
                $credentials = Validator::make(
                    $request->all(),
                    [
                        'username' => ['required'],
                        'password' => ['required'],
                    ]
                );
                $validated = $credentials->validated();  // Retrieve the validated input
                $validateduserName = $credentials->safe()->only(['username']);
                $validateduserPassword = $credentials->safe()->only(['password']);
                $validatedPassword = implode(" ", $validateduserPassword);
                $user = User::where('username', $validateduserName)->first();
                if (!$user || !Hash::check($validatedPassword, $user->password)) {
                    return response(['message' => 'The provided credentials are incorrect.'], 401);
                }
                $token = $user->createToken('token');
                return response(
                    [
                        'token' => $token->plainTextToken
                    ],
                    201
                );
            }
        } catch (\Exception $ex) {
            Log::channel('error')->error('Method Not Allowed');
            return response(['message' => 'The POST method is supported for this route'], 405);
        }
    }
    /**
     * Return RXCUI for the list of prescribed and positive detected medications  
     *
     * @param  string $drug_name
     * @return integer $rxcui
     */
    public function getRxcui($drug_name)
    {
        try {
            $response = Http::get(
                Config::get('nih.rxcui'),
                [
                    'name' => $drug_name
                ]
            );
            $content = $response->getBody()->getContents();
            $res = json_decode($content, true);
            if (isset($res['idGroup']['rxnormId'][0]) && is_array($res)) {
                $rxcui = $res['idGroup']['rxnormId'][0];
                return $rxcui;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * Return drug-drug interactions based on the RXCUIs list
     *
     * @param  string $rxcuisList
     * @return array $raw_data
     */

    public function getDDI($rxcuisList)
    {
        try {
            $response = Http::get(Config::get('nih.rxcuiList') . $rxcuisList);
            $data = $response->getBody()->getContents();
            $raw_data = json_decode($data, true);
            return $raw_data;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * To get TGT(Ticket Granting Ticket) 
     *
     * @return $ticketURL
     */

    public function getTGT()
    {
        try {
            $response = Http::asForm()->post(
                Config::get('nih.utsApiKey'),
                [
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
            foreach ($dom->getElementsByTagName('form') as $input) {
                // Show the attribute action
                $ticketURL = $input->getAttribute('action');
            }
            return $ticketURL;
        } catch (\Illuminate\Http\Client\ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    /**
     * To get service ticket using TGT generated
     *
     * @return $serviceTicket
     */
    public function getServiceTicket()
    {
        try {
            $ticketGeneratedURL = $this->getTGT();
            $response = Http::asForm()->post(
                $ticketGeneratedURL,
                [
                    'service' => Config::get('nih.umlUrl'),
                ]
            );
            $serviceTicket = $response->getBody()->getContents();
            return $serviceTicket;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * Crosswalk between ICD code and MeSH code using NIH
     *
     * @param  string $icd
     * @return array $response_data
     */
    public function getMeshCode($icd)
    {
        try {
            $serviceTicket =  $this->getServiceTicket();
            $response = Http::get(Config::get('nih.crosswalk') . $icd . '?targetSource=MSH&ticket=' . $serviceTicket);
            $content = $response->getBody()->getContents();
            $response_data = json_decode($content, true);
            return $response_data;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * Get boxed warning based on substance name
     *
     * @param  string $drug_name
     * @return array $bw_data
     */

    public function getBoxedWarning($drug_name)
    {
        try {
            $todayDate = Carbon::now()->format('Y-m-d');
            $dateArray    = explode('-',  $todayDate); //breaks a string into an array
            $resultDate = implode("", $dateArray);
            $fromDate = config('nih.fromDate');
            $time = $fromDate . '+TO+' . $resultDate;

            $response = Http::get(Config::get('nih.fdaBaseUrl') . $drug_name . '+AND+_exists_:boxed_warning&sort=effective_time:[' . $time . ']&limit=1');
            $content = $response->getBody()->getContents();
            $bw_data = json_decode($content, true);

            if (isset($bw_data['results'][0]) && is_array($bw_data)) {
                return $bw_data['results'][0];
            } else {
                return false;
            }
        } catch (\Illuminate\Http\Client\ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * Get contraindication conditions - list of drugs from NIH for a MeSH code  
     *
     * @param  string $meshCode
     * @return array $dataSets
     */

    public function getConditions($meshCode)
    {
        try {
            $response = Http::get(Config::get('nih.conditions') . $meshCode . '&relaSource=MEDRT&rela=CI_with');
            $response_data = $response->getBody()->getContents();
            $dataSets = json_decode($response_data, true);
            return $dataSets;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * Function to sort the listing of parent drugs and metabolites 
     *
     * @param  string $arrayWithoutSorting
     * @return array $sortedArrayListing
     */

    public function insertValueAtPosition($arrayWithoutSorting)
    {

        $arrayListingWithoutSorting = array();

        foreach ($arrayWithoutSorting as $key => $arrayWithoutSortingResult) {
            //'description' contains information of metabolite for a test
            if (isset($arrayWithoutSortingResult['description']) && !empty($arrayWithoutSortingResult['description'])) {
                foreach ($arrayWithoutSorting as $keyName => $resultantValue) {
                    if (strpos($arrayWithoutSortingResult['description'], $keyName)) {
                        $arrayListingWithoutSorting[$keyName][$key] = $arrayWithoutSortingResult;
                    }
                }
            }
        }

        foreach ($arrayListingWithoutSorting as $key => $value) {
            $arrayListingWithoutSorting[$key][$key] = $arrayWithoutSorting[$key];
        }
        $sortedArrayListing = array();
        foreach ($arrayListingWithoutSorting as $keyData => $valueResult) {
            if (is_array($valueResult)) {
                $reverseResult = array_reverse($valueResult);
                foreach ($reverseResult as $keyResult => $setval) {
                    $sortedArrayListing[$keyResult] = $setval;
                }
            }
        }

        if (!empty($sortedArrayListing)) {
            foreach ($sortedArrayListing as $keyData => $sortedArrayListingData) {
                unset($arrayWithoutSorting[$keyData]);
            }
        }

        return array_merge($sortedArrayListing, $arrayWithoutSorting);
    }

    /**
     * POST generated PDF reports with orderCode,reportStatus and PDF URL to Dendi Order Reports 
     *
     * @param  string $orderCode, $pdfReport, $reportStatus
     * @return array $responseData
     */

    public function postReportToDendi($orderCode, $pdfReport, $reportStatus)
    {
        try {
            $response = Http::withHeaders(['Authorization' => config('nih.token')])->post('https://newstar.dendisoftware.com/api/v1/orders/reports/', [
                'external_url' => $pdfReport,
                'report_status' => $reportStatus,
                'order_code' => $orderCode,
                'trigger_webhook' => false,
            ]);
            $content = $response->getBody()->getContents();
            $responseData = json_decode($content, true);
            return $responseData;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    public function strposa($haystack, $needles = array(), $offset = 0)
    {
        $chr = array();
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false) $chr[$needle] = $res;
        }
        if (empty($chr)) return false;
        return min($chr);
    }

    /**
     * Get order test results containing all tests with results for an order using Dendi Order Test Results
     *
     * @param $orderCode
     * @return array $testResults
     */

    public function getOrderTestResults($orderCode = '')
    {
        try {
            $meds_detected = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareTestResults') . $orderCode);
            $response_data = $meds_detected->getBody()->getContents();
            $resultData = json_decode($response_data, true);
            $testResults = $resultData['results'][0]['tests'];
            return $testResults;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    private function orderTestResultsFromPanels($testPanels): array
    {
        $orderTestResults = array();
        foreach ($testPanels as $panel) {
            foreach ($panel["results"] as $result) {

                //TODO: ducktap : stratus, prev api was in below format
                //  $result["test_type"] = trim($panel["panel_name"]); // TODO: alt_panel_name is empty on some cases

                //TODO: ducktap: recent api give data format.
                $result["test_type"] = trim($result["test_description"]); // TODO: alt_panel_name is empty on some cases

                $result["testmethod_name"] = trim($panel["testmethod_name"]);

                //remove "\t" and others from  flag ""
                $result["result_medication"] = str_replace("\t", "", $result["result_medication"]);

                if ($result["testmethod_name"] == "SafeDrugs") {
                    $orderTestResults[] = $result;
                } else // TODO: Remove this else block
                {
                    $orderTestResults[] = $result;
                }
            }
        }
        return $orderTestResults;
    }

    /**
     * List of prescribed medications and their metabolites UUIDs 
     *
     * @param  array $medicationUuids
     * @return array $this->uuids
     */

    public function fetchAllUuids($uuids, $medicationUuids = [])
    {
        try {
            foreach ($medicationUuids as $medicationUuid) {
                array_push($uuids, $medicationUuid);
                $this->fetchRecursiveMetabolites($uuids, $medicationUuid);
            }
        } catch (Exception $error) {
            //TODO: log here.
        }

        return $uuids;
    }

    /**
     * To get medication names using medication UUID from Dendi Test Targets API 
     *
     * @param  string $medicationUuid
     * @return string $uuidName
     */

    public function fetchNameFromUuid($medicationUuid = '')
    {

        try {
            $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId') . $medicationUuid);
            $responseData = $metaboliteName->getBody()->getContents();
            $resultSet = json_decode($responseData, true);
            $uuidName = $resultSet['name'];
            return $uuidName;
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * To find metabolites of metabolites recursively for a test UUID using Dendi Test Target API  
     *
     * @param  string $medicationUuid
     * @return array $this->uuids
     */

    public function fetchRecursiveMetabolites($uuids, $medicationUuid = '')
    {

        try {
            $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId') . $medicationUuid);
            $responseData = $medications_name->getBody()->getContents();
            $resultSet = json_decode($responseData, true);

            $metabolitesIds = $resultSet['metabolites'];

            if (!empty($metabolitesIds)) {
                foreach ($metabolitesIds as $metaboliteUuid) {
                    // push in $this->uuids array with (prescribedUuid ++ metaboliteUuid)
                    array_push($uuids, $medicationUuid . '++' . $metaboliteUuid);
                    if (!in_array($metaboliteUuid, $uuids) && !in_array($metaboliteUuid, $metabolitesIds)) {
                        $this->fetchRecursiveMetabolites($uuids, $metaboliteUuid);
                    }
                }
            }
        } catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }

        return $uuids;
    }

    private function medicationNamesFromMedications($medications): array
    {
        $medicationName = array();
        foreach ($medications as $medication) {
            $medicationName[] = $medication["name"];
        }

        return $medicationName;
    }

    public function ackToStratusAfterPDFgeneration($orderCode, $show_log = 0)
    {
        ini_set('max_execution_time', '0');
        if ($show_log == 1) {
            dump("ack api called");
        }

        try {
            
            $ack_api_url = Config::get('nih.stratus_ack_api') . "/" . $orderCode . "/ack";
            $response = Http::withBasicAuth(Config::get('nih.stratusUserName'), Config::get('nih.stratusPassword'))
                ->post($ack_api_url);

            if ($show_log == 1) {
                dump("after ack api called url: " . $ack_api_url . " -> use/pass: " . Config::get('nih.stratusUserName') . " , " . Config::get('nih.stratusPassword'));
                dump("response: " . $response);
            }

            if ($response->successful()) {
                $data = $response->json(); // Assuming the API returns JSON data
                if ($data['status'] == "ok") {
                    return true;
                } else {
                    Log::channel('error')->error('ack api called failed for orderCode : ' . $orderCode . " errorMessage: " . $data['message']);
                }

                //dump("success");
                //return response()->json(['message' => 'Requested orderCodes stored into DB successfully']);
            } else {
                Log::channel('error')->error('ack api called failed for orderCode : ' . $orderCode);
                //dump("failed");
                //return response()->json(['error' => 'Failed to fetch orderCode from the Stratus API'], $response->status());
            }
        } catch (Exception $ex) {
            Log::channel('error')->error('ack api called failed for orderCode : ' . $orderCode . "excetion: " . $ex->getMessage());
            //dump("exception: " .$ex->getMessage());
            //return $ex->getMessage();
        }

        return false;
    }
    public function postPDF_to_Stratus($fileName, $orderCode)
    {
        // $fileName = "generate-236400-59d5fc52-94eb-42f3-aa55-db8a6318f1ca.pdf";
        $pdfFilePath = public_path() . "/pdf" . "/" . $fileName;

        if (!file_exists($pdfFilePath)) {
            abort(404);
        }

        $fileContents = file_get_contents($pdfFilePath);
        $base64encoded = base64_encode($fileContents);

        $requestEndpoint = Config::get('nih.stratus_post_base64_report_pdf_api'); //"https://testapi.stratusdx.net/interface/result/upload/base64";

        $reqestBody = [
            "order" => [
                "accession_id" => $orderCode,
                "alt_order_id" => $orderCode
            ],
            "result_report_base64" => $base64encoded
        ];

        $user = Config::get('nih.stratusUserName_base64_api'); //"test_purpose_only";
        $pass = Config::get('nih.stratusPassword_base64_api'); // "test-purpose-only";

        $response = Http::withBasicAuth($user, $pass)
            ->post($requestEndpoint, $reqestBody);

        // return $response->body();
        return $response;
    }

    //**  Below common pdf generation service to call from ->  a. api and b. background service */
    public function common_getPDFReport_service($orderCode, $show_log = 0)
    {
        $data;
        $response;
        $pdf;

        try {

            ini_set('max_execution_time', '0');

            //1. Get PDF generating data.
            if ($show_log == 1) {
                dump('called method : getPdfDataForStratus ');
            }

            $this->Log_scheduler_info('-> pdf generation - call getPdfDataForStratus for order_Code: ' . $orderCode);

            $response = $this->getPdfDataForStratus($orderCode, $show_log);
            $this->Log_scheduler_info('-> pdf generation - got response for order_Code: ' . $orderCode);
            $this->Log_scheduler_info('-> pdf generation - response : ' . json_encode($response));

            if ($show_log == 1) {
                dump('response: ');
                dump($response);
            }

            if ($response == null || $response["content"] == null || $response["status"] == "500") {
                return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
                Log::channel('error')->error('generate pdf conent failed for orderCode : ' . $orderCode);
            } else {
                // Log::channel('error')->error('generate pdf conent failed for orderCode : ' . $orderCode);
            }

            $this->Log_scheduler_info('got pdf data: ' . json_encode($response));

            // 2. Create PDF
            $data = $response["content"];
            $pdf = PDF::loadView('generatePdfReport', $data)->setPaper('a1', 'portrait');
            $pdf->getDomPDF()->set_option("enable_php", true);
            $fileName = "generate-$orderCode.pdf";

            $this->Log_scheduler_info('path 1: ' . public_path() . "/pdf/$fileName");
            $this->Log_scheduler_info('path 1: ' . public_path() . "/pdf/$fileName");


            file_put_contents(public_path() . "/pdf/$fileName", $pdf->output());

            $fileUrl = URL::to('/') . "/pdf/$fileName";

            Storage::disk('local')->put('public/pdf/' . $fileName, $pdf->output());
            $file_path = \Storage::url($fileName);

            $this->Log_scheduler_info('pdf created: ' . $fileName);

            // 3. Upload to "Stratus"
            $response_StratusUpdated =  $this->postPDF_to_Stratus($fileName, $orderCode);
            if ($show_log == 1) {
                dump('response_StratusUpdated: ');
                dump($response_StratusUpdated);
            }
            $this->Log_scheduler_info('pdf uploaded to stratus: ' . $response_StratusUpdated);

            // 4. Upload at S3
            $url = "";
            if (env('UPLOAD_PDF_TO_S3') == 'yes') {
                if ($this->uploadToS3($fileName)) {
                    $url = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' . $fileName;
                }
                if ($show_log == 1) {
                    dump('S3 url: ');
                    dump($url);
                }
                $this->Log_scheduler_info('pdf uploaded to s3 url: ' . $url);
            }

            // 5. ack to Stratus
            $this->Log_scheduler_info('do ack ');
            if ($response_StratusUpdated["status"] == "ok") {
                //NOTE: ack to stratus to remove from request queue.
                $response_ack = true;
                if (env('SEND_ACK_TO_STRATUS') == 'yes') {
                    $response_ack =  $this->ackToStratusAfterPDFgeneration($orderCode);
                    if ($show_log == 1) {
                        dump('response_ack: ');
                        dump($response_ack);
                    }
                    $this->Log_scheduler_info('ack done: orderCode' . $orderCode);
                } else {
                    if (
                        env('SEND_ACK_TO_STRATUS_NO_1') == $orderCode || env('SEND_ACK_TO_STRATUS_NO_2') == $orderCode || env('SEND_ACK_TO_STRATUS_NO_3') == $orderCode
                        || env('SEND_ACK_TO_STRATUS_NO_4') == $orderCode || env('SEND_ACK_TO_STRATUS_NO_5') == $orderCode || env('SEND_ACK_TO_STRATUS_NO_6') == $orderCode
                    ) {
                        // don't ack, keep reserve
                    } else {
                        $response_ack =  $this->ackToStratusAfterPDFgeneration($orderCode);
                        if ($show_log == 1) {
                            dump('response_ack: ');
                            dump($response_ack);
                        }
                    }
                }

                if ($response_ack == true) {
                    return Response::json(['url' => $url], 200)->header('Accept', 'application/json');
                } else {
                    return Response::json(['error' => "ack to stratus failed."], 500)->header('Accept', 'application/json');
                }

                $this->Log_scheduler_info('ack done 2: orderCode' . $orderCode);
            } else {
                $this->Log_scheduler_info('ack failed : orderCode' . $orderCode);

                Log::channel('error')->error('Upload pfd report to Stratus DB - api called failed for orderCode : ' . $orderCode);
                return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
            }

            //Unset Data
            $response = '';
            $data = '';
            $pdf = '';
            unset($response);
            unset($data);
            unset($pdf);
            //GC collection
            gc_collect_cycles();
            $this->Log_scheduler_info('error response: orderCode' . $orderCode);
            return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
        } catch (Exception $exx) {
            //GC collection
            gc_collect_cycles();
            $this->Log_scheduler_info('$$$$ --> Exception->' . $exx->getMessage());
        }
        finally {
            //Unset Data
            unset($response);
            unset($data);
            unset($pdf);
            
            //GC collection
            gc_collect_cycles();
        }
    }

    public function getPDFReport_from_background_service($order_code)
    {
        ini_set('max_execution_time', '0');
        try {
            if ($order_code == "") {
                return "The 'order_code' attribute field is required.";
            } else {
                $orderCode = $order_code;
            }

            $this->Log_scheduler_info('-> pdf generation - call common_getPDFReport_service for order_Code: ' . $orderCode);

            return $this->common_getPDFReport_service($orderCode, 0);
        } catch (Exception $ex) {
            Log::channel('error')->error('Exception: ' . $ex->getMessage());
            return Response::json(['error' => "Report generation failed"], 500)->header('Accept', 'application/json');
            //return $ex->getMessage();
        }
    }
}
