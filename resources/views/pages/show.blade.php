{{--
    Policy-style content page (PageController): privacy, terms, shipping, returns and extra pages.
    $page      Page
    $bodyHtml  Safe HTML from the page's Markdown
--}}
<x-policy-page :title="$page->title" :subtitle="$page->subtitle" :meta-title="$page->meta_title" :description="$page->meta_description" :updated="\App\Support\Numerals::date($page->updated_at, 'j F Y')">
    {!! $bodyHtml !!}
</x-policy-page>
