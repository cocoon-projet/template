<?php

namespace Cocoon\View\Extensions;

use Cocoon\View\AbstractExtension;
use Carbon\Carbon;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            'timeago' => function ($date) {
                return Carbon::parse($date)->diffForHumans();
            },
            'age' => function ($date) {
                return Carbon::parse($date)->age;
            },
            'calendar' => function ($date) {
                return Carbon::parse($date)->format('j F Y');
            },
            'duration' => function ($seconds) {
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                
                if ($hours > 0) {
                    return $hours . ' heure' . ($hours > 1 ? 's' : '') .
                           ($minutes > 0 ? ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '');
                }
                
                return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            }
        ];
    }

    public function getFunctions(): array
    {
        return [
            'is_future' => function ($date) {
                return Carbon::parse($date)->isFuture();
            },
            'is_past' => function ($date) {
                return Carbon::parse($date)->isPast();
            },
            'is_today' => function ($date) {
                return Carbon::parse($date)->isToday();
            },
            'is_weekend' => function ($date) {
                return Carbon::parse($date)->isWeekend();
            }
        ];
    }

    public function getDirectives(): array
    {
        return [];
    }
}
