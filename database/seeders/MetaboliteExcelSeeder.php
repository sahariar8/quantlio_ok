<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MetaboliteExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = 'metabolites';
        $file = dirname(__FILE__) . '/files/drug-clas-and-metabolites.xlsx';

        $array = Excel::toArray([], $file);

        $metabolites = $array[0];

        foreach ($metabolites as $key => $item) {
            if ($key == 0) continue;

            // print_r($item);
            DB::table($table)->insert([
                'testName' => $item[1],
                'class' => $item[2],
                'parent' => $item[3],
                'metabolite' => $item[4]
            ]);
        }
    }
}
