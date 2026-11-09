<?php

namespace App\Services;

class NationalHolidayService
{
    /**
     * Master daftar hari libur nasional Indonesia & cuti bersama resmi
     * Format: 'YYYY-MM-DD' => ['name' => '...', 'is_cuti_bersama' => bool]
     */
    protected array $holidays = [
        // --- TAHUN 2025 ---
        '2025-01-01' => ['name' => 'Tahun Baru 2025 Masehi', 'is_cuti_bersama' => false],
        '2025-01-27' => ['name' => "Isra Mi'raj Nabi Muhammad SAW", 'is_cuti_bersama' => false],
        '2025-01-28' => ['name' => 'Cuti Bersama Tahun Baru Imlek', 'is_cuti_bersama' => true],
        '2025-01-29' => ['name' => 'Tahun Baru Imlek 2576 Kongzili', 'is_cuti_bersama' => false],
        '2025-03-28' => ['name' => 'Cuti Bersama Hari Suci Nyepi', 'is_cuti_bersama' => true],
        '2025-03-29' => ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)', 'is_cuti_bersama' => false],
        '2025-03-31' => ['name' => 'Hari Raya Idul Fitri 1446 H (Hari 1)', 'is_cuti_bersama' => false],
        '2025-04-01' => ['name' => 'Hari Raya Idul Fitri 1446 H (Hari 2)', 'is_cuti_bersama' => false],
        '2025-04-02' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2025-04-03' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2025-04-04' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2025-04-07' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2025-04-18' => ['name' => 'Wafat Yesus Kristus (Jumat Agung)', 'is_cuti_bersama' => false],
        '2025-04-20' => ['name' => 'Kebangkitan Yesus Kristus (Paskah)', 'is_cuti_bersama' => false],
        '2025-05-01' => ['name' => 'Hari Buruh Internasional', 'is_cuti_bersama' => false],
        '2025-05-12' => ['name' => 'Hari Raya Waisak 2569 BE', 'is_cuti_bersama' => false],
        '2025-05-13' => ['name' => 'Cuti Bersama Hari Raya Waisak', 'is_cuti_bersama' => true],
        '2025-05-29' => ['name' => 'Kenaikan Yesus Kristus', 'is_cuti_bersama' => false],
        '2025-05-30' => ['name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'is_cuti_bersama' => true],
        '2025-06-01' => ['name' => 'Hari Lahir Pancasila', 'is_cuti_bersama' => false],
        '2025-06-06' => ['name' => 'Hari Raya Idul Adha 1446 H', 'is_cuti_bersama' => false],
        '2025-06-09' => ['name' => 'Cuti Bersama Hari Raya Idul Adha', 'is_cuti_bersama' => true],
        '2025-06-27' => ['name' => 'Tahun Baru Islam 1447 H (1 Muharram)', 'is_cuti_bersama' => false],
        '2025-08-17' => ['name' => 'Proklamasi Kemerdekaan RI (HUT RI ke-80)', 'is_cuti_bersama' => false],
        '2025-09-05' => ['name' => 'Maulid Nabi Muhammad SAW', 'is_cuti_bersama' => false],
        '2025-12-25' => ['name' => 'Hari Raya Natal', 'is_cuti_bersama' => false],
        '2025-12-26' => ['name' => 'Cuti Bersama Hari Raya Natal', 'is_cuti_bersama' => true],

        // --- TAHUN 2026 ---
        '2026-01-01' => ['name' => 'Tahun Baru 2026 Masehi', 'is_cuti_bersama' => false],
        '2026-01-16' => ['name' => "Isra Mi'raj Nabi Muhammad SAW (1447 H)", 'is_cuti_bersama' => false],
        '2026-02-16' => ['name' => 'Cuti Bersama Tahun Baru Imlek', 'is_cuti_bersama' => true],
        '2026-02-17' => ['name' => 'Tahun Baru Imlek 2577 Kongzili', 'is_cuti_bersama' => false],
        '2026-03-18' => ['name' => 'Cuti Bersama Hari Suci Nyepi', 'is_cuti_bersama' => true],
        '2026-03-19' => ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'is_cuti_bersama' => false],
        '2026-03-20' => ['name' => 'Hari Raya Idul Fitri 1447 H (Hari 1)', 'is_cuti_bersama' => false],
        '2026-03-21' => ['name' => 'Hari Raya Idul Fitri 1447 H (Hari 2)', 'is_cuti_bersama' => false],
        '2026-03-23' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2026-03-24' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'is_cuti_bersama' => true],
        '2026-04-03' => ['name' => 'Wafat Yesus Kristus (Jumat Agung)', 'is_cuti_bersama' => false],
        '2026-04-05' => ['name' => 'Kebangkitan Yesus Kristus (Paskah)', 'is_cuti_bersama' => false],
        '2026-05-01' => ['name' => 'Hari Buruh Internasional', 'is_cuti_bersama' => false],
        '2026-05-14' => ['name' => 'Kenaikan Yesus Kristus', 'is_cuti_bersama' => false],
        '2026-05-15' => ['name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'is_cuti_bersama' => true],
        '2026-05-27' => ['name' => 'Hari Raya Idul Adha 1447 H', 'is_cuti_bersama' => false],
        '2026-05-28' => ['name' => 'Cuti Bersama Hari Raya Idul Adha', 'is_cuti_bersama' => true],
        '2026-05-31' => ['name' => 'Hari Raya Waisak 2570 BE', 'is_cuti_bersama' => false],
        '2026-06-01' => ['name' => 'Hari Lahir Pancasila', 'is_cuti_bersama' => false],
        '2026-06-16' => ['name' => 'Tahun Baru Islam 1448 H (1 Muharram)', 'is_cuti_bersama' => false],
        '2026-08-17' => ['name' => 'Hari Kemerdekaan RI (HUT RI ke-81)', 'is_cuti_bersama' => false],
        '2026-08-25' => ['name' => 'Maulid Nabi Muhammad SAW (1448 H)', 'is_cuti_bersama' => false],
        '2026-12-25' => ['name' => 'Hari Raya Natal', 'is_cuti_bersama' => false],
        '2026-12-26' => ['name' => 'Cuti Bersama Hari Raya Natal', 'is_cuti_bersama' => true],

        // --- TAHUN 2027 ---
        '2027-01-01' => ['name' => 'Tahun Baru 2027 Masehi', 'is_cuti_bersama' => false],
        '2027-01-05' => ['name' => "Isra Mi'raj Nabi Muhammad SAW", 'is_cuti_bersama' => false],
        '2027-02-06' => ['name' => 'Tahun Baru Imlek 2578 Kongzili', 'is_cuti_bersama' => false],
        '2027-03-09' => ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1949)', 'is_cuti_bersama' => false],
        '2027-03-10' => ['name' => 'Hari Raya Idul Fitri 1448 H (Hari 1)', 'is_cuti_bersama' => false],
        '2027-03-11' => ['name' => 'Hari Raya Idul Fitri 1448 H (Hari 2)', 'is_cuti_bersama' => false],
        '2027-03-26' => ['name' => 'Wafat Yesus Kristus (Jumat Agung)', 'is_cuti_bersama' => false],
        '2027-03-28' => ['name' => 'Kebangkitan Yesus Kristus (Paskah)', 'is_cuti_bersama' => false],
        '2027-05-01' => ['name' => 'Hari Buruh Internasional', 'is_cuti_bersama' => false],
        '2027-05-06' => ['name' => 'Kenaikan Yesus Kristus', 'is_cuti_bersama' => false],
        '2027-05-16' => ['name' => 'Hari Raya Idul Adha 1448 H', 'is_cuti_bersama' => false],
        '2027-05-20' => ['name' => 'Hari Raya Waisak 2571 BE', 'is_cuti_bersama' => false],
        '2027-06-01' => ['name' => 'Hari Lahir Pancasila', 'is_cuti_bersama' => false],
        '2027-06-06' => ['name' => 'Tahun Baru Islam 1449 H', 'is_cuti_bersama' => false],
        '2027-08-15' => ['name' => 'Maulid Nabi Muhammad SAW', 'is_cuti_bersama' => false],
        '2027-08-17' => ['name' => 'Hari Kemerdekaan RI (HUT RI ke-82)', 'is_cuti_bersama' => false],
        '2027-12-25' => ['name' => 'Hari Raya Natal', 'is_cuti_bersama' => false],
    ];

    /**
     * Dapatkan daftar hari libur dalam rentang tanggal tertentu (Y-m-d).
     */
    public function getHolidays(string $from, string $to): array
    {
        $result = [];

        foreach ($this->holidays as $date => $info) {
            if ($date >= $from && $date <= $to) {
                $result[] = [
                    'date'             => $date,
                    'name'             => $info['name'],
                    'is_cuti_bersama'  => $info['is_cuti_bersama'],
                    'is_holiday'       => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Cek apakah tanggal merupakan hari libur nasional atau cuti bersama.
     */
    public function getHoliday(string $date): ?array
    {
        return $this->holidays[$date] ?? null;
    }

    /**
     * Dapatkan seluruh data libur (untuk passing ke view / JSON).
     */
    public function all(): array
    {
        return $this->holidays;
    }
}

