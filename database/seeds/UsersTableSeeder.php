<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'first_name' => 'admin',
            'last_name' => 'admin',
            'display_name' => 'admin',
            'category'=> 'Female',
            'email' => 'dinkyk@ilogixinfotech.com',
            'password' => bcrypt('admin@123'),
            'type' => '2'
        ]);
    }
}
