{{-- Initial avatar. color: rose | blue | violet | teal (omit for brand default). --}}
@props([
    'name',
    'color' => null,
])

<span @class(['avatar', 'avatar-'.$color => $color]) aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
