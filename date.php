<?php
if (!function_exists('datetime'))
{
    function datetime($date = 'now')
    {
        Carbon::setLocale(config('lang'));
        $date = new Carbon($date);
        $date->timezone = new DateTimeZone(config('time_zone'));
        return $date;
    }
}

if (!function_exists('diffForHumans'))
{
    function diffForHumans($date)
    {
        return datetime($date)->diffForHumans();
    }
}