<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use WP_User;
use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\ValueObjects\Mailbox;

use function array_merge;

final class AddressTest extends WPTestCase
{
    
    /** @test */
    public function test_from_string()
    {
        $address = Mailbox::create('calvin@web.de');
        $this->assertSame('calvin@web.de', $address->address());
        $this->assertSame('calvin@web.de', $address->toString());
        $this->assertSame('', $address->name());
        
        $address = Mailbox::create('Calvin Alkan <calvin@web.de>');
        $this->assertSame('calvin@web.de', $address->address());
        $this->assertSame('Calvin Alkan <calvin@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
    }
    
    /** @test */
    public function test_from_string_throws_exception_for_invalid_email()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[calvin@webde] is not a valid email');
        $address = Mailbox::create('calvin@webde');
    }
    
    /** @test */
    public function test_from_string_throws_exception_for_bad_pattern()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[Calvin <calvin@webde] is not a valid address');
        $address = Mailbox::create('Calvin <calvin@webde');
    }
    
    /** @test */
    public function test_from_array_with_names_keys()
    {
        $address = Mailbox::create(['name' => 'Calvin Alkan', 'email' => 'c@web.de']);
        $this->assertSame('c@web.de', $address->address());
        $this->assertSame('Calvin Alkan <c@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
        
        $address = Mailbox::create(['email' => 'c@web.de', 'name' => 'Calvin Alkan',]);
        $this->assertSame('c@web.de', $address->address());
        $this->assertSame('Calvin Alkan <c@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
    }
    
    /** @test */
    public function test_from_array_with_numerical_keys()
    {
        $address = Mailbox::create(['c@web.de', 'Calvin Alkan']);
        $this->assertSame('c@web.de', $address->address());
        $this->assertSame('Calvin Alkan <c@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
    }
    
    /** @test */
    public function test_from_array_with_numerical_keys_must_have_email_part_first()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[calvin alkan] is not a valid email.');
        
        $address = Mailbox::create(['Calvin Alkan', 'c@web.de']);
    }
    
    /** @test */
    public function test_from_wp_user_with_first_name_and_last_name()
    {
        $admin = $this->createAdmin([
            'first_name' => 'Calvin',
            'last_name' => 'Alkan',
            'user_email' => 'c@web.de',
        ]);
        
        $address = Mailbox::create($admin);
        
        $this->assertSame('c@web.de', $address->address());
        $this->assertSame('Calvin Alkan <c@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
        
        $admin = $this->createAdmin([
            'first_name' => 'Marlon',
            'user_email' => 'm@web.de',
        ]);
        
        $address = Mailbox::create($admin);
        
        $this->assertSame('m@web.de', $address->address());
        $this->assertSame('Marlon <m@web.de>', $address->toString());
        $this->assertSame('Marlon', $address->name());
    }
    
    /** @test */
    public function test_from_wp_user_with_only_display_name()
    {
        $admin = $this->createAdmin([
            'display_name' => 'Calvin Alkan',
            'user_email' => 'c@web.de',
        ]);
        
        $address = Mailbox::create($admin);
        
        $this->assertSame('c@web.de', $address->address());
        $this->assertSame('Calvin Alkan <c@web.de>', $address->toString());
        $this->assertSame('Calvin Alkan', $address->name());
    }
    
    /** @test */
    public function test_exception_if_no_valid_argument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '$address has to be string,array or an instance of WP_User. Got [integer].'
        );
        
        $address = Mailbox::create(1);
    }
    
    private function createAdmin(array $data) :WP_User
    {
        return $this->factory()->user->create_and_get(
            array_merge($data, ['role' => 'administrator'])
        );
    }
    
}