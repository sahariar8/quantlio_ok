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
use App\Models\IcdCode;
use Illuminate\Support\Facades\Storage;
use URL;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use App\Models\FdaTestDetail;
use App\Models\Lis_orders_details;
use App\Models\Lis_medication_details;
use App\Models\Lis_orders_test_results;

/**
 *   Class ContraindicationsAPI
 *   This class is used to create a new user, generate authentication token 
 *   and calculate contraindications
 *   @package App\Http\Controllers
*/ 
class ContraindicationsAPINew extends Controller
{

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

    public function getTestDetails($medName)
    {   
        try{
            
            $response = Http::get(Config::get('nih.fdaWarnings').$medName);           
            $content = $response->getBody()->getContents();
            $conditionsData = json_decode($content, true);
            $detailsFromFda = array();

            if(isset($conditionsData['results']) && is_array($conditionsData)) {
                if(isset($conditionsData['results'][0]['warnings_and_cautions'][0])){
                    $detailsFromFda['warnings_and_cautions'] = $conditionsData['results'][0]['warnings_and_cautions'][0];
                }
                if(isset($conditionsData['results'][0]['drug_interactions'][0])){
                    $detailsFromFda['drug_interactions'] = $conditionsData['results'][0]['drug_interactions'][0];
                }
                if(isset($conditionsData['results'][0]['contraindications'][0])){
                    $detailsFromFda['contraindications'] = $conditionsData['results'][0]['contraindications'][0];
                }
                if(isset($conditionsData['results'][0]['precautions'][0])){
                    $detailsFromFda['precautions'] = $conditionsData['results'][0]['precautions'][0];
                }
                if(isset($conditionsData['results'][0]['warnings'][0])){
                    $detailsFromFda['warnings'] = $conditionsData['results'][0]['warnings'][0];
                }
            }else{
                return false;
            }
            return $detailsFromFda;
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
            krsort($arrayListingWithoutSorting[$key]);
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
            $res = strpos(strtolower($haystack), strtolower($needle), $offset);
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
     * To get medication names using medication UUID from Dendi Test Targets API 
     *
     * @param  string $medicationUuid
     * @return string $resultSet
    */

    public function fetchResultFromUuid ($medicationUuid = '') {

        try{
            $metaboliteName = Http::accept('application/json')->withHeaders(['Authorization' => config('nih.token')])->get(Config::get('nih.dendiSoftwareId').$medicationUuid);
            $responseData = $metaboliteName->getBody()->getContents();
            $resultSet = json_decode($responseData, true);
            return $resultSet;
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
                $state = $dataSet['results'][0]['patient']['state'];

                if(!empty($icdCodeArray)){
                    foreach($icdCodeArray as $icdCodeArray_key => $icdCodeArray_value){
                        $icdCodes[] = $icdCodeArray_value['full_code'];
                    }
                }

                if(!empty($testPanel)){
                    foreach ($testPanel as $testPanel_key => $testPanel_value) {
                        $testPanelType[] = $testPanel_value['test_panel_type']['name'];
                    }
                }

                //save Lis orders API's response into "lis_orders_details" table.

                $updateOrcreateOrderDetails = Lis_orders_details::updateOrCreate(
                    ['order_code' => $orderCode],
                    ['in_house_lab_locations' => $inHouseLabLocations, 'patient_name' => $patientFirstName." ".$patientLastName, 'patient_dob' => $patientDOB, 'patient_gender' => $patientGender, 'patient_phone_number' => $patientPhone, 'account_name' => $accountName, 'provider_first_name' => $providerFirstName, 'provider_last_name' => $providerLastName, 'accession_number' => $accession, 'clia_sample_type' => $sampleName, 'sample_collection_date' => $collected, 'received_date' => $receivedDate, 'icd_codes' => $icdCodes, 'medication_uuids' => $medicationsListFromDendi, 'test_panel_type' => $testPanelType, 'state' => $state, 'reported_date' => $reported]
                );


                /*create or update record in order detail and order history table.*/
                //$query    = OrderDetail:: firstOrCreate(['order_code' => $orderCode]); 
                $createOrderHistory = OrderHistory::create(['order_code' => $orderCode,'in_house_lab_location' => $inHouseLabLocations,'medications' => $medicationsListFromDendi,'account_name' => $accountName,'provider_name' => $providerFirstName,'patient_name' => $patientFirstName, 'accession' => $accession]);

                //getOrderDetails from "lis_orders_details" table.
                //Note - Here, We can directly use Lis order API's response. But to manage different Lis we are using data from database.
                $getOrderDetails = Lis_orders_details::where(['order_code' => $orderCode])->get();
                $icd_codes = [];
                $prescribedMedicineIds = [];
                $in_house_lab_location = '';
                if(!empty($getOrderDetails)){
                    $icd_codes = $getOrderDetails[0]->icd_codes;
                    $prescribedMedicineIds = $getOrderDetails[0]->medication_uuids;
                    $test_panel_type = $getOrderDetails[0]->test_panel_type;

                    $patient_name = $getOrderDetails[0]->patient_name;
                    $patient_dob = date_create($getOrderDetails[0]->patient_dob);
                    $patient_gender = $getOrderDetails[0]->patient_gender;
                    $patient_phone = $getOrderDetails[0]->patient_phone_number;
                    $account_name = $getOrderDetails[0]->account_name;
                    $provider_name = $getOrderDetails[0]->provider_first_name.' '.$getOrderDetails[0]->provider_last_name;
                    $accession_number = $getOrderDetails[0]->accession_number;
                    $sample_type = $getOrderDetails[0]->clia_sample_type;
                    $sample_collection_date = date_create($getOrderDetails[0]->sample_collection_date);
                    $reportedDate = date_create($reported);
                    $in_house_lab_location = $getOrderDetails[0]->in_house_lab_locations;
                    $received_date = $getOrderDetails[0]->received_date;
                }

                if(!empty($prescribedMedicineIds)){
                    foreach ($prescribedMedicineIds as $prescribedMedicineIds_key => $prescribedMedicineIds_value) {
                        $prescribedMedicineId = $prescribedMedicineIds_value;
                        //Here, get result from dendi's test target API for each prescribed medicine id (which we are getting from dendi's order API) and get name of prescribed medicine.
                        $prescribedMedicineResult = $this->fetchResultFromUuid($prescribedMedicineId);
                        $prescribedMedicineName = $prescribedMedicineResult['name'];
                        $metaboliteIds = $prescribedMedicineResult['metabolites'];
                        $metaboliteId = '';
                        $metaboliteName = '';
                        if(!empty($metaboliteIds)){
                            foreach ($metaboliteIds as $metaboliteIds_key => $metaboliteIds_value) {
                                $metaboliteId = $metaboliteIds_value;
                                //Here, getting result from dendi's test target API for each metabolite id (which we are getting from dendi's test target API) and getting name of metabolite.
                                $metaboliteResult = $this->fetchResultFromUuid($metaboliteId);
                                $metaboliteName = $metaboliteResult['name'];

                                $updateOrCreateMedicationDetails = Lis_medication_details::updateOrCreate(
                                    ['order_code' => $orderCode,'medication_uuids' => $prescribedMedicineId,'metabolite_id' => $metaboliteId],
                                    ['medication_name' => $prescribedMedicineName, 'metabolite_name' => $metaboliteName]
                                );
                            }
                        }
                        else{
                            $updateOrCreateMedicationDetails = Lis_medication_details::updateOrCreate(
                                ['order_code' => $orderCode,'medication_uuids' => $prescribedMedicineId,'metabolite_id' => $metaboliteId],
                                ['medication_name' => $prescribedMedicineName, 'metabolite_name' => $metaboliteName]
                            );
                        }
                    }
                }

                //Here, getting results from dendi's test results API. 
                $testResults = $this->getOrderTestResults($orderCode);
                //save Lis test results API's response into "lis_orders_test_results" table.
                if(!empty($testResults)){
                    foreach ($testResults as $testResults_key => $testResults_value) {
                        $test_name = $testResults_value['test_type'];
                        $result_quantitative = $testResults_value['result']['result_quantitative'];
                        $result_qualitative = $testResults_value['result']['result_qualitative'];

                        $updateOrCreateOrdersTestResults = Lis_orders_test_results::updateOrCreate(
                            ['order_code' => $orderCode, 'test_name' => $test_name],
                            ['result_quantitative' => $result_quantitative, 'result_qualitative' => $result_qualitative]
                        );
                    }
                }

                $meshFromUml = array();
                $meshCodeArray = array();
                $icdToMesh = array();
                $umlMeshResult = array();
                $icdCodeValueWithNames = array();
                
                if(!empty($icd_codes)){
                    foreach($icd_codes as $key => $value){
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
                else{
                    Log::channel('error')->error('ICD codes not available');  
                }

                if(!empty($icdCodeValueWithNames)){
                    $icdCodeValues = implode(",\n", $icdCodeValueWithNames);
                }else{
                    $icdCodeValues = "";
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

                //Array containing Prescribed Medication names from "lis_medication_details" table.
                $prescribedMedicationDetails = Lis_medication_details::distinct()->where(['order_code' => $orderCode])->get(['medication_name']);

                $prescribedMedications = array();
                $prescribedMedicineNameArray = [];
                $metaboliteNameArray = [];
                if(!empty($prescribedMedicationDetails))
                {
                    foreach($prescribedMedicationDetails as $prescribedMedicationDetails_key => $prescribedMedicationDetails_value){
                        $prescribedMedicationName = $prescribedMedicationDetails_value->medication_name;
                        $prescribedMedicineNameArray[] = $prescribedMedicationDetails_value->medication_name;
                        array_push($prescribedMedications,strtolower($prescribedMedicationName));

                        $metaboliteDetails = Lis_medication_details::where(['order_code' => $orderCode, 'medication_name' => $prescribedMedicationName])->get(['metabolite_name']);
                        if(!empty($metaboliteDetails)){
                            foreach ($metaboliteDetails as $metaboliteDetails_key => $metaboliteDetails_value) {
                               $metaboliteNameArray[] = $metaboliteDetails_value->metabolite_name;
                            }
                        }
                    }
                }

                if(!empty($prescribedMedicineNameArray)){
                    $medications = implode(' , ', $prescribedMedicineNameArray);  
                }
                else{
                    $medications = 'None';   
                }
                
               
                //Filter the positive detected medications from Order test Results Dendi API
                //getOrderDetails from "lis_orders_test_results" table.
                //Note - Here, We can directly use Lis test results API's response i.e $testResults variable. But to manage different Lis we are using data from database.
                $getTestResults = Lis_orders_test_results::where(['order_code' => $orderCode])->get();
                if(!empty($getTestResults)){
                    foreach($getTestResults as $getTestResults_key => $getTestResults_value){
                        if($getTestResults_value->result_qualitative == 'Detected'){
                            $detectedTest = strtolower($getTestResults_value->test_name);
                            // Array containing list of prescribed and positive detected medications
                            array_push($prescribedMedications, $detectedTest);
                        }
                    }
                }

                $patientAge = DB::table('lis_orders_details')->selectRaw("TIMESTAMPDIFF(YEAR, `patient_dob`, current_date) AS age")->where('order_code', $orderCode)->get();
                $labLocations = DB::table('lab_locations')->get();

                $query    = Lis_orders_details::where('order_code',$orderCode)->update(['prescribed_medications' => $prescribedMedications ,'provider_npi' => $providerNpi,'order_test_result' => $testResults,'patient_age' => $patientAge[0]->age ]);

                if(!empty($labLocations)){
                    foreach($labLocations as $labLocation){
                        if($in_house_lab_location == $labLocation->location){
                            $query    = Lis_orders_details::where('order_code', $orderCode)->update(['location_id' => $labLocation->id]);
                        }
                    }
                }
                

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

                    /*code for getting [CI] starts here.*/
                    $arrayResult = array();
                    $condition = true;
                    $ddiComments = array();
                   
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
                                                        $arrayResult[$value] = "[CI] " .$nvalues[0]['name'];
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
                    /*code for getting [CI] ends here.*/

                    /*first part [DDI] code starts here.*/
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
                    /*first part [DDI] code ends here.*/

                    $query = Lis_orders_details::where('order_code',$orderCode)->update(['drug_drug_interactions' => $responseResult,'contraindicated_conditions' => $arrayResult,'boxed_warnings' => $boxedWarningData,'report_status' => '0']);

                    $testPanelType_str = implode(' , ', $test_panel_type);
                    $panelTestResult = array();
                    $panelTests = DB::table('panel_tests')->get();
                    if(str_contains($testPanelType_str, '4 Panel Urine Screen')){
                        $panelTestsArray = ['Amphetamines','Barbiturates','Benzodiazepines','Buprenorphines','Opiates'];
                        if(!empty($getTestResults)){
                            foreach($getTestResults as $getTestResults_key => $getTestResults_value){
                                if(in_array($getTestResults_value->test_name,$panelTestsArray)){
                                    $panelTestName = $getTestResults_value->test_name;
                                    $panelTestResult[$panelTestName] = $getTestResults_value->result_qualitative;
                                }
                            }
                        }
                    }

                    $testTypeArray = ['pH','Specific Gravity','Urine Creatinine'];
                    $eiaInformations = array();
                    $methData = array();
                    $quantitativeResult = '';
                    $quantitativeResultSpecificGravity = '';
                    $quantitativeResultCreatinine = '';
                    $detectedMedicines = [];
                    $notDetectedMedicines = [];

                    foreach($getTestResults as $key => $value){

                        if ( in_array($value->test_name,$testTypeArray)){
                            if($value->test_name == 'pH'){
                                $quantitativeResults = $value->result_quantitative;
                                $quantitativeResult = round($quantitativeResults, 2);
                            }elseif($value->test_name == 'Specific Gravity'){
                                $quantitativeResultSpecificGravitys = $value->result_quantitative;
                                $quantitativeResultSpecificGravity = round($quantitativeResultSpecificGravitys, 3);
                            }elseif($value->test_name == 'Urine Creatinine'){
                                $quantitativeResultCreatinines = $value->result_quantitative;
                                $quantitativeResultCreatinine = round($quantitativeResultCreatinines, 2);
                            }else{
                                $quantitativeResult = '';
                                $quantitativeResultSpecificGravity = '';
                                $quantitativeResultCreatinine = '';
                            }
                        }else{
                            if($value->result_qualitative == 'Negative' || $value->result_qualitative == 'Positive'){
                                $testTypeSpecimen = $value->test_name;
                                $eiaInformations[$testTypeSpecimen] = $value->result_quantitative;
                            }
                            elseif($value->result_qualitative == 'Not Detected' || $value->result_qualitative == 'Detected'){
                                if($value->test_name == 'D-Methamphetamine %' || $value->test_name == 'L-Methamphetamine %'){
                                    $methData[$value->test_name] = $value->result_quantitative;
                                }
                                if($value->result_qualitative == 'Detected'){
                                    $detectedMedicines[] = $value->test_name;
                                }else{
                                    $notDetectedMedicines[] = $value->test_name;
                                }
                            }else{
                                Log::channel('error')->error('PDF report is pending');
                                return response(['message' => 'PDF report is pending'], 200);    
                            }
                        }  
                    }

                    /*Code for getting all the information (class, test, cutoff, Results) except [CI] and [DDI] for All the sections ("NOT PRESCRIBED and DETECTED", "PRESCRIBED and NOT DETECTED", "PRESCRIBED and DETECTED", "NOT PRESCRIBED and NOT DETECTED") starts here.*/

                    $commonTestsLisAndDb = DB::select("SELECT test_details.dendi_test_name, test_details.class, test_details.description, test_details.LLOQ,test_details.LLOQ, ULOQ, lis_orders_test_results.result_quantitative, lis_orders_test_results.result_qualitative FROM test_details INNER JOIN lis_orders_test_results ON test_details.dendi_test_name = lis_orders_test_results.test_name WHERE lis_orders_test_results.order_code='".$orderCode."'");

                    $prescribedDetected = [];
                    $prescribedNotDetected = [];
                    $notPrescribedDetected = [];
                    $notPrescribedNotDetected = [];
                    if(!empty($commonTestsLisAndDb)){
                        foreach ($commonTestsLisAndDb as $commonTestsLisAndDb_key => $commonTestsLisAndDb_value) {
                            if (in_array($commonTestsLisAndDb_value->dendi_test_name, $prescribedMedicineNameArray) || in_array($commonTestsLisAndDb_value->dendi_test_name, $metaboliteNameArray)){
                                $parentOfThisMetabolite = '';
                                $metaboliteOfThisParent = [];
                                if($commonTestsLisAndDb_value->description){
                                    $description_array = explode(" ",$commonTestsLisAndDb_value->description);
                                    $parentOfThisMetabolite = $description_array[2];    
                                }
                                else{
                                    $metaboliteOfThisParent_query = Lis_medication_details::where(['order_code' => $orderCode, 'medication_name' => $commonTestsLisAndDb_value->dendi_test_name])->get(['metabolite_name']);
                                    if(!empty($metaboliteOfThisParent_query)){
                                        foreach ($metaboliteOfThisParent_query as $metaboliteOfThisParent_query_key => $metaboliteOfThisParent_query_value) {
                                           $metaboliteOfThisParent[] = $metaboliteOfThisParent_query_value->metabolite_name;
                                        }
                                    }
                                }

                                if(in_array($parentOfThisMetabolite, $detectedMedicines) || in_array($commonTestsLisAndDb_value->dendi_test_name, $detectedMedicines) || count(array_intersect($metaboliteOfThisParent, $detectedMedicines)) > 0){

                                    $prescribedDetected[$commonTestsLisAndDb_value->dendi_test_name] = array('class' => $commonTestsLisAndDb_value->class, 'description' => $commonTestsLisAndDb_value->description, 'LLOQ' =>  $commonTestsLisAndDb_value->LLOQ, 'ULOQ' => $commonTestsLisAndDb_value->ULOQ, 'result_quantitative' => $commonTestsLisAndDb_value->result_quantitative, 'result_qualitative' => $commonTestsLisAndDb_value->result_qualitative);
                                }
                                else{

                                    $prescribedNotDetected[$commonTestsLisAndDb_value->dendi_test_name] = array('class' => $commonTestsLisAndDb_value->class, 'description' => $commonTestsLisAndDb_value->description, 'LLOQ' =>  $commonTestsLisAndDb_value->LLOQ, 'ULOQ' => $commonTestsLisAndDb_value->ULOQ, 'result_quantitative' => $commonTestsLisAndDb_value->result_quantitative, 'result_qualitative' => $commonTestsLisAndDb_value->result_qualitative);
                                }
                            }
                            else{
                                if($commonTestsLisAndDb_value->result_qualitative == 'Detected'){
                                    $notPrescribedDetected[$commonTestsLisAndDb_value->dendi_test_name] = array('class' => $commonTestsLisAndDb_value->class, 'description' => $commonTestsLisAndDb_value->description, 'LLOQ' =>  $commonTestsLisAndDb_value->LLOQ, 'ULOQ' => $commonTestsLisAndDb_value->ULOQ, 'result_quantitative' => $commonTestsLisAndDb_value->result_quantitative, 'result_qualitative' => $commonTestsLisAndDb_value->result_qualitative);
                                }
                                if($commonTestsLisAndDb_value->result_qualitative == 'Not Detected'){
                                    $notPrescribedNotDetected[$commonTestsLisAndDb_value->dendi_test_name] = array('class' => $commonTestsLisAndDb_value->class, 'description' => $commonTestsLisAndDb_value->description, 'LLOQ' =>  $commonTestsLisAndDb_value->LLOQ, 'ULOQ' => $commonTestsLisAndDb_value->ULOQ, 'result_quantitative' => $commonTestsLisAndDb_value->result_quantitative, 'result_qualitative' => $commonTestsLisAndDb_value->result_qualitative);
                                }
                            }
                        }
                    }

                    $groupPrescribedDetected = array();
                    $groupNotPrescribedDetected = array();
                    $groupPrescribedNotDetected = array();
                    $sortedPrescribedDetected = array();
                    $sortedNotPrescribedDetected = array();
                    $sortedPrescribedNotDetected = array();

                    $nsaidClassTestArray = array();
                    $anticoagulantClassTestArray = array();

                    if (!empty($prescribedDetected)) {
                        ksort($prescribedDetected);
                        $prescribedDetected = $this->insertValueAtPosition($prescribedDetected);
                    
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
                    }

                    if (!empty($prescribedNotDetected)) {
                        ksort($prescribedNotDetected);
                        $prescribedNotDetected = $this->insertValueAtPosition($prescribedNotDetected);
                    
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
                    }

                    if (!empty($notPrescribedDetected)) {
                        ksort($notPrescribedDetected);
                        $notPrescribedDetected = $this->insertValueAtPosition($notPrescribedDetected);
                    
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
                    }

                    $collection = collect($notPrescribedNotDetected);
                    $chunks = $collection->chunk(12);
                    $chunks->all();

                    /*Code for getting all the information (class, test, cutoff, Results) except [CI] and [DDI] for All the sections ("NOT PRESCRIBED and DETECTED", "PRESCRIBED and NOT DETECTED", "PRESCRIBED and DETECTED", "NOT PRESCRIBED and NOT DETECTED") ends here.*/

                    /*second part [DDI] code starts here.*/
                    $newArray = array();
                    $allSectionTest = array_keys(array_merge($sortedPrescribedNotDetected, $sortedPrescribedDetected, $sortedNotPrescribedDetected));
                    $abc = array();

                    if(!empty($prescribedMedicineNameArray)){
                        foreach($prescribedMedicineNameArray as $key => $value){
                            
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
    
                    if(!empty($responseResult)){
                        $keywords = DB::table('keywords')->distinct()->get(); 
                        $primaryKeywords = DB::table('keywords')->distinct('primary_keyword')->pluck('primary_keyword');        
                        foreach ($responseResult as $drugName => $values) {
                            foreach($values as $nameKey => $resultDataValues){
                                $drugNameValue = ucwords($drugName);
                                $drugInteractedWith = ucwords($resultDataValues['drug_interacted_with']);
                               
                                foreach($keywords as $result){
                                    if(str_contains(strtolower($resultDataValues['description']), strtolower($result->primary_keyword)) && str_contains(strtolower($resultDataValues['description']), strtolower($result->secondary_keyword))){
                                        $drugInteractedWithArray[$drugNameValue][$result->resultant_keyword][] = $drugInteractedWith;
                                    }
                                }
                                if ( ( $this->strposa($resultDataValues['description'], $primaryKeywords) ) == false ) {
                                    $drugInteractedWithArray[$drugNameValue]['other'][] = $resultDataValues['description'];
                                }
                            }
                        }
                    
                        if(!empty($abc)){        
                            foreach ($abc as $drugName => $values) {
                                foreach($values as $key => $resultDataValues){
                                    foreach($keywords as $result){
                                        if (str_contains(strtolower($resultDataValues), strtolower($result->primary_keyword)) ){
                                            $drugInteractedWithArray[$drugName][$result->resultant_keyword][] = $key;
                                        }
                                    }
                                    if ( ( $this->strposa($resultDataValues, $primaryKeywords) ) == false)  {
                                        $drugInteractedWithArray[$drugName]['other'][] = $resultDataValues;
                                    }
                                }
                            }
                        }

                        $sortedKeywordsInteractions = array();
                        $drugInteractionsWithKeyword = array();

                        foreach($drugInteractedWithArray as $testName => $data){
                          
                            if (array_key_exists("other",$data))
                            {
                                $b[$testName]['other']= $data['other'];
                                unset($data['other']);
                                $drugInteractionsWithKeyword[$testName] = $data;
                                foreach($drugInteractionsWithKeyword as $k =>$v){
                                    foreach($b as $key => $result){
                                        if($k == $key){
                                            $sortedKeywordsInteractions[$testName] = array_merge($v,$result);
                                        }
                                        
                                    }
                                }
                            }else{
                                $sortedKeywordsInteractions[$testName] = $data;
                            }
                        }
                        
                        foreach($sortedKeywordsInteractions as $testName => $data){
                            foreach($data as $keyword => $keyResult){
                                if($keyword != "other"){
                                    $dataResult[$testName][$keyword] = implode(",",$keyResult);
                                }else{
                                    $dataResult[$testName][$keyword] = implode(" ",$keyResult);
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
                        $ddiComments[$key] = "[DDI] " . $output; 
                    }
                    /*second part [DDI] code ends here.*/

                    //$labLocations = DB::table('lab_locations')->get();
                    $tests = DB::table('test_details')->get();
                    $orders = DB::table('lis_orders_details')->select('order_code', 'report_status')->get();

                    //Creating url for Non CI case and storing test details into an array start.
                    $testDetailsFromFda = array();
                    $urlForFda = array();
                    if(isset($tests) && !empty($tests)){
                        foreach($tests as $resultValue){
                            foreach($allSectionTest as $key => $valueTestName){
                                if(ucwords($valueTestName) == ucwords($resultValue->dendi_test_name)|| $valueTestName == $resultValue->dendi_test_name){
                                    if($resultValue->class == 'NSAID' && strpos($icdCodeValues, 'Chronic kidney disease') !== false){
                                        if($valueTestName == "Salicylic Acid"){
                                            $urlForFda[] = Config::get('nih.newStarAnalytics')."aspirin";
                                            $testDetailsFromFda['aspirin'] = $this->getTestDetails('aspirin');
                                        }elseif(isset($resultValue->description) && !empty($resultValue->description)){
                                            $parentTestName = substr( $resultValue->description, strlen( 'Metabolite of ' ));
                                            $urlForFda[] = Config::get('nih.newStarAnalytics').$parentTestName;
                                            $testDetailsFromFda[$parentTestName] = $this->getTestDetails($parentTestName);
                                        }else{
                                            $urlForFda[] = Config::get('nih.newStarAnalytics').$valueTestName;
                                            $testDetailsFromFda[$valueTestName] = $this->getTestDetails($valueTestName);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //Creating url for Non CI case and storing test details into an array end.

                    //Creating url for CI and storing test details into an array start.
                    $metforminText = "if GFR <30 mL/min";
                    $finalCiResponse = array();
                    foreach($arrayResult as $testName => $ciDescription){
                        if(str_contains($ciDescription, '[CI]') && ucfirst($testName) == "Metformin" && str_contains($ciDescription, 'Chronic kidney disease')){
                            $ciResult = $ciDescription . "(" . $metforminText .")";
                            $finalCiResponse[$testName] = $ciResult;
                        }else{
                            $finalCiResponse[$testName] = $ciDescription;
                        }
                        
                        foreach($tests as $resultValue){
                           
                            if(ucwords($testName) == ucwords($resultValue->dendi_test_name)){
                            
                                if($resultValue->class !== "Diuretic"){
                                    /*$icdCodeStr = "E11.9 Type 2 diabetes mellitus without complications, D50.0 Iron deficiency
                                        anemia secondary to blood loss (chronic), M10.9 Gout, Z51.81, Z79.899, D51.0
                                        Anemia, Pernicious,Intrinsic Factor Deficiency, I10 Hypertension,Essential
                                        Hypertension, E03.9 Hypothyroidism,Myxedema, M12.9 Joint Diseases, E55.9
                                        Vitamin D Deficiency, E78.5 Hyperlipidemias";
                                        $icdCodeValueWithNames = explode(",",$icdCodeStr);*/
                                    if(ucwords($resultValue->class) == "Antidiabetic"){
                                        foreach ($icdCodeValueWithNames as $icdCodeValueWithNames_key => $icdCodeValueWithNames_val){
                                            if(strpos($icdCodeValueWithNames_val, "diabetes") !== false && strpos($icdCodeValueWithNames_val, " with ") !== false){
                                                if(ucwords($testName) == "Salicylic Acid"){
                                                    $urlForFda[] = Config::get('nih.newStarAnalytics')."aspirin";
                                                    $testDetailsFromFda['aspirin'] = $this->getTestDetails('aspirin');
                                                } elseif(isset($resultValue->description) && !empty($resultValue->description)){
                                                    $parentTestName = substr( $resultValue->description, strlen( 'Metabolite of ' ));
                                                    $urlForFda[] = Config::get('nih.newStarAnalytics').$parentTestName;
                                                    $testDetailsFromFda[$parentTestName] = $this->getTestDetails($parentTestName);
                                                }else{
                                                    $urlForFda[] = Config::get('nih.newStarAnalytics').$testName;
                                                    $testDetailsFromFda[$testName] = $this->getTestDetails($testName);
                                                }
                                                break;    
                                            }
                                        }
                                    }
                                    else{
                                        if(ucwords($testName) == "Salicylic Acid"){
                                            $urlForFda[] = Config::get('nih.newStarAnalytics')."aspirin";
                                            $testDetailsFromFda['aspirin'] = $this->getTestDetails('aspirin');
                                        } elseif(isset($resultValue->description) && !empty($resultValue->description)){
                                            $parentTestName = substr( $resultValue->description, strlen( 'Metabolite of ' ));
                                            $urlForFda[] = Config::get('nih.newStarAnalytics').$parentTestName;
                                            $testDetailsFromFda[$parentTestName] = $this->getTestDetails($parentTestName);
                                        }else{
                                            $urlForFda[] = Config::get('nih.newStarAnalytics').$testName;
                                            $testDetailsFromFda[$testName] = $this->getTestDetails($testName);
                                        }   
                                    }    
                                }
                            }
                        }
                    }
                    //Creating url for CI and storing test details into an array end.

                    //saving test details which we are getting from above block of code into our database table start.
                    foreach($testDetailsFromFda as $testName => $fdaTestDetails){
                      
                        if(isset($testName)){
                            $values = array('test_name' => $testName);
                            DB::table('test_details_from_fda')->insert($values);
                        }
                        if(isset($fdaTestDetails['precautions'])){
                            $query = FdaTestDetail::where('test_name', $testName)->update(['precautions' => $fdaTestDetails['precautions']]);
                        }
                        if(isset($fdaTestDetails['contraindications'])){
                            $query = FdaTestDetail::where('test_name', $testName)->update(['contraindications' => $fdaTestDetails['contraindications']]);
                        }
                        if(isset($fdaTestDetails['warnings_and_cautions'])){
                            $query = FdaTestDetail::where('test_name', $testName)->update(['warnings_and_cautions' => $fdaTestDetails['warnings_and_cautions']]);
                        }
                        if(isset($fdaTestDetails['drug_interactions'])){
                            $query = FdaTestDetail::where('test_name', $testName)->update(['drug_interactions' => $fdaTestDetails['drug_interactions']]);
                        }
                        if(isset($fdaTestDetails['warnings'])){
                            $query = FdaTestDetail::where('test_name', $testName)->update(['warnings' => $fdaTestDetails['warnings']]);
                        }
                    }
                    //saving test details which we are getting from above block of code into our database table end.
                    $data = [
                        'code' => $orderCode,
                        'patientName' => $patient_name,
                        'patientDOB' => date_format($patient_dob,"m/d/Y"),
                        'patientGender' => $patient_gender,
                        'patientPhone' => $patient_phone,
                        'account' => $accountName,
                        'provider' => $provider_name,
                        'accession' => $accession_number,
                        'sample_type' => $sample_type,
                        'reported' => date_format($reportedDate,"m/d/Y"),
                        'collected' => date_format($sample_collection_date,"m/d/Y"),
                        'in_house_lab_location' => $in_house_lab_location,
                        'testInformation' => $testPanelType_str,
                        'icdCode' => $icdCodeValues,
                        'icdCodeArr' => $icdCodeValueWithNames,
                        'medications' => $medications,
                        'quantitativeResult' => $quantitativeResult,
                        'quantitativeResultSpecificGravity' => $quantitativeResultSpecificGravity,
                        'quantitativeResultCreatinine' => $quantitativeResultCreatinine,
                        'notPrescribedDetected' => $sortedNotPrescribedDetected,
                        'notDetectednotPrescribed' => $chunks,
                        'prescribedNotDetected' => $sortedPrescribedNotDetected,
                        'sortedPrescribedDetected' => $sortedPrescribedDetected,
                        'tests' => $tests,
                        'orders' => $orders,
                        'receivedDate' => date("m/d/Y", strtotime($received_date)),
                        'labLocations' => $labLocations,
                        'ddiComments' => $ddiComments,
                        'methData' => $methData,
                        'metforminText' => $metforminText,
                        'panelTests' => $panelTests,
                        'panelTestResult' => $panelTestResult,
                        'arrayResult' => $finalCiResponse,
                        'urlForFda' => $urlForFda,
                        'nsaidClassTestArray' => $nsaidClassTestArray,
                        'anticoagulantClassTestArray' => $anticoagulantClassTestArray
                    ]; 
                    $pdf = PDF::loadView('generatePDFNew', $data)->setPaper('a1', 'portrait');        
                    $pdf->getDomPDF()->set_option("enable_php", true);
                    $fileName = "uat-generate-$orderCode.pdf";
                    $filePath = 'pdf/' . $fileName;
                    Storage::disk('s3')->put($filePath , $pdf->output(), 'public');
                  //  $url = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' .$filePath;
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
                }
            }else{
                return response()->json(['message' => 'No results for this order code'],200);
            }

        }

    }
}
