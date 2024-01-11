<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class DrugBrandExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = 'trades';
        $file = dirname(__FILE__) . '/files/drug-brand.xlsx';

        $array = Excel::toArray([], $file);

        $rxcuis = $array[0];

        foreach ($rxcuis as $key => $item) {
            if ($key == 0) continue;

            // print_r($item);
            DB::table($table)->insert([
                'generic' => $item[1],
                'brand' => $item[2]
            ]);
        }
    }
}
