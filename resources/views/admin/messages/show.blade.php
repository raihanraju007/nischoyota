{{--
    One contact message (F-026). Data from Admin\ContactMessageController@show:
    $message   ContactMessage with user
    $statuses  ContactMessageStatus cases
--}}
@use('App\Modules\Account\Enums\UserRole')

<x-layouts.admin :title="$message->subject->label()" active="messages"
    :subtitle="'From '.$message->name.' · '.$message->created_at->timezone(config('shop.timezone'))->format('d M Y, h:i A')"
    :back-url="route('admin.messages.index')" back-label="Messages">
    <div class="admin-grid">
        <div>
            <section class="panel">
                <div class="panel-head">
                    <h2>Message</h2>
                    <x-admin.status :status="$message->status->value" />
                </div>
                <p class="message-body">{{ $message->message }}</p>
            </section>
        </div>

        <div>
            <section class="panel">
                <h2 class="panel-title">Sender</h2>
                <dl class="info-list">
                    <div><dt>Name</dt><dd>{{ $message->name }}</dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:{{ $message->phone }}">{{ $message->phone }}</a></dd></div>
                    <div><dt>Email</dt><dd>@if ($message->email)<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>@else — @endif</dd></div>
                    @if ($message->user && $message->user->role === UserRole::Customer)
                        <div><dt>Account</dt><dd><a class="link" href="{{ route('admin.customers.show', $message->user->id) }}">View customer</a></dd></div>
                    @endif
                </dl>
            </section>

            <section class="panel">
                <h2 class="panel-title">Status</h2>
                <div class="form-actions-stack">
                    @foreach ($statuses as $status)
                        @continue($status === $message->status)
                        <form method="POST" action="{{ route('admin.messages.status', $message) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status->value }}">
                            <button @class(['btn btn-block', 'btn-primary' => $status->value === 'replied', 'btn-outline' => $status->value !== 'replied']) type="submit">Mark as {{ $status->label() }}</button>
                        </form>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
