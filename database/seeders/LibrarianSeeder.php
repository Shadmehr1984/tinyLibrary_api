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
        $librarian->name = $_ENV['SPECIAL_LIBRARIAN_NAME'];
        $librarian->email = $_ENV['SPECIAL_LIBRARAIN_EMAIL'];
        $librarian->password = Hash::make($_ENV['SPECIAL_LIBRARIAN_PASSWORD']);
        $librarian->phone = $_ENV['SPECIAL_LIBRARIAN_PHONE'];
        $librarian->address = $_ENV['SPECIAL_LIBRARIAN_ADDRESS'];
        $librarian->save();
        //random librarian
        Librarian::factory(2)->create();
    }
}
