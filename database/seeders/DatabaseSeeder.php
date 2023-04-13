<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        User::factory()->create([
            'name' => 'Douglas Ramírez',
            'email' => 'contacto@nuwebs.com.co',
        ]);
        $this->call(LaratrustSeeder::class);
        User::where('email', 'contacto@nuwebs.com.co')->first()->addRole('superadmin');
    }
}
