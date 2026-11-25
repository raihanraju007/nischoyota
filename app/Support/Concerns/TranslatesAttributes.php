<?php

namespace App\Support\Concerns;

use App\Support\Locale;

/**
 * Lets a model keep a Bangla copy of some columns in `<column>_bn`. While the page is in Bangla, reading the
 * column (`$product->name`) returns the Bangla text when there is one and the English text otherwise.
 * Writes, `getRawOriginal()` and the admin panel (always English) are not affected.
 *
 * The model lists the translated columns in `$translatedAttributes`.
 */
trait TranslatesAttributes
{
    public function getAttributeValue($key): mixed
    {
        $value = parent::getAttributeValue($key);

        if (! in_array($key, $this->translatedAttributes, true) || Locale::current() !== Locale::Bangla) {
            return $value;
        }

        $banglaValue = $this->attributes[$key.'_bn'] ?? null;

        return filled($banglaValue) ? $banglaValue : $value;
    }
}
