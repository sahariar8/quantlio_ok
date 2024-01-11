<?php

namespace App\Http\Controllers;
use App\Models\FdaTestDetail;
use Illuminate\Http\Request;

class FdaController extends Controller
{
    public function getFdaTestDetails($testName){
        $fdaTestDetails = FdaTestDetail::select('test_name', 'contraindications', 'precautions','warnings_and_cautions','drug_interactions','warnings')->where('test_name', '=', $testName)->distinct()->get();
        return view('fdaFurtherInformation', compact('fdaTestDetails'));
    }
}
