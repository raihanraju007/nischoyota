<?php

namespace App\Modules\Content\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Content\Exceptions\SystemPageException;
use App\Modules\Content\Http\Requests\Admin\SavePageRequest;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function __construct(private readonly PageService $pageService) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => $this->pageService->allForAdmin()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => null]);
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function store(SavePageRequest $request): RedirectResponse
    {
        $page = $this->pageService->create($request->toData());

        return to_route('admin.pages.edit', $page)->with('success', __('Page ":title" created.', ['title' => $page->title]));
    }

    public function update(SavePageRequest $request, Page $page): RedirectResponse
    {
        $this->pageService->update($page, $request->toData());

        return to_route('admin.pages.edit', $page)->with('success', __('Page saved.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        try {
            $this->pageService->delete($page);
        } catch (SystemPageException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.pages.index')->with('success', __('Page ":title" deleted.', ['title' => $page->title]));
    }
}
