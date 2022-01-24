<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPMail\ValueObjects\Address;
use Snicco\Component\BetterWPMail\ValueObjects\AddressList;

final class AddressListTest extends WPTestCase
{
    
    /** @test */
    public function duplicate_addresses_are_filtered_out()
    {
        $address1 = Address::create('calvin@web.de');
        $address2 = Address::create('calvin@web.de');
        
        $list = new AddressList([$address1, $address2]);
        
        $this->assertCount(1, $list);
        
        $count = 0;
        foreach ($list as $key => $address) {
            $count++;
            $this->assertIsInt($key);
            $this->assertSame($address1, $address);
        }
        $this->assertSame(1, $count);
    }
    
    /** @test */
    public function duplicate_addresses_are_filtered_out_if_they_have_the_same_email_and_different_name()
    {
        $address1 = Address::create('Calvin <calvin@web.de>');
        $address2 = Address::create('Marlon <calvin@web.de>');
        
        $list = new AddressList([$address1, $address2]);
        
        $this->assertCount(1, $list);
        
        $count = 0;
        foreach ($list as $key => $address) {
            $count++;
            $this->assertIsInt($key);
            $this->assertSame($address1, $address);
        }
        $this->assertSame(1, $count);
    }
    
    /** @test */
    public function test_has()
    {
        $address1 = Address::create('calvin@web.de');
        $address2 = Address::create('marlon@web.de');
        
        $list = new AddressList([$address1, $address2]);
        $this->assertTrue($list->has('calvin@web.de'));
        $this->assertTrue($list->has('marlon@web.de'));
        $this->assertFalse($list->has('john@web.de'));
        
        $this->assertTrue($list->has('Calvin Alkan <calvin@web.de>'));
        $this->assertTrue($list->has(Address::create('calvin@web.de')));
    }
    
}