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
use Illuminate\Support\Facades\Storage;
use URL;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use App\Models\OrderTestClassSection;
use App\Services\PDFGenerationService;

class GeneratePDFReportController extends Controller
{
    public $uuids = []; 
    public $singleUuid = '';

    // //**  Below common pdf generation service to call from ->  a. api and b. background service */
    // public function common_getPDFReport_service($_pdfGenerationService, $orderCode, $show_log=0)
    // {
    //     ini_set('max_execution_time', '0');

    //         //1. Get PDF generating data.
    //         if($show_log == 1)
    //         {
    //             dump('called method : getPdfDataForStratus ');
    //         }
    //         $response = $_pdfGenerationService->getPdfDataForStratus($orderCode, $show_log);
    //         if($show_log == 1)
    //         {
    //             dump('response: ');
    //             dump($response);
    //         }

    //         if($response == null || $response["content"] == null|| $response["status"] == "500")
    //         {
    //             return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json'); 
    //             Log::channel('error')->error('generate pdf conent failed for orderCode : ' . $orderCode);
    //         }
    //         else{
    //             // Log::channel('error')->error('generate pdf conent failed for orderCode : ' . $orderCode);
    //         }

    //         // 2. Create PDF
    //         $data = $response["content"];
    //         $pdf = PDF::loadView('generatePdfReport', $data)->setPaper('a1', 'portrait');
    //         $pdf->getDomPDF()->set_option("enable_php", true);
    //         $fileName = "generate-$orderCode.pdf";
    //         file_put_contents("pdf/$fileName" , $pdf->output());

    //         $fileUrl = URL::to('/') . "/pdf/$fileName";

    //         Storage::disk('local')->put('public/pdf/'.$fileName, $pdf->output());
    //         $file_path = \Storage::url($fileName);

    //         // 3. Upload to "Stratus"
    //         $response_StratusUpdated =  $_pdfGenerationService->postPDF_to_Stratus($fileName, $orderCode);
    //         if($show_log == 1)
    //         {
    //             dump('response_StratusUpdated: ');
    //             dump($response_StratusUpdated);
    //         }

    //         // 4. Upload at S3
    //         $url = "";
    //         if(env('UPLOAD_PDF_TO_S3') == 'yes')
    //         {
    //             if($_pdfGenerationService->uploadToS3($fileName)){
    //                 $url = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') .'/' .$fileName;
    //             }
    //             if($show_log == 1)
    //             {
    //                 dump('S3 url: ');
    //                 dump($url);
    //             }
    //         }

    //         // 5. ack to Stratus
    //         if($response_StratusUpdated["status"] == "ok")
    //         {
    //             //NOTE: ack to stratus to remove from request queue.
    //             $response_ack = true;
    //             if(env('SEND_ACK_TO_STRATUS') == 'yes')
    //             {
    //                 $response_ack =  $_pdfGenerationService->ackToStratusAfterPDFgeneration($orderCode);
    //                 if($show_log == 1)
    //                 {
    //                     dump('response_ack: ');
    //                     dump($response_ack);
    //                 }
    //             }
    //             if($response_ack == true)
    //             {
    //                 return Response::json(['url' => $url], 200)->header('Accept', 'application/json'); 
    //             }
    //             else
    //             {
    //                 return Response::json(['error' => "ack to stratus failed."], 500)->header('Accept', 'application/json');
    //             }
    //         }
    //         else
    //         {
    //             Log::channel('error')->error('Upload pfd report to Stratus DB - api called failed for orderCode : ' . $orderCode);
    //             return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
    //         }

    //         return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
    //         //return $pdf->download('generatePDF123.pdf');
    //         //ddd($file_path);
    // }

    public function getPDFReport_from_api(Request $request, PDFGenerationService $_pdfGenerationService)
    {
        ini_set('max_execution_time', '0');

        if (! $request->isMethod('POST') ) { 
            return Response::json(['error' => "Bad request."], 400)->header('Accept', 'application/json');  
        }

        if (true) {

            $url = $request->fullUrl();
            $payload = $request->input();
            $orderCode = "";

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

            try
            {
                return $_pdfGenerationService->common_getPDFReport_service($orderCode, 0);
            }
            catch (Exception $ex ) {
                Log::channel('error')->error('Exception: ' . $ex->getMessage()); 
                return Response::json(['error' => "Report generation failed"], 500)->header('Accept', 'application/json');  
            }

        //     //1. Get PDF generating data.
        //     $response = $_pdfGenerationService->getPdfDataForStratus($orderCode);
        //     if($response == null || $response["content"] == null)
        //     {
        //         return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json'); 
        //     }
        //     else{
        //         Log::channel('error')->error('generate pdf conent failed for orderCode : ' . $orderCode);
        //     }

        //     // 2. Create PDF
        //     $data = $response["content"];
        //    $pdf = PDF::loadView('generatePdfReport', $data)->setPaper('a1', 'portrait');
        //     $pdf->getDomPDF()->set_option("enable_php", true);
        //     $fileName = "generate-$orderCode.pdf";
        //     file_put_contents("pdf/$fileName" , $pdf->output());

        //     $fileUrl = URL::to('/') . "/pdf/$fileName";

        //     Storage::disk('local')->put('public/pdf/'.$fileName, $pdf->output());
        //     $file_path = \Storage::url($fileName);

        //     //Upload to "Stratus"
        //     $response_StratusUpdated =  $_pdfGenerationService->postPDF_to_Stratus($fileName, $orderCode);

        //     if($_pdfGenerationService->uploadToS3($fileName)){
        //         $url = 'https://s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') .'/' .$fileName;
        //        // return Response::json(['url' => $url], 200)->header('Accept', 'application/json'); 
        //     }

        //     // dump("response_StratusUpdated______");
        //     // dump($response_StratusUpdated);

        //     if($response_StratusUpdated["status"] == "ok")
        //     {
        //         //NOTE: ack to stratus to remove from request queue.
        //       $response_ack =  $_pdfGenerationService->ackToStratusAfterPDFgeneration($orderCode);
        //       if($response_ack == true)
        //       {
        //         return Response::json(['url' => $url], 200)->header('Accept', 'application/json'); 
        //       }
        //       else
        //       {
        //         return Response::json(['error' => "ack to stratus failed."], 500)->header('Accept', 'application/json');
        //       }
        //     }
        //     else
        //     {
        //         Log::channel('error')->error('Upload pfd report to Stratus DB - api called failed for orderCode : ' . $orderCode);
        //         return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
        //     }

        //     return Response::json(['error' => "Report generation failed."], 500)->header('Accept', 'application/json');
        //     //return $pdf->download('generatePDF123.pdf');
        //     //ddd($file_path);
        }
    }

    // public function getPDFReport_from_background_service($order_code, PDFGenerationService $_pdfGenerationService)
    // {
    //     ini_set('max_execution_time', '0');

    //     ini_set('max_execution_time', '0');
    //     $orderCode = "";

    //     try
    //     {
    //         if($order_code == "")
    //         {
    //             return "The 'order_code' attribute field is required.";
    //         }
    //         else
    //         {
    //             $orderCode = $order_code ;
    //         }

    //         return $_pdfGenerationService->common_getPDFReport_service($orderCode, 0);
    //     }
    //     catch(Exception $ex)
    //     {
    //         Log::channel('error')->error('Exception: ' . $ex->getMessage()); 
    //         return Response::json(['error' => "Report generation failed"], 500)->header('Accept', 'application/json');  
    //         //return $ex->getMessage();
    //     }
    // }

    public function PDFReport_Get_Live($show_log, $order_code, PDFGenerationService $_pdfGenerationService)
    {
        ini_set('max_execution_time', '0');
        $orderCode = "";

        try
        {
            if($order_code == "")
            {
                return "The 'order_code' attribute field is required.";
            }
            else
            {
                $orderCode = $order_code ;
            }
        }
        catch(Exception $ex)
        {
            return $ex->getMessage();
        }

        //1. Get PDF generating data.
       // $response = $_pdfGenerationService->getPdfDataForStratus($orderCode, $show_log);
        
        try
        {
            return $_pdfGenerationService->common_getPDFReport_service($orderCode, $show_log);
        }
        catch(Exception $ex)
        {
            Log::channel('error')->error('Exception: ' . $ex->getMessage()); 
            return Response::json(['error' => "Report generation failed"], 500)->header('Accept', 'application/json');  
            //return $ex->getMessage();
        }
    }

    public function test_Stratus_ack($show_log,$order_code, PDFGenerationService $_pdfGenerationService)
    {
        $response_ack =  $_pdfGenerationService->ackToStratusAfterPDFgeneration($order_code, $show_log);
        if($response_ack == true)
        {
          return Response::json(['url' => "ack ok for orderCode: ".$order_code], 200)->header('Accept', 'application/json'); 
        }
        else
        {
          return Response::json(['error' => "ack to stratus failed for orderCode: ".$order_code], 500)->header('Accept', 'application/json');
        }
    }
}
