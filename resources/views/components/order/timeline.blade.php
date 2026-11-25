{{--
    Order status timeline.
    steps: list of ['title', 'note', 'state' => done | current | upcoming]
--}}
@props(['steps'])

@php
    $icons = ['done' => 'check', 'current' => 'truck', 'upcoming' => 'dot'];
@endphp

<ol class="timeline">
    @foreach ($steps as $step)
        <li @class(['timeline-step', $step['state'] => $step['state'] !== 'upcoming'])>
            <span class="timeline-dot" aria-hidden="true">
                <x-icon :name="$icons[$step['state']]" :stroke-width="$step['state'] === 'done' ? 2.5 : 2" />
            </span>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['note'] }}</p>
        </li>
    @endforeach
</ol>
