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
use Illuminate\Support\Facades\Log;
use App\Exceptions\Handle;
use Illuminate\Http\Client\ConnectionException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\WebhookPayload;
use App\Models\OrderDetails;
use App\Models\IcdCode;


class GetDDIController extends Controller
{
  
    /**
     * To get TGT(Ticket Granting Ticket) 
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
     * to get service ticket using TGT generated
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
     * crosswalk between ICD code and MeSH code
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

    public function getMeshValue(Request $request)
    {
        $results = DB::table('mesh_codes')->get('mesh');
        // $result_array = array();
        foreach($results as $key => $value)
        {  
            $meshCode = $value->mesh;
            $conditionsResponseFromNihApi = $this->getConditions($meshCode);
            if(isset($conditionsResponseFromNihApi['drugMemberGroup']['drugMember'])){
                $conditionsResponseFromNih = $conditionsResponseFromNihApi['drugMemberGroup']['drugMember'];
                $arrayname = [];

                foreach ($conditionsResponseFromNih as $key => $list) {
                
                    if(isset($list['minConcept']['name'])) {
                        
                        $arrayname[] = $list['minConcept']['name'];
                        $result = DB::table('mesh_codes')->where('mesh',$meshCode)->update(['ci_with' => $arrayname]);
                        // $result_array[$meshCode] = $arrayname;
                    }
                }
            }
        }
        
        // $results = DB::table('icd_mesh_codes')->whereBetween('id',[71953,71999])->get('icd');
        // foreach($results as $key => $value)
        // {  
        //     $icd_value = $value->icd;
        //     $meshCodeData = $this->getMeshCode($icd_value);
            
        //     if (empty($meshCodeData)) {
        //         $result = DB::table('icd_mesh_codes')->where('icd',$icd_value)->update(['is_script_run' => 1]);
        //         Log::channel('error')->error('MeSH code not found'); 
        //     } else if (!empty($meshCodeData)){
                
        //         if(array_key_exists('error', $meshCodeData)){
        //             $result = DB::table('icd_mesh_codes')->where('icd',$icd_value)->update(['is_script_run' => 1]);
        //             Log::channel('error')->error('MeSH code not found'); 
        //         }
        //         else{
        //             $meshCode = $meshCodeData['result'][0]['ui'];
        //             $result = DB::table('icd_mesh_codes')->where('icd',$icd_value)->update(['mesh' => $meshCode,'is_script_run' => 1]);
                    
        //         }
        //     } 
        // } 
    }           
}
