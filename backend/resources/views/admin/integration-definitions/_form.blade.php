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

    if (!typeEl || !keyEl) return

    var inUse = String(keyEl.getAttribute('data-in-use') || '') === '1'

    function applyNetworkKeyLock() {
      if (inUse) return

      var type = String(typeEl.value || '')
      if (type === 'network_website_embed') {
        keyEl.value = 'network_website_embed'
        keyEl.setAttribute('readonly', 'readonly')
        keyEl.classList.add('bg-gray-50')
      } else {
        keyEl.removeAttribute('readonly')
        keyEl.classList.remove('bg-gray-50')
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
        return
      }

      sizeBoxEl.classList.remove('hidden')

      var caps = parseCapabilities()
      var w = caps.ad_width
      var h = caps.ad_height

      var wStr = w && !isNaN(Number(w)) ? String(parseInt(w, 10)) : ''
      var hStr = h && !isNaN(Number(h)) ? String(parseInt(h, 10)) : ''
      sizeEl.value = wStr && hStr ? wStr + 'x' + hStr : ''
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

    typeEl.addEventListener('change', applyNetworkKeyLock)
    typeEl.addEventListener('change', applyNetworkSizeUI)

    if (capabilitiesEl) {
      capabilitiesEl.addEventListener('input', applyNetworkSizeUI)
    }

    if (sizeEl) {
      sizeEl.addEventListener('change', syncNetworkSizeToCapabilities)
    }

    applyNetworkKeyLock()
    applyNetworkSizeUI()
  })()
</script>
