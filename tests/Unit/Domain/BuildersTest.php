<?php

namespace Tests\Unit\Domain;

use App\Domain\Builders\MemberBuilder;
use App\Domain\Entities\Member;
use App\Domain\Exceptions\NotSetAttributeException;
use App\Domain\ValueObjects\Date;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BuildersTest extends TestCase
{
    public function test_member_builder_builds_a_complete_member(): void
    {
        $member = (new MemberBuilder())
            ->set_id(7)
            ->set_name('Alex Reader')
            ->set_email(new Email('reader@example.test'))
            ->set_password('hashed-password')
            ->set_phone(new Phone('+491234567890'))
            ->set_address('Berlin')
            ->set_membership_date(new Date('2025-02-15'))
            ->set_active(true)
            ->set_penalty_balance(0)
            ->build();

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame(7, $member->get()['id']);
        $this->assertSame('Alex Reader', $member->get()['name']);
        $this->assertSame('reader@example.test', $member->get()['email']->get());
        $this->assertTrue($member->get()['active']);
        $this->assertSame(0, $member->get()['penalty_balance']);
    }

    public function test_member_builder_rejects_a_member_with_multiple_missing_attributes(): void
    {
        $builder = (new MemberBuilder())
            ->set_id(null)
            ->set_name('Incomplete Member');

        $this->expectException(NotSetAttributeException::class);
        $this->expectExceptionMessage('all attributes must be set');

        $builder->build();
    }

    public function test_member_builder_rejects_integer_active_values_other_than_zero_or_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('active must be 0 or 1');

        (new MemberBuilder())->set_active(2);
    }
}
