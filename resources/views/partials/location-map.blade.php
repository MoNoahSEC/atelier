@props([
    'mapId'        => 'atelier-map',
    'latInputId'   => 'input-latitude',
    'lngInputId'   => 'input-longitude',
    'cityInputId'  => null,
    'streetInputId'=> null,
    'initialLat'   => 30.0444,
    'initialLng'   => 31.2357,
    'disabled'     => false,
])

@once
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    /* ===== ATELIER LUXURY MAP STYLES ===== */
    .atelier-map-shell {
        font-family: 'Inter', system-ui, sans-serif;
    }

    /* Clear vivid map tiles — no gray filter */
    .map-tile-satellite .leaflet-tile { filter: none !important; }
    .map-tile-street .leaflet-tile { filter: contrast(1.05) saturate(0.7) brightness(1.02) !important; }

    /* Animated pulse ring for GPS accuracy */
    @keyframes gpsRingPulse {
        0%   { transform: scale(0.8); opacity: 0.6; }
        60%  { transform: scale(1.0); opacity: 0.15; }
        100% { transform: scale(0.8); opacity: 0.6; }
    }
    .gps-accuracy-ring { animation: gpsRingPulse 2s ease-in-out infinite; }

    /* Black custom pin */
    .atl-pin-wrap {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        width: 36px;
        height: 44px;
        filter: drop-shadow(0 4px 10px rgba(0,0,0,0.45));
        transition: transform 0.18s cubic-bezier(.4,0,.2,1);
    }
    .atl-pin-wrap:hover { transform: translateY(-3px) scale(1.08); }
    .atl-pin-body {
        width: 36px;
        height: 36px;
        background: #000;
        border: 3px solid #fff;
        clip-path: polygon(50% 100%, 0% 30%, 15% 0%, 85% 0%, 100% 30%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .atl-pin-dot {
        width: 10px;
        height: 10px;
        background: #fff;
        border-radius: 50% !important;
        margin-top: -4px;
    }

    /* Leaflet popup luxury */
    .leaflet-popup-content-wrapper {
        border: 2px solid #000 !important;
        border-radius: 0 !important;
        box-shadow: 4px 4px 0 0 rgba(0,0,0,1) !important;
        background: #fff !important;
        padding: 0 !important;
    }
    .leaflet-popup-content {
        margin: 0 !important;
        padding: 10px 14px !important;
        font-size: 11px !important;
        font-family: 'Inter', sans-serif !important;
        line-height: 1.5 !important;
    }
    .leaflet-popup-tip { background: #000 !important; }

    /* Leaflet zoom controls */
    .leaflet-control-zoom {
        border: 2px solid #000 !important;
        border-radius: 0 !important;
        box-shadow: 3px 3px 0 0 rgba(0,0,0,1) !important;
        overflow: hidden;
    }
    .leaflet-control-zoom a {
        border-radius: 0 !important;
        background: #fff !important;
        color: #000 !important;
        font-weight: 800 !important;
        width: 30px !important;
        height: 30px !important;
        line-height: 28px !important;
        font-size: 16px !important;
        border-bottom: 1px solid #000 !important;
    }
    .leaflet-control-zoom a:last-child { border-bottom: none !important; }
    .leaflet-control-zoom a:hover { background: #000 !important; color: #fff !important; }

    /* Search suggestion row */
    .atl-suggestion { transition: background 0.12s; }
    .atl-suggestion:hover { background: #F5F5F0; }

    /* District pill */
    .atl-district-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.03em;
        border: 1.5px solid rgba(0,0,0,0.3);
        background: #fff;
        color: #000;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }
    .atl-district-pill:hover,
    .atl-district-pill.active {
        background: #000;
        border-color: #000;
        color: #fff;
    }

    /* Bottom coord bar */
    .atl-coord-bar {
        background: linear-gradient(90deg, #000 0%, #1a1a1a 100%);
        color: #fff;
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        letter-spacing: 0.04em;
        padding: 6px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-top: 2px solid #000;
    }
</style>
@endonce

<div class="atelier-map-shell" id="mapshell-{{ $mapId }}"
    x-data="{
        searchQ: '',
        results: [],
        searching: false,
        pasteOpen: false,
        pasteVal: '',
        activeDistrict: null,
        doSearch() {
            if (!this.searchQ || this.searchQ.length < 2) { this.results = []; return; }
            this.searching = true;
            clearTimeout(this._st);
            this._st = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQ)}&countrycodes=eg&limit=5&addressdetails=1`, { headers: { 'Accept-Language': 'ar,en' } })
                .then(r => r.json())
                .then(d => { this.searching = false; this.results = d || []; })
                .catch(() => { this.searching = false; this.results = []; });
            }, 360);
        },
        pickResult(item) {
            window['mapFly_{{ str_replace('-','_',$mapId) }}'](+item.lat, +item.lon, item.display_name.split(',')[0]);
            this.results = [];
            this.searchQ = item.display_name.split(',')[0];
        },
        parsePaste() {
            window['mapParsePaste_{{ str_replace('-','_',$mapId) }}'](this.pasteVal);
            this.pasteVal = '';
            this.pasteOpen = false;
        }
    }"
>
    <!-- ── SECTION HEADER ──────────────────────── -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <div class="w-0.5 h-5 bg-black shrink-0"></div>
            <span class="font-editorial text-[11px] font-bold uppercase tracking-[0.18em] text-black">
                تحديد موقع التوصيل
            </span>
        </div>
        <div class="flex items-center gap-2">
            <!-- Layer toggle -->
            <button type="button"
                onclick="window['mapToggleLayer_{{ str_replace('-','_',$mapId) }}']()"
                id="layerBtn-{{ $mapId }}"
                class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider font-editorial border border-black px-2 py-1 bg-white hover:bg-black hover:text-white transition-colors"
            >
                <span id="layerIco-{{ $mapId }}">🛰</span>
                <span id="layerLbl-{{ $mapId }}">قمر صناعي</span>
            </button>
            <!-- GPS -->
            <button type="button"
                id="gpsBtn-{{ $mapId }}"
                onclick="window['mapGPS_{{ str_replace('-','_',$mapId) }}']()"
                class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider font-editorial bg-black text-white px-2.5 py-1 hover:bg-gray-800 transition-colors"
            >
                🎯 <span id="gpsTxt-{{ $mapId }}">GPS</span>
            </button>
        </div>
    </div>

    <!-- ── LIVE SEARCH ─────────────────────────── -->
    <div class="relative mb-2">
        <div class="flex items-center border-2 border-black bg-white focus-within:shadow-[3px_3px_0_0_rgba(0,0,0,1)] transition-shadow">
            <span class="pl-3 pr-2 text-black/40 text-sm select-none pointer-events-none">🔍</span>
            <input
                type="text"
                x-model="searchQ"
                @input="doSearch()"
                placeholder="ابحث: الزمالك، التجمع الخامس، مول العرب، شارع التحرير..."
                class="flex-1 py-2.5 pr-1 text-xs bg-transparent focus:outline-none placeholder-gray-400 font-sans"
                autocomplete="off"
                @keydown.escape="results = []"
            >
            <span x-show="searching" class="pl-3 pr-3 text-[9px] text-gray-400 font-mono shrink-0">بحث...</span>
        </div>
        <!-- Dropdown -->
        <div
            x-show="results.length > 0"
            @click.away="results = []"
            x-cloak
            class="absolute left-0 right-0 top-full z-40 bg-white border-2 border-black border-t-0 shadow-[4px_4px_0_0_rgba(0,0,0,1)] max-h-48 overflow-y-auto"
        >
            <template x-for="r in results" :key="r.place_id">
                <div
                    @click="pickResult(r)"
                    class="atl-suggestion flex items-center gap-2.5 px-3 py-2.5 cursor-pointer border-b border-black/10 last:border-0"
                >
                    <span class="text-sm shrink-0">📍</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-black truncate font-sans" x-text="r.display_name.split(',')[0]"></p>
                        <p class="text-[10px] text-black/40 truncate font-sans" x-text="r.display_name.split(',').slice(1,3).join(',')"></p>
                    </div>
                    <span class="ml-auto text-[9px] font-bold uppercase bg-black text-white px-1.5 py-0.5 shrink-0 font-editorial">اختيار</span>
                </div>
            </template>
        </div>
    </div>

    <!-- ── DISTRICT SHORTCUT PILLS ────────────── -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1.5 mb-2 scrollbar-none" style="-ms-overflow-style:none; scrollbar-width:none;">
        @php
        $districts = [
            ['name' => 'التجمع الخامس', 'lat' => 30.0074, 'lng' => 31.4339],
            ['name' => 'الزمالك',        'lat' => 30.0626, 'lng' => 31.2224],
            ['name' => 'المعادي',        'lat' => 29.9602, 'lng' => 31.2569],
            ['name' => 'مدينة نصر',     'lat' => 30.0566, 'lng' => 31.3301],
            ['name' => 'مصر الجديدة',   'lat' => 30.0914, 'lng' => 31.3236],
            ['name' => 'الشيخ زايد',    'lat' => 30.0469, 'lng' => 30.9842],
            ['name' => '6 أكتوبر',      'lat' => 29.9723, 'lng' => 30.9427],
            ['name' => 'المهندسين',      'lat' => 30.0537, 'lng' => 31.2001],
            ['name' => 'الإسكندرية',    'lat' => 31.2001, 'lng' => 29.9187],
        ];
        @endphp
        @foreach($districts as $d)
        <button
            type="button"
            class="atl-district-pill"
            onclick="window['mapFly_{{ str_replace('-','_',$mapId) }}']({{ $d['lat'] }}, {{ $d['lng'] }}, '{{ $d['name'] }}'); this.closest('.atelier-map-shell').querySelectorAll('.atl-district-pill').forEach(b=>b.classList.remove('active')); this.classList.add('active');"
        >{{ $d['name'] }}</button>
        @endforeach
    </div>

    <!-- ── PASTE LINK BOX ─────────────────────── -->
    <div x-show="pasteOpen" x-cloak class="mb-2 bg-[#F5F5F0] border-2 border-black p-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-bold uppercase tracking-wider font-editorial text-black">لصق رابط Google Maps أو إحداثيات</span>
            <button type="button" @click="pasteOpen=false" class="text-xs font-bold text-black/50 hover:text-black">✕</button>
        </div>
        <div class="flex gap-2">
            <input type="text" x-model="pasteVal"
                placeholder="https://maps.app.goo.gl/... أو 30.062, 31.222"
                class="flex-1 border border-black p-2 text-xs bg-white focus:outline-none font-mono min-w-0">
            <button type="button" @click="parsePaste()"
                class="bg-black text-white px-4 py-2 text-[10px] font-bold uppercase tracking-wider font-editorial hover:bg-gray-800 shrink-0">
                تثبيت
            </button>
        </div>
    </div>

    <!-- ── MAP CANVAS ─────────────────────────── -->
    <div class="relative border-2 border-black overflow-hidden shadow-[5px_5px_0_0_rgba(0,0,0,1)]">
        <div id="{{ $mapId }}" class="w-full {{ $disabled ? 'pointer-events-none opacity-60' : '' }}"
             style="height: 320px; min-height: 280px; z-index: 10; background: #e8e8e3;"></div>

        <!-- Paste link floating button -->
        <button type="button" @click="pasteOpen=!pasteOpen"
            class="absolute top-3 right-3 z-20 bg-white border-2 border-black text-[9px] font-bold uppercase tracking-wider font-editorial px-2 py-1 shadow-[2px_2px_0_0_rgba(0,0,0,1)] hover:bg-black hover:text-white transition-colors"
            title="لصق رابط Google Maps"
        >🔗 لصق رابط</button>

        <!-- Map loading indicator -->
        <div id="mapLoading-{{ $mapId }}" class="absolute inset-0 flex items-center justify-center bg-[#F5F5F0] z-30">
            <div class="text-center space-y-2">
                <div class="w-8 h-8 border-3 border-black border-t-transparent mx-auto" style="border-width:3px; border-style:solid; animation: spin 0.8s linear infinite;"></div>
                <p class="text-[10px] font-editorial font-bold uppercase tracking-wider text-black">تحميل الخريطة...</p>
            </div>
        </div>
    </div>

    <!-- ── COORD STATUS BAR ────────────────────── -->
    <div class="atl-coord-bar">
        <div class="flex items-center gap-2 min-w-0">
            <span class="w-2 h-2 rounded-full bg-green-400 shrink-0" style="border-radius:50% !important;"
                  id="statusDot-{{ $mapId }}"></span>
            <span id="statusTxt-{{ $mapId }}" class="truncate">اسحب الدبوس أو اضغط على الخريطة لتثبيت موقع التوصيل</span>
        </div>
        <span id="coordBadge-{{ $mapId }}" class="font-mono text-amber-300 font-bold shrink-0"></span>
    </div>

    <!-- ── REVERSE GEOCODE CHIPS ──────────────── -->
    <div id="addrChips-{{ $mapId }}" class="hidden border-2 border-black border-t-0 bg-[#F9F9F6] px-4 py-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-sans">
        <span class="flex items-center gap-1 font-semibold text-black">
            🏙️ <span id="chipGov-{{ $mapId }}">القاهرة</span>
        </span>
        <span class="text-black/30">|</span>
        <span class="flex items-center gap-1 text-black/70 truncate max-w-xs">
            🛣️ <span id="chipStreet-{{ $mapId }}">تحديد المنطقة...</span>
        </span>
        <span class="ml-auto font-mono text-[10px] text-black/50">
            📍 <span id="chipCoords-{{ $mapId }}"></span>
        </span>
    </div>

    <!-- Hidden inputs -->
    <input type="hidden" name="latitude"  id="{{ $latInputId }}"  value="{{ $initialLat }}" {{ $disabled ? 'disabled' : '' }}>
    <input type="hidden" name="longitude" id="{{ $lngInputId }}"  value="{{ $initialLng }}" {{ $disabled ? 'disabled' : '' }}>
</div>

@push('scripts')
<script>
(function() {
    const MAP_ID      = '{{ $mapId }}';
    const FN          = '{{ str_replace('-','_',$mapId) }}';
    const LAT_EL      = '{{ $latInputId }}';
    const LNG_EL      = '{{ $lngInputId }}';
    const CITY_EL     = '{{ $cityInputId }}';
    const STREET_EL   = '{{ $streetInputId }}';
    const INIT_LAT    = {{ (float)($initialLat ?: 30.0444) }};
    const INIT_LNG    = {{ (float)($initialLng ?: 31.2357) }};

    let map, marker, gpsCircle, currentLayer;

    const layers = {
        street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19,
            className: 'map-tile-street'
        }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri, Maxar',
            maxZoom: 19,
            className: 'map-tile-satellite'
        })
    };

    function pinIcon() {
        return L.divIcon({
            className: '',
            html: `<div class="atl-pin-wrap"><div class="atl-pin-body"><div class="atl-pin-dot"></div></div></div>`,
            iconSize: [36, 44],
            iconAnchor: [18, 44],
            popupAnchor: [0, -46]
        });
    }

    function setCoords(lat, lng) {
        document.getElementById(LAT_EL).value = lat.toFixed(6);
        document.getElementById(LNG_EL).value = lng.toFixed(6);

        const fmt = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        const badge = document.getElementById('coordBadge-' + MAP_ID);
        if (badge) badge.textContent = fmt;

        const chipCoords = document.getElementById('chipCoords-' + MAP_ID);
        if (chipCoords) chipCoords.textContent = fmt;

        const dot = document.getElementById('statusDot-' + MAP_ID);
        if (dot) { dot.style.background = '#4ade80'; }
    }

    function setStatus(msg) {
        const el = document.getElementById('statusTxt-' + MAP_ID);
        if (el) el.textContent = msg;
    }

    function showChips(show) {
        const el = document.getElementById('addrChips-' + MAP_ID);
        if (el) el.classList.toggle('hidden', !show);
        if (el) el.style.display = show ? 'flex' : 'none';
    }

    function reverseGeocode(lat, lng) {
        setStatus('قراءة تفاصيل العنوان...');
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
            headers: { 'Accept-Language': 'ar,en' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.address) { setStatus('تم تثبيت الموقع ✓'); return; }
            const a = data.address;
            const city   = a.city || a.town || a.state || a.county || 'القاهرة';
            const street = [a.road, a.suburb, a.neighbourhood, a.building].filter(Boolean).join('، ');

            if (CITY_EL) {
                const cityEl = document.getElementById(CITY_EL);
                if (cityEl) { cityEl.value = city; cityEl.setAttribute('data-auto', '1'); }
            }
            if (STREET_EL) {
                const stEl = document.getElementById(STREET_EL);
                if (stEl && street) { stEl.value = street; stEl.setAttribute('data-auto', '1'); }
            }

            const chipGov = document.getElementById('chipGov-' + MAP_ID);
            if (chipGov) chipGov.textContent = city;
            const chipStreet = document.getElementById('chipStreet-' + MAP_ID);
            if (chipStreet) chipStreet.textContent = street || 'موقع محدد بدقة';

            setStatus(`✓ ${city}${street ? ' — ' + street.split('،')[0] : ''}`);
            showChips(true);

            // Popup on marker
            if (marker) {
                marker.bindPopup(`<strong>${city}</strong>${street ? '<br><span style="color:#555">' + street + '</span>' : ''}`).openPopup();
            }
        })
        .catch(() => setStatus('تم تثبيت الموقع ✓'));
    }

    function updatePin(lat, lng, reverse = true) {
        setCoords(lat, lng);
        if (reverse) reverseGeocode(lat, lng);
    }

    function init() {
        const el = document.getElementById(MAP_ID);
        if (!el || el._leaflet_id) return;

        // Hide loading
        const loading = document.getElementById('mapLoading-' + MAP_ID);
        if (loading) loading.remove();

        map = L.map(MAP_ID, {
            center: [INIT_LAT, INIT_LNG],
            zoom: 13,
            zoomControl: true,
            scrollWheelZoom: false,
        });

        currentLayer = 'street';
        layers.street.addTo(map);

        marker = L.marker([INIT_LAT, INIT_LNG], {
            icon: pinIcon(),
            draggable: !{{ $disabled ? 'true' : 'false' }},
            title: 'اسحب الدبوس لتغيير مكان التسليم'
        }).addTo(map);

        setCoords(INIT_LAT, INIT_LNG);

        marker.on('dragend', () => {
            const p = marker.getLatLng();
            updatePin(p.lat, p.lng, true);
        });

        map.on('click', e => {
            marker.setLatLng(e.latlng);
            updatePin(e.latlng.lat, e.latlng.lng, true);
        });

        // Store refs
        window['mapInstance_' + FN] = map;
        window['markerInstance_' + FN] = marker;
    }

    // Public API
    window['mapFly_' + FN] = function(lat, lng, label) {
        if (!map) return;
        map.flyTo([lat, lng], 15, { duration: 1.0, easeLinearity: 0.4 });
        marker.setLatLng([lat, lng]);
        updatePin(lat, lng, true);
        const chipStreet = document.getElementById('chipStreet-' + MAP_ID);
        if (chipStreet && label) chipStreet.textContent = label;
        showChips(true);
    };

    window['mapParsePaste_' + FN] = function(input) {
        input = (input || '').trim();
        // Coords: "30.05, 31.22" or "30.05 31.22"
        const m1 = input.match(/(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)/);
        if (m1) {
            const lat = +m1[1], lng = +m1[2];
            if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                window['mapFly_' + FN](lat, lng, 'موقع مُدخل');
                return;
            }
        }
        // Google Maps q= param
        const m2 = input.match(/q=(-?\d+\.?\d*)[,+](-?\d+\.?\d*)/);
        if (m2) { window['mapFly_' + FN](+m2[1], +m2[2], 'رابط Google Maps'); return; }
        // @lat,lng
        const m3 = input.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
        if (m3) { window['mapFly_' + FN](+m3[1], +m3[2], 'رابط Google Maps'); return; }

        alert('لم يتمكن من استخراج الإحداثيات. أدخل مثل: 30.062, 31.222 أو الصق رابط Google Maps.');
    };

    window['mapToggleLayer_' + FN] = function() {
        if (!map) return;
        const lbl = document.getElementById('layerLbl-' + MAP_ID);
        const ico = document.getElementById('layerIco-' + MAP_ID);
        if (currentLayer === 'street') {
            map.removeLayer(layers.street);
            layers.satellite.addTo(map);
            currentLayer = 'satellite';
            if (lbl) lbl.textContent = 'أبيض وأسود';
            if (ico) ico.textContent = '🗺️';
        } else {
            map.removeLayer(layers.satellite);
            layers.street.addTo(map);
            currentLayer = 'street';
            if (lbl) lbl.textContent = 'قمر صناعي';
            if (ico) ico.textContent = '🛰';
        }
    };

    window['mapGPS_' + FN] = function() {
        if (!navigator.geolocation) { alert('GPS غير مدعوم في هذا المتصفح.'); return; }
        const btnTxt = document.getElementById('gpsTxt-' + MAP_ID);
        if (btnTxt) btnTxt.textContent = '...جاري';
        setStatus('جاري تحديد موقعك الحالي...');

        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude, lng = pos.coords.longitude, acc = pos.coords.accuracy || 50;
            if (gpsCircle) map.removeLayer(gpsCircle);
            gpsCircle = L.circle([lat, lng], {
                radius: acc,
                color: '#000',
                fillColor: '#000',
                fillOpacity: 0.08,
                weight: 1.5,
                className: 'gps-accuracy-ring'
            }).addTo(map);
            window['mapFly_' + FN](lat, lng, '');
            if (btnTxt) { btnTxt.textContent = '✓ تم'; setTimeout(() => btnTxt.textContent = 'GPS', 2500); }
        }, () => {
            if (btnTxt) { btnTxt.textContent = 'GPS'; }
            setStatus('تعذر الوصول للـ GPS — ابحث عن منطقتك بالأعلى');
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    };

    // Refresh on event (e.g. when shown after being hidden)
    window.addEventListener('map-refresh-{{ $mapId }}', () => {
        if (map) { setTimeout(() => map.invalidateSize(), 200); }
    });

    // Init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

@if(!isset($pushScriptsOnce_map_spin))
@php($pushScriptsOnce_map_spin = true)
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endif
@endpush
