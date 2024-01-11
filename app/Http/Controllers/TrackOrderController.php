<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use Illuminate\Support\Facades\Validator;
use DB;

class TrackOrderController extends Controller
{
    public function index(){
		$orderDetails = DB::select("SELECT distinct in_house_lab_locations FROM lis_orders_details");
		Log::info("orderDetails");
		Log::info($orderDetails);
		return view('trackOrders', ['orderDetails' => $orderDetails, 'articleName' => 'Article 1']);
    }

    public function exportTrackingReportCsv(Request $request){
		Log::info($request);
    	$validator = Validator::make($request->all(),[
			'lab' => 'required|not_in:0',
    		'start_date' => 'required|date_format:m-d-Y',
        	'end_date' => 'required|date_format:m-d-Y|after_or_equal:start_date'
      	]);
        
		if($validator->fails()){
			return redirect('/trackOrders')->with('validation_error', ' ')->withErrors($validator)->withInput();
		}

		$lab = $request['lab'];
		$lab_arr = [];
		if(!empty($lab)){
			foreach($lab as $lab_key => $lab_value){
				$lab_arr[] = '"'.$lab_value.'"';
			}
		}
		$lab_str = implode(",", $lab_arr);
   		$fileName = 'trackOrderReport.csv';
   		
   		$start_date_arr = explode("-",$request['start_date']);
   		$start_date = $start_date_arr[2]."-".$start_date_arr[0]."-".$start_date_arr[1];

   		$end_date_arr = explode("-",$request['end_date']);
   		$end_date = $end_date_arr[2]."-".$end_date_arr[0]."-".$end_date_arr[1];

   		/*Log::info("startDate");
   		Log::info($start_date);
   		Log::info("endDate");
   		Log::info($end_date);*/

      	$actual_end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));

   		//DB::enableQueryLog();
   		$trackOrderDetails = DB::select("SELECT COUNT(order_code) as order_code_count, in_house_lab_locations FROM lis_orders_details WHERE `updated_at` BETWEEN "."'".$start_date."'"." AND "."'".$actual_end_date."'"." AND in_house_lab_locations IN(".$lab_str.") group by in_house_lab_locations");
   		//dd(DB::getQueryLog());

		foreach ($trackOrderDetails as $trackOrderDetails_value) {
			$trackOrderDetails_value->start_date = date('m-d-Y', strtotime($start_date));
			$trackOrderDetails_value->end_date = date('m-d-Y', strtotime($end_date));
		}

		//Log::info($trackOrderDetails);

		$headers = array(
			"Content-type"        => "text/csv",
			"Content-Disposition" => "attachment; filename=$fileName",
			"Pragma"              => "no-cache",
			"Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
			"Expires"             => "0"
		);

		$report_heading = array("Report for ".date('m-d-Y', strtotime($start_date))." to ".date('m-d-Y', strtotime($end_date)));
		$columns = array('Lab Name', 'Order Code Count');

		$callback = function() use($trackOrderDetails, $columns, $report_heading) {
        	$file = fopen('php://output', 'w');
        	fputcsv($file, $report_heading);
        	fputcsv($file, $columns);

        	foreach ($trackOrderDetails as $trackOrderDetail) {
				$row['Lab Name']  = $trackOrderDetail->in_house_lab_locations;
				$row['Order Code Count']    = $trackOrderDetail->order_code_count;

				fputcsv($file, array($row['Lab Name'], $row['Order Code Count']));
        	}

        	fclose($file);
      	};

		return response()->stream($callback, 200, $headers);
    }  
}
