<?php

namespace App\models;

use InvalidArgumentException;

class Ent 
{
    public $id;
    public $startDate;
    public $endDate;
    public $price;

    public function __construct(\DateTime $startDate, \DateTime $endDate, float $price)
    {
        $startDate->setTime(0, 0, 0);
        $startDate->setTimezone(new \DateTimeZone('UTC'));

        $endDate->setTime(0, 0, 0);
        $endDate->setTimezone(new \DateTimeZone('UTC'));

        if ($startDate > $endDate)
            throw new InvalidArgumentException('End Date has to be greater or equal to Start Date');
        
        if ($price < 0)
            throw new InvalidArgumentException('Price cannot be negative');

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->price = $price;
    }    
}