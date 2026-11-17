<?php

namespace Tests\Support\Helpers;

use App\Models\Librarian;
use App\Models\Member;

trait AuthenticatesApiUsers
{
    protected function memberToken(Member $member, string $name = 'api-test'): string
    {
        return $member->createToken($name)->plainTextToken;
    }

    protected function librarianToken(Librarian $librarian, string $name = 'api-test'): string
    {
        return $librarian->createToken($name)->plainTextToken;
    }

    protected function authenticateAsMember(Member $member): static
    {
        return $this->withToken($this->memberToken($member));
    }

    protected function authenticateAsLibrarian(Librarian $librarian): static
    {
        return $this->withToken($this->librarianToken($librarian));
    }
}
