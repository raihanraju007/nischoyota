<?php

namespace App\Modules\Content\Repositories;

use App\Modules\Content\Models\Page;
use Illuminate\Database\Eloquent\Collection;

final class PageRepository
{
    /**
     * @return Collection<int, Page>
     */
    public function allForAdmin(): Collection
    {
        return Page::query()->orderByDesc('is_system')->orderBy('title')->get();
    }

    /**
     * A published page by slug; $isSystem picks system pages (own URLs) or extra pages (/page/{slug}).
     */
    public function findPublished(string $slug, bool $isSystem): ?Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('is_system', $isSystem)
            ->where('is_published', true)
            ->first();
    }

    public function slugExists(string $slug): bool
    {
        return Page::query()->where('slug', $slug)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Page
    {
        return Page::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        return $page;
    }

    public function delete(Page $page): void
    {
        $page->delete();
    }
}
