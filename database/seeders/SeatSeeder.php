<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Seat;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $rows = range('A', 'L');
        foreach ($rows as $row) {
            for ($i = 1; $i <= 10; $i++) {
                Seat::create([
                    'row' => $row,
                    'number' => $i,
                    'label' => $row . $i,
                ]);
            }
        }
    }
}
