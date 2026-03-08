<div class="space-y-6">
    <div>
        <label class="block font-medium text-sm text-gray-700" for="key">Key *</label>
        <input
            id="key"
            name="key"
            type="text"
            value="{{ old('key', $definition->key) }}"
            required
            data-in-use="{{ (($inUseCount ?? 0) > 0) ? '1' : '0' }}"
            data-existing="{{ $definition->id ? '1' : '0' }}"
            @readonly(($inUseCount ?? 0) > 0)
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ (($inUseCount ?? 0) > 0) ? 'bg-gray-50 opacity-50 cursor-not-allowed' : '' }}"
        />
        @if(($inUseCount ?? 0) > 0)
            <p class="mt-1 text-xs text-gray-500">Key kan ikke ændres, når integrationstypen er i brug.</p>
        @endif
        @error('key')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="type">Type *</label>
        <select id="type" name="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @php
                $knownTypes = [
                    'website_embed' => 'Website embed',
                    'network_website_embed' => 'Network website embed',
                ];

                $currentType = (string) old('type', $definition->type);
            @endphp

            @foreach($knownTypes as $value => $label)
                <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
            @endforeach

            @if($currentType !== '' && !array_key_exists($currentType, $knownTypes))
                <option value="{{ $currentType }}" selected>{{ $currentType }}</option>
            @endif
        </select>
        @error('type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="name">Navn *</label>
        <input id="name" name="name" type="text" value="{{ old('name', $definition->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="description">Beskrivelse</label>
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $definition->description) }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div id="networkSizeFields" class="hidden rounded-md border bg-gray-50 p-4">
        <div class="text-sm font-semibold text-gray-900">Annonceformat</div>
        <p class="mt-1 text-xs text-gray-600">Valgfrit. Hvis du angiver størrelse, vil kun annoncer i denne størrelse kunne udgives og vises i embed.</p>

        <div class="mt-4 grid gap-2">
            <label class="block font-medium text-sm text-gray-700" for="networkAdSize">Størrelse</label>
            <select id="networkAdSize" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Ingen krav</option>
                @php
                    $allowedSizes = config('smartads.allowed_ad_sizes', []);
                @endphp
                @if(is_array($allowedSizes))
                    @foreach($allowedSizes as $s)
                        @php
                            $w = is_array($s) && isset($s['width']) && is_numeric($s['width']) ? (int) $s['width'] : null;
                            $h = is_array($s) && isset($s['height']) && is_numeric($s['height']) ? (int) $s['height'] : null;
                        @endphp
                        @if($w && $h)
                            <option value="{{ $w }}x{{ $h }}">{{ $w }}×{{ $h }}</option>
                        @endif
                    @endforeach
                @endif
            </select>
        </div>

        <div class="mt-6 grid gap-2">
            <label class="block font-medium text-sm text-gray-700" for="networkViewMode">Visning</label>
            <select id="networkViewMode" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="grid">Grid</option>
                <option value="slideshow">Slideshow</option>
            </select>
            <p class="text-xs text-gray-600">Vælg hvordan annoncerne vises i network embed.</p>
        </div>

        <div id="networkSlideshowFields" class="mt-4 hidden rounded-md border bg-white p-4">
            <div class="text-sm font-semibold text-gray-900">Slideshow</div>
            <p class="mt-1 text-xs text-gray-600">Autoplay og loop er altid slået til.</p>

            <div class="mt-4 grid gap-2">
                <label class="block font-medium text-sm text-gray-700" for="networkItemsPerView">Antal synlige</label>
                <select id="networkItemsPerView" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @for($n = 1; $n <= 6; $n++)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endfor
                </select>
            </div>

            <div class="mt-4 grid gap-2">
                <label class="block font-medium text-sm text-gray-700" for="networkIntervalMs">Skift hvert (ms)</label>
                <input id="networkIntervalMs" type="number" min="1500" max="20000" step="500" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <p class="text-xs text-gray-600">1500-20000 ms.</p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <input type="hidden" name="is_active" value="0" />
        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $definition->is_active)) class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
        <label class="text-sm text-gray-700" for="is_active">Aktiv</label>
    </div>

    <div>
        <label class="block font-medium text-sm text-gray-700" for="capabilities">Capabilities (JSON array)</label>
        <textarea id="capabilities" name="capabilities" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('capabilities', is_array($definition->capabilities) ? json_encode($definition->capabilities) : '') }}</textarea>
        @error('capabilities')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<script>
  ;(function () {
    var typeEl = document.getElementById('type')
    var keyEl = document.getElementById('key')
    var capabilitiesEl = document.getElementById('capabilities')
    var sizeBoxEl = document.getElementById('networkSizeFields')
    var sizeEl = document.getElementById('networkAdSize')
    var viewModeEl = document.getElementById('networkViewMode')
    var slideshowBoxEl = document.getElementById('networkSlideshowFields')
    var itemsPerViewEl = document.getElementById('networkItemsPerView')
    var intervalMsEl = document.getElementById('networkIntervalMs')

    if (!typeEl || !keyEl) return

    var inUse = String(keyEl.getAttribute('data-in-use') || '') === '1'
    var isExisting = String(keyEl.getAttribute('data-existing') || '') === '1'

    function randomSuffix(len) {
      var chars = 'abcdefghijklmnopqrstuvwxyz0123456789'
      var out = ''
      for (var i = 0; i < len; i++) {
        out += chars.charAt(Math.floor(Math.random() * chars.length))
      }
      return out
    }

    function applyNetworkKeyLock() {
      var type = String(typeEl.value || '')
      if (type === 'network_website_embed') {
        var current = String(keyEl.value || '').trim()
        if (current === '' || current === 'network_website_embed') {
          keyEl.value = 'network_website_embed_' + randomSuffix(6)
        }

        if (inUse || isExisting) {
          keyEl.setAttribute('readonly', 'readonly')
          keyEl.classList.add('bg-gray-50')
        } else {
          keyEl.removeAttribute('readonly')
          keyEl.classList.remove('bg-gray-50')
        }
      } else {
        if (!inUse) {
          keyEl.removeAttribute('readonly')
          keyEl.classList.remove('bg-gray-50')
        }
      }
    }

    function parseCapabilities() {
      if (!capabilitiesEl) return {}
      var raw = String(capabilitiesEl.value || '').trim()
      if (!raw) return {}
      try {
        var v = JSON.parse(raw)
        if (v && typeof v === 'object') return v
      } catch (e) {}
      return {}
    }

    function writeCapabilities(obj) {
      if (!capabilitiesEl) return
      try {
        capabilitiesEl.value = JSON.stringify(obj)
      } catch (e) {}
    }

    function applyNetworkSizeUI() {
      if (!sizeBoxEl || !sizeEl) return

      var type = String(typeEl.value || '')
      if (type !== 'network_website_embed') {
        sizeBoxEl.classList.add('hidden')
        if (slideshowBoxEl) slideshowBoxEl.classList.add('hidden')
        return
      }

      sizeBoxEl.classList.remove('hidden')

      var caps = parseCapabilities()
      var w = caps.ad_width
      var h = caps.ad_height

      var wStr = w && !isNaN(Number(w)) ? String(parseInt(w, 10)) : ''
      var hStr = h && !isNaN(Number(h)) ? String(parseInt(h, 10)) : ''
      sizeEl.value = wStr && hStr ? wStr + 'x' + hStr : ''

      if (viewModeEl) {
        var vm = String(caps.view_mode || 'grid')
        if (vm !== 'grid' && vm !== 'slideshow') vm = 'grid'
        viewModeEl.value = vm
      }

      if (itemsPerViewEl) {
        var ipv = caps.slideshow_items_per_view
        var ipvNum = ipv && !isNaN(Number(ipv)) ? parseInt(ipv, 10) : 3
        if (!ipvNum || ipvNum < 1) ipvNum = 1
        if (ipvNum > 6) ipvNum = 6
        itemsPerViewEl.value = String(ipvNum)
      }

      if (intervalMsEl) {
        var im = caps.slideshow_interval_ms
        var imNum = im && !isNaN(Number(im)) ? parseInt(im, 10) : 4000
        if (!imNum || imNum < 1500) imNum = 1500
        if (imNum > 20000) imNum = 20000
        intervalMsEl.value = String(imNum)
      }

      if (slideshowBoxEl && viewModeEl) {
        if (String(viewModeEl.value || '') === 'slideshow') slideshowBoxEl.classList.remove('hidden')
        else slideshowBoxEl.classList.add('hidden')
      }
    }

    function syncNetworkSizeToCapabilities() {
      if (!sizeEl) return
      var type = String(typeEl.value || '')
      if (type !== 'network_website_embed') return

      var caps = parseCapabilities()

      var v = String(sizeEl.value || '').trim()
      if (v === '') {
        delete caps.ad_width
        delete caps.ad_height
      } else {
        var parts = v.split('x')
        var w = parts[0] ? parseInt(parts[0], 10) : 0
        var h = parts[1] ? parseInt(parts[1], 10) : 0
        if (w > 0 && h > 0) {
          caps.ad_width = w
          caps.ad_height = h
        } else {
          delete caps.ad_width
          delete caps.ad_height
        }
      }

      writeCapabilities(caps)
    }

    function syncNetworkViewModeToCapabilities() {
      if (!viewModeEl) return
      var type = String(typeEl.value || '')
      if (type !== 'network_website_embed') return
      var caps = parseCapabilities()
      var vm = String(viewModeEl.value || 'grid')
      if (vm !== 'grid' && vm !== 'slideshow') vm = 'grid'
      caps.view_mode = vm
      writeCapabilities(caps)
      applyNetworkSizeUI()
    }

    function syncNetworkSlideshowToCapabilities() {
      var type = String(typeEl.value || '')
      if (type !== 'network_website_embed') return
      var caps = parseCapabilities()

      if (itemsPerViewEl) {
        var ipv = parseInt(String(itemsPerViewEl.value || '3'), 10)
        if (!ipv || ipv < 1) ipv = 1
        if (ipv > 6) ipv = 6
        caps.slideshow_items_per_view = ipv
      }

      if (intervalMsEl) {
        var im = parseInt(String(intervalMsEl.value || '4000'), 10)
        if (!im || im < 1500) im = 1500
        if (im > 20000) im = 20000
        caps.slideshow_interval_ms = im
      }

      writeCapabilities(caps)
    }

    typeEl.addEventListener('change', applyNetworkKeyLock)
    typeEl.addEventListener('change', applyNetworkSizeUI)

    if (capabilitiesEl) {
      capabilitiesEl.addEventListener('input', applyNetworkSizeUI)
    }

    if (sizeEl) {
      sizeEl.addEventListener('change', syncNetworkSizeToCapabilities)
    }

    if (viewModeEl) {
      viewModeEl.addEventListener('change', syncNetworkViewModeToCapabilities)
    }

    if (itemsPerViewEl) {
      itemsPerViewEl.addEventListener('change', syncNetworkSlideshowToCapabilities)
    }

    if (intervalMsEl) {
      intervalMsEl.addEventListener('input', syncNetworkSlideshowToCapabilities)
      intervalMsEl.addEventListener('change', syncNetworkSlideshowToCapabilities)
    }

    applyNetworkKeyLock()
    applyNetworkSizeUI()
  })()
</script>
