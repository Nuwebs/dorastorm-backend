<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LaratrustSeeder::class);

        $root = Role::where("name", "superadmin")->first();
        User::factory()->create([
            'name' => 'Douglas',
            'email' => 'contacto@nuwebs.com.co',
        ])->addRole($root);
    }
}
