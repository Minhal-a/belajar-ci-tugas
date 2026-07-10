<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run()
    {
        // tanggal mulai = tanggal migration dibuat (hari ini)
        $startDate = date("Y-m-d");

        // 10 data nominal diskon, bebas kamu ganti sesuai selera
        $nominalList = [
            100000, 150000, 200000, 250000, 300000,
            350000, 400000, 200000, 300000, 500000,
        ];

        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                // +0 hari = hari ini, +1 sampai +9 = 9 hari berikutnya
                'tanggal'    => date("Y-m-d", strtotime($startDate . " +{$i} day")),
                'nominal'    => $nominalList[$i],
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

        foreach ($data as $item) {
            $this->db->table('discount')->insert($item);
        }
    }
}