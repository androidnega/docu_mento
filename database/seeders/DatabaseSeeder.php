<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InstitutionSeeder::class);
        $this->call(TtuFacultiesDepartmentsSeeder::class);
        $this->call(StaffSeeder::class);
        $this->call(GroupNameSeeder::class);
    }
}
