<style>
    .smartads-grid{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start}
    .smartads-img{display:block;width:auto;height:auto;max-width:none;max-height:none;object-fit:contain}
    .smartads-link{display:inline-block;text-decoration:none}
    .smartads-empty{color:#6b7280;font-size:14px}
</style>

@if($ads->isEmpty())
    <div class="smartads-empty">Ingen annoncer</div>
@else
    <div class="smartads-grid">
        @foreach($ads as $ad)
            @if (is_string($ad->local_file_path) && $ad->local_file_path !== '')
                @php
                    $w = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                    $h = is_numeric($ad->image_height) ? (int) $ad->image_height : null;
                    $companyWebsiteUrl = (string) ($ad->company?->website_url ?? '');
                @endphp

                @if($companyWebsiteUrl !== '')
                    <a class="smartads-link" href="{{ $companyWebsiteUrl }}" target="_blank" rel="noopener noreferrer">
                        <img
                            class="smartads-img"
                            src="{{ asset('storage/' . $ad->local_file_path) }}"
                            alt="Annonce"
                            @if($w && $h)
                                width="{{ $w }}"
                                height="{{ $h }}"
                                style="width: {{ $w }}px; height: {{ $h }}px;"
                            @endif
                        />
                    </a>
                @else
                    <img
                        class="smartads-img"
                        src="{{ asset('storage/' . $ad->local_file_path) }}"
                        alt="Annonce"
                        @if($w && $h)
                            width="{{ $w }}"
                            height="{{ $h }}"
                            style="width: {{ $w }}px; height: {{ $h }}px;"
                        @endif
                    />
                @endif
            @endif
        @endforeach
    </div>
@endif
