@props([
    'logs',
])

<section class="detail-section">
    <div class="section-title">
        <p class="eyebrow">Aktivitas</p>
        <h2>Log</h2>
    </div>

    @if ($logs->isEmpty())
        <div class="empty-state"><strong>Belum ada log tercatat</strong></div>
    @else
        <ol class="log-list">
            @foreach ($logs as $log)
                <li class="log-item">
                    <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->locale('id')->translatedFormat('j M Y H:i') }}</time>
                    <strong>{{ $log->eventLabel() }}</strong>
                    <p>{{ $log->description }}</p>
                    <span>Oleh {{ $log->whoLabel() }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
