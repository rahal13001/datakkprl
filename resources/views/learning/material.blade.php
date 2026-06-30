<x-layouts.app>
    <div id="learning-material-app" class="min-h-screen bg-slate-50" data-material-id="{{ $material->id }}" data-group-key="{{ $material->group->accessKey() }}" data-access-uuid="{{ $access->access_uuid }}">
        <style>
            .learning-shell { max-width: 1120px; margin: 0 auto; padding: 7rem 1.5rem 5rem; }
            .learning-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 1rem; background: white; box-shadow: 0 18px 45px rgba(15, 23, 42, .08); }
            .learning-viewer { width: 100%; min-height: 72vh; border: 0; background: #0f172a; }
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
                        <a id="download-material" href="{{ route('belajar-kkprl.material.download', [$material, 'access_uuid' => $access->access_uuid]) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700">
                            <i class="fa-solid fa-download mr-2"></i>Unduh PDF
                        </a>
                    @endif
                </div>
                <div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Akses grup aktif atas nama <strong>{{ $access->name }}</strong>. Semua materi dalam grup ini dapat dibuka tanpa mengisi formulir lagi.
                </div>
            </header>

            <section class="learning-card">
                @if($material->isPdf())
                    <iframe id="material-viewer" src="{{ route('belajar-kkprl.material.pdf', [$material, 'access_uuid' => $access->access_uuid]) }}" title="{{ $material->title }}" class="learning-viewer"></iframe>
                @elseif($material->videoEmbedUrl())
                    <iframe id="material-viewer" src="{{ $material->videoEmbedUrl() }}" title="{{ $material->title }}" class="learning-viewer" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                @else
                    <div class="p-10 text-center">
                        <p class="text-slate-600">Video ini tersedia dari sumber eksternal.</p>
                        <a id="external-video" href="{{ $material->safeVideoUrl() }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex rounded-xl bg-sky-700 px-5 py-3 font-bold text-white">Buka sumber video</a>
                    </div>
                @endif
            </section>
        </main>
    </div>

    @if(config('learning.detailed_tracking_enabled'))
    @php
        $materialTrackingEndpoints = [
            'start' => route('learning.session.start'),
            'end' => route('learning.session.end'),
            'activity' => route('learning.activity.store'),
        ];
    @endphp
    <script>
        (() => {
            const app = document.getElementById('learning-material-app');
            const materialId = Number(app.dataset.materialId);
            const groupKey = app.dataset.groupKey;
            const accessUuid = app.dataset.accessUuid;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const endpoints = {{ Illuminate\Support\Js::from($materialTrackingEndpoints) }};
            const milestones = new Set();
            let sessionId = null;
            let ending = false;

            const request = async (url, options = {}) => {
                const response = await fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, ...options });
                if (!response.ok) throw new Error('Tracking request failed.');
                return response.json();
            };
            const activity = async (type, extra = {}) => {
                try {
                    await request(endpoints.activity, { method: 'POST', body: JSON.stringify({ access_uuid: accessUuid, session_id: sessionId, group_key: groupKey, material_id: materialId, page_url: location.href, activity_type: type, ...extra }) });
                } catch (_) {}
            };
            const endSession = () => {
                if (!sessionId || ending) return;
                ending = true;
                const leave = new FormData();
                leave.set('_token', csrf); leave.set('access_uuid', accessUuid); leave.set('session_id', sessionId); leave.set('group_key', groupKey); leave.set('material_id', materialId); leave.set('page_url', location.href); leave.set('activity_type', 'leave_page');
                navigator.sendBeacon(endpoints.activity, leave);
                const session = new FormData();
                session.set('_token', csrf); session.set('access_uuid', accessUuid); session.set('session_id', sessionId); session.set('group_key', groupKey);
                navigator.sendBeacon(endpoints.end, session);
            };

            document.getElementById('download-material')?.addEventListener('click', () => activity('download_file'));
            document.getElementById('external-video')?.addEventListener('click', () => activity('play_video'));

            let scrollTimer;
            addEventListener('scroll', () => {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(() => {
                    const max = document.documentElement.scrollHeight - innerHeight;
                    if (max <= 0) return;
                    const progress = Math.min(100, Math.round(scrollY / max * 100));
                    [25, 50, 75, 100].forEach(mark => {
                        if (progress >= mark && !milestones.has(mark)) {
                            milestones.add(mark);
                            activity(`scroll_${mark}`, { progress_percent: mark });
                            if (mark === 100) activity('complete_material', { progress_percent: 100 });
                        }
                    });
                }, 250);
            }, { passive: true });

            setInterval(() => { if (!document.hidden) activity('heartbeat'); }, 45000);
            addEventListener('pagehide', endSession);

            (async () => {
                try {
                    const session = await request(endpoints.start, { method: 'POST', body: JSON.stringify({ access_uuid: accessUuid, group_key: groupKey }) });
                    sessionId = session.session_id;
                    await activity('open_material');
                } catch (_) {}
            })();
        })();
    </script>
    @endif
</x-layouts.app>
