<?php

namespace App\Modules\Content\Models;

use App\Support\Concerns\TranslatesAttributes;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * body is Markdown; render it with PageService::renderBody(). is_system is set by the seeder only.
 */
#[Fillable(['slug', 'title', 'title_bn', 'subtitle', 'subtitle_bn', 'body', 'body_bn', 'meta_title', 'meta_description', 'is_published'])]
#[UseFactory(PageFactory::class)]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use TranslatesAttributes;

    /**
     * @var list<string>
     */
    protected array $translatedAttributes = ['title', 'subtitle', 'body'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
