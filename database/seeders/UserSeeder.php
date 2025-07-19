<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'Admin Lesles.id',
            'role' => 'admin',
            'phone' => '',
            'email' => 'admin@lesles.id',
            'password' => 'thinkplanexecute',
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Operator Billy Lesles.id',
            'role' => 'operator',
            'email' => 'operator_billy@lesles.id',
            'password' => 'opbilly_lesles.id',
            'profile_complete' => true,
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Operator Khafid Lesles.id',
            'role' => 'operator',
            'email' => 'operator_khafid@lesles.id',
            'password' => 'opkhafid_lesles.id',
            'profile_complete' => true,
        ]);
        \App\Models\User::factory()->create([
            'name' => 'Operator Kholidah Lesles.id',
            'role' => 'operator',
            'email' => 'operator_kholidah@lesles.id',
            'password' => 'opkholidah_lesles.id',
            'profile_complete' => true,
        ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Dani Alfaza',
        //     'role' => 'guru',
        //     'phone' => '085123456782',
        //     'email' => 'dani@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Bahar Yusuf Zakaria',
        //     'role' => 'guru',
        //     'phone' => '085123456783',
        //     'email' => 'bahar@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Naufal Fakhrian',
        //     'role' => 'guru',
        //     'phone' => '085123456784',
        //     'email' => 'naufal@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Faris Akbar',
        //     'role' => 'guru',
        //     'phone' => '085123456785',
        //     'email' => 'faris@mail.com',
        //     'password' => 'password',
        // ]);

        
        // \App\Models\User::factory()->create([
        //     'name' => 'Andi Pratama Putra',
        //     'role' => 'murid',
        //     'phone' => '085123456786',
        //     'email' => 'andi@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Siti Nurhaliza Ramadhani',
        //     'role' => 'murid',
        //     'phone' => '085123456787',
        //     'email' => 'siti@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Rizky Maulana Akbar',
        //     'role' => 'murid',
        //     'phone' => '085123456788',
        //     'email' => 'rizky@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Dewi Kartika Sari',
        //     'role' => 'murid',
        //     'phone' => '085123456789',
        //     'email' => 'dewi@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Muhammad Ilham Saputra',
        //     'role' => 'murid',
        //     'phone' => '085123456790',
        //     'email' => 'ilham@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Lestari Ayu',
        //     'role' => 'murid',
        //     'phone' => '085123456791',
        //     'email' => 'lestari@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Agus Santoso',
        //     'role' => 'murid',
        //     'phone' => '085123456792',
        //     'email' => 'agus@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Febriana Hapsari',
        //     'role' => 'murid',
        //     'phone' => '085123456793',
        //     'email' => 'febriana@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Hendra Wijaya',
        //     'role' => 'murid',
        //     'phone' => '085123456794',
        //     'email' => 'hendra@mail.com',
        //     'password' => 'password',
        // ]);
        // \App\Models\User::factory()->create([
        //     'name' => 'Intan Permatasari',
        //     'role' => 'murid',
        //     'phone' => '085123456795',
        //     'email' => 'intan@mail.com',
        //     'password' => 'password',
        // ]);
    }
}
