<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('currencies')->insert([
            ['code'=>'USD','name'=>'US Dollar','symbol'=>'$','is_default'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['code'=>'KHR','name'=>'Cambodian Riel','symbol'=>'៛','is_default'=>false,'created_at'=>now(),'updated_at'=>now()],
        ]);

        DB::table('payment_methods')->insert([
            ['code'=>'CASH','name'=>'Cash','created_at'=>now(),'updated_at'=>now()],
            ['code'=>'BAKONG','name'=>'Bakong','created_at'=>now(),'updated_at'=>now()],
            ['code'=>'KHQR','name'=>'KHQR','created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
