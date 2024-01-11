<?php

namespace App\Http\Controllers;

use App\Models\Rxcui;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class RxCUIController extends Controller
{
    //
    function __construct()
    {
        // Add your middleware logic here
        $this->middleware('permission:insert-rxcui', ['only' => ['create']]);
        $this->middleware('permission:edit-rxcui', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-rxcui', ['only' => ['destroy']]);
    }

    public function index()
    {
        $rxcui = Rxcui::all();
        return view('rxcui', ['rxcuiItems' => $rxcui])->with('i');
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'drugsName' => 'required',
            'RxCUI' => 'required',
            'parentDrugName' => 'nullable',
            'parentRxcui' => 'nullable',
            'analyt' => 'nullable'
        ]);

        if ($validator->fails()) {
            return redirect('/rxcui')->with('validation_error', ' ')->withErrors($validator)->withInput();
        } else {
            $rxcui = new Rxcui;
            $rxcui->drugsName = $request->input('drugsName');
            $rxcui->RxCUI = $request->input('RxCUI');
            $rxcui->parentDrugName = $request->input('parentDrugName');
            $rxcui->parentRxcui = $request->input('parentRxcui');
            $rxcui->analyt = $request->input('analyt');

            if ($rxcui->save()) {
                return redirect('/rxcui')->with('save_success', 'RxCUI created successfully.');
            } else {
                return redirect('/rxcui')->with('save_error', 'There is some problem while save!');
            }
        }
    }

    public function edit($id)
    {
        $rxcui = Rxcui::find($id);
        return response()->json([
            'status' => 200,
            'rxcuiItems' => $rxcui,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'drugsName' => 'required|unique:rxcuis,drugsName,' . $id,
            'RxCUI' => 'required',
            'parentDrugName' => 'nullable',
            'parentRxcui' => 'nullable',
            'analyt' => 'nullable'
        ]);

        if ($validator->fails()) {
            return redirect('/rxcui')->with('validation_error', $id)->withErrors($validator)->withInput();
        } else {
            $rxcui = Rxcui::find($id);
            $rxcui->drugsName = $request->input('drugsName');
            $rxcui->RxCUI = $request->input('RxCUI');
            $rxcui->parentDrugName = $request->input('parentDrugName');
            $rxcui->parentRxcui = $request->input('parentRxcui');
            $rxcui->analyt = $request->input('analyt');

            if ($rxcui->update()) {
                return redirect('/rxcui')->with('save_success', 'RxCUI updated successfully.');
            } else {
                return redirect('/rxcui')->with('save_error', 'There is some problem while save!');
            }
        }
    }


    public function destroy(Request $request)
    {
        $rxcuiId = $request->input('deleting_id');
        $rxcui = Rxcui::find($rxcuiId);
        if ($rxcui->delete()) {
            return redirect('/rxcui')->with('save_success', 'RxCUI Item deleted successfully.');
        } else {
            return redirect('/rxcui')->with('save_error', 'There is some problem while Delete!');
        }
    }
}
