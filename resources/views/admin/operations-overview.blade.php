<x-filament-widgets::widget>
<div class="studio-dashboard" wire:poll.30s>
    <section class="studio-welcome">
        <div><div class="studio-eyebrow">{{ __('studio.workspace') }} <span> / </span> {{ now()->format('d.m.Y') }}</div>
        <h2>{{ __('studio.hello') }}، {{ auth()->user()->name }}</h2><p>{{ __('studio.intro') }}</p>
        <div class="studio-welcome-actions"><a class="studio-button" href="{{ \App\Filament\Resources\Devices\DeviceResource::getUrl() }}">{{ __('studio.manage') }} <span aria-hidden="true">↗</span></a>
        @can('create', \App\Models\Device::class)<a class="studio-text-link" href="{{ \App\Filament\Resources\Devices\DeviceResource::getUrl('create') }}">+ {{ __('studio.add') }}</a>@endcan</div></div>
        <div class="studio-orbit" aria-hidden="true"><div class="studio-orbit-inner"><svg viewBox="0 0 80 80" fill="none"><rect x="12" y="16" width="56" height="39" rx="8" stroke="currentColor" stroke-width="3"/><path d="M29 65h22M40 55v10M35 28l14 8-14 8V28Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></svg></div><span class="studio-orbit-dot"></span></div>
    </section>
    <div class="studio-metrics">
        @foreach ([['all',$counts->total,'squares-2x2','violet'],['online',$online,'signal','green'],['pending',$counts->pending ?? 0,'clock','amber'],['expiring',$expiring,'calendar-days','rose']] as [$label,$value,$icon,$tone])
        <article class="studio-metric"><div class="studio-metric-top"><span>{{ __('studio.'.$label) }}</span><span class="studio-icon {{ $tone }}">@svg('heroicon-o-'.$icon)</span></div><strong>{{ number_format($value) }}</strong><small>{{ __('studio.'.$label.'_hint') }}</small></article>
        @endforeach
    </div>
    <div class="studio-grid">
        <section class="studio-card studio-recent"><div class="studio-card-heading"><div><span class="studio-eyebrow">{{ __('studio.devices_label') }}</span><h3>{{ __('studio.recent') }}</h3></div><a class="studio-text-link" href="{{ \App\Filament\Resources\Devices\DeviceResource::getUrl() }}">{{ __('studio.view_all') }} ←</a></div>
            <div class="studio-table-scroll"><table class="studio-table"><thead><tr><th>{{ __('admin.device_name') }}</th><th>{{ __('control.method') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('control.last_seen') }}</th></tr></thead><tbody>
            @forelse($recent as $device)<tr><td><div class="studio-device-cell"><span class="studio-device-icon">@svg('heroicon-o-computer-desktop')</span><div>@can('update',$device)<a href="{{ \App\Filament\Resources\Devices\DeviceResource::getUrl('edit',['record'=>$device]) }}">{{ $device->device_name ?: $device->device_id }}</a>@else<span>{{ $device->device_name ?: $device->device_id }}</span>@endcan<small>{{ $device->subscriber_name ?: ($device->platform ?: '—') }}</small></div></div></td><td><span class="studio-method">{{ __('control.'.$device->login_method) }}</span></td><td><span class="studio-pill {{ $device->status->value }}">{{ $device->status->label() }}</span></td><td>{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td></tr>
            @empty<tr><td colspan="4"><div class="studio-empty">@svg('heroicon-o-computer-desktop')<strong>{{ __('studio.no_devices') }}</strong><p>{{ __('studio.no_devices_hint') }}</p></div></td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section class="studio-card"><div class="studio-card-heading"><div><span class="studio-eyebrow">{{ __('studio.distribution') }}</span><h3>{{ __('studio.methods') }}</h3></div><span class="studio-subtle">{{ $counts->total }} {{ __('studio.devices_label') }}</span></div><div class="studio-methods">
        @foreach(['activation'=>'violet','xtream'=>'green','m3u'=>'amber'] as $method=>$tone)
        @php($percentage = $counts->total ? round(($methods[$method] ?? 0) / $counts->total * 100) : 0)
        <div class="studio-method-row"><div><span><i class="studio-dot {{ $tone }}"></i>{{ __('control.'.$method) }}</span><strong>{{ $methods[$method] ?? 0 }}</strong></div><div class="studio-track"><span class="{{ $tone }}" style="width:{{ $percentage }}%"></span></div><small>{{ $percentage }}%</small></div>
        @endforeach</div><div class="studio-card-note">{{ __('studio.methods_hint') }}</div></section>
        <section class="studio-card"><div class="studio-card-heading"><div><span class="studio-eyebrow">{{ __('studio.follow_up') }}</span><h3>{{ __('studio.needs_attention') }}</h3></div><span class="studio-count">{{ $counts->pending ?? 0 }}</span></div>
        <div class="studio-list">@forelse($attention as $device)<div class="studio-list-row"><span class="studio-icon amber">@svg('heroicon-o-clock')</span><div><strong>{{ $device->device_name ?: $device->device_id }}</strong><small>{{ $device->created_at->diffForHumans() }}</small></div>@can('update',$device)<a class="studio-mini-link" href="{{ \App\Filament\Resources\Devices\DeviceResource::getUrl('edit',['record'=>$device]) }}">{{ __('studio.review') }}</a>@endcan</div>@empty<div class="studio-empty">@svg('heroicon-o-check-circle')<strong>{{ __('studio.all_clear') }}</strong><p>{{ __('studio.all_clear_hint') }}</p></div>@endforelse</div></section>
        <section class="studio-card"><div class="studio-card-heading"><div><span class="studio-eyebrow">{{ __('studio.timeline') }}</span><h3>{{ __('studio.activity') }}</h3></div><span class="studio-subtle">{{ __('studio.latest_five') }}</span></div><div class="studio-timeline">
        @forelse($events as $event)<div class="studio-event"><i></i><div><strong>{{ __('control.events.'.$event->event) }}</strong><span>{{ $event->device_name ?: '—' }}</span></div><time>{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</time></div>@empty<div class="studio-empty">@svg('heroicon-o-bolt')<strong>{{ __('control.no_events') }}</strong></div>@endforelse</div></section>
    </div>
    <div class="studio-page-footer"><span>IPTV <b>Workspace</b></span><span>{{ __('studio.auto_refresh') }}</span></div>
</div></x-filament-widgets::widget>