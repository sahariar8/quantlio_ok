<?php

namespace App\Services;

use Config;
use Illuminate\Support\Facades\Http;
use App\Models\Rxcui;

class RxcuiService
{
    public function getAllRxcuiCodes()
    {
        return Rxcui::all();
    }

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
            // Log::channel('error')->error('Problem in fetching data from requested URL');
            abort(404, 'Problem in fetching data from requested URL');
        }
    }

    public function _getAllRxcuiCodesByMedication($medications)
    {
        $rxcuiCodes = $this->getAllRxcuiCodes()->collect();

        $medListWithRxcuiCode = array_map(function ($medication) use ($rxcuiCodes) {
            $foundCodeByMed = $rxcuiCodes->filter(function ($code) use ($medication) {
                return strtolower($code->drugsName) == strtolower($medication);
            })->first();

            // print_r($foundCodeByMed);
            $code = null;

            // this var id for test purpose
            $codeWithParent = [
                "code" => null,
                "parent" => null
            ];

            if ($foundCodeByMed) {
                if ($foundCodeByMed["RxCUI"] != null && $foundCodeByMed["RxCUI"] != "not found") {
                    $code = $foundCodeByMed["RxCUI"];
                    $codeDev["code"] = $code;
                } else if (
                    $foundCodeByMed["RxCUI"] == "not found"
                    && ($foundCodeByMed["parentRxcui"] != null
                        && $foundCodeByMed['parentRxcui'] != "not found"
                    )
                ) {
                    $code = $foundCodeByMed["parentRxcui"];
                    $codeDev["parent"] = $code;
                } else if ($foundCodeByMed["RxCUI"] == "not found") {
                    //TODO: uncomment here : jafar
                    $code = $this->getRxcui($medication);
                }
            } else {
                 //TODO: uncomment here : jafar
               $code = $this->getRxcui($medication);
            }

            return [
                "medication" => $medication,
                "rxcuiCode" => $code
            ];
        }, $medications);

        return $medListWithRxcuiCode;
    }

    public function getAllRxcuiCodesByMedication($codes)
    {
        $respCodes = [];
        foreach ($this->_getAllRxcuiCodesByMedication($codes) as $item) {
            $respCodes[$item['medication']] = [
                'rxcuiCode' => $item['rxcuiCode']
            ];
        }
        return $respCodes;
    }
}
