<?php

namespace App\Utils;

enum DsFeature
{
    case AUTH;
    case ALLOW_SIGNUPS;
    case MAINTENANCE_ROUTES;

    public static function enabled(DsFeature $feature): bool
    {
        return in_array($feature, config('app.features'));
    }
}
