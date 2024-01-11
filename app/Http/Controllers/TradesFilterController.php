<?php

namespace App\Http\Controllers;

use App\Models\OrderCodeQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class TradesFilterController extends Controller
{
    function __construct()
     {
         // Add your middleware logic here
        //  $this->middleware('pdelete-failed-ordersermission:insert-trades', ['only' => ['create']]);
        //  $this->middleware('permission:edit-trades', ['only' => ['edit', 'update']]);
         $this->middleware('permission:delete.failed.orders', ['only' => ['destroy']]);
     }

    public function index()

    {
        $filteredRecords = OrderCodeQueue::where('numberOfFailur', '>=', 5)->get();
        return view('trades_filter.tradesFilter', ['trades' => $filteredRecords])->with('i');
    }

    public function create()
    {
        return view('trades_filter.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'generic' => 'required|string',
            'brand' => 'required|string',
        ]);

        ordercodequeue::create($data);
        return redirect()->route('trades')->with('success', 'Trades Add Successfully');
    }

    // public function edit(ordercodequeue $tradesFilter)
    // {
    //     return view('trades_filter.edit',['trades' => $tradesFilter]);

    // }

    // public function update(Request $request, ordercodequeue $tradesFilter)
    // {
    //     $data = $request->validate([
    //         'generic'=>'required|string',
    //         'brand'=>'required|string',
    //     ]);
    //     $tradesFilter->update($data);
    //     return redirect()->route('trades')->with('success','Trades Updated Successfully');
    // }

    // public function destroy(OrderCodeQueue $tradesFilter)
    // {
    //     $tradesFilter->delete();
    //     return redirect()->route('trades')->with('success', 'Trades Deleted Successfully');
    // }

    public function destroy(Request $request){
        
        $testId = $request->input('delete_test_id');
        $test   = ordercodequeue::find($testId);
        $test->delete();
 
        return redirect()->back()->with('status','Test deleted successfully');
     } 

}
