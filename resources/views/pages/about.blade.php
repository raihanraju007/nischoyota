{{--
    About page (PageController, system page "about").
    $page      Page
    $bodyHtml  Safe HTML from the page's Markdown
--}}
<x-layouts.app :title="$page->meta_title ?? 'About Us'" :description="$page->meta_description" :breadcrumbs="['About Us' => null]">
    <x-page-hero :title="$page->title">{{ $page->subtitle }}</x-page-hero>

    <div class="prose">
        {!! $bodyHtml !!}
    </div>

    <x-trust-strip />
</x-layouts.app>
