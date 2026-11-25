{{--
    FAQ page (PageController, system page "faq").
    $page  Page
    $faq   ['introHtml' => safe HTML, 'items' => list of ['question' => text, 'answerHtml' => safe HTML]]
--}}
<x-layouts.app :title="$page->meta_title ?? 'FAQ'" :description="$page->meta_description" :breadcrumbs="['FAQ' => null]">
    <x-page-hero :title="$page->title">{{ $page->subtitle }}</x-page-hero>

    <section class="section">
        @if ($faq['introHtml'] !== '')
            <div class="prose">{!! $faq['introHtml'] !!}</div>
        @endif

        @if ($faq['items'] !== [])
            <div class="accordion">
                @foreach ($faq['items'] as $item)
                    <div @class(['accordion-item', 'open' => $loop->first])>
                        <button class="accordion-trigger" type="button" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $item['question'] }}
                            <x-icon name="chevron-down" class="icon-sm acc-icon" />
                        </button>
                        <div class="accordion-body">{!! $item['answerHtml'] !!}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <x-empty-state title="Still have questions?" :action-url="route('contact')" action-label="Contact Us" action-class="btn-outline-primary" small>
            Our support team is happy to help you any day from 9am to 10pm.
        </x-empty-state>
    </section>
</x-layouts.app>
