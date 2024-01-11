<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = 'permissions';

        $premission = [
            'insert-metabolites',
            'edit-metabolites',
            'update-metabolites',
            'delete-metabolites',
            'insert-rxcui',
            'edit-rxcui',
            'update-rxcui',
            'delete-rxcui',
            'insert-trades',
            'edit-trades',
            'update-trades',
            'delete-trades',
        ];

        foreach ($premission as $key => $item) {

            // print_r($item);
            DB::table($table)->insert([
                'name' => $item,
                'guard_name' => 'web',
                'groupedList_id' => 2
            ]);
        }
    }
}
