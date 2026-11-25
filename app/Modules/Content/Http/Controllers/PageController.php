<?php

namespace App\Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Enums\PageTemplate;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Services\PageService;
use Illuminate\View\View;

/**
 * Storefront content pages (F-020): system pages on their own URLs, extra pages at /page/{slug}.
 */
final class PageController extends Controller
{
    public function __construct(private readonly PageService $pageService) {}

    public function system(string $slug): View
    {
        return $this->render($this->pageService->findPublished($slug, isSystem: true) ?? abort(404));
    }

    public function show(string $slug): View
    {
        return $this->render($this->pageService->findPublished($slug, isSystem: false) ?? abort(404));
    }

    private function render(Page $page): View
    {
        $template = PageTemplate::for($page);

        return view($template->view(), [
            'page' => $page,
            'bodyHtml' => $template === PageTemplate::Faq ? null : $this->pageService->renderBody($page),
            'faq' => $template === PageTemplate::Faq ? $this->pageService->faqItems($page) : null,
        ]);
    }
}
