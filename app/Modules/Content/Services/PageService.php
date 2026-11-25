<?php

namespace App\Modules\Content\Services;

use App\Modules\Content\Data\PageData;
use App\Modules\Content\Exceptions\SystemPageException;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Repositories\PageRepository;
use App\Support\SafeMarkdown;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Collection;

final class PageService
{
    private const FALLBACK_SLUG = 'page';

    public function __construct(private readonly PageRepository $pageRepository) {}

    /**
     * @return Collection<int, Page>
     */
    public function allForAdmin(): Collection
    {
        return $this->pageRepository->allForAdmin();
    }

    public function findPublished(string $slug, bool $isSystem): ?Page
    {
        return $this->pageRepository->findPublished($slug, $isSystem);
    }

    /**
     * FAQ page: each "## Heading" is a question and the Markdown under it the answer; text before the first heading is the intro.
     *
     * @return array{introHtml: string, items: list<array{question: string, answerHtml: string}>}
     */
    public function faqItems(Page $page): array
    {
        $sections = preg_split('/^##[ \t]+(.+?)[ \t]*$/m', $page->body, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [$page->body];
        $items = [];

        for ($index = 1; $index < count($sections); $index += 2) {
            $items[] = ['question' => $sections[$index], 'answerHtml' => SafeMarkdown::toHtml($sections[$index + 1] ?? '')];
        }

        return ['introHtml' => SafeMarkdown::toHtml($sections[0]), 'items' => $items];
    }

    /**
     * Markdown → HTML with raw HTML stripped and unsafe links (javascript:, data:…) removed.
     */
    public function renderBody(Page $page): string
    {
        return SafeMarkdown::toHtml($page->body);
    }

    public function create(PageData $data): Page
    {
        return $this->pageRepository->create([
            ...$this->attributesFrom($data),
            'slug' => $data->slug ?? UniqueSlug::generate($data->title, $this->pageRepository->slugExists(...), self::FALLBACK_SLUG),
        ]);
    }

    /**
     * A system page keeps its slug (storefront routes use it).
     */
    public function update(Page $page, PageData $data): Page
    {
        return $this->pageRepository->update($page, [
            ...$this->attributesFrom($data),
            'slug' => $page->is_system ? $page->slug : ($data->slug ?? $page->slug),
        ]);
    }

    /**
     * @throws SystemPageException
     */
    public function delete(Page $page): void
    {
        if ($page->is_system) {
            throw SystemPageException::cannotDelete();
        }

        $this->pageRepository->delete($page);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(PageData $data): array
    {
        return [
            'title' => $data->title,
            'subtitle' => $data->subtitle,
            'body' => $data->body,
            'title_bn' => $data->titleBn,
            'subtitle_bn' => $data->subtitleBn,
            'body_bn' => $data->bodyBn,
            'meta_title' => $data->metaTitle,
            'meta_description' => $data->metaDescription,
            'is_published' => $data->isPublished,
        ];
    }
}
