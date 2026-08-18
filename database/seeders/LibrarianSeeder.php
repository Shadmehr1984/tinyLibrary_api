<?php

namespace Database\Seeders;

use App\Models\Librarian;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LibrarianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //special librarian
        $librarian = new Librarian();
        $librarian->name = 'shady';
        $librarian->email = 'ssszzzast@gmail.com';
        $librarian->password = Hash::make('ssszzzast21');
        $librarian->phone = '+989389262193';
        $librarian->address = 'yek ja dar kiran';
        $librarian->save();
        //random librarian
        Librarian::factory(2)->create();
    }
}
