<style>
    .smartads-grid{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start}
    .smartads-img{display:block;width:100%;height:auto;max-width:100%;object-fit:contain}
    .smartads-link{display:inline-block;text-decoration:none}
    .smartads-empty{color:#6b7280;font-size:14px}

    .smartads-carousel{position:relative;width:100%}
    .smartads-viewport{overflow:hidden;width:100%}
    .smartads-track{display:flex;gap:16px;will-change:transform;transition:transform 500ms ease}
    .smartads-item{flex:0 0 auto;box-sizing:border-box}

    .smartads-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:2;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:9999px;background:rgba(0,0,0,0.55);color:#fff;cursor:pointer;user-select:none}
    .smartads-nav:hover{background:rgba(0,0,0,0.7)}
    .smartads-nav:active{background:rgba(0,0,0,0.8)}
    .smartads-nav:focus{outline:2px solid rgba(59,130,246,0.8);outline-offset:2px}
    .smartads-prev{left:8px}
    .smartads-next{right:8px}
</style>

@if($ads->isEmpty())
    <div class="smartads-empty">Ingen annoncer</div>
@else
    @php
        $viewMode = isset($viewMode) && is_string($viewMode) ? $viewMode : 'grid';
        $itemsPerView = isset($itemsPerView) && is_numeric($itemsPerView) ? (int) $itemsPerView : 3;
        if ($itemsPerView < 1) { $itemsPerView = 1; }
        if ($itemsPerView > 6) { $itemsPerView = 6; }
        $intervalMs = isset($intervalMs) && is_numeric($intervalMs) ? (int) $intervalMs : 4000;
        if ($intervalMs < 1500) { $intervalMs = 1500; }
        if ($intervalMs > 20000) { $intervalMs = 20000; }

        $adItems = $ads->filter(fn($ad) => is_string($ad->local_file_path) && $ad->local_file_path !== '')->values();
    @endphp

    @if($viewMode === 'slideshow')
        @php
            $carouselId = 'smartads-network-carousel-' . (string) ($definition->key ?? '');
            $itemWidth = (100 / max(1, $itemsPerView));
        @endphp

        <div class="smartads-carousel" id="{{ $carouselId }}" data-items-per-view="{{ $itemsPerView }}" data-interval-ms="{{ $intervalMs }}">
            <button type="button" class="smartads-nav smartads-prev" aria-label="Forrige">‹</button>
            <button type="button" class="smartads-nav smartads-next" aria-label="Næste">›</button>
            <div class="smartads-viewport">
                <div class="smartads-track">
                    @for($i = 0; $i < min($itemsPerView, $adItems->count()); $i++)
                        @php
                            $idx = $adItems->count() - min($itemsPerView, $adItems->count()) + $i;
                            $ad = $adItems[$idx];
                            $w = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                            $h = is_numeric($ad->image_height) ? (int) $ad->image_height : null;
                            $companyWebsiteUrl = (string) ($ad->company?->website_url ?? '');
                            $targetUrl = is_string($ad->target_url ?? null) && trim((string) $ad->target_url) !== ''
                                ? trim((string) $ad->target_url)
                                : $companyWebsiteUrl;
                        @endphp

                        <div class="smartads-item" style="width: {{ $itemWidth }}%;">
                            @if($targetUrl !== '')
                                <a class="smartads-link" href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer">
                                    <img
                                        class="smartads-img"
                                        src="{{ asset('storage/' . $ad->local_file_path) }}"
                                        alt="Annonce"
                                        @if($w && $h)
                                            width="{{ $w }}"
                                            height="{{ $h }}"
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
                                    @endif
                                />
                            @endif
                        </div>
                    @endfor

                    @foreach($adItems as $ad)
                        @php
                            $w = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                            $h = is_numeric($ad->image_height) ? (int) $ad->image_height : null;
                            $companyWebsiteUrl = (string) ($ad->company?->website_url ?? '');
                            $targetUrl = is_string($ad->target_url ?? null) && trim((string) $ad->target_url) !== ''
                                ? trim((string) $ad->target_url)
                                : $companyWebsiteUrl;
                        @endphp

                        <div class="smartads-item" style="width: {{ $itemWidth }}%;">
                            @if($targetUrl !== '')
                                <a class="smartads-link" href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer">
                                    <img
                                        class="smartads-img"
                                        src="{{ asset('storage/' . $ad->local_file_path) }}"
                                        alt="Annonce"
                                        @if($w && $h)
                                            width="{{ $w }}"
                                            height="{{ $h }}"
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
                                    @endif
                                />
                            @endif
                        </div>
                    @endforeach

                    @for($i = 0; $i < min($itemsPerView, $adItems->count()); $i++)
                        @php
                            $ad = $adItems[$i];
                            $w = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                            $h = is_numeric($ad->image_height) ? (int) $ad->image_height : null;
                            $companyWebsiteUrl = (string) ($ad->company?->website_url ?? '');
                            $targetUrl = is_string($ad->target_url ?? null) && trim((string) $ad->target_url) !== ''
                                ? trim((string) $ad->target_url)
                                : $companyWebsiteUrl;
                        @endphp

                        <div class="smartads-item" style="width: {{ $itemWidth }}%;">
                            @if($targetUrl !== '')
                                <a class="smartads-link" href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer">
                                    <img
                                        class="smartads-img"
                                        src="{{ asset('storage/' . $ad->local_file_path) }}"
                                        alt="Annonce"
                                        @if($w && $h)
                                            width="{{ $w }}"
                                            height="{{ $h }}"
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
                                    @endif
                                />
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <script>
          (function(){
            var root = document.getElementById(@json($carouselId));
            if(!root) return;
            var track = root.querySelector('.smartads-track');
            if(!track) return;
            var items = root.querySelectorAll('.smartads-item');
            var total = items ? items.length : 0;
            var itemsPerView = parseInt(root.getAttribute('data-items-per-view') || '3', 10);
            if(!itemsPerView || itemsPerView < 1) itemsPerView = 1;
            if(itemsPerView > 6) itemsPerView = 6;
            var intervalMs = parseInt(root.getAttribute('data-interval-ms') || '4000', 10);
            if(!intervalMs || intervalMs < 1500) intervalMs = 1500;
            if(intervalMs > 20000) intervalMs = 20000;

            var prevBtn = root.querySelector('.smartads-prev');
            var nextBtn = root.querySelector('.smartads-next');

            if(total <= itemsPerView) {
              track.style.transform = 'translateX(0px)';
              return;
            }

            var cloneCount = itemsPerView;
            var originalCount = total - (cloneCount * 2);
            if(originalCount < 1) {
              track.style.transform = 'translateX(0px)';
              return;
            }

            var index = cloneCount;
            var gapPx = 16;

            var timer = null;

            function translateForIndex(i, animate){
              var item = items[0];
              if(!item) return;
              var itemW = item.getBoundingClientRect().width;
              var offset = i * (itemW + gapPx);
              track.style.transition = animate ? 'transform 500ms ease' : 'none';
              track.style.transform = 'translateX(' + (-offset) + 'px)';
            }

            function restart(){
              if(timer) window.clearInterval(timer);
              timer = window.setInterval(stepNext, intervalMs);
            }

            function stepNext(){
              index = index + 1;
              translateForIndex(index, true);

              if(index >= (originalCount + cloneCount)) {
                window.setTimeout(function(){
                  index = cloneCount;
                  translateForIndex(index, false);
                }, 520);
              }
            }

            function stepPrev(){
              index = index - 1;
              translateForIndex(index, true);

              if(index < cloneCount) {
                window.setTimeout(function(){
                  index = (originalCount + cloneCount - 1);
                  translateForIndex(index, false);
                }, 520);
              }
            }

            if(nextBtn) {
              nextBtn.addEventListener('click', function(){
                stepNext();
                restart();
              });
            }
            if(prevBtn) {
              prevBtn.addEventListener('click', function(){
                stepPrev();
                restart();
              });
            }

            translateForIndex(index, false);
            restart();
          })();
        </script>
    @else
        <div class="smartads-grid">
            @foreach($adItems as $ad)
                @php
                    $w = is_numeric($ad->image_width) ? (int) $ad->image_width : null;
                    $h = is_numeric($ad->image_height) ? (int) $ad->image_height : null;
                    $companyWebsiteUrl = (string) ($ad->company?->website_url ?? '');
                    $targetUrl = is_string($ad->target_url ?? null) && trim((string) $ad->target_url) !== ''
                        ? trim((string) $ad->target_url)
                        : $companyWebsiteUrl;
                @endphp

                @if($targetUrl !== '')
                    <a class="smartads-link" href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer">
                        <img
                            class="smartads-img"
                            src="{{ asset('storage/' . $ad->local_file_path) }}"
                            alt="Annonce"
                            @if($w && $h)
                                width="{{ $w }}"
                                height="{{ $h }}"
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
                        @endif
                    />
                @endif
            @endforeach
        </div>
    @endif
@endif
