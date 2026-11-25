{{--
    Content pages (PageController@index). Admin only.
    $pages  Page collection (system pages first)
--}}
<x-layouts.admin :title="__('Pages')" active="pages" :subtitle="__('About, FAQ and policy pages shown in the store.')">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('admin.pages.create') }}"><x-icon name="plus" class="icon-sm" /> {{ __('New Page') }}</a>
    </x-slot:actions>

    <section class="panel panel-flush">
        @if ($pages->isEmpty())
            <x-empty-state icon="edit" :title="__('No pages yet')" small />
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Page') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Last updated') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td>
                                    <a class="link" href="{{ route('admin.pages.edit', $page) }}"><strong>{{ $page->title }}</strong></a>
                                    <span class="table-sub">/{{ $page->slug }}{{ $page->is_system ? ' · store page' : '' }}</span>
                                </td>
                                <td><x-admin.status :status="$page->is_published ? 'active' : 'draft'" /></td>
                                <td class="text-light">{{ \App\Support\Numerals::date($page->updated_at->timezone(config('shop.timezone')), 'd M Y') }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.pages.edit', $page) }}" aria-label="Edit {{ $page->title }}">
                                            <x-icon name="edit" class="icon-sm" />
                                        </a>
                                        @unless ($page->is_system)
                                            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-page"
                                                    data-modal-action="{{ route('admin.pages.destroy', $page) }}" aria-label="Delete {{ $page->title }}">
                                                <x-icon name="trash" class="icon-sm" />
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @push('overlays')
        <x-confirm-modal id="modal-delete-page" :title="__('Delete this page?')" confirm-label="Yes, Delete" method="DELETE">
            {{ __('The page and its text are removed. Links to it will stop working.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
