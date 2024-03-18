<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 50; $i++) {
            $totalAmount = rand(25000, 50000000) * 2;
            Voucher::create([
                'submitter_id' => rand(1, 10),
                'submitter_object' => 'customer',
                'total_amount' =>  $totalAmount,
                'object_type' => $faker->randomElement(['receipt', 'expense_voucher']),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}
