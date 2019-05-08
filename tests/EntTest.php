<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\models\Ent;

class EntTest extends TestCase
{
    public function testCreateInstance(): void
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $ent = new Ent($today, $tomorrow, 15.5);

        $this->assertInstanceOf(Ent::class, $ent);
        $this->assertEquals($today, $ent->startDate);
        $this->assertEquals($tomorrow, $ent->endDate);
        $this->assertEquals(15.5, $ent->price);
    }

    public function testStartDateCannotBeGreaterThanEndDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('End Date has to be greater or equal to Start Date'));

        new Ent(new \DateTime('tomorrow'), new \DateTime('today'), 90);
    }

    public function testEndDateIsGreaterThanStartDate(): void
    {
        $ent = new Ent(new \DateTime('today'), new \DateTime('tomorrow'), 90);

        $this->assertTrue($ent->endDate > $ent->startDate);
    }

    public function testPriceCannotBeNegative(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Price cannot be negative'));

        new Ent(new \DateTime('today'), new \DateTime('tomorrow'), -90);
    }

    public function testCanPriceIsPositive(): void
    {
        $ent = new Ent(new \DateTime('today'), new \DateTime('tomorrow'), 90);

        $this->assertTrue($ent->price >= 0);
    }
}