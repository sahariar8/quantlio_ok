<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use DOMDocument;
use Exception;
use Response;
use DB;
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
use Illuminate\Support\Facades\Storage;
use URL;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;


/**
 *   Class ContraindicationsAPI
 *   This class is used to create a new user, generate authentication token 
 *   and calculate contraindications
 *   @package App\Http\Controllers
*/ 
class ContraindicationsAPI extends Controller
{
    public $uuids = []; 
    public $singleUuid = '';
    
  
    /**
     * API to register the user
     *
     * @param  Request $request
     * @return JsonResponse
    */
    public function register(Request $request)
    {
        try{
            if ($request->isMethod('POST') ) {    
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
        }catch(\Exception $ex){
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
        try{
            if ($request->isMethod('POST') ) {    
                $credentials = Validator::make(
                    $request->all(), [
                    'username' => ['required'],
                    'password' => ['required'],
                    ]
                );
                $validated = $credentials->validated();  // Retrieve the validated input
                $validateduserName = $credentials->safe()->only(['username']);  
                $validateduserPassword = $credentials->safe()->only(['password']);
                $validatedPassword = implode(" ", $validateduserPassword);
                $user = User::where('username', $validateduserName)->first();
                if(!$user || !Hash::check($validatedPassword, $user->password)) {
                    return response(['message' => 'The provided credentials are incorrect.'], 401);
                }
                $token = $user->createToken('token');
                return response(
                    [
                    'token' => $token->plainTextToken
                    ], 201
                );
            }
        }catch(\Exception $ex){
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
        try{
            $response = Http::get(
                Config::get('nih.rxcui'), [
                'name' => $drug_name
                ]
            );
            $content = $response->getBody()->getContents();
            $res = json_decode($content, true);
            if(isset($res['idGroup']['rxnormId'][0]) && is_array($res)) {
                $rxcui = $res['idGroup']['rxnormId'][0];
                return $rxcui;
            }
        }catch (\Illuminate\Http\Client\ConnectionException $ex) {
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
        try{
            $response = Http::get(Config::get('nih.rxcuiList').$rxcuisList);   
            $data = $response->getBody()->getContents();
            $raw_data = json_decode($data, true);
            return $raw_data;
        }catch (ConnectionException $ex){
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
    /**
     * To get service ticket using TGT generated
     *
     * @return $serviceTicket
     */
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

    /**
     * Crosswalk between ICD code and MeSH code using NIH
     *
     * @param  string $icd
     * @return array $response_data
     */
    public function getMeshCode($icd)
    {
        try{
            $serviceTicket =  $this->getServiceTicket();
            $response = Http::get(Config::get('nih.crosswalk').$icd.'?targetSource=MSH&ticket='.$serviceTicket);
            $content = $response->getBody()->getContents();
            $response_data = json_decode($content, true);
            return $response_data;
        }
        catch (ConnectionException $ex) {
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
        try{
            $todayDate = Carbon::now()->format('Y-m-d');
            $dateArray    = explode('-',  $todayDate); //breaks a string into an array
            $resultDate = implode("", $dateArray);
            $fromDate = config('nih.fromDate');
            $time = $fromDate.'+TO+'.$resultDate;

            $response = Http::get(Config::get('nih.fdaBaseUrl').$drug_name.'+AND+_exists_:boxed_warning&sort=effective_time:['.$time.']&limit=1');           
            $content = $response->getBody()->getContents();
            $bw_data = json_decode($content, true);
            
            if(isset($bw_data['results'][0]) && is_array($bw_data)) {
                return $bw_data['results'][0];
            }else{
                return false;
            }
        }catch (\Illuminate\Http\Client\ConnectionException $ex) {
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
        try{
            $response = Http::get(Config::get('nih.conditions').$meshCode.'&relaSource=MEDRT&rela=CI_with');
            $response_data = $response->getBody()->getContents();
            $dataSets = json_decode($response_data, true);
            return $dataSets;
        }
        catch (ConnectionException $ex) {
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

    public function insertValueAtPosition($arrayWithoutSorting) {

        $arrayListingWithoutSorting = array();

        foreach($arrayWithoutSorting as $key => $arrayWithoutSortingResult){
             //'description' contains information of metabolite for a test
            if(isset($arrayWithoutSortingResult['description']) && !empty($arrayWithoutSortingResult['description'])){
                foreach($arrayWithoutSorting as $keyName => $resultantValue) {
                    if(strpos($arrayWithoutSortingResult['description'], $keyName)) {
                        $arrayListingWithoutSorting[$keyName][$key] = $arrayWithoutSortingResult;
                    }
                }
            }
        }
         
        foreach($arrayListingWithoutSorting as $key => $value){
            $arrayListingWithoutSorting[$key][$key] = $arrayWithoutSorting[$key];
        }
        $sortedArrayListing = array();
        foreach($arrayListingWithoutSorting as $keyData => $valueResult){
            if(is_array($valueResult)) {
                $reverseResult = array_reverse($valueResult);
                foreach($reverseResult as $keyResult => $setval){
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
        try{
            $response = Http::withHeaders(['Authorization' => config('nih.token')])->post('https://newstar.dendisoftware.com/api/v1/orders/reports/', [
                'external_url' => $pdfReport,
                'report_status' => $reportStatus,
                'order_code' => $orderCode,
                'trigger_webhook' => false,
            ]);
            $content = $response->getBody()->getContents();
            $responseData = json_decode($content, true);
            return $responseData;
        }
        catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    public function strposa($haystack, $needles=array(), $offset=0) {
        $chr = array();
        foreach($needles as $needle) {
                $res = strpos($haystack, $needle, $offset);
                if ($res !== false) $chr[$needle] = $res;
        }
        if(empty($chr)) return false;
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
        try{
            $meds_detected = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareTestResults').$orderCode);
            $response_data = $meds_detected->getBody()->getContents();
            $resultData = json_decode($response_data, true);
            $testResults = $resultData['results'][0]['tests'];
            return $testResults;
        }
        catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }
    
    /**
     * List of prescribed medications and their metabolites UUIDs 
     *
     * @param  array $medicationUuids
     * @return array $this->uuids
    */

    public function fetchAllUuids ($medicationUuids = []) {
        foreach ($medicationUuids as $medicationUuid) {
            array_push($this->uuids, $medicationUuid);
            $this->fetchRecursiveMetabolites($medicationUuid);
        }
    }

    /**
     * To get medication names using medication UUID from Dendi Test Targets API 
     *
     * @param  string $medicationUuid
     * @return string $uuidName
    */

    public function fetchNameFromUuid ($medicationUuid = '') {

        try{
            $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medicationUuid);
            $responseData = $metaboliteName->getBody()->getContents();
            $resultSet = json_decode($responseData, true);
            $uuidName = $resultSet['name'];
            return $uuidName;
        }
        catch (ConnectionException $ex) {
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

    public function fetchRecursiveMetabolites ($medicationUuid = '') {

        try{
            $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medicationUuid);
            $responseData = $medications_name->getBody()->getContents();
            $resultSet = json_decode($responseData, true);

            $metabolitesIds = $resultSet['metabolites'];

            if ( !empty($metabolitesIds) ) {
                foreach ($metabolitesIds as $metaboliteUuid) {
                    // push in $this->uuids array with (prescribedUuid ++ metaboliteUuid)
                    array_push($this->uuids, $medicationUuid . '++' . $metaboliteUuid); 
                    if(!in_array($metaboliteUuid,$this->uuids) && !in_array($metaboliteUuid,$metabolitesIds)){
                        $this->fetchRecursiveMetabolites($metaboliteUuid);
                    }
                }  
            }
        }
        catch (ConnectionException $ex) {
            Log::channel('error')->error('Problem in fetching data from requested URL'); 
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    /**
     * calculate the interactions between prescribed medications,
     * detected medications and ICD codes and generate PDF report
     * @param $request
     * @return array $responseResult
    */

    public function getInteractions(Request $request)
    {
    
        if ($request->isMethod('POST') ) {    
            $url = $request->fullUrl();
            $payload = $request->input();
            try{
              
                $rules = array('order_code' => 'required');
                $request->validate($rules,array('required' => 'The :attribute field is required.')); 

                //to receive webhook JSON payload to get order code
                $webhookPayloadData     = WebhookPayload::create(['url' => $url, 'payload' => $payload]); 
                $dataSet = json_decode($webhookPayloadData, true);
               
                $orderCode = $dataSet['payload']['order_code'];

            }catch (\Illuminate\Validation\ValidationException $e ) {
           
                $validationError = $e->errors();
                foreach ($rules as $key => $value ) {
                    $arrImplode[] = implode( ', ', $validationError[$key] );
                }
                $message = implode(', ', $arrImplode);
                // Populate the respose array for the JSON
                $arrResponse = array(
                    'order_code' => $message,
                );
                Log::channel('error')->error('The :attribute field is required.'); 
                return response()->json($arrResponse);
            }

            // Get the required information from Dendi Order API using order code
            $response = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareOrders').$orderCode);
            $response_data = $response->getBody()->getContents();
            $dataSet = json_decode($response_data, true);
            
            if(!empty($dataSet['results'][0])){
                $icdCodeArray = $dataSet['results'][0]['icd10_codes'];
                $accountName = $dataSet['results'][0]['account']['name'];
                $inHouseLabLocations = $dataSet['results'][0]['in_house_lab_locations'][0]['name'];
                $providerNpi = $dataSet['results'][0]['provider']['npi'];
                $receivedDate = $dataSet['results'][0]['received_date'];
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
                $medicationsListFromDendi = $dataSet['results'][0]['medication_uuids'];
                $query    = OrderDetail:: firstOrCreate(['order_code' => $orderCode]); 
                $queryHistory   =  OrderHistory:: create(['order_code' => $orderCode]); 
                $queryHistory   = OrderHistory::where('order_code',$orderCode)->update(['in_house_lab_location' => $inHouseLabLocations,'medications' => $medicationsListFromDendi,'account_name' => $accountName,'provider_name' => $providerFirstName,'patient_name' => $patientFirstName, 'accession' => $accession]);
                        
                /**
                 *  Crosswalk between ICD and MeSH code 
                 *  Array containing MeSH codes
                **/ 

                $meshFromUml = array();
                $meshCodeArray = array();
                $icdToMesh = array();
                $namesFromUml = array();
                $icdVariables = array();
                $umlMeshResult = array();
                $icdCodeValueWithNames = array();
                $testResults = $this->getOrderTestResults($orderCode);
                // Array containing the list of ICD codes
                if(!empty($icdCodeArray)){
                    foreach($icdCodeArray as $keys => $icd){
                        $icdCodes[] = $icd['full_code'];
                    }
                    if(!empty($icdCodes)){
                        foreach($icdCodes as $key => $value){
                            $meshCodeData = $this->getMeshCode($value);
                            if(array_key_exists('error', $meshCodeData) || empty($meshCodeData['result'])){
                                Log::channel('error')->error('MeSH code not found for '.$value); 
                                $icdVariable = str_replace( ".", '', $value);
                                $query    = IcdCode::where('icd','LIKE','%'.$icdVariable.'%')->first();
                                if(!empty($query)){
                                    $meshCode = $query->mesh; // ICD-MeSH from database
                                    array_push($icdToMesh,$meshCode);
                                    if(!empty($query->description)){
                                        $icdDescription = $query->description;
                                        $icdCodeValueWithName = $value ." " . $icdDescription;
                                        array_push($icdCodeValueWithNames,$icdCodeValueWithName);
                                    }
                                }else{
                                    array_push($icdCodeValueWithNames,$value); 
                                }
                            }else{
                                array_push($meshCodeArray,$meshCodeData);  // Array containing MeSH code information from UMLs
                                $condition = true;
                                foreach($meshCodeArray as $key => $mesh){
                                    $meshArray = array();
                                    $meshArray[] = $mesh['result'];
                                    if(!empty($mesh['result'])){
                                        $meshCode = $mesh['result'][0];
                                        array_push($meshFromUml,$meshCode['ui']);
                                    }
                                }
                                foreach($meshFromUml as $key => $meshValue){
                                    array_push($icdToMesh,$meshValue);
                                }
                                
                                foreach($meshArray as $resultName){
                                    if($condition == true){
                                        if(!empty($resultName)){
                                            foreach($resultName as $key => $nameValue){
                                              
                                                $namesFromUmls[$value][] = $nameValue['name'];
                                                foreach($namesFromUmls as $icdCode => $namesFromUml){
                                                    $combinedName = implode(",",$namesFromUml);
                                                    $icdCodeValueWithName = $icdCode ." " . $combinedName;
                                                    $umlMeshResult[$value] = $icdCodeValueWithName;
                                                } 
                                            }
                                            array_push($icdCodeValueWithNames,$icdCodeValueWithName);
                                        }else{
                                            array_push($icdCodeValueWithNames,$value);
                                        }
                                    }
                                    $condition = false;
                                }
                            }
                        }
                    }
                    if(!empty($icdCodeValueWithNames)){
                        $icdCodeValues = implode(",\n", $icdCodeValueWithNames);
                    }else{
                        $icdCodeValues = [];
                    }
                    $icdToMeshCodes = implode(",",$icdToMesh);
                }else{
                    Log::channel('error')->error('ICD codes not available');  
                }
                
                // Array containing contraindication conditions (list of test names) for MeSH codes from database and UMLs
                $meshConditions = array();
                if(is_array($icdToMesh) && !empty($icdToMesh)){
                    foreach($icdToMesh as $key => $code) {
                        $conditionsResponseFromNihApi = $this->getConditions($code);
                        if(isset($conditionsResponseFromNihApi['drugMemberGroup']['drugMember'])){
                            $conditionsResponseFromNih = $conditionsResponseFromNihApi['drugMemberGroup']['drugMember'];
                            $conditionsArray = array();
    
                            foreach ($conditionsResponseFromNih as $key => $list) {
                                if(isset($list['minConcept']['name'])) {
                                    array_push($conditionsArray,$list['minConcept']['name']);
                                    $meshConditions[$code] = $conditionsArray;
                                }
                            }
                        }
                    } 
                }

                // Array containing Prescribed Medication names from Orders API medication UUIDs 
                $prescribedMedications = array();
                $prescribedMedicationIds = $dataSet['results'][0]['medication_uuids']; 
                if(!empty($prescribedMedicationIds))
                {
                    foreach($prescribedMedicationIds as $key => $prescribedMedicationId){
                        $testTargetData = $this->fetchNameFromUuid($prescribedMedicationId);
                        array_push($prescribedMedications,strtolower($testTargetData));
                    }
                }
               
                //  Filter the positive detected medications from Order test Results Dendi API
                if(!empty($testResults)){
                    foreach($testResults as $key => $value){
                        if($value['result']['result_qualitative'] == 'Detected'){
                            $detectedTest = strtolower($value['test_type']);
                            $detectedMedicationUUID = $value['uuid'];
                            // Array containing list of prescribed and positive detected medications
                            array_push($prescribedMedications, $detectedTest);
                        }
                    }
                }
        
                $query    = OrderDetail::where('order_code',$orderCode)->update(['icd_codes' => $icdCodes,'prescribed_medications' => $prescribedMedications,'account_name' => $accountName,'provider_name' => $providerFirstName ,'provider_npi' => $providerNpi,'in_house_lab_location' => $inHouseLabLocations,'order_test_result' => $testResults ]);
                $medicationRxcuis = array();
                $boxedWarningData = array();

                // Get RxCUI and Boxed Warning for the list of medications
                foreach ($prescribedMedications as $key => $value){
                    $medicationRxcuiValue = $this->getRxcui($value);
                    $boxedWarningValue = $this->getBoxedWarning($value); 
                    if(!empty($boxedWarningValue)) {
                        $boxedWarningResults[] = $boxedWarningValue;
                    }else{
                        Log::channel('error')->error('Boxed warning not found for '.$value);
                    }         
                    if(!empty($medicationRxcuiValue)) {
                        $medicationRxcuis[] =  $medicationRxcuiValue; 
                    }else{
                        Log::channel('error')->error('Failed to get rxcui '.$value);
                    }
                }

                if(!empty($medicationRxcuis)) {
                
                    // Boxed Warning response specific to drug name
                    if(!empty($boxedWarningResults)) {
                        foreach($boxedWarningResults as $bw_key => $bw_value){
                            $arrVal = $bw_value['boxed_warning'][0];
                            $open_fda = $bw_value['openfda'];
                            if(isset($open_fda) && !empty($open_fda)){
                                $boxedWarningData[$bw_key]['substance_name'] = $bw_value['openfda']['substance_name'];
                                $boxedWarningData[$bw_key]['boxed_warning'] =  $arrVal;  
                            }else{
                                $boxedWarningData[$bw_key]['boxed_warning'] =  $arrVal;   
                            }  
                        }
                    }
                
                    // Filter the medicines $prescribedMedications (prescribed + positive detected medication) from calculated conditions( $meshConditions )
                    $arrayResult = array();
                    $condition = true;
                    $contraindicationComments = array();
                   
                    if(!empty($meshConditions)){
                        foreach($meshConditions as $keys => $values)
                        { 
                            foreach($values as $key => $value)
                            { 
                               
                                if(in_array(strtolower($value), $prescribedMedications)) {
                                    if($condition == true ) {
                                        foreach($meshCodeArray as $key => $mesh)
                                        {
                                            $resultArray[] = $mesh['result']; 
                                            foreach($resultArray as $nkeys => $nvalues)
                                            { 
                                                if(isset($nvalues[$key])){
                                                    if($keys == $nvalues[$key]['ui']) {
                                                        $arrayResult[$key]['CI_with'] = $nvalues[0]['name'];
                                                    }
                                                }
                                            } 
                                        } 
                                    }
                                  
                                    $condition = false;
                                    foreach($icdCodes as $key => $valueCode){
                                        $meshCodeData = $this->getMeshCode($valueCode);
                                        if(array_key_exists('error', $meshCodeData) || empty($meshCodeData['result'])){
                                            Log::channel('error')->error('MeSH code not found for '.$valueCode); 
                                            $icdVariable = str_replace( ".", '', $valueCode);
                                            
                                            if(isset($icdVariable) && !empty($icdVariable)){
                                            $query    = IcdCode::where('icd','LIKE','%'.$icdVariable.'%')->first();
                                                if(!empty($query->description)){
                                                    $arrayResult[$value] = "[CI]" .$query->description; 
                                                }
                                            }
                                        }
                                    }
                                }   
                            } 
                        }
                    }
    
                    $rxcuis_string = implode('+', $medicationRxcuis);  //  returns a string from the elements of an array  
                    $ddi_row_data = $this->getDDI($rxcuis_string);
                    $descriptionArray = array();
                    $responseResult = array();

                    // Response array containing drug-drug interactions with both drug names and description
                    if(!empty($ddi_row_data)){
                        if (array_key_exists("fullInteractionTypeGroup", $ddi_row_data)) {
                            $type = $ddi_row_data['fullInteractionTypeGroup'][0]['fullInteractionType'];
                            $detected_med_uuids = array();
                            foreach ( $type as $key => $value ) 
                            {
                                $drugName = $value['interactionPair'][0]['interactionConcept'][0]['minConceptItem']['name'];
                                $drugInteractedWith = $value['interactionPair'][0]['interactionConcept'][1]['minConceptItem']['name'];
                                $description = $value['interactionPair'][0]['description'];

                                $descriptionArray['drug_interacted_with'] = $drugInteractedWith;
                                $descriptionArray['description'] = $description;
                                $responseResult[$drugName][] = $descriptionArray;
                                
                            }
                        }else{
                            Log::channel('error')->error('No drug-drug interactions found');
                        }
                    }else{
                        Log::channel('error')->error('No data found');
                    }
                    $query = OrderDetail::where('order_code',$orderCode)->update(['drug_drug_interactions' => $responseResult,'contraindicated_conditions' => $arrayResult,'boxed_warnings' => $boxedWarningData,'report_status' => '0']);

                    // Test Information from Order API
                    foreach($testPanel as $keyValue => $resultValue){
                        $testInformations[] = $resultValue['test_panel_type']['name'];
                        if(is_array($testInformations)){
                            $testInformation = implode(' , ', $testInformations);
                        }
                    }
                    $panelTestResult = array();
                    if(str_contains($testInformation, 'CareView360')){
                        $panelTests = DB::table('panel_tests')->get();
                        if(str_contains($testInformation, '4 Panel Urine Screen')){
                            $panelTestsArray = ['Amphetamines','Barbiturates','Benzodiazepines','Buprenorphines','Opiates'];
                            foreach($testResults as $key => $value){
                                if(in_array($value['test_type'],$panelTestsArray)){
                                    $panelTestName = $value['test_type'];
                                    $panelTestResult[$panelTestName] = $value['result']['result_qualitative'];
                                }
                            }
                        }
                        // Prescribed Medications based on order code
                        $medicationUids = $dataSet['results'][0]['medication_uuids']; 
                        $date = date_create($patientDOB);
                        $reportedDate = date_create($reported);
                        $collectedDate = date_create($collected);
                        $prescribedWithoutMetabolites = array();
            
                        $this->fetchAllUuids($medicationUids);
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
                            // $rawResult = $this->getOrderTestResults($orderCode);
                            
                            if ($prescribedUuid == $uuid) {
                                foreach($testResults as $key => $arrayValue){
                                    if($name == ucwords($arrayValue['test_type']) || $name == $arrayValue['test_type']){
                                        $finalArray[$this->singleUuid][$name] = $arrayValue['result'];
                                    }
                                }
                            }
                            else{
                                foreach($testResults as $key => $arrayValue){
                                    if($name == ucwords($arrayValue['test_type']) || $name == $arrayValue['test_type']){
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

                            foreach($medicationUids as $key => $prescribedMedicationId){
                            $medications_name = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$prescribedMedicationId);
                            $responseData = $medications_name->getBody()->getContents();
                            $resultSet = json_decode($responseData, true);
                            $medicineName = $resultSet['name'];
                            $prescribedMedsArray[$medicineName] = $resultSet['metabolites'];
                            }

                            foreach($prescribedMedsArray as $medicineName => $metabolitesArray){
                                array_push($medicineNamesArray,$medicineName);

                                if(!empty($metabolitesArray) && is_array($metabolitesArray)){
                                    foreach($metabolitesArray as $key => $metaboliteId){
                                        $metabolitesData = array();
                                        $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$metaboliteId);
                                        $responseData = $metaboliteName->getBody()->getContents();
                                        $resultSet = json_decode($responseData, true);
                                        array_push($metabolitesData,$resultSet['name']);
                                        $prescribedWithMetabolites[$medicineName] = $metabolitesData;
                                    }
                                }else{
                                    $prescribedWithoutMetabolites[$medicineName] = [];
                                }
                                $prescribedAndWithMetabolites = array_merge($prescribedWithMetabolites,$prescribedWithoutMetabolites);
                            }
                            if(!empty($medicineNamesArray)){
                                $medications = implode(' , ', $medicineNamesArray);
                            }
                        }else{
                            $medications = 'None';
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
                            $prescribedDetected = [];
                            $notPrescribedDetectedSorted = array();
                            $methData = array();
                            $quantitativeResult = '';
                            $quantitativeResultSpecificGravity = '';
                            $quantitativeResultCreatinine = '';
                            
                            foreach($testResults as $key => $value){
                
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
                                        if($value['test_type'] == 'D-Methamphetamine %' || $value['test_type'] == 'L-Methamphetamine %'){
                                            $methData[$value['test_type']] = $value['result'];
                                        }
                                        if($value['result']['result_qualitative'] == 'Detected'){
                                            $detectedMedicines[$testType] = $value['result'];
                                        }else{
                                            $notDetectedMedicines[$testType] = $value['result'];
                                        }
                                    }else{
                                        Log::channel('error')->error('PDF report is pending');
                                        return response(['message' => 'PDF report is pending'], 200);    
                                    }
                                }  
                            }
                           
                            $condition = true;
                            $prescribedNotPrescribedTests = array();
                            
                            foreach($notDetectedMedicines as $notDetectedName => $values){
                                if(!empty($medicineNamesArray) && !empty($finalArray)){
                                    if($condition == true){
                                        foreach($finalArray as $uuid => $data){
                                            foreach($data as $testName => $testResult){
                                                array_push($prescribedNotPrescribedTests,$testName);
                                            }
                                        } 
                                    }
                                    $condition = false;   
                                    if(!in_array($notDetectedName,$medicineNamesArray) && !in_array($notDetectedName,$prescribedNotPrescribedTests)){
                                        $notDetectednotPrescribed[$notDetectedName] = $values;  
                                    }
                                }
                                $notDetectednotPrescribed[$notDetectedName] = $values; 
                            }
                            $groupPrescribedDetected = array();
                            $groupNotPrescribedDetected = array();
                            $groupPrescribedNotDetected = array();
                            $sortedPrescribedDetected = array();
                            $sortedNotPrescribedDetected = array();
                            $sortedPrescribedNotDetected = array();
                            $prescribedTestDetected = array();
                            $prescribedNotTestDetected = array();

                            $nsaidClassTestArray = array();
                            $anticoagulantClassTestArray = array();

                            foreach($finalArray as $uuid => $individualArray) {
                                $valueCount = count($individualArray);
                                foreach ($individualArray as $finalTestName => $finalTestValue ) {
                                    if ($finalTestValue['result_qualitative'] == 'Detected') {
                                        $prescribedTestDetected[$uuid] = $finalArray[$uuid];
                                    }
                                } 
                            }
                            foreach($prescribedTestDetected as $testId => $testResult){
                                foreach($testResult as $testName => $testData){
                                    foreach($tests as $resultValue){
                                        if(ucwords($resultValue->dendi_test_name) == $testName || $resultValue->dendi_test_name == $testName){
                                            $class  = $resultValue->class;
                                            $testData['class'] = $class;  
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
                        foreach($prescribedNotTestDetected as $testId => $testResult){
                            foreach($testResult as $testName => $testData){
                                foreach($tests as $resultValue){
                                    if(ucwords($resultValue->dendi_test_name) == $testName || $resultValue->dendi_test_name == $testName){
                                        $class  = $resultValue->class;
                                        $testData['class'] = $class;  
                                    }
                                }
                                $prescribedNotDetected[$testName] = $testData;
                            }
                        }
                        foreach($prescribedDetected as $key => $prescribedDetectedResult){
                            if(isset($prescribedDetectedResult['class']) && !empty($prescribedDetectedResult['class'])){
                                $class = $prescribedDetectedResult['class'];
                                $groupPrescribedDetected[$class][$key] = $prescribedDetectedResult;

                                if(strtoupper($class) == 'NSAID'){
                                    $nsaidClassTestArray[$class][] = $key;
                                }
                                if(strtoupper($class) == 'ANTICOAGULANT'){
                                    $anticoagulantClassTestArray[strtoupper($class)][] = $key;
                                }
                            }
                        }
                
                        foreach($groupPrescribedDetected as $keys => $valuesSorted){
                            foreach($valuesSorted as $key => $sortedListing){
                                $sortedPrescribedDetected[$key] = $sortedListing;
                            }
                        }
                
                        foreach($detectedMedicines as $index => $value){
                            if(!empty($medicineNamesArray)){
                                if(!in_array(ucwords($index),$medicineNamesArray) && !array_key_exists($index,$prescribedDetected)){
                                    foreach($tests as $resultValue){
                                        if(ucwords($resultValue->dendi_test_name) == ucwords($index)){
                                            $descriptionTest  = $resultValue->description;
                                            $value['description'] = $descriptionTest;
                                            $class  = $resultValue->class;
                                            $value['class'] = $class;  
                                        }
                                    }
                                    $notPrescribedDetected[$index] = $value;
                                }
                            }else{
                                foreach($tests as $resultValue){
                                    if(ucwords($resultValue->dendi_test_name) == ucwords($index)){
                                        $descriptionTest  = $resultValue->description;
                                        $value['description'] = $descriptionTest;
                                        $class  = $resultValue->class;
                                        $value['class'] = $class;  
                                    }
                                }
                                $notPrescribedDetected[$index] = $value;
                            }
                        }
                        if (!empty($notPrescribedDetected)) {
                            $notPrescribedDetected = $this->insertValueAtPosition($notPrescribedDetected);
                        }
                        
                        foreach($notPrescribedDetected as $key => $notPrescribedDetectedResult){
                            if(isset($notPrescribedDetectedResult['class']) && !empty($notPrescribedDetectedResult['class'])){
                                $class = $notPrescribedDetectedResult['class'];
                                $groupNotPrescribedDetected[$class][$key] = $notPrescribedDetectedResult;

                                if(strtoupper($class) == 'NSAID'){
                                    $nsaidClassTestArray[$class][] = $key;
                                }
                                if(strtoupper($class) == 'ANTICOAGULANT'){
                                    $anticoagulantClassTestArray[strtoupper($class)][] = $key;
                                }

                            }
                        }
                        
                        foreach($groupNotPrescribedDetected as $keys => $valuesSorted){
                            foreach($valuesSorted as $key => $sortedListing){
                                $sortedNotPrescribedDetected[$key] = $sortedListing;
                            }
                        }
                        if (!empty($prescribedNotDetected)) {
                            $prescribedNotDetected = $this->insertValueAtPosition($prescribedNotDetected);
                        }
            
                        foreach($prescribedNotDetected as $key => $prescribedNotDetectedResult){
                            if(isset($prescribedNotDetectedResult['class']) && !empty($prescribedNotDetectedResult['class'])){
                                $class = $prescribedNotDetectedResult['class'];
                                $groupPrescribedNotDetected[$class][$key] = $prescribedNotDetectedResult;

                                if(strtoupper($class) == 'NSAID'){
                                    $nsaidClassTestArray[$class][] = $key;
                                }
                                if(strtoupper($class) == 'ANTICOAGULANT'){
                                    $anticoagulantClassTestArray[strtoupper($class)][] = $key;
                                }

                            }
                        }
                        
                        foreach($groupPrescribedNotDetected as $keys => $valuesSorted){
                            foreach($valuesSorted as $key => $sortedListing){
                                $sortedPrescribedNotDetected[$key] = $sortedListing;
                            }
                        }
                        $newArray = array();
                        $allSectionTest = array_keys(array_merge($sortedPrescribedNotDetected, $sortedPrescribedDetected, $sortedNotPrescribedDetected));
                        $abc = array();
                        if(!empty($medicineNamesArray)){
                            foreach($medicineNamesArray as $key => $value){
                                
                                foreach($responseResult as $keyResultName => $valueDataResult){
                                    foreach($valueDataResult as $keyData => $valueData){
                                        if(ucwords($value) == ucwords($keyResultName) && !array_key_exists(ucwords($keyResultName),$sortedPrescribedDetected) && !array_key_exists(ucwords($keyResultName),$sortedPrescribedNotDetected) && !array_key_exists(ucwords($keyResultName),$sortedNotPrescribedDetected)){
                                            if(array_key_exists($valueData['drug_interacted_with'],$responseResult)){
                                                $newArray[$value][] = $valueData['description'];
                                            }
                                        }
                                    }
                                }
                            }
                            foreach ($allSectionTest as $testName) {
                                foreach($newArray as $drugNameKey => $descriptionResults){
                                    foreach($descriptionResults as $descriptionResult){
                                        if(str_contains($descriptionResult,$testName)){
                                            $abc[$testName][$drugNameKey] = $descriptionResult;
                                        }
                                    }
                                }
                            }
                        }
                        $drugInteractedWithArray = array();
                        $dataResult = array();
                        $keywordDummyArray = [
                            'metabolism',
                            'efficacy',
                            'excretion',
                            'absorption',
                            'concentration',
                            'adverse effects',
                            'hepatotoxic',
                            'hypotension',
                            'hypoglycemic',
                            'hypoglycemia',
                            'methemoglobinemia',
                            'increased serum creatinine',  
                            'immunosuppressive activity',
                            'vasodilatory activity',
                            'nephrotoxicity',
                            'CNS Depression',
                            'CNS Depressant',
                            'arrhythmogenic',
                            'antihypertensive',
                            'hypotensive',    
                            'increase',
                            'decrease',                           
                        ];
        
                        if(!empty($responseResult)){
                            $keywords = DB::table('drug_interaction_keywords')->get();        
                            foreach ($responseResult as $drugName => $values) {
                                foreach($values as $nameKey => $resultDataValues){
                                    $drugNameValue = ucwords($drugName);
                                    $drugInteractedWith = ucwords($resultDataValues['drug_interacted_with']);
                                   
                                    foreach($keywords as $result){
                                        if (str_contains($resultDataValues['description'], $result->keywords) || str_contains($resultDataValues['description'], strtolower($result->keywords))){
                                            $drugInteractedWithArray[$drugNameValue][$result->keywords][] = $drugInteractedWith;
                                        }
                                    }
                                    if ( ( $this->strposa($resultDataValues['description'], $keywordDummyArray, 1) ) == false ) {
                                        $drugInteractedWithArray[$drugNameValue]['other'][] = $resultDataValues['description'];
                                    }
                                }
                            }
                        
                            if(!empty($abc)){
                                $keywords = DB::table('drug_interaction_keywords')->get();        
                                foreach ($abc as $drugName => $values) {
                                    foreach($values as $key => $resultDataValues){
                                        foreach($keywords as $result){
                                            if (str_contains($resultDataValues, $result->keywords) || str_contains($resultDataValues, strtolower($result->keywords))){
                                                $drugInteractedWithArray[$drugName][$result->keywords][] = $key;
                                            }
                                        }
                                        if ( ( $this->strposa($resultDataValues, $keywordDummyArray, 1) ) == false)  {
                                            $drugInteractedWithArray[$drugName]['other'][] = $resultDataValues;
                                        }
                                    }
                                }
                            }
                            foreach($drugInteractedWithArray as $keyName => $dataResultValue){
                
                                if(array_key_exists("adverse effects",$dataResultValue)){
                                    $dataResultValue['Increase Adverse Effects'] = $dataResultValue['adverse effects'];
                                    unset($dataResultValue['adverse effects']);
                                }
                                if(array_key_exists("hepatotoxic",$dataResultValue)){
                                    $dataResultValue['Hepatotoxicity'] = $dataResultValue['hepatotoxic'];
                                    unset($dataResultValue['hepatotoxic']);
                                }
                                if(array_key_exists("immunosuppressive activity",$dataResultValue)){
                                    $dataResultValue['Increased Immunosuppression'] = $dataResultValue['immunosuppressive activity'];
                                    unset($dataResultValue['immunosuppressive activity']);
                                }
                                if(array_key_exists("vasodilatory activity",$dataResultValue)){
                                    $dataResultValue['Increased Vasodilation'] = $dataResultValue['vasodilatory activity'];
                                    unset($dataResultValue['vasodilatory activity']);
                                }
                                if(array_key_exists("antihypertensive",$dataResultValue) && array_key_exists("decrease",$dataResultValue)){
                                    $dataResultValue['Decreased Antihypertensive Activity'] = $dataResultValue['antihypertensive'];
                                    unset($dataResultValue['antihypertensive']);
                                }
                                if(array_key_exists("arrhythmogenic",$dataResultValue) && array_key_exists("increase",$dataResultValue)){
                                    $dataResultValue['Increased Arrhythmogenic Activity'] = $dataResultValue['arrhythmogenic'];
                                    unset($dataResultValue['arrhythmogenic']);
                                }
                                if(array_key_exists("CNS Depression",$dataResultValue) && array_key_exists("increase",$dataResultValue)){
                                    $dataResultValue['Increased CNS Depression'] = $dataResultValue['CNS Depression'];
                                    unset($dataResultValue['CNS Depression']);
                                }
                                // if(array_key_exists("CNS Depressant",$dataResultValue) && array_key_exists("increase",$dataResultValue)){
                                //     $dataResultValue['Increased CNS Depression'] = $dataResultValue['CNS Depressant'];
                                //     unset($dataResultValue['CNS Depressant']);
                                // }
                                if(array_key_exists("hypotensive",$dataResultValue) && array_key_exists("increase",$dataResultValue)){
                                    $dataResultValue['Hypotension'] = $dataResultValue['hypotensive'];
                                    unset($dataResultValue['hypotensive']);
                                }
                                // if(array_key_exists("increase",$dataResultValue)){
                                //     $dataResultValue['Increased'] = $dataResultValue['increase'];
                                //     unset($dataResultValue['increase']);
                                // }
                                // if(array_key_exists("decrease",$dataResultValue)){
                                //     $dataResultValue['Decreased'] = $dataResultValue['decrease'];
                                //     unset($dataResultValue['decrease']);
                                // }
                                if(array_key_exists("concentration",$dataResultValue)){
                                    $dataResultValue['Concentration - Increase'] = $dataResultValue['concentration'];
                                    unset($dataResultValue['concentration']);
                                }
                                if(array_key_exists("other",$dataResultValue)){
                                    $v = $dataResultValue['other'];
                                    unset($dataResultValue['other']);
                                    $dataResultValue['other'] = $v;
                                }
                                
                                foreach($dataResultValue as $keyResult => $data){
                                    $keyword = ucwords($keyResult);
                                    
                                   if($keyResult != "other"){
                                        $dataResult[$keyName][$keyword] = implode(",",$data);
                                    }else{
                                        $dataResult[$keyName][$keyword] = implode(" ",$data);
                                    }
                                }
                            }
                        }
        
                        foreach($dataResult as $key => $valueDatas){
                            $output = implode(' - ', array_map(
                                function ($v, $k) { return sprintf("(%s) %s", $k, $v); },
                                $valueDatas,
                                array_keys($valueDatas)
                            ));
                            $contraindicationComments[$key] = "[DDI] " . $output; 
                        }
                        
                        $labLocation = DB::table('lab_locations')->where('location', 'Newstar Medical Laboratories - Atlanta')->first();
                        $labLocationTempe = DB::table('lab_locations')->where('location', 'Newstar Medical Laboratories - Tempe')->first();
                        $collection = collect($notDetectednotPrescribed);
                        
                        $chunks = $collection->chunk(12);
                        $chunks->all();
                        
                        // $queryUpdated = OrderDetail::where('order_code',$orderCode)->update(['report_status' => '1' ]);
                        $orders = DB::table('order_details')->select('order_code', 'report_status')->get();
                       
                        $metforminText = "if GFR <30 mL/min";
                        
                        Log::info("sortedPrescribedNotDetected");
                        Log::info($sortedPrescribedNotDetected);

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
                            'notPrescribedDetected' => $sortedNotPrescribedDetected,
                            'notDetectednotPrescribed' => $chunks,
                            'prescribedNotDetected' => $sortedPrescribedNotDetected,
                            'sortedPrescribedDetected' => $sortedPrescribedDetected,
                            'tests' => $tests,
                            'orders' => $orders,
                            'receivedDate' => date("m/d/Y", strtotime($receivedDate)),
                            'labLocation' => $labLocation,
                            'labLocationTempe' => $labLocationTempe,
                            'contraindicationComments' => $contraindicationComments,
                            'methData' => $methData,
                            'metforminText' => $metforminText,
                            'panelTests' => $panelTests,
                            'panelTestResult' => $panelTestResult,
                            'icdToMeshCodes' => $icdToMeshCodes,
                            'arrayResult' => $arrayResult,
                            'nsaidClassTestArray' => $nsaidClassTestArray,
                            'anticoagulantClassTestArray' => $anticoagulantClassTestArray
                        ]; 
                        $pdf = PDF::loadView('generatePDF', $data)->setPaper('a1', 'portrait');        
                        $pdf->getDomPDF()->set_option("enable_php", true);
                        $fileName = "uatOld-generate-$orderCode.pdf";
                        $filePath = 'pdf/' . $fileName;
                        Storage::disk('s3')->put($filePath , $pdf->output(), 'public');
                       // $url = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' .$filePath;
                        dd($url);
                        foreach($orders as $values){
                            if($values->order_code == $orderCode){
                                if($values->report_status == '1' ){
                                    $reportStatus = 'final';
                                }else{
                                    $reportStatus = 'amended';
                                }
                            }
                        }
            
                        /*$record = $this->postReportToDendi($orderCode, $url, $reportStatus);
                        return Response::json(['url' => $record], 200)->header('Accept', 'application/json');*/

                        // return Response::json(['url' => $url], 200)->header('Accept', 'application/json'); 
                    }else{
                        return response(['message' => 'Not a careview profile'], 200);    
                    }   
                }
            }else{
                return response()->json(['message' => 'No results for this order code'],200);
            }
        }
    }
}
