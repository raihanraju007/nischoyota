<?php

namespace App\Support;

/**
 * For FormRequests: tidies text fields before validation. A field sent as an array (`phone[]=1`) is left
 * untouched, so its `string` rule rejects it instead of the cast throwing "Array to string conversion".
 */
trait NormalizesStringInput
{
    /**
     * @param  array<string, callable(string): mixed>  $normalizers  field => how to tidy its string value
     */
    protected function normalizeStrings(array $normalizers): void
    {
        $normalized = [];

        foreach ($normalizers as $field => $normalize) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = $normalize($value);
            }
        }

        $this->merge($normalized);
    }
}
