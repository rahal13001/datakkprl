<x-layouts.app>
    <div id="learning-material-app" class="min-h-screen bg-slate-50" data-material-id="{{ $material->id }}" data-material-key="{{ $material->accessKey() }}">
        <style>
            [data-hidden] { display: none !important; }
            .learning-shell { max-width: 1120px; margin: 0 auto; padding: 7rem 1.5rem 5rem; }
            .learning-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 1rem; background: white; box-shadow: 0 18px 45px rgba(15, 23, 42, .08); }
            .learning-viewer { width: 100%; min-height: 72vh; border: 0; background: #0f172a; }
            .visitor-overlay { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; overflow-y: auto; padding: 1.5rem; background: rgba(15, 23, 42, .7); backdrop-filter: blur(8px); }
            .visitor-modal { width: min(100%, 580px); border-radius: 1rem; background: white; padding: 2rem; box-shadow: 0 28px 70px rgba(0,0,0,.25); }
            .visitor-input { width: 100%; border: 1px solid #cbd5e1; border-radius: .75rem; padding: .8rem 1rem; color: #0f172a; outline: none; }
            .visitor-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
            .visitor-label { display: block; margin-bottom: .45rem; font-size: .875rem; font-weight: 700; color: #334155; }
            .visitor-submit { width: 100%; border-radius: .75rem; background: #0369a1; padding: .9rem 1rem; font-weight: 800; color: white; }
            .visitor-submit:disabled { cursor: wait; opacity: .65; }
        </style>

        <main class="learning-shell">
            <a href="{{ route('belajar-kkprl.group', $material->group) }}" class="inline-flex items-center gap-2 text-sm font-bold text-sky-700">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke {{ $material->group->title }}
            </a>

            <header class="my-7">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold uppercase text-sky-700">{{ $material->isPdf() ? 'PDF' : 'Video' }}</span>
                        <h1 class="mt-4 text-3xl font-bold text-slate-950 lg:text-4xl">{{ $material->title }}</h1>
                        <p class="mt-3 max-w-3xl leading-relaxed text-slate-600">{{ $material->description }}</p>
                    </div>
                    @if($material->isPdf())
                        <a id="download-material" data-hidden href="#" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                            <i class="fa-solid fa-download mr-2"></i>Unduh PDF
                        </a>
                    @endif
                </div>
                <div id="welcome-back" data-hidden class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <span id="welcome-text"></span>
                    <button id="change-visitor" type="button" class="font-bold underline">Bukan Anda? Ubah data pengunjung</button>
                </div>
            </header>

            <section id="material-content" data-hidden class="learning-card">
                @if($material->isPdf())
                    <iframe id="material-viewer" title="{{ $material->title }}" class="learning-viewer"></iframe>
                @elseif($material->videoEmbedUrl())
                    <iframe id="material-viewer" title="{{ $material->title }}" class="learning-viewer" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                @else
                    <div class="p-10 text-center">
                        <p class="text-slate-600">Video ini tersedia dari sumber eksternal.</p>
                        <a id="external-video" data-hidden href="{{ $material->safeVideoUrl() }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex rounded-xl bg-sky-700 px-5 py-3 font-bold text-white">Buka sumber video</a>
                    </div>
                @endif
            </section>
        </main>

        <div id="visitor-overlay" class="visitor-overlay" data-hidden>
            <div class="visitor-modal" role="dialog" aria-modal="true" aria-labelledby="visitor-title">
                <h2 id="visitor-title" class="text-2xl font-bold text-slate-950">Data Pengunjung Materi</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">Sebelum mengakses materi pembelajaran ini, mohon isi data singkat berikut. Data ini digunakan untuk monitoring pemanfaatan materi dan peningkatan layanan.</p>
                <form id="visitor-form" class="mt-7 grid gap-5">
                    <div><label class="visitor-label" for="visitor-name">Nama</label><input class="visitor-input" id="visitor-name" name="name" required maxlength="150" placeholder="Masukkan nama Anda"></div>
                    <div><label class="visitor-label" for="visitor-institution">Asal Instansi</label><input class="visitor-input" id="visitor-institution" name="institution" required maxlength="200" placeholder="Contoh: Dinas Kelautan dan Perikanan, Universitas, Sekolah, Umum"></div>
                    <div><label class="visitor-label" for="visitor-purpose">Tujuan Mengakses Materi</label><textarea class="visitor-input" id="visitor-purpose" name="access_purpose" required maxlength="255" rows="3" placeholder="Contoh: Belajar mandiri, referensi kerja, pelatihan, tugas sekolah/kuliah"></textarea></div>
                    <p id="visitor-error" data-hidden class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"></p>
                    <button id="visitor-submit" class="visitor-submit" type="submit">Mulai Belajar</button>
                </form>
            </div>
        </div>
    </div>

    @php
        $learningEndpoints = [
            'store' => route('learning.access.store'),
            'me' => route('learning.access.me'),
            'start' => route('learning.session.start'),
            'end' => route('learning.session.end'),
            'activity' => route('learning.activity.store'),
            'pdf' => $material->isPdf() ? route('belajar-kkprl.material.pdf', $material) : null,
            'download' => $material->isPdf() ? route('belajar-kkprl.material.download', $material) : null,
            'video' => $material->videoEmbedUrl(),
        ];
    @endphp
    <script>
        (() => {
            const app = document.getElementById('learning-material-app');
            const materialId = Number(app.dataset.materialId);
            const materialKey = app.dataset.materialKey;
            const storageKey = `krl_lms_access_${materialKey}`;
            const browserKey = 'krl_lms_browser_uuid';
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const endpoints = {{ Illuminate\Support\Js::from($learningEndpoints) }};
            const overlay = document.getElementById('visitor-overlay');
            const content = document.getElementById('material-content');
            const welcome = document.getElementById('welcome-back');
            const error = document.getElementById('visitor-error');
            const milestones = new Set();
            let access = null;
            let sessionId = null;
            let ending = false;

            const uuid = () => crypto.randomUUID ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
            const browserUuid = localStorage.getItem(browserKey) || uuid();
            localStorage.setItem(browserKey, browserUuid);
            const show = el => el?.removeAttribute('data-hidden');
            const hide = el => el?.setAttribute('data-hidden', '');
            const request = async (url, options = {}) => {
                const response = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers || {}) }, ...options });
                if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'Permintaan tidak dapat diproses.');
                return response.json();
            };
            const payload = extra => ({ access_uuid: access.access_uuid, material_key: materialKey, ...extra });
            const activity = async (type, extra = {}) => {
                if (!access) return;
                try { await request(endpoints.activity, { method: 'POST', body: JSON.stringify(payload({ session_id: sessionId, page_url: location.href, activity_type: type, ...extra })) }); } catch (_) {}
            };
            const unlock = (visitor, returning = false) => {
                ending = false;
                access = visitor;
                hide(overlay); show(content); show(welcome);
                document.getElementById('welcome-text').textContent = `Selamat datang kembali, ${visitor.name}. Anda dapat melanjutkan akses ke materi ini.`;
                const viewer = document.getElementById('material-viewer');
                if (viewer) viewer.src = endpoints.pdf ? `${endpoints.pdf}?access_uuid=${encodeURIComponent(visitor.access_uuid)}` : endpoints.video;
                const download = document.getElementById('download-material');
                if (download) { download.href = `${endpoints.download}?access_uuid=${encodeURIComponent(visitor.access_uuid)}`; show(download); }
                show(document.getElementById('external-video'));
                if (!returning) activity('open_material');
            };

            document.getElementById('visitor-form').addEventListener('submit', async event => {
                event.preventDefault(); hide(error);
                const button = document.getElementById('visitor-submit'); button.disabled = true;
                const fields = Object.fromEntries(new FormData(event.currentTarget));
                try {
                    const visitor = await request(endpoints.store, { method: 'POST', body: JSON.stringify({ ...fields, material_id: materialId, material_key: materialKey, browser_uuid: browserUuid }) });
                    localStorage.setItem(storageKey, JSON.stringify({ access_uuid: visitor.access_uuid, name: visitor.name }));
                    sessionId = visitor.session_id; unlock(visitor); 
                } catch (e) { error.textContent = e.message; show(error); }
                finally { button.disabled = false; }
            });

            document.getElementById('change-visitor').addEventListener('click', () => {
                endSession(); localStorage.removeItem(storageKey); access = null; sessionId = null; hide(content); hide(welcome); show(overlay);
            });
            document.getElementById('download-material')?.addEventListener('click', () => activity('download_file'));
            document.getElementById('external-video')?.addEventListener('click', () => activity('play_video'));

            let scrollTimer;
            addEventListener('scroll', () => {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(() => {
                    const max = document.documentElement.scrollHeight - innerHeight;
                    if (max <= 0) return;
                    const progress = Math.min(100, Math.round(scrollY / max * 100));
                    [25, 50, 75, 100].forEach(mark => { if (progress >= mark && !milestones.has(mark)) { milestones.add(mark); activity(`scroll_${mark}`, { progress_percent: mark }); if (mark === 100) activity('complete_material', { progress_percent: 100 }); } });
                }, 250);
            }, { passive: true });

            setInterval(() => { if (!document.hidden) activity('heartbeat'); }, 45000);
            const endSession = () => {
                if (!access || !sessionId || ending) return; ending = true;
                const form = new FormData(); form.set('_token', csrf); form.set('access_uuid', access.access_uuid); form.set('material_key', materialKey); form.set('session_id', sessionId);
                navigator.sendBeacon(endpoints.end, form);
                const log = new FormData(); log.set('_token', csrf); log.set('access_uuid', access.access_uuid); log.set('material_key', materialKey); log.set('session_id', sessionId); log.set('page_url', location.href); log.set('activity_type', 'leave_page');
                navigator.sendBeacon(endpoints.activity, log);
            };
            addEventListener('pagehide', endSession);

            (async () => {
                let saved = null;
                try { saved = JSON.parse(localStorage.getItem(storageKey)); } catch (_) { localStorage.removeItem(storageKey); }
                if (!saved?.access_uuid) { show(overlay); return; }
                try {
                    const visitor = await request(`${endpoints.me}?${new URLSearchParams({ access_uuid: saved.access_uuid, material_key: materialKey })}`);
                    access = visitor;
                    const session = await request(endpoints.start, { method: 'POST', body: JSON.stringify(payload({})) });
                    sessionId = session.session_id; unlock(visitor, true); activity('open_material');
                } catch (_) { localStorage.removeItem(storageKey); access = null; show(overlay); }
            })();
        })();
    </script>
</x-layouts.app>
