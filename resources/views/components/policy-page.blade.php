{{-- Layout for legal/policy pages: breadcrumb, hero and a prose body. Slot = policy content. --}}
@props([
    'title',
    'subtitle' => null,
    'breadcrumb' => null,
    'metaTitle' => null,
    'description' => null,
    'updated' => null,
])

<x-layouts.app :title="$metaTitle ?? $title" :description="$description" :breadcrumbs="[($breadcrumb ?? $title) => null]">
    <x-page-hero :title="$title">{{ $subtitle }}</x-page-hero>

    <div class="prose">
        @if ($updated)
            <p class="updated">Last updated: {{ $updated }}</p>
        @endif
        {{ $slot }}
    </div>
</x-layouts.app>
