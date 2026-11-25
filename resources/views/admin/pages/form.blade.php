{{--
    Create / edit a content page (PageController@create / @edit). Admin only.
    $page  Page or null. The body is Markdown; FAQ questions are "## " headings.
--}}
@php
    $isEditing = $page !== null;
    $isFaq = $page?->slug === 'faq';
@endphp

<x-layouts.admin :title="$isEditing ? 'Edit Page' : 'New Page'" active="pages" :subtitle="$page?->title"
                 :back-url="route('admin.pages.index')" back-label="Pages">
    <form method="POST" action="{{ $isEditing ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="admin-grid">
            <div>
                <section class="panel">
                    <x-form.input name="title" label="Title" :value="$page?->title" maxlength="120" required />
                    <x-form.input name="subtitle" label="Subtitle" :value="$page?->subtitle" maxlength="255" optional hint="The line under the title on the page." />
                    <x-form.textarea name="body" label="Content" rows="22" class="textarea-code" :value="$page?->body" required />
                </section>
            </div>

            <div>
                <section class="panel">
                    <h2 class="panel-title">Publish</h2>
                    <label class="switch">
                        <span class="switch-text"><strong>Published</strong><span>Visible in the store</span></span>
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page?->is_published ?? true))>
                    </label>
                </section>

                <section class="panel">
                    <h2 class="panel-title">Formatting</h2>
                    @if ($isFaq)
                        <p class="form-hint">Each question starts with <code>## </code>; the text below it is the answer.</p>
                    @endif
                    <ul class="markdown-help">
                        <li><code>## Heading</code></li>
                        <li><code>**bold**</code> → <strong>bold</strong></li>
                        <li><code>- item</code> → bullet list</li>
                        <li><code>1. item</code> → numbered list</li>
                        <li><code>[text](/return-policy)</code> → link</li>
                        <li>Empty line → new paragraph</li>
                    </ul>
                    <p class="form-hint mb-0">HTML is not allowed and is removed.</p>
                </section>

                <section class="panel">
                    <h2 class="panel-title">Search Engine (SEO)</h2>
                    @if ($page?->is_system)
                        <p class="form-hint">URL: <strong>/{{ $page->slug }}</strong> (store page, cannot change)</p>
                    @else
                        <x-form.input name="slug" label="URL Slug" :value="$page?->slug" hint="Leave empty to generate from the title." />
                    @endif
                    <x-form.input name="meta_title" label="SEO Title" :value="$page?->meta_title" optional />
                    <x-form.textarea name="meta_description" label="SEO Description" rows="3" :value="$page?->meta_description" />
                </section>

                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('admin.pages.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $isEditing ? 'Save Page' : 'Create Page' }}</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
