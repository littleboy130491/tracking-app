@php
    /** @var \App\Support\CustomerShipmentProgress $progress */
    $processes = $progress->processes();
    $current = $progress->currentProcess();
@endphp

<section class="cargo-tracking" aria-label="{{ $progress->heading() }}">
    <div class="section-title">
        <p class="eyebrow">{{ $progress->heading() }}</p>
        <h2>Shipment Details</h2>
    </div>

    <article class="route-card">
        <div class="route-card-head">
            <p class="process-kicker">Progress</p>
            @if ($current)
                <p class="route-current">{{ $current['kicker'] }} · {{ $current['title'] }}</p>
            @endif
        </div>

        <ol class="process-track" style="--progress: {{ $progress->progressPercent() }}%;">
            @foreach ($processes as $process)
                <li class="process-track-step is-{{ $process['state'] }}">
                    <span>{{ $process['kicker'] }}</span>
                    <strong>{{ $process['title'] }}</strong>
                </li>
            @endforeach
        </ol>
    </article>

    <ol class="voyage-timeline">
        @foreach ($processes as $process)
            <li class="voyage-stop is-{{ $process['state'] }}">
                <div class="voyage-rail" aria-hidden="true"></div>
                <div class="voyage-place">
                    <strong>{{ $process['kicker'] }}</strong>
                    <span>{{ $process['title'] }}</span>
                </div>
                <div class="voyage-event-copy">
                    <dl class="facts-grid">
                        @foreach ($process['fields'] as $item)
                            <div @class(['fact-wide' => ! empty($item['wide'])])>
                                <dt>{{ $item['label'] }}</dt>
                                <dd>
                                    @if (! empty($item['url']))
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['value'] }}</a>
                                    @else
                                        {{ $item['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                        @if ($process['schedule'])
                            <div class="fact-wide">
                                <dt>Container Shipping Schedule</dt>
                                <dd>{{ $process['schedule'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($process['spjm'] !== [])
                        <div class="spjm-extra">
                            <p class="process-kicker">Tambahan step SPJM</p>
                            <dl class="facts-grid">
                                @foreach ($process['spjm'] as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>
                                            @if (! empty($item['url']))
                                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['value'] }}</a>
                                            @else
                                                {{ $item['value'] }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    @foreach ($process['containers'] as $row)
                        <div class="container-progress-card">
                            <div class="container-progress-head">
                                <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer">{{ $row['container']->container_number }}</a>
                            </div>
                            <dl class="facts-grid">
                                @foreach ($row['fields'] as $item)
                                    <div>
                                        <dt>{{ $item['label'] }}</dt>
                                        <dd>
                                            @if (! empty($item['url']))
                                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">{{ $item['value'] }}</a>
                                            @else
                                                {{ $item['value'] }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                            @if ($row['photos'] !== [])
                                <div class="photo-grid">
                                    @foreach ($row['photos'] as $label => $url)
                                        <figure>
                                            <figcaption>{{ $label }}</figcaption>
                                            @if ($url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                                                    <img src="{{ $url }}" alt="{{ $label }} {{ $row['container']->container_number }}">
                                                </a>
                                            @else
                                                <span class="photo-empty">Belum ada foto</span>
                                            @endif
                                        </figure>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </li>
        @endforeach
    </ol>
</section>
