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
use App\Models\LabLocation;
use App\Models\DrugInteraction;
use App\Models\ordercodequeue;
use Illuminate\Support\Facades\Storage;
use URL;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use App\Models\OrderTestClassSection;
use App\Services\PDFGenerationService;
use OrderCodeQueueToProcess;
use Psy\Readline\Hoa\Console;
// use App\Http\Controllers\Console;

class SchedulerJobController extends Controller
{
    public $uuids = []; 
    public $singleUuid = '';

    public function getAndStoreRequestedOrderCode()
    {
        $_pdfGenerationService = new PDFGenerationService();

        ini_set('max_execution_time', '0');
        $tableName = 'order_code_queues';
        try
        {
            // $response = Http::withBasicAuth(Config::get('nih.stratusUserName'), Config::get('nih.stratusPassword'))
            // ->get(Config::get('nih.stratus_requestQueue'));

            $response = Http::withBasicAuth('salvus_stratusdx_11', '86c7d164-7ad3')
            ->get('https://testapi.stratusdx.net/interface/results');

            // $_pdfGenerationService->Log_scheduler_info(
            $_pdfGenerationService->Log_scheduler_info('=> Stratus request queue : ' . $response);

            if ($response->successful()) 
            {
                $data = $response->json(); // Assuming the API returns JSON data

                //1. Check exist in status (Pending or Processing)
                // $filteredRecords = ordercodequeue::whereIn('workStatus', [0, 1])->get();
                $filteredRecords = DB::table($tableName)->where('workStatus', '<', 2)->get();
    
                // Store the data in the database
                foreach ($data['results'] as $item) {
                    $valueExists = false;
                    foreach ($filteredRecords as $data) {
                        if ($data->orderCode == $item) {
                            $valueExists = true;
                            break;
                        }
                    }
                    
                    if ($valueExists) {
                        //dump( "Record exists -> ". $item);
                    } else {
                        DB::table($tableName)->insert([
                            'orderCode' => $item
                        ]);
                        $_pdfGenerationService->Log_scheduler_info('=> New request : ' . $item);
                    }
                }
    
                //dump("success");
                return response()->json(['message' => 'Requested orderCodes stored into DB successfully']);
            } 
            else 
            {
                Log::channel('scheduler_error')->error('$$$ -> error - Failed to fetch orderCode from the Stratus API : ' . $response->status());
                return response()->json(['error' => 'Failed to fetch orderCode from the Stratus API'], $response->status());
            }
        }
        catch(Exception $ex)
        {
            Log::channel('scheduler_error')->error('$$$ -> exception : ' . $ex->getMessage());
            return $ex->getMessage();
        }
    }

    public function generatePDFreportAndUpdateResponse()
    {
        ini_set('max_execution_time', '0');

        $_pdfGenerationService = new PDFGenerationService();

        try
        {
            foreach ($this->getPendingOrders() as $order) {
                /**
                 * update order code to processing with workStatus value 1
                 */
                $tableName = 'order_code_queues';
                $_pdfGenerationService->Log_scheduler_info('-> pdf generation started for order_Code: '. $order->orderCode); 

                // OrderCodeQueue::where("orderCode", $order->orderCode)->update(["workStatus" => 1]);
                DB::table($tableName)->where("orderCode", $order->orderCode)->update(["workStatus" => 1]);

                //ducktap : bypass // because it takes huge time.
                if($order->orderCode == '235741-daffb2d0-2516-4c35-b16a-d65530834e27')
                {
                    continue;
                }

                // For Local test // RUN_TEST_REQUEST
                if (env('RUN_TEST_REQUEST_BYPASS') == 'yes') 
                {
                    // Go ahead
                    if($order->orderCode == '236399-78c2de22-f509-484f-a5c6-11263fdd3b99' || $order->orderCode == '236398-4fcb2a99-3863-4c8c-9ec5-5b3a7fab969c' || $order->orderCode == '236391-5a0e403c-4fff-4b45-9cf7-e13a478d4684')
                    {
                        continue;
                    }
                }

                // // For Local test // RUN_TEST_REQUEST
                // if (env('RUN_TEST_REQUEST') == 'yes') 
                // {
                //     // Go ahead
                // }
                // else
                // {
                //     // don't run test request
                //     if($order->orderCode == '236399-78c2de22-f509-484f-a5c6-11263fdd3b99' || $order->orderCode == '236398-4fcb2a99-3863-4c8c-9ec5-5b3a7fab969c' || $order->orderCode == '236391-5a0e403c-4fff-4b45-9cf7-e13a478d4684')
                //     {
                //         continue;
                //     }
                // }

               

                //Ducktap: jafar : call api directly
                $response = Http::post(Config::get('nih.stratus_pdf_generation_api').$order->orderCode, []);
                // Handle the response as needed
                $data = $response->json();
                Log::channel('scheduler_error')->error('$$$ -> api output  : '. implode(', ', $data)); 
                // You can also check for success
                $output_report = false;
                if ($response->successful()) {
                    $output_report = true;
                } else {
                    // Handle the error response
                    $statusCode = $response->status();
                    $errorData = $response->json();
                }

              // $pdf_generation_output = $_pdfGenerationService->getPDFReport_from_background_service($order->orderCode);




                if ($output_report) {
                    /**
                     * update order code to processing with workStatus value 2
                     */
                    DB::table($tableName)->where("orderCode", $order->orderCode)->update(["workStatus" => 2]);
                    //OrderCodeQueue::where("orderCode", $order->orderCode)->update(["workStatus" => 2]);
                    $_pdfGenerationService->Log_scheduler_info('-> pdf generation successful for order_Code: '. $order->orderCode); 
                    $_pdfGenerationService->Log_scheduler_info('-> pdf generation successful for order_Code: '. $order->orderCode); 
                } else {
                    /**
                     * update order code to processing with workStatus value 0
                     * and increment the number of failure
                     */
                    DB::table($tableName)->where("orderCode", $order->orderCode)->update(["workStatus" => 0, "numberOfFailur" => $order->numberOfFailur + 1 ]);
                    //  OrderCodeQueue::where("orderCode", $order->orderCode)
                    //     ->update([
                    //         "workStatus" => 0,
                    //         "numberOfFailur" => $order->numberOfFailur + 1
                    //     ]);
                    
                        Log::channel('scheduler_error')->error('$$$ -> pdf generation failed for order_code: '. $order->orderCode); 
                        $_pdfGenerationService->Log_scheduler_info('-> pdf generation failed for order_Code: '. $order->orderCode); 
                }
            }
        }
        catch(Exception $ex)
        {
            $_pdfGenerationService->Log_scheduler_info('-> ERROR for order_Code: '. $ex->getMessage()); 
            Log::channel('scheduler_error')->error('$$$ -> pdf generation - Exception: '. $ex->getMessage()); 
            return $ex->getMessage();
        }

    }

    public function getPendingOrders()
    {
        $tableName = 'order_code_queues';
        // remove take(2) methon in between
       // return OrderCodeQueue::where('workStatus', '=', 0)->where('numberOfFailur', '<', 5)->take(1)->get();
        return DB::table($tableName)->where('workStatus', '=', 0)->where('numberOfFailur', '<', 5)->take(1)->get();
    }

    // public function generatePdfReports()
    // {
    //     foreach ($this->getPendingOrders() as $order) {
    //         /**
    //          * update order code to processing with workStatus value 1
    //          */
    //         OrderCodeQueue::where("orderCode", $order->orderCode)->update(["workStatus" => 1]);

    //         if ($this->getPDFReport_from_background_service($order->orderCode)) {
    //             /**
    //              * update order code to processing with workStatus value 2
    //              */
    //             OrderCodeQueue::where("orderCode", $order->orderCode)->update(["workStatus" => 2]);
    //         } else {
    //             /**
    //              * update order code to processing with workStatus value 0
    //              * and increment the number of failure
    //              */
    //             OrderCodeQueue::where("orderCode", $order->orderCode)
    //                 ->update([
    //                     "workStatus" => 0,
    //                     "numberOfFailur" => $order->numberOfFailur + 1
    //                 ]);
    //         }
    //     }
    //    // return $this->test();
    // }
    
}
