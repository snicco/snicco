<?php

namespace Snicco\Support;

use DateInterval;
use DateTimeInterface;
use Carbon\Carbon as BaseCarbon;
use Snicco\Traits\InteractsWithTime;
use Carbon\CarbonImmutable as BaseCarbonImmutable;

class Carbon extends BaseCarbon
{
    
    use InteractsWithTime;
    
    /**
     * Get a GMT datetime string for usage in HTTP headers
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     *
     * @return string
     */
    public static function lastModified($delay = 0) :string
    {
        return self::expires($delay);
    }
    
    /**
     * Get a GMT datetime string for usage in HTTP headers
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     *
     * @return string
     */
    public static function expires($delay = 0) :string
    {
        $timestamp = self::availableAt($delay);
        return parent::createFromTimestampUTC($timestamp)->format('D, d M Y H:i:s').' GMT';
    }
    
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
    
    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     *
     * @return int
     */
    protected static function availableAt($delay = 0) :int
    {
        $delay = self::parseDateInterval($delay);
        
        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : self::now()->addRealSeconds($delay)->getTimestamp();
    }
    
    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     *
     * @return DateTimeInterface|int
     */
    protected static function parseDateInterval($delay)
    {
        if ($delay instanceof DateInterval) {
            $delay = self::now()->add($delay);
        }
        
        return $delay;
    }
    
}