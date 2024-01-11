<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $states = DB::table('order_details')->distinct('state')->get('state');
        $providers = DB::table('order_details')->distinct('provider_name')->get('provider_name');
        $patients = DB::table('order_details')->distinct('patient_name')->get('patient_name');
        $genders = DB::table('order_details')->distinct('patient_gender')->get('patient_gender');
        $tests = DB::table('order_test_class_sections')->distinct('test')->get('test');
        $testClasses = DB::table('order_test_class_sections')->distinct('test_class')->get('test_class');
        $lablocations = DB::table('order_details')->distinct('account_name')->pluck('account_name');
    
        return view('home' , compact('states','providers','patients','tests','testClasses','genders','lablocations'));
    }
    
    /**
     * Fetch clinic names based on states selected
     *
     * @return \Illuminate\Http\Response
    */

    public function fetchClinic(Request $request)
    {
        $data['clinics'] = DB::table('order_details')->whereIn('state', $request->state)->distinct('account_name')
                                ->get(["account_name"]);
        return response()->json($data);
    }

     /**
     * Fetch provider names based on clinics selected
     *
     * @return \Illuminate\Http\Response
    */

    public function fetchProvider(Request $request)
    {
        $data['providers'] = DB::table('order_details')
                    ->where('provider_name', '<>', '', 'and')
                    ->whereIn('account_name', $request->clinic)
                    ->distinct('provider_name')
                    ->get(["provider_name"]);
        return response()->json($data);
    }

    /**
     * Fetch patient names based on providers selected
     *
     * @return \Illuminate\Http\Response
    */
    
    public function fetchPatient(Request $request)
    {
        $data['patients'] = DB::table('order_details')
                    ->where('patient_name', '<>', '', 'and')
                    ->whereIn('provider_name', $request->provider)
                    ->distinct('patient_name')
                    ->get(["patient_name"]);
        return response()->json($data);
    }
    
    public function exportExcel(Request $request){
        
        $validator = Validator::make($request->all(),[
            'ageTo' => 'gte:ageFrom',
            'reported_date_to' => 'nullable|after_or_equal:reported_date_from'
        ]);
        
        if($validator->fails()){
            return redirect('/home')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }
        $statesQuery = DB::table('order_details')->distinct('state')->where('state', '<>', '', 'and');
        if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
        }
        $states = $statesQuery->get('state');
        $final = array();
        $dataResult = array();
        
        foreach($states as $state){
            if(!empty($state->state)){
                $dataQuery = DB::table('order_test_class_sections')
                ->join('order_details', 'order_details.id', '=', 'order_test_class_sections.order_id')
                ->select('order_test_class_sections.test_class', 'order_test_class_sections.section_id', 'order_details.state')
                ->selectRaw('count(order_test_class_sections.test_class) as number_of_classes');

                if ($request->has('clinics')) {
                    $dataQuery->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataQuery->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataQuery->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataQuery->whereIn('test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataQuery->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataQuery->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataQuery->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataQuery->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }

                $dataQuery->groupBy('order_test_class_sections.section_id','order_test_class_sections.test_class','order_details.state')
                        ->having('state', '=', $state->state);

                if(sizeof($dataQuery->get())){
                    // $dataResult[] = $dataQuery->get();
                    array_push($dataResult,$dataQuery->get());
                }
            }   
        }
        
        if($dataResult){
            foreach($dataResult as $data){   
                $xyz = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['test_class'] = $values->test_class;
                    $testDetails['count'] = $values->number_of_classes;
    
                    $xyz[$values->section_id][]= $testDetails;
                    $final[$values->state] = $xyz;
                }
            }
            /* ---- REPORT BY STATE ------*/
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setWidth(120, 'pt');
            }
            
            $sheet->setCellValue('A1', 'Report by States')->getStyle('A1')->getFont()->setBold( true );
            $sheet->setCellValue('A2', 'State');
            $sheet->setCellValue('B2', 'Prescribed Detected');
            $sheet->setCellValue('E2', 'Not Prescribed Detected');
            $sheet->setCellValue('H2', 'Prescribed Not Detected');
    
            // $sheet
            // ->getStyle('A2:I2')
            // ->getFill()
            // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            // ->getStartColor()
            // ->setARGB('FFFF00');
            
            $rowCount = 2;
            $count = 3;
            $columnCountA = 3;
            $columnCountB = 3;
            $columnCountE = 3;
            $columnCountH = 3;
            $total = 0;
            $totalVal = 0;
            $totalValue = 0;
            if (!empty($final)) {
                foreach ($final as $state => $data) {
                    $loopStateValue = $state;
                    foreach ($data as $sectionId => $array){
                        foreach($array as $key =>$valueData){
                            $sheet->setCellValue('A'.$columnCountA, $state);  
                            if($sectionId == 1){
                                $sheet->setCellValue('B'.$columnCountB, $valueData['test_class']);
                                $sheet->setCellValue('C'.$columnCountB, $valueData['count']);
                                $columnCountB++;
                                $total = $total+$valueData['count'];
                                $sheet->setCellValue('C2', $total);

                            }elseif($sectionId == 2){
                                $sheet->setCellValue('E'.$columnCountE, $valueData['test_class']);
                                $sheet->setCellValue('F'.$columnCountE, $valueData['count']);
                                $columnCountE++;
                                $totalVal = $totalVal+$valueData['count'];
                                $sheet->setCellValue('F2', $totalVal);
                            }else{
                                $sheet->setCellValue('H'.$columnCountH, $valueData['test_class']);
                                $sheet->setCellValue('I'.$columnCountH, $valueData['count']);
                                $columnCountH++;
                                $totalValue = $totalValue+$valueData['count'];
                                $sheet->setCellValue('I2', $totalValue);
                            }
                        }
                        $rowCount++;
                    }
                    $maximumCount = max([$columnCountB, $columnCountE, $columnCountH]) + 1;
                    $columnCountA = $columnCountB = $columnCountE =  $columnCountH = $maximumCount;
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A2'.':I'.$maximumCount)->applyFromArray($styleArray);
                $reportByClinics = $maximumCount + 2;
                $lastRow = $reportByClinics;
            }

            /* ---- REPORT BY CLINIC ------*/

            $sheet->setCellValue('A'.$reportByClinics, 'Report by Clinics')->getStyle('A'.$reportByClinics)->getFont()->setBold( true );
            $reportByClinics++;
            $sheet->setCellValue('A'.$reportByClinics, 'State');
            $sheet->setCellValue('B'.$reportByClinics, 'Clinic');
            $sheet->setCellValue('C'.$reportByClinics, 'Prescribed Detected');
            $sheet->setCellValue('F'.$reportByClinics, 'Not Prescribed Detected');
            $sheet->setCellValue('I'.$reportByClinics, 'Prescribed Not Detected');
            $reportByClinics++;
            $countRowList = $countColumn = $stateRow = $reportByClinics;
            
            if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
            }
            $dataByClinics = array();
            foreach($states as $state){
                  
                $dataQuery = DB::table('order_test_class_sections')
                                ->join('order_details', 'order_details.id', '=', 'order_test_class_sections.order_id')
                                ->select('order_test_class_sections.test_class', 'order_test_class_sections.section_id', 'order_details.state','order_details.account_name','order_test_class_sections.test')
                                ->selectRaw('count(order_test_class_sections.test_class) as number_of_classes');
    
                if ($request->has('clinics')) {
                    $dataQuery->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataQuery->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataQuery->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataQuery->whereIn('test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataQuery->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataQuery->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataQuery->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataQuery->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                $dataQuery->groupBy('order_test_class_sections.section_id','order_test_class_sections.test_class','order_details.state','order_details.account_name','order_test_class_sections.test')
                        ->having('state', '=', $state->state);
    
                $accountDataResult[] = $dataQuery->get();
                }
               
                foreach($accountDataResult as $data){   
                    $dataResultant = array();
                    foreach($data as $key => $values){   
                        $testDetails = array();
                        $testDetails['test_class'] = $values->test_class;
                        $testDetails['count'] = $values->number_of_classes;
        
                        $dataResultant[$values->account_name][$values->section_id][]= $testDetails;
                        $dataByClinics[$values->state] = $dataResultant;
                    }
                }
                if (!empty($dataByClinics)) {
                  foreach ($dataByClinics as $state => $data) {
                      
                    foreach ($data as $accountName => $array){
                        $sheet->setCellValue('A'.$stateRow, $state); 
                        $sheet->setCellValue('B'.$stateRow, $accountName);

                        foreach($array as $sectionId => $valueResult){ 
                            foreach($valueResult as $valueData){
                                if($sectionId == 1){
                                    $sheet->setCellValue('C'.$reportByClinics, $valueData['test_class']);
                                    $sheet->setCellValue('D'.$reportByClinics, $valueData['count']);
                                    $reportByClinics++;
                                }elseif($sectionId == 2){
                                    $sheet->setCellValue('F'.$countRowList, $valueData['test_class']);
                                    $sheet->setCellValue('G'.$countRowList, $valueData['count']);
                                    $countRowList++;
                                }else{
                                    $sheet->setCellValue('I'.$countColumn, $valueData['test_class']);
                                    $sheet->setCellValue('J'.$countColumn, $valueData['count']);
                                    $countColumn++;
                                }
                            }
                        }
                        $maximumCount = max([$reportByClinics,$countRowList,$countColumn]) + 1;
                        $stateRow = $countRowList = $countColumn = $reportByClinics = $maximumCount;
                    } 
                  }
                  $styleArray = [
                      'borders' => [
                          'outline' => [
                              'borderStyle' => Border::BORDER_THIN,
                              'color' => ['argb' => '000'],
                          ],
                      ],
                  ];  
                  $sheet->getStyle('A'.$lastRow.':J'.$maximumCount)->applyFromArray($styleArray); 
                }
              $reportByProvider = $maximumCount + 2;
  
              $rowLastCount = $reportByProvider;
  
              /* ---- REPORT BY PROVIDER ------*/
              $sheet->setCellValue('A'.$reportByProvider, 'Report by Provider')->getStyle('A'.$reportByProvider)->getFont()->setBold( true );
              $reportByProvider++;
              $sheet->setCellValue('A'.$reportByProvider, 'State');
              $sheet->setCellValue('B'.$reportByProvider, 'Clinic');
              $sheet->setCellValue('C'.$reportByProvider, 'Provider');
              $sheet->setCellValue('D'.$reportByProvider, 'Prescribed Detected');
              $sheet->setCellValue('G'.$reportByProvider, 'Not Prescribed Detected');
              $sheet->setCellValue('J'.$reportByProvider, 'Prescribed Not Detected');
              $reportByProvider++;
              $columnCount = $countSection = $counts = $reportByProvider;
  
             
              if ($request->has('states')) {
                $statesQuery->wherein('state',  $request->input('states'));
              }
              $dataByProviders = array();
              foreach($states as $state){
                  
                  $dataQuery = DB::table('order_test_class_sections')
                                  ->join('order_details', 'order_details.id', '=', 'order_test_class_sections.order_id')
                                  ->select('order_test_class_sections.test_class', 'order_test_class_sections.section_id', 'order_test_class_sections.test', 'order_details.state','order_details.account_name','order_details.provider_name','order_details.patient_name','order_details.patient_gender')
                                  ->selectRaw('count(order_test_class_sections.test_class) as number_of_classes');
      
                    if ($request->has('clinics')) {
                        $dataQuery->whereIn('account_name', $request->input('clinics'));
                    }
                    if ($request->has('providers')) {
                        $dataQuery->whereIn('provider_name', $request->input('providers'));
                    }
                    if ($request->has('patients')) {
                        $dataQuery->whereIn('patient_name', $request->input('patients'));
                    }
                    if ($request->has('tests')) {
                        $dataQuery->whereIn('test', $request->input('tests'));
                    }
                    if ($request->has('classes')) {
                        $dataQuery->whereIn('test_class', $request->input('classes'));
                    }
                    if ($request->has('gender')) {
                        $dataQuery->whereIn('patient_gender', $request->input('gender'));
                    }
                    if ($request->has('ageFrom') && $request->has('ageTo') ) {
                        if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                            $dataQuery->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                        }
                    }
                    if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                        $dataQuery->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                    }
                  $dataQuery->groupBy('order_test_class_sections.section_id','order_test_class_sections.test_class','order_test_class_sections.test','order_details.state','order_details.account_name','order_details.provider_name','order_details.patient_name','order_details.patient_gender')
                          ->having('state', '=', $state->state);
              
                  $providerDataResult[] = $dataQuery->get();
              }
              // dd($providerDataResult);
  
              foreach($providerDataResult as $data){   
                  $dataResultant = array();
                  foreach($data as $key => $values){   
                      $testDetails = array();
                      $testDetails['test_class'] = $values->test_class;
                      $testDetails['count'] = $values->number_of_classes;
      
                      $dataResultant[$values->account_name][$values->provider_name][$values->section_id][]= $testDetails;
                      $dataByProviders[$values->state] = $dataResultant;
                    }
                }
                if (!empty($dataByProviders)) {
                    foreach ($dataByProviders as $state => $data) {
                        foreach ($data as $accountName => $array){
                          $sheet->setCellValue('A'.$columnCount, $state); 
                          $sheet->setCellValue('B'.$columnCount, $accountName);
                            foreach($array as $providerName => $details){
                              $sheet->setCellValue('C'.$columnCount, $providerName);
                                foreach($details as $sectionId => $valueResult){ 
                                  foreach($valueResult as $valueData){
                                      if($sectionId == 1){
                                          $sheet->setCellValue('D'.$reportByProvider, $valueData['test_class']);
                                          $sheet->setCellValue('E'.$reportByProvider, $valueData['count']);
                                          $reportByProvider++;
                                        }elseif($sectionId == 2){
                                          $sheet->setCellValue('G'.$countSection, $valueData['test_class']);
                                          $sheet->setCellValue('H'.$countSection, $valueData['count']);
                                          $countSection++;
                                        }else{
                                          $sheet->setCellValue('J'.$counts, $valueData['test_class']);
                                          $sheet->setCellValue('K'.$counts, $valueData['count']);
                                          $counts++;
                                        }
                                    }
                                }
                            }
                        $maximumCount = max([$reportByProvider,$columnCount,$countSection,$counts]) + 1;
                        $reportByProvider = $columnCount = $countSection = $counts = $maximumCount;
                        }
                    }
                  $styleArray = [
                      'borders' => [
                          'outline' => [
                              'borderStyle' => Border::BORDER_THIN,
                              'color' => ['argb' => '000'],
                            ],
                        ],
                    ];  
                    $sheet->getStyle('A'.$rowLastCount.':K'.$maximumCount)->applyFromArray($styleArray);
                }
            $writer = new Xlsx($spreadsheet);
            $fileName = 'data.xlsx';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="metrics_reporting.xls"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }
        else{
            return redirect('/home')->with('save_error', __('No Records for selected data'));
        }
            
    }
     /**
     * Generate report by unique patients
     *
     * @return \Illuminate\Http\Response
    */
    public function exportUniqueData(Request $request){
        
        $validator = Validator::make($request->all(),[
            'ageTo' => 'gte:ageFrom',
            'reported_date_to' => 'nullable|after_or_equal:reported_date_from'
        ]);
        
        if($validator->fails()){
            return redirect('/home')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }
    
        $statesQuery = DB::table('order_details')->distinct('state')->where('state', '<>', '', 'and');
        if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
        }
        $states = $statesQuery->get('state');
    
        $statesResult = array();
        $dataQuery = array();
        
        foreach($states as $state){
            
            $dataResult = DB::query()->fromSub(function ($query) {
                $query->from('order_details')
                ->join('order_test_class_sections', 'order_details.id', '=', 'order_test_class_sections.order_id')
                ->select('order_details.reported_date','order_details.patient_name','order_details.patient_gender','order_details.patient_age', 'order_details.patient_DOB','order_details.provider_name', 'order_test_class_sections.test_class','order_test_class_sections.test','order_details.account_name', 'order_details.state', 'order_test_class_sections.section_id')    
                ->groupBy('order_details.patient_name', 'order_details.patient_DOB', 'order_test_class_sections.test_class', 'order_details.state', 'order_test_class_sections.section_id');
            }, 't');

            if ($request->has('clinics')) {
                $dataResult->wherein('account_name', $request->input('clinics'));
            }
            if ($request->has('patients')) {
                $dataResult->whereIn('patient_name', $request->input('patients'));
            }
            if ($request->has('providers')) {
                $dataResult->whereIn('provider_name', $request->input('providers'));
            }
            if ($request->has('tests')) {
                $dataResult->whereIn('test', $request->input('tests'));
            }
            if ($request->has('classes')) {
                $dataResult->whereIn('test_class', $request->input('classes'));
            }
            if ($request->has('gender')) {
                $dataResult->whereIn('patient_gender', $request->input('gender'));
            }
            if ($request->has('ageFrom') && $request->has('ageTo') ) {
                if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                    $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                }
            }
            if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
            }
            
            $dataResult->select('*',DB::raw('count(*) as count'))
            ->groupBy('test_class','patient_name','patient_DOB','state','section_id')
            ->having('state', '=', $state->state);
            
            if(sizeof($dataResult->get())){
                array_push($dataQuery,$dataResult->get());
            }
        }
        
        if($dataQuery){
            foreach($dataQuery as $dataResult){
                foreach($dataResult as $data){   
                    $testDetails = array();
                    $testDetails['test_class'] = $data->test_class;
                    $testDetails['count'] = $data->count;
                    $statesResult[$data->state][$data->section_id][] = $testDetails;
                }
            }
            
            /* ---- REPORT BY STATE ------*/
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setWidth(120, 'pt');
            }
            
            $sheet->setCellValue('A1', 'Report by States')->getStyle('A1')->getFont()->setBold( true );
            $sheet->setCellValue('A2', 'State');
            $sheet->setCellValue('B2', 'Prescribed Detected');
            $sheet->setCellValue('E2', 'Not Prescribed Detected');
            $sheet->setCellValue('H2', 'Prescribed Not Detected');
    
            $rowCount = 2;
            $count = 3;
            $columnCountA = 3;
            $columnCountB = 3;
            $columnCountE = 3;
            $columnCountH = 3;
            $total = 0;
            $totalVal = 0;
            $totalValue = 0;
            if (!empty($statesResult)) {
                foreach ($statesResult as $state => $data) {
                    $loopStateValue = $state;
                    foreach ($data as $sectionId => $array){
                        foreach($array as $key =>$valueData){
                            $sheet->setCellValue('A'.$columnCountA, $state);  
                            if($sectionId == 1){
                                $sheet->setCellValue('B'.$columnCountB, $valueData['test_class']);
                                $sheet->setCellValue('C'.$columnCountB, $valueData['count']);
                                $columnCountB++;
                                $total = $total+$valueData['count'];
                                $sheet->setCellValue('C2', $total);
    
                            }elseif($sectionId == 2){
                                $sheet->setCellValue('E'.$columnCountE, $valueData['test_class']);
                                $sheet->setCellValue('F'.$columnCountE, $valueData['count']);
                                $columnCountE++;
                                $totalVal = $totalVal+$valueData['count'];
                                $sheet->setCellValue('F2', $totalVal);
                            }else{
                                $sheet->setCellValue('H'.$columnCountH, $valueData['test_class']);
                                $sheet->setCellValue('I'.$columnCountH, $valueData['count']);
                                $columnCountH++;
                                $totalValue = $totalValue+$valueData['count'];
                                $sheet->setCellValue('I2', $totalValue);
                            }
                        }
                        $rowCount++;
                    }
                    $maximumCount = max([$columnCountB, $columnCountE, $columnCountH]) + 1;
                    $columnCountA = $columnCountB = $columnCountE =  $columnCountH = $maximumCount;
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A2'.':I'.$maximumCount)->applyFromArray($styleArray);
                $reportByClinics = $maximumCount + 2;
                $lastRow = $reportByClinics;
            }
        
            /* ---- REPORT BY CLINIC ------*/
    
            $sheet->setCellValue('A'.$reportByClinics, 'Report by Clinics')->getStyle('A'.$reportByClinics)->getFont()->setBold( true );
            $reportByClinics++;
            $sheet->setCellValue('A'.$reportByClinics, 'State');
            $sheet->setCellValue('B'.$reportByClinics, 'Clinic');
            $sheet->setCellValue('C'.$reportByClinics, 'Prescribed Detected');
            $sheet->setCellValue('F'.$reportByClinics, 'Not Prescribed Detected');
            $sheet->setCellValue('I'.$reportByClinics, 'Prescribed Not Detected');
            $reportByClinics++;
            $countRowList = $countColumn = $stateRow = $reportByClinics;
             
            $dataQueryResult = array();
            foreach($states as $state){
                $dataResultAccount = DB::query()->fromSub(function ($query) {
                    $query->from('order_details')
                    ->join('order_test_class_sections', 'order_details.id', '=', 'order_test_class_sections.order_id')
                    ->select('order_details.reported_date','order_details.patient_name','order_details.patient_gender','order_details.patient_age','order_details.state','order_details.account_name','order_test_class_sections.test','order_details.provider_name','order_details.patient_DOB','order_test_class_sections.test_class', 'order_test_class_sections.section_id')    
                    ->groupBy('order_details.patient_name', 'order_details.patient_DOB', 'order_test_class_sections.test_class', 'order_details.state', 'order_test_class_sections.section_id');
                }, 't');
    
                if ($request->has('clinics')) {
                    $dataResultAccount->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResultAccount->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResultAccount->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResultAccount->whereIn('test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResultAccount->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResultAccount->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResultAccount->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResultAccount->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
    
                $dataResultAccount->select('*',DB::raw('count(*) as count'))
                ->groupBy('test_class','patient_name','patient_DOB','state','section_id','account_name')
                ->having('state', '=', $state->state);
                
                if(sizeof($dataResultAccount->get())){
                    array_push($dataQueryResult,$dataResultAccount->get());
                }
            }
            if($dataQueryResult){
                foreach($dataQueryResult as $accountDataResult){
                    foreach($accountDataResult as $key => $values){   
                
                        $testDetails = array();
                        $testDetails['test_class'] = $values->test_class;
                        $testDetails['count'] = $values->count;
                        $dataByClinics[$values->state][$values->account_name][$values->section_id][] = $testDetails;
                    }
                }
            }
    
            if (!empty($dataByClinics)) {
                foreach ($dataByClinics as $state => $data) {
                    
                    foreach ($data as $accountName => $array){
    
                        $sheet->setCellValue('A'.$stateRow, $state); 
                        $sheet->setCellValue('B'.$stateRow, $accountName);
    
                        foreach($array as $sectionId => $valueResult){ 
                            foreach($valueResult as $valueData){
                                if($sectionId == 1){
                                    $sheet->setCellValue('C'.$reportByClinics, $valueData['test_class']);
                                    $sheet->setCellValue('D'.$reportByClinics, $valueData['count']);
                                    $reportByClinics++;
                                }elseif($sectionId == 2){
                                    $sheet->setCellValue('F'.$countRowList, $valueData['test_class']);
                                    $sheet->setCellValue('G'.$countRowList, $valueData['count']);
                                    $countRowList++;
                                }else{
                                    $sheet->setCellValue('I'.$countColumn, $valueData['test_class']);
                                    $sheet->setCellValue('J'.$countColumn, $valueData['count']);
                                    $countColumn++;
                                }
                            }
                        }
                        $maximumCount = max([$reportByClinics,$countRowList,$countColumn]) + 1;
                        $stateRow = $countRowList = $countColumn = $reportByClinics = $maximumCount;
                    }
                    
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A'.$lastRow.':J'.$maximumCount)->applyFromArray($styleArray);
                
            }
            $reportByProvider = $maximumCount + 2;
            $rowLastCount = $reportByProvider;
    
    
            /* ---- REPORT BY PROVIDER ------*/
            $sheet->setCellValue('A'.$reportByProvider, 'Report by Provider')->getStyle('A'.$reportByProvider)->getFont()->setBold( true );
            $reportByProvider++;
            $sheet->setCellValue('A'.$reportByProvider, 'State');
            $sheet->setCellValue('B'.$reportByProvider, 'Clinic');
            $sheet->setCellValue('C'.$reportByProvider, 'Provider');
            $sheet->setCellValue('D'.$reportByProvider, 'Prescribed Detected');
            $sheet->setCellValue('G'.$reportByProvider, 'Not Prescribed Detected');
            $sheet->setCellValue('J'.$reportByProvider, 'Prescribed Not Detected');
            $reportByProvider++;
            $columnCount = $countSection = $counts = $reportByProvider;
    
            $dataQuery = array();
            foreach($states as $state){
    
                $dataResult = DB::query()->fromSub(function ($query) {
                    $query->from('order_details')
                    ->join('order_test_class_sections', 'order_details.id', '=', 'order_test_class_sections.order_id')
                    ->select('order_details.reported_date','order_details.patient_name','order_details.patient_gender', 'order_details.patient_age','order_details.patient_DOB', 'order_test_class_sections.test_class','order_test_class_sections.test','order_details.state', 'order_details.account_name','order_details.provider_name','order_test_class_sections.section_id')    
                    ->groupBy('order_details.patient_name', 'order_details.patient_DOB', 'order_test_class_sections.test_class', 'order_details.state', 'order_test_class_sections.section_id');
                }, 't');
        
                if ($request->has('clinics')) {
                    $dataResult->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResult->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResult->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResult->whereIn('test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResult->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResult->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                
                $dataResult->select('*',DB::raw('count(*) as count'))
                ->groupBy('test_class','patient_name','patient_DOB','state','section_id')
                ->having('state', '=', $state->state);
                
                if(sizeof($dataResult->get())){
                    array_push($dataQuery,$dataResult->get());
                }
    
            }
            
            if($dataQuery){
                foreach($dataQuery as $providerDataResult){
                    foreach($providerDataResult as $key => $values){   
                        $testDetails = array();
                        $testDetails['test_class'] = $values->test_class;
                        $testDetails['count'] = $values->count;
                        $dataByProviders[$values->state][$values->account_name][$values->provider_name][$values->section_id][] = $testDetails;
                    }
                }
            }
            if (!empty($dataByProviders)) {
                foreach ($dataByProviders as $state => $data) {
                    foreach ($data as $accountName => $array){
                    $sheet->setCellValue('A'.$columnCount, $state); 
                    $sheet->setCellValue('B'.$columnCount, $accountName);
                        foreach($array as $providerName => $details){
                        $sheet->setCellValue('C'.$columnCount, $providerName);
                            foreach($details as $sectionId => $valueResult){ 
                            foreach($valueResult as $valueData){
                                if($sectionId == 1){
                                    $sheet->setCellValue('D'.$reportByProvider, $valueData['test_class']);
                                    $sheet->setCellValue('E'.$reportByProvider, $valueData['count']);
                                    $reportByProvider++;
                                    }elseif($sectionId == 2){
                                    $sheet->setCellValue('G'.$countSection, $valueData['test_class']);
                                    $sheet->setCellValue('H'.$countSection, $valueData['count']);
                                    $countSection++;
                                    }else{
                                    $sheet->setCellValue('J'.$counts, $valueData['test_class']);
                                    $sheet->setCellValue('K'.$counts, $valueData['count']);
                                    $counts++;
                                    }
                                }
                            }
                        }
                    $maximumCount = max([$reportByProvider,$columnCount,$countSection,$counts]) + 1;
                    $reportByProvider = $columnCount = $countSection = $counts = $maximumCount;
                    }
                }
                $styleArray = [
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A'.$rowLastCount.':K'.$maximumCount)->applyFromArray($styleArray);
            }
            $writer = new Xlsx($spreadsheet);
            $fileName = 'data.xlsx';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="myfile.xls"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }else{
            return redirect('/home')->with('save_error', __('No Records for selected data'));
        }
    }
    public function exportDDIReport(Request $request){
        
        $validator = Validator::make($request->all(),[
            'ageTo' => 'gte:ageFrom',
            'reported_date_to' => 'nullable|after_or_equal:reported_date_from'
        ]);
        
        if($validator->fails()){
            return redirect('/home')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }
        $statesQuery = DB::table('order_details')->distinct('state');
        if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
        }
        $states = $statesQuery->get('state');
        $dataQuery = array();
        
        foreach($states as $state){
            if(!empty($state->state)){
                $dataResult = DB::table('order_details')
                ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                ->select('order_details.state','order_details.account_name','order_details.provider_name','drug_interactions.drug_class','drug_interactions.prescribed_test')
                ->selectRaw('count(drug_interactions.drug_class) as count')
                ->where('drug_interactions.drug_class', '<>', '', 'and');
                if ($request->has('clinics')) {
                    $dataResult->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResult->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResult->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResult->whereIn('prescribed_test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResult->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResult->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                $dataResult->groupBy('drug_interactions.drug_class')
                ->having('state', '=', $state->state);
                if(sizeof($dataResult->get())){
                    array_push($dataQuery,$dataResult->get());
                }
            }
        }
        
        $statesResult = array();
        if($dataQuery){
            foreach($dataQuery as $dataResults){
                foreach($dataResults as $dataResult){
                    $testDetails = array();
                    $testDetails['drug_class'] = $dataResult->drug_class;
                    $testDetails['count'] = $dataResult->count;
                    $statesResult[$dataResult->state][] = $testDetails;
                }
            }
            
            /* ---- REPORT BY STATE ------*/
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setWidth(120, 'pt');
            }
            
            $sheet->setCellValue('A1', 'Report by States')->getStyle('A1')->getFont()->setBold( true );
            $sheet->setCellValue('A2', 'State');
            $sheet->setCellValue('B2', 'Class');
    
            $rowCount = 2;
            $count = 3;
            $columnCountA = 3;
            $columnCountB = 3;
            $columnCountE = 3;
            $columnCountH = 3;
            $total = 0;
            $totalVal = 0;
            $totalValue = 0;
            if (!empty($statesResult)) {
                foreach ($statesResult as $state => $data) {
                    $loopStateValue = $state;
                    foreach ($data as $key => $valueData){
                        $sheet->setCellValue('A'.$columnCountA, $state);  
                        $sheet->setCellValue('B'.$columnCountB, $valueData['drug_class']);
                        $sheet->setCellValue('C'.$columnCountB, $valueData['count']);
                        $columnCountB++;
                        $total = $total+$valueData['count'];
                        $sheet->setCellValue('C2', $total);
                        $rowCount++;
                    }
                    $maximumCount = max([$columnCountB, $columnCountE, $columnCountH]) + 1;
                    $columnCountA = $columnCountB = $columnCountE =  $columnCountH = $maximumCount;
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A2'.':C'.$maximumCount)->applyFromArray($styleArray);
                $reportByClinics = $maximumCount + 2;
                $lastRow = $reportByClinics;
            }
            /* ---- REPORT BY CLINIC ------*/

            $sheet->setCellValue('A'.$reportByClinics, 'Report by Clinics')->getStyle('A'.$reportByClinics)->getFont()->setBold( true );
            $reportByClinics++;
            $sheet->setCellValue('A'.$reportByClinics, 'State');
            $sheet->setCellValue('B'.$reportByClinics, 'Clinic');
            $sheet->setCellValue('C'.$reportByClinics, 'Class');
            $reportByClinics++;
            $countRowList = $countColumn = $stateRow = $reportByClinics;
            $accountDataResult = array();
            
            foreach($states as $state){
    
                $dataResult = DB::table('order_details')
                    ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                    ->select('order_details.state','order_details.account_name','order_details.provider_name','drug_interactions.drug_class','drug_interactions.prescribed_test')
                    ->selectRaw('count(drug_interactions.drug_class) as count')
                    ->where('drug_interactions.drug_class', '<>', '', 'and');
                    
    
                if ($request->has('clinics')) {
                    $dataResult->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResult->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResult->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResult->whereIn('prescribed_test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResult->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResult->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                $dataResult->groupBy('drug_interactions.drug_class')
                ->having('state', '=', $state->state);
                if(sizeof($dataResult->get())){
                    array_push($accountDataResult,$dataResult->get());
                }
            }
            
            foreach($accountDataResult as $data){   
                $dataResultant = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['drug_class'] = $values->drug_class;
                    $testDetails['count'] = $values->count;

                    $dataResultant[$values->account_name][]= $testDetails;
                    $dataByClinics[$values->state] = $dataResultant;
                }
            }
            if (!empty($dataByClinics)) {
                foreach ($dataByClinics as $state => $data) {
                    
                    foreach ($data as $accountName => $array){

                        $sheet->setCellValue('A'.$stateRow, $state); 
                        $sheet->setCellValue('B'.$stateRow, $accountName);

                        foreach($array as $sectionId => $valueResult){ 
                           
                            $sheet->setCellValue('C'.$reportByClinics, $valueResult['drug_class']);
                            $sheet->setCellValue('D'.$reportByClinics, $valueResult['count']);
                            $reportByClinics++;
                            
                        }
                        $maximumCount = max([$reportByClinics]) + 1;
                        $stateRow = $countRowList = $countColumn = $reportByClinics = $maximumCount;
                    }
                }
                $styleArray = [
                      'borders' => [
                          'outline' => [
                              'borderStyle' => Border::BORDER_THIN,
                              'color' => ['argb' => '000'],
                            ],
                      ],
                  ];  
                $sheet->getStyle('A'.$lastRow.':D'.$maximumCount)->applyFromArray($styleArray);
            }
            $reportByProvider = $maximumCount + 2;
            $rowLastCount = $reportByProvider;
  
            /* ---- REPORT BY PROVIDER ------*/

            $sheet->setCellValue('A'.$reportByProvider, 'Report by Provider')->getStyle('A'.$reportByProvider)->getFont()->setBold( true );
            $reportByProvider++;
            $sheet->setCellValue('A'.$reportByProvider, 'State');
            $sheet->setCellValue('B'.$reportByProvider, 'Clinic');
            $sheet->setCellValue('C'.$reportByProvider, 'Provider');
            $sheet->setCellValue('D'.$reportByProvider, 'Class');

            $reportByProvider++;
            $columnCount = $countSection = $counts = $reportByProvider;
            
            $providerDataResult = array();            
            foreach($states as $state){
    
                $dataResult = DB::table('order_details')
                    ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                    ->select('order_details.state','order_details.account_name','order_details.provider_name','drug_interactions.drug_class','drug_interactions.prescribed_test')
                    ->selectRaw('count(drug_interactions.drug_class) as count')
                    ->where('drug_interactions.drug_class', '<>', '', 'and');
                    
    
                if ($request->has('clinics')) {
                    $dataResult->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResult->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResult->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResult->whereIn('prescribed_test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResult->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResult->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                $dataResult->groupBy('drug_interactions.drug_class')
                ->having('state', '=', $state->state);
                if(sizeof($dataResult->get())){
                    array_push($providerDataResult,$dataResult->get());
                }
            }

            foreach($providerDataResult as $data){   
                $dataResultant = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['drug_class'] = $values->drug_class;
                    $testDetails['count'] = $values->count;
    
                    $dataResultant[$values->account_name][$values->provider_name][]= $testDetails;
                    $dataByProviders[$values->state] = $dataResultant;
                }
            }
            if (!empty($dataByProviders)) {
                foreach ($dataByProviders as $state => $data) {
                    foreach ($data as $accountName => $array){
                        $sheet->setCellValue('A'.$columnCount, $state); 
                        $sheet->setCellValue('B'.$columnCount, $accountName);
                        foreach($array as $providerName => $details){
                            $sheet->setCellValue('C'.$columnCount, $providerName);
                            foreach($details as $sectionId => $valueResult){ 
                                $sheet->setCellValue('D'.$reportByProvider, $valueResult['drug_class']);
                                $sheet->setCellValue('E'.$reportByProvider, $valueResult['count']);
                                $reportByProvider++;
                            }
                        }
                    $maximumCount = max([$reportByProvider,$columnCount,$countSection,$counts]) + 1;
                    $reportByProvider = $columnCount = $countSection = $counts = $maximumCount;
                    }
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A'.$rowLastCount.':E'.$maximumCount)->applyFromArray($styleArray);
            }
            $writer = new Xlsx($spreadsheet);
            $fileName = 'data.xlsx';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="myfile.xls"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }else{
            return redirect('/home')->with('save_error', __('No Records for selected data'));
        }
    }
    public function exportDetailedDDIReport(Request $request){
        $validator = Validator::make($request->all(),[
            'ageTo' => 'gte:ageFrom',
            'reported_date_to' => 'nullable|after_or_equal:reported_date_from'
        ]);
        
        if($validator->fails()){
            return redirect('/home')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }
        $statesQuery = DB::table('order_details')->distinct('state');
        if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
        }
        $states = $statesQuery->get('state');
        $dataQuery = array();
        
        foreach($states as $state){

            $dataResult = DB::table('order_details')
                ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                ->select('order_details.state','drug_interactions.keyword','drug_interactions.prescribed_test')
                ->selectRaw('count(drug_interactions.keyword) as count')
                ->where('drug_interactions.keyword', '<>', '', 'and');
                

            if ($request->has('clinics')) {
                $dataResult->wherein('account_name', $request->input('clinics'));
            }
            if ($request->has('patients')) {
                $dataResult->whereIn('patient_name', $request->input('patients'));
            }
            if ($request->has('providers')) {
                $dataResult->whereIn('provider_name', $request->input('providers'));
            }
            if ($request->has('tests')) {
                $dataResult->whereIn('prescribed_test', $request->input('tests'));
            }
            if ($request->has('classes')) {
                $dataResult->whereIn('test_class', $request->input('classes'));
            }
            if ($request->has('gender')) {
                $dataResult->whereIn('patient_gender', $request->input('gender'));
            }
            if ($request->has('ageFrom') && $request->has('ageTo') ) {
                if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                    $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                }
            }
            if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
            }
            $dataResult->groupBy('drug_interactions.keyword','order_details.state')
            ->having('state', '=', $state->state);
            if(sizeof($dataResult->get())){
                array_push($dataQuery,$dataResult->get());
            }
        }
        $statesResult = array();
       
        if($dataQuery){
            foreach($dataQuery as $dataResults){
                foreach($dataResults as $dataResult){
                    $result = array();
                    $result['keyword'] = $dataResult->keyword;
                    $result['count'] = $dataResult->count;
                    $statesResult[$dataResult->state][] = $result;
                }
            }
            /* ---- REPORT BY STATE ------*/
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach (range('A', 'C') as $column) {
                $sheet->getColumnDimension($column)->setWidth(120, 'pt');
            }
            
            $sheet->setCellValue('A1', 'Report by States')->getStyle('A1')->getFont()->setBold( true );
            $sheet->setCellValue('A2', 'State');
            $sheet->setCellValue('B2', 'Keyword');
    
            $rowCount = 2;
            $count = 3;
            $columnCountA = 3;
            $columnCountB = 3;
            
            $total = 0;
            $totalVal = 0;
            $totalValue = 0;
            if (!empty($statesResult)) {
                foreach ($statesResult as $state => $data) {
                    $loopStateValue = $state;
                    foreach ($data as $key => $valueData){
                        $sheet->setCellValue('A'.$columnCountA, $state);  
                        $sheet->setCellValue('B'.$columnCountB, $valueData['keyword']);
                        $sheet->setCellValue('C'.$columnCountB, $valueData['count']);
                        $columnCountB++;
                        $total = $total+$valueData['count'];
                        $sheet->setCellValue('C2', $total);
                        $rowCount++;
                    }
                    $maximumCount = max([$columnCountB]) + 1;
                    $columnCountA = $columnCountB = $maximumCount;
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A2'.':C'.$maximumCount)->applyFromArray($styleArray);
                $reportByClinics = $maximumCount + 2;
                $lastRow = $reportByClinics;
            }
                /* ---- REPORT BY CLINIC ------*/

                $sheet->setCellValue('A'.$reportByClinics, 'Report by Clinics')->getStyle('A'.$reportByClinics)->getFont()->setBold( true );
                $reportByClinics++;
                $sheet->setCellValue('A'.$reportByClinics, 'State');
                $sheet->setCellValue('B'.$reportByClinics, 'Clinic');
                $sheet->setCellValue('C'.$reportByClinics, 'Keyword');
                $reportByClinics++;
                $countRowList = $countColumn = $stateRow = $reportByClinics;
                $accountDataResult = array();
                
                foreach($states as $state){
        
                    $dataResult = DB::table('order_details')
                        ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                        ->select('order_details.state','order_details.account_name','drug_interactions.keyword','drug_interactions.prescribed_test')
                        ->selectRaw('count(drug_interactions.keyword) as count')
                        ->where('drug_interactions.keyword', '<>', '', 'and');
                    if ($request->has('clinics')) {
                        $dataResult->wherein('account_name', $request->input('clinics'));
                    }
                    if ($request->has('patients')) {
                        $dataResult->whereIn('patient_name', $request->input('patients'));
                    }
                    if ($request->has('providers')) {
                        $dataResult->whereIn('provider_name', $request->input('providers'));
                    }
                    if ($request->has('tests')) {
                        $dataResult->whereIn('prescribed_test', $request->input('tests'));
                    }
                    if ($request->has('classes')) {
                        $dataResult->whereIn('test_class', $request->input('classes'));
                    }
                    if ($request->has('gender')) {
                        $dataResult->whereIn('patient_gender', $request->input('gender'));
                    }
                    if ($request->has('ageFrom') && $request->has('ageTo') ) {
                        if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                            $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                        }
                    }
                    if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                        $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                    }
                    $dataResult->groupBy('drug_interactions.keyword','order_details.state')
                    ->having('state', '=', $state->state);
                    if(sizeof($dataResult->get())){
                        array_push($accountDataResult,$dataResult->get());
                    }
                }
                
                foreach($accountDataResult as $data){   
                $dataResultant = array();
                    foreach($data as $key => $values){   
                        $testDetails = array();
                        $testDetails['keyword'] = $values->keyword;
                        $testDetails['count'] = $values->count;
                        
                        $dataResultant[$values->account_name][]= $testDetails;
                        $dataByClinics[$values->state] = $dataResultant;
                    }
                }
                
                if (!empty($dataByClinics)) {
                    foreach ($dataByClinics as $state => $data) {
                        
                        foreach ($data as $accountName => $array){

                            $sheet->setCellValue('A'.$stateRow, $state); 
                            $sheet->setCellValue('B'.$stateRow, $accountName);

                            foreach($array as $sectionId => $valueResult){ 
                            
                                $sheet->setCellValue('C'.$reportByClinics, $valueResult['keyword']);
                                $sheet->setCellValue('D'.$reportByClinics, $valueResult['count']);
                                $reportByClinics++;
                                
                            }
                            $maximumCount = max([$reportByClinics]) + 1;
                            $stateRow = $countRowList = $countColumn = $reportByClinics = $maximumCount;
                        }
                    }
                    $styleArray = [
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => '000'],
                                ],
                        ],
                    ];  
                    $sheet->getStyle('A'.$lastRow.':D'.$maximumCount)->applyFromArray($styleArray);
                }
            $reportByProvider = $maximumCount + 2;
            $rowLastCount = $reportByProvider;

            /* ---- REPORT BY PROVIDER ------*/

            $sheet->setCellValue('A'.$reportByProvider, 'Report by Provider')->getStyle('A'.$reportByProvider)->getFont()->setBold( true );
            $reportByProvider++;
            $sheet->setCellValue('A'.$reportByProvider, 'State');
            $sheet->setCellValue('B'.$reportByProvider, 'Clinic');
            $sheet->setCellValue('C'.$reportByProvider, 'Provider');
            $sheet->setCellValue('D'.$reportByProvider, 'Keyword');
 
            $reportByProvider++;
            $columnCount = $countSection = $counts = $reportByProvider;
             
            $providerDataResult = array();            
            foreach($states as $state){
    
                $dataResult = DB::table('order_details')
                    ->join('drug_interactions', 'order_details.order_code', '=', 'drug_interactions.order_id')
                    ->select('order_details.state','order_details.account_name','order_details.provider_name','drug_interactions.keyword','drug_interactions.prescribed_test')
                    ->selectRaw('count(drug_interactions.keyword) as count')
                    ->where('drug_interactions.keyword', '<>', '', 'and');
                    
    
                if ($request->has('clinics')) {
                    $dataResult->wherein('account_name', $request->input('clinics'));
                }
                if ($request->has('patients')) {
                    $dataResult->whereIn('patient_name', $request->input('patients'));
                }
                if ($request->has('providers')) {
                    $dataResult->whereIn('provider_name', $request->input('providers'));
                }
                if ($request->has('tests')) {
                    $dataResult->whereIn('prescribed_test', $request->input('tests'));
                }
                if ($request->has('classes')) {
                    $dataResult->whereIn('test_class', $request->input('classes'));
                }
                if ($request->has('gender')) {
                    $dataResult->whereIn('patient_gender', $request->input('gender'));
                }
                if ($request->has('ageFrom') && $request->has('ageTo') ) {
                    if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                        $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                    }
                }
                if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                    $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
                }
                $dataResult->groupBy('drug_interactions.keyword','order_details.state')
                ->having('state', '=', $state->state);
                if(sizeof($dataResult->get())){
                    array_push($providerDataResult,$dataResult->get());
                }
            }
            foreach($providerDataResult as $data){   
                $dataResultant = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['keyword'] = $values->keyword;
                    $testDetails['count'] = $values->count;
    
                    $dataResultant[$values->account_name][$values->provider_name][]= $testDetails;
                    $dataByProviders[$values->state] = $dataResultant;
                }
            }
            if (!empty($dataByProviders)) {
                foreach ($dataByProviders as $state => $data) {
                    foreach ($data as $accountName => $array){
                        $sheet->setCellValue('A'.$columnCount, $state); 
                        $sheet->setCellValue('B'.$columnCount, $accountName);
                        foreach($array as $providerName => $details){
                            $sheet->setCellValue('C'.$columnCount, $providerName);
                            foreach($details as $sectionId => $valueResult){ 
                                $sheet->setCellValue('D'.$reportByProvider, $valueResult['keyword']);
                                $sheet->setCellValue('E'.$reportByProvider, $valueResult['count']);
                                $reportByProvider++;
                            }
                        }
                    $maximumCount = max([$reportByProvider,$columnCount,$countSection,$counts]) + 1;
                    $reportByProvider = $columnCount = $countSection = $counts = $maximumCount;
                    }
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A'.$rowLastCount.':E'.$maximumCount)->applyFromArray($styleArray);
            }
  
            $writer = new Xlsx($spreadsheet);
            $fileName = 'data.xlsx';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="myfile.xls"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }else{
            return redirect('/home')->with('save_error', __('No Records for selected data'));
        }
    }
    public function searchConditionsFilter($states, $request){
        $dataQuery = array();
        foreach($states as $state){

            $dataResult = DB::table('order_details')
                ->join('conditions', 'order_details.order_code', '=', 'conditions.order_id')
                ->select('order_details.state','order_details.account_name','order_details.provider_name','conditions.drug_class','conditions.prescribed_test')
                ->selectRaw('count(conditions.drug_class) as count')
                ->where('conditions.drug_class', '<>', ' ', 'and');
                

            if ($request->has('clinics')) {
                $dataResult->wherein('account_name', $request->input('clinics'));
            }
            if ($request->has('patients')) {
                $dataResult->whereIn('patient_name', $request->input('patients'));
            }
            if ($request->has('providers')) {
                $dataResult->whereIn('provider_name', $request->input('providers'));
            }
            if ($request->has('tests')) {
                $dataResult->whereIn('prescribed_test', $request->input('tests'));
            }
            if ($request->has('classes')) {
                $dataResult->whereIn('test_class', $request->input('classes'));
            }
            if ($request->has('gender')) {
                $dataResult->whereIn('patient_gender', $request->input('gender'));
            }
            if ($request->has('ageFrom') && $request->has('ageTo') ) {
                if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
                    $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
                }
            }
            if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
                $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
            }
            $dataResult->groupBy('conditions.drug_class')
            ->having('state', '=', $state->state);
            if(sizeof($dataResult->get())){
                array_push($dataQuery,$dataResult->get());
            }
        }
        return $dataQuery;
    }
    public function exportCIReport(Request $request){
        $validator = Validator::make($request->all(),[
            'ageTo' => 'gte:ageFrom',
            'reported_date_to' => 'nullable|after_or_equal:reported_date_from'
        ]);
        
        if($validator->fails()){
            return redirect('/home')->with('validation_error', ' ')->withErrors($validator)->withInput();
        }
        $statesQuery = DB::table('order_details')->distinct('state');
        if ($request->has('states')) {
            $statesQuery->wherein('state',  $request->input('states'));
        }
        $states = $statesQuery->get('state');
        $dataQuery = $this->searchConditionsFilter($states, $request);

        $statesResult = array();
       
        if($dataQuery){
            foreach($dataQuery as $dataResults){
                foreach($dataResults as $dataResult){
                    $testDetails = array();
                    $testDetails['drug_class'] = $dataResult->drug_class;
                    $testDetails['count'] = $dataResult->count;
                    $statesResult[$dataResult->state][] = $testDetails;
                }
            }

            /* ---- REPORT BY STATE ------*/
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setWidth(120, 'pt');
            }
            
            $sheet->setCellValue('A1', 'Report by States')->getStyle('A1')->getFont()->setBold( true );
            $sheet->setCellValue('A2', 'State');
            $sheet->setCellValue('B2', 'Class');
    
            $rowCount = 2;
            $count = 3;
            $columnCountA = 3;
            $columnCountB = 3;
            $columnCountE = 3;
            $columnCountH = 3;
            $total = 0;
            $totalVal = 0;
            $totalValue = 0;
            if (!empty($statesResult)) {
                foreach ($statesResult as $state => $data) {
                    $loopStateValue = $state;
                    foreach ($data as $key => $valueData){
                        $sheet->setCellValue('A'.$columnCountA, $state);  
                        $sheet->setCellValue('B'.$columnCountB, $valueData['drug_class']);
                        $sheet->setCellValue('C'.$columnCountB, $valueData['count']);
                        $columnCountB++;
                        $total = $total+$valueData['count'];
                        $sheet->setCellValue('C2', $total);
                        $rowCount++;
                    }
                    $maximumCount = max([$columnCountB, $columnCountE, $columnCountH]) + 1;
                    $columnCountA = $columnCountB = $columnCountE =  $columnCountH = $maximumCount;
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A2'.':C'.$maximumCount)->applyFromArray($styleArray);
                $reportByClinics = $maximumCount + 2;
                $lastRow = $reportByClinics;
            }
            /* ---- REPORT BY CLINIC ------*/

            $sheet->setCellValue('A'.$reportByClinics, 'Report by Clinics')->getStyle('A'.$reportByClinics)->getFont()->setBold( true );
            $reportByClinics++;
            $sheet->setCellValue('A'.$reportByClinics, 'State');
            $sheet->setCellValue('B'.$reportByClinics, 'Clinic');
            $sheet->setCellValue('C'.$reportByClinics, 'Class');
            $reportByClinics++;
            $countRowList = $countColumn = $stateRow = $reportByClinics;
            $accountDataResult = array();
            
            // foreach($states as $state){
    
            //     $dataResult = DB::table('order_details')
            //         ->join('conditions', 'order_details.id', '=', 'conditions.order_id')
            //         ->select('order_details.state','order_details.account_name','order_details.provider_name','conditions.drug_class')
            //         ->selectRaw('count(conditions.drug_class) as count')
            //         ->where('conditions.drug_class', '<>', '', 'and');
                    
    
            //     if ($request->has('clinics')) {
            //         $dataResult->wherein('account_name', $request->input('clinics'));
            //     }
            //     if ($request->has('patients')) {
            //         $dataResult->whereIn('patient_name', $request->input('patients'));
            //     }
            //     if ($request->has('providers')) {
            //         $dataResult->whereIn('provider_name', $request->input('providers'));
            //     }
            //     if ($request->has('tests')) {
            //         $dataResult->whereIn('test', $request->input('tests'));
            //     }
            //     if ($request->has('classes')) {
            //         $dataResult->whereIn('test_class', $request->input('classes'));
            //     }
            //     if ($request->has('gender')) {
            //         $dataResult->whereIn('patient_gender', $request->input('gender'));
            //     }
            //     if ($request->has('ageFrom') && $request->has('ageTo') ) {
            //         if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
            //             $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
            //         }
            //     }
            //     if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
            //         $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
            //     }
            //     $dataResult->groupBy('conditions.drug_class')
            //     ->having('state', '=', $state->state);
            //     if(sizeof($dataResult->get())){
            //         array_push($accountDataResult,$dataResult->get());
            //     }
            // }
            $accountDataResult = $this->searchConditionsFilter($states, $request);
            
            foreach($accountDataResult as $data){   
            $dataResultant = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['drug_class'] = $values->drug_class;
                    $testDetails['count'] = $values->count;

                    $dataResultant[$values->account_name][]= $testDetails;
                    $dataByClinics[$values->state] = $dataResultant;
                }
            }
            if (!empty($dataByClinics)) {
                foreach ($dataByClinics as $state => $data) {
                    
                    foreach ($data as $accountName => $array){

                        $sheet->setCellValue('A'.$stateRow, $state); 
                        $sheet->setCellValue('B'.$stateRow, $accountName);

                        foreach($array as $sectionId => $valueResult){ 
                           
                            $sheet->setCellValue('C'.$reportByClinics, $valueResult['drug_class']);
                            $sheet->setCellValue('D'.$reportByClinics, $valueResult['count']);
                            $reportByClinics++;
                            
                        }
                        $maximumCount = max([$reportByClinics]) + 1;
                        $stateRow = $countRowList = $countColumn = $reportByClinics = $maximumCount;
                    }
                }
                $styleArray = [
                      'borders' => [
                          'outline' => [
                              'borderStyle' => Border::BORDER_THIN,
                              'color' => ['argb' => '000'],
                            ],
                      ],
                  ];  
                $sheet->getStyle('A'.$lastRow.':D'.$maximumCount)->applyFromArray($styleArray);
            }
            $reportByProvider = $maximumCount + 2;
            $rowLastCount = $reportByProvider;
  
            /* ---- REPORT BY PROVIDER ------*/

            $sheet->setCellValue('A'.$reportByProvider, 'Report by Provider')->getStyle('A'.$reportByProvider)->getFont()->setBold( true );
            $reportByProvider++;
            $sheet->setCellValue('A'.$reportByProvider, 'State');
            $sheet->setCellValue('B'.$reportByProvider, 'Clinic');
            $sheet->setCellValue('C'.$reportByProvider, 'Provider');
            $sheet->setCellValue('D'.$reportByProvider, 'Class');

            $reportByProvider++;
            $columnCount = $countSection = $counts = $reportByProvider;
            
            $providerDataResult = array();            
            // foreach($states as $state){
    
            //     $dataResult = DB::table('order_details')
            //         ->join('conditions', 'order_details.id', '=', 'conditions.order_id')
            //         ->select('order_details.state','order_details.account_name','order_details.provider_name','conditions.drug_class')
            //         ->selectRaw('count(conditions.drug_class) as count')
            //         ->where('conditions.drug_class', '<>', '', 'and');
                    
    
            //     if ($request->has('clinics')) {
            //         $dataResult->wherein('account_name', $request->input('clinics'));
            //     }
            //     if ($request->has('patients')) {
            //         $dataResult->whereIn('patient_name', $request->input('patients'));
            //     }
            //     if ($request->has('providers')) {
            //         $dataResult->whereIn('provider_name', $request->input('providers'));
            //     }
            //     if ($request->has('tests')) {
            //         $dataResult->whereIn('test', $request->input('tests'));
            //     }
            //     if ($request->has('classes')) {
            //         $dataResult->whereIn('test_class', $request->input('classes'));
            //     }
            //     if ($request->has('gender')) {
            //         $dataResult->whereIn('patient_gender', $request->input('gender'));
            //     }
            //     if ($request->has('ageFrom') && $request->has('ageTo') ) {
            //         if($request->input('ageFrom') != NULL && $request->input('ageTo') != NULL){
            //             $dataResult->whereBetween('patient_age', [$request->input('ageFrom'), $request->input('ageTo')]);
            //         }
            //     }
            //     if ($request->filled('reported_date_from') && $request->filled('reported_date_to') ) {
            //         $dataResult->whereBetween('reported_date', [$request->input('reported_date_from'), $request->input('reported_date_to')]);
            //     }
            //     $dataResult->groupBy('conditions.drug_class')
            //     ->having('state', '=', $state->state);
            //     if(sizeof($dataResult->get())){
            //         array_push($providerDataResult,$dataResult->get());
            //     }
            // }
            $providerDataResult = $this->searchConditionsFilter($states, $request);
            foreach($providerDataResult as $data){   
                $dataResultant = array();
                foreach($data as $key => $values){   
                    $testDetails = array();
                    $testDetails['drug_class'] = $values->drug_class;
                    $testDetails['count'] = $values->count;
    
                    $dataResultant[$values->account_name][$values->provider_name][]= $testDetails;
                    $dataByProviders[$values->state] = $dataResultant;
                }
            }
            if (!empty($dataByProviders)) {
                foreach ($dataByProviders as $state => $data) {
                    foreach ($data as $accountName => $array){
                        $sheet->setCellValue('A'.$columnCount, $state); 
                        $sheet->setCellValue('B'.$columnCount, $accountName);
                        foreach($array as $providerName => $details){
                            $sheet->setCellValue('C'.$columnCount, $providerName);
                            foreach($details as $sectionId => $valueResult){ 
                                $sheet->setCellValue('D'.$reportByProvider, $valueResult['drug_class']);
                                $sheet->setCellValue('E'.$reportByProvider, $valueResult['count']);
                                $reportByProvider++;
                            }
                        }
                    $maximumCount = max([$reportByProvider,$columnCount,$countSection,$counts]) + 1;
                    $reportByProvider = $columnCount = $countSection = $counts = $maximumCount;
                    }
                }
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000'],
                        ],
                    ],
                ];  
                $sheet->getStyle('A'.$rowLastCount.':E'.$maximumCount)->applyFromArray($styleArray);
            }
            $writer = new Xlsx($spreadsheet);
            $fileName = 'data.xlsx';
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="myfile.xls"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        }else{
            return redirect('/home')->with('save_error', __('No Records for selected data'));
        }
    }
}
