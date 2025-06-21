<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Dany Guru',
        //     'role' => 'guru',
        //     'phone' => '085123456789',
        //     'email' => 'dany@mail.com',
        // ]);

        $this->call([
            UserSeeder::class,
            UserLevelSeeder::class,
        ]);

        $this->call([
            ClassSeeder::class,
        ]);

    }
}
