<?php

namespace App\Models;

class Tag extends \Spatie\Tags\Tag
{
    /**
     * Override
     * The return value is the name of the string used for no tag translations.
     * If you want to use the default tag translation system delete this function.
     * By default (if you delete this function) the names of the tags are set by
     * app()->getLocale() method.
     * If you want to customize the names of the i18n tags, if you need it,
     * you may change this method.
     */
    public static function getLocale()
    {
        return 'noi18n';
    }
}
