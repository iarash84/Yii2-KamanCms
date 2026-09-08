<?php

namespace tests\integration;

use frontend\models\DashboardPreference;
use PHPUnit\Framework\TestCase;

class DashboardPreferenceTest extends TestCase
{
    public function testLayoutIsRestrictedToKnownWidgetsAndCompleted(): void
    {
        $layout = DashboardPreference::normalize([
            'order' => ['analytics', 'unknown', 'analytics'],
            'hidden' => ['metrics', 'invalid'],
            'collapsed' => ['analytics', 'invalid'],
            'quick_links' => ['media', 'invalid', 'media', 'users'],
        ]);
        self::assertSame(['analytics', 'metrics', 'quick_actions', 'recent_activity', 'system_status'], $layout['order']);
        self::assertSame(['metrics'], $layout['hidden']);
        self::assertSame(['analytics'], $layout['collapsed']);
        self::assertSame(['media', 'users'], $layout['quick_links']);
    }

    public function testAllWidgetsCannotBePersistedAsHidden(): void
    {
        $layout = DashboardPreference::normalize([
            'hidden' => DashboardPreference::WIDGETS,
        ]);

        self::assertSame([], $layout['hidden']);
    }
}
