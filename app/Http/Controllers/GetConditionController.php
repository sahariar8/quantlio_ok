<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DOMDocument;

class GetConditionController extends Controller
{
    public function getTGT() {
        try{
            $response = Http::asForm()->post('https://utslogin.nlm.nih.gov/cas/v1/api-key', [
                'apikey' => 'b48227a9-a4bd-44b9-bcb0-83ac29654d6a',
            ]);
        $ticketGeneratedURL = $response->getBody()->getContents();
        $dom = new DOMDocument();

        # Parse the HTML
        # The @ before the method call suppresses any warnings that
        # loadHTML might throw because of invalid HTML in the page.
        @$dom->loadHTML($ticketGeneratedURL);

        # Iterate over all the <form> tags
        foreach($dom->getElementsByTagName('form') as $input) {
        # Show the attribute action
        // echo $input->getAttribute('action');
        $ticketURL = $input->getAttribute('action');
        }
        return $ticketURL;
        
    }catch(RequestException $ex) {
            throw new Exception("error occured");
        }
    }
    public function getServiceTicket(){
        try{
            $ticketGeneratedURL = $this->getTGT();
            $response = Http::asForm()->post($ticketGeneratedURL, [
                'service' => 'http://umlsks.nlm.nih.gov',
            ]);
        $serviceTicket = $response->getBody()->getContents();
        return $serviceTicket;
    }catch (RequestException $ex){
            throw new Exception("error occured");
        }
    }
    public function getMeshCode($icd){
        try{
            $serviceTicket =  $this->getServiceTicket();
            $response = Http::get('https://uts-ws.nlm.nih.gov/rest/crosswalk/current/source/ICD10CM/'.$icd.'?targetSource=MSH&ticket='.$serviceTicket);
            $content = $response->getBody()->getContents();
            $response_data = json_decode($content, true);
            // $mesh_code = $response_data['result'][0]['ui'];
            return $response_data;
        }
        catch (RequestException $ex){
            throw new Exception("error occured");
        }
    }
    public function getCondition(Request $request){
        try{
            if ( $request->isMethod( 'GET' ) ) {	
                // Call Dendi API to get ICD code
                $icdCode =  "I51.9";
                $mesh_code_data = $this->getMeshCode($icdCode);
                $mesh_code = $mesh_code_data['result'][0]['ui'];
                $response = Http::get('https://rxnav.nlm.nih.gov/REST/rxclass/classMembers.json?classId='.$mesh_code.'&relaSource=MEDRT&rela=CI_with');
                $response_data = $response->getBody()->getContents();
                $data_set = json_decode($response_data, true);
                $response_array = $data_set['drugMemberGroup']['drugMember'];
                
                $prescribed_medications = array("acrivastine","Alprazolam","almotriptan","aldesleukin","Amphetamine"); 
                $result_array = array();

                foreach($response_array as $key => $value){
                    $drug_name = $value['minConcept']['name'];
                    if(in_array($drug_name,$prescribed_medications)){
                        // echo "This name exists ".$drug_name.".";
                        $result_array[$key]['name'] = $value['minConcept']['name'];
                        $result_array[$key]['CI_with'] = $mesh_code_data['result'][0]['name'];
                    }    
                }
                return response()->json(['Conditions' => $result_array ,'status' => true, 'message' => 'Success!']);
            }
        }
        catch (RequestException $ex){
            throw new Exception("error occured");
        }
    }
}
