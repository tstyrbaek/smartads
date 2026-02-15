<style>
    .smartads-grid{font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,"Noto Sans","Liberation Sans",sans-serif;display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:16px}
    @media (min-width: 640px){.smartads-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
    @media (min-width: 1024px){.smartads-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
    .smartads-card{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;background:#fff}
    .smartads-img{width:100%;height:auto;display:block}
    .smartads-link{display:block;text-decoration:none}
    .smartads-empty{font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,"Noto Sans","Liberation Sans",sans-serif;color:#6b7280;font-size:14px}
</style>

    @php
        $websiteUrl = (string) ($instance->company?->website_url ?? '');
    @endphp

    @if($ads->isEmpty())
        <div class="smartads-empty">Ingen annoncer</div>
    @else
        <div class="smartads-grid">
            @foreach($ads as $ad)
                @if (is_string($ad->local_file_path) && $ad->local_file_path !== '')
                    <div class="smartads-card">
                        @if($websiteUrl !== '')
                            <a class="smartads-link" href="{{ $websiteUrl }}" target="_blank" rel="noopener noreferrer">
                                <img class="smartads-img" src="{{ asset('storage/' . $ad->local_file_path) }}" alt="Annonce" />
                            </a>
                        @else
                            <img class="smartads-img" src="{{ asset('storage/' . $ad->local_file_path) }}" alt="Annonce" />
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
