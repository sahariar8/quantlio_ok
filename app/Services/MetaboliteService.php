<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MetaboliteService
{
    public function getAll()
    {
        return DB::table('metabolites')->whereNotNull('metabolite')->get();
    }

    public function getMetaboliteByTest($tests)
    {
        $metabolites = $this->getAll();
        $matchMetabolites = array_map(
            function ($test) use ($metabolites) {
                $foundMetabolite = $metabolites->filter(function ($code) use ($test) {
                    return strtolower($code->testName) == strtolower($test);
                })->first();

                if ($foundMetabolite) {
                    return [
                        'testName' => $test,
                        'className' => $foundMetabolite->class,
                        'metabolite' => $foundMetabolite->metabolite
                    ];
                }

                return [
                    'testName' => $test,
                    'className' => null,
                    'metabolite' => null
                ];
            },
            $tests
        );

        $resp = [];

        foreach ($matchMetabolites as $item) {
            $resp[$item['testName']] = [
                "className" => $item['className'],
                "metabolite" => $item['metabolite']
            ];
        }

        return $resp;
    }

    public function _getMetaboliteByTest($test)
    {
        // $arr = [];
        // foreach ($this->_getMetaboliteByTest($test) as $item) {
        //     array_push($arr, $item);
        // }
        // return $arr;
    }
}
