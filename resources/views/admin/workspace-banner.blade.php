@unless(request()->routeIs('filament.admin.pages.operations-dashboard'))
<div class="studio-context"><span class="studio-eyebrow">{{ __('studio.workspace') }} / {{ __('studio.administration') }}</span><span>{{ now()->format('d M Y') }}</span></div>
@endunless