{{--
    Contact message inbox (F-026). Data from Admin\ContactMessageController@index:
    $messages   LengthAwarePaginator<ContactMessage>
    $tabCounts  status value and 'all' => count (search applied)
    $filter     ContactMessageFilter (status: null = all, search)
    $statuses   ContactMessageStatus cases
--}}
@use('Illuminate\Support\Str')

<x-layouts.admin :title="__('Messages')" active="messages" :subtitle="__('Questions and requests from the contact form.')">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="{{ __('Filter by status') }}">
            @foreach ([...$statuses, null] as $status)
                <a href="{{ route('admin.messages.index', $filter->queryFor($status)) }}" @class(['active' => $filter->status === $status]) @if ($filter->status === $status) aria-current="page" @endif>
                    {{ $status?->label() ?? __('All') }} <span class="tab-count">{{ $tabCounts[$status?->value ?? 'all'] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-toolbar" method="GET" action="{{ route('admin.messages.index') }}" role="search">
            <input type="hidden" name="status" value="{{ $filter->status?->value ?? 'all' }}">
            <div class="admin-search">
                <x-icon name="search" class="icon-sm" />
                <label for="message-search" class="visually-hidden">{{ __('Search messages') }}</label>
                <input class="input" type="search" id="message-search" name="search" value="{{ $filter->search }}" placeholder="{{ __('Name, phone or email…') }}">
            </div>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> {{ __('Search') }}</button>
            </div>
        </form>

        @if ($messages->isEmpty())
            <x-empty-state icon="mail" :title="__('No messages here')" small>
                @if ($filter->search)
                    <a href="{{ route('admin.messages.index', ['status' => 'all']) }}">{{ __('Show all messages') }}</a>
                @else
                    Messages from the contact form appear here.
                @endif
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('From') }}</th>
                            <th>{{ __('Message') }}</th>
                            <th>{{ __('Received') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr>
                                <td>
                                    <a class="link" href="{{ route('admin.messages.show', $message) }}"><strong>{{ $message->name }}</strong></a>
                                    <span class="table-sub">{{ $message->phone }}</span>
                                </td>
                                <td>
                                    <strong>{{ $message->subject->label() }}</strong>
                                    <span class="table-sub">{{ Str::limit($message->message, 120) }}</span>
                                </td>
                                <td class="text-light">{{ \App\Support\Numerals::date($message->created_at->timezone(config('shop.timezone')), 'd M Y, h:i A') }}</td>
                                <td><x-admin.status :status="$message->status->value" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($messages->firstItem()), 'last' => \App\Support\Numerals::localize($messages->lastItem()), 'total' => \App\Support\Numerals::localize($messages->total()), 'products' => __('messages')]) }}</span>
                {{ $messages->links() }}
            </div>
        @endif
    </section>
</x-layouts.admin>
