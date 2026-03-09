<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        User::create([
            'company_id' => $company->id,
            'name'       => 'システム管理者',
            'email'      => 'admin@example.com',
            'password'   => Hash::make('password'),
            'is_admin'   => true,
        ]);

        User::create([
            'company_id' => $company->id,
            'name'       => '田中 花子（部下）',
            'email'      => 'subordinate@example.com',
            'password'   => Hash::make('password'),
            'is_admin'   => false,
        ]);

        User::create([
            'company_id' => $company->id,
            'name'       => '山田 太郎（上司）',
            'email'      => 'manager@example.com',
            'password'   => Hash::make('password'),
            'is_admin'   => false,
        ]);
    }
}
