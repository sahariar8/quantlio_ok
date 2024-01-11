<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RxcuiExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = 'rxcuis';
        $file = dirname(__FILE__) . '/files/rxcui.xlsx';

        $array = Excel::toArray([], $file);

        $rxcuis = $array[0];

        foreach ($rxcuis as $key => $item) {
            if ($key == 0) continue;

            // print_r($item);
            DB::table($table)->insert([
                'drugsName' => $item[1],
                'RxCUI' => $item[2],
                'parentDrugName' => $item[3],
                'parentRxcui' => $item[4],
                'analyt' => $item[5]
            ]);
        }
    }
}
