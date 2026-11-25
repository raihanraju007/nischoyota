<?php

namespace App\Modules\Dashboard\Http\Requests\Admin;

use App\Modules\Dashboard\Enums\ChartPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowDashboardRequest extends FormRequest
{
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
            'period' => ['nullable', Rule::enum(ChartPeriod::class)],
        ];
    }

    public function period(): ChartPeriod
    {
        return ChartPeriod::tryFrom($this->integer('period')) ?? ChartPeriod::Last7Days;
    }
}
