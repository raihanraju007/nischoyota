{{--
    Create / edit a content page (PageController@create / @edit). Admin only.
    $page  Page or null. The body is Markdown; FAQ questions are "## " headings.
--}}
@php
    $isEditing = $page !== null;
    $isFaq = $page?->slug === 'faq';
@endphp

<x-layouts.admin :title="$isEditing ? __('Edit Page') : __('New Page')" active="pages" :subtitle="$page?->title"
                 :back-url="route('admin.pages.index')" back-label="Pages">
    <form method="POST" action="{{ $isEditing ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="admin-grid">
            <div>
                <section class="panel">
                    <x-form.input name="title" :label="__('Title')" :value="$page?->title" maxlength="120" required />
                    <x-form.input name="subtitle" :label="__('Subtitle')" :value="$page?->subtitle" maxlength="255" optional hint="The line under the title on the page." />
                    <x-form.textarea name="body" :label="__('Content')" rows="22" class="textarea-code" :value="$page?->body" required />
                    <x-form.input name="title_bn" :label="__('Title (বাংলা)')" :value="$page?->title_bn" maxlength="120" optional hint="Shown instead of the title when the visitor uses Bangla." />
                    <x-form.input name="subtitle_bn" :label="__('Subtitle (বাংলা)')" :value="$page?->subtitle_bn" maxlength="255" optional />
                    <x-form.textarea name="body_bn" :label="__('Content (বাংলা)')" rows="22" class="textarea-code" :value="$page?->body_bn" optional />
                </section>
            </div>

            <div>
                <section class="panel">
                    <h2 class="panel-title">{{ __('Publish') }}</h2>
                    <label class="switch">
                        <span class="switch-text"><strong>{{ __('Published') }}</strong><span>{{ __('Visible in the store') }}</span></span>
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page?->is_published ?? true))>
                    </label>
                </section>

                <section class="panel">
                    <h2 class="panel-title">{{ __('Formatting') }}</h2>
                    @if ($isFaq)
                        <p class="form-hint">{{ __('Each question starts with') }} <code>## </code>{{ __('; the text below it is the answer.') }}</p>
                    @endif
                    <ul class="markdown-help">
                        <li><code>{{ __('## Heading') }}</code></li>
                        <li><code>{{ __('**bold**') }}</code> → <strong>{{ __('bold') }}</strong></li>
                        <li><code>{{ __('- item') }}</code> {{ __('→ bullet list') }}</li>
                        <li><code>{{ __('1. item') }}</code> {{ __('→ numbered list') }}</li>
                        <li><code>{{ __('[text](/return-policy)') }}</code> {{ __('→ link') }}</li>
                        <li>{{ __('Empty line → new paragraph') }}</li>
                    </ul>
                    <p class="form-hint mb-0">{{ __('HTML is not allowed and is removed.') }}</p>
                </section>

                <section class="panel">
                    <h2 class="panel-title">{{ __('Search Engine (SEO)') }}</h2>
                    @if ($page?->is_system)
                        <p class="form-hint">{{ __('URL:') }} <strong>/{{ $page->slug }}</strong> {{ __('(store page, cannot change)') }}</p>
                    @else
                        <x-form.input name="slug" :label="__('URL Slug')" :value="$page?->slug" hint="Leave empty to generate from the title." />
                    @endif
                    <x-form.input name="meta_title" :label="__('SEO Title')" :value="$page?->meta_title" optional />
                    <x-form.textarea name="meta_description" :label="__('SEO Description')" rows="3" :value="$page?->meta_description" />
                </section>

                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('admin.pages.index') }}">{{ __('Cancel') }}</a>
                    <button class="btn btn-primary" type="submit">{{ $isEditing ? __('Save Page') : __('Create Page') }}</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
