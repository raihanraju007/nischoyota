<?php

namespace App\Modules\Account\Http\Requests;

use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyCodeRequest extends FormRequest
{
    use NormalizesStringInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    public function code(): string
    {
        return $this->string('code')->toString();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings(['code' => fn (string $code): ?string => preg_replace('/\s+/', '', $code)]);
    }
}
