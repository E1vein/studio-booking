<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Studio;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create(['name' => 'Admin Studio', 'email' => 'admin@demo.com',
            'password' => bcrypt('password123'), 'role' => 'admin']);
        User::create(['name' => 'Client Demo', 'email' => 'user@demo.com',
            'password' => bcrypt('password123'), 'role' => 'client']);

        $studios = [
            ['Studio A - Podcast', 'Ruang kedap suara untuk podcast & rekaman vokal.', 150000, 4, [
                ['podcast1', 'Meja podcast 4 mikrofon'],
                ['podcast2', 'Dinding akustik kedap suara'],
                ['podcast3', 'Ruang kontrol & mixer'],
            ]],
            ['Studio B - Foto', 'Studio foto dengan backdrop dan lighting lengkap.', 200000, 8, [
                ['foto1', 'Backdrop putih & warna'],
                ['foto2', 'Softbox & strobe lighting'],
                ['foto3', 'Area ganti & makeup'],
            ]],
            ['Studio C - Musik', 'Ruang band dengan drum, amp, dan PA system.', 180000, 6, [
                ['musik1', 'Drum set lengkap'],
                ['musik2', 'Amp gitar & bass'],
                ['musik3', 'PA system & monitor'],
            ]],
        ];

        foreach ($studios as [$name, $desc, $rate, $cap, $photos]) {
            $studio = Studio::create([
                'name' => $name, 'description' => $desc,
                'hourly_rate' => $rate, 'capacity' => $cap,
            ]);

            foreach (range(0, 6) as $day) {
                $studio->schedules()->create([
                    'day_of_week' => $day, 'open_time' => '09:00', 'close_time' => '21:00',
                ]);
            }

            foreach ($photos as $order => [$seed, $caption]) {
                $studio->photos()->create([
                    'path'       => "https://picsum.photos/seed/{$seed}/800/500",
                    'caption'    => $caption,
                    'sort_order' => $order,
                ]);
            }
        }
    }
}
