<style>
    .krl-material-list {
        display: grid;
        gap: 12px;
    }

    .krl-material-card {
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .krl-material-card:hover {
        border-color: #bae6fd;
        box-shadow: 0 8px 24px rgba(14, 116, 144, 0.08);
        transform: translateY(-1px);
    }

    .krl-material-icon {
        display: grid;
        width: 46px;
        height: 46px;
        place-items: center;
        border-radius: 12px;
        background: #ecfeff;
        color: #0e7490;
    }

    .krl-material-icon svg {
        width: 23px;
        height: 23px;
    }

    .krl-material-copy {
        min-width: 0;
    }

    .krl-material-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .krl-material-title {
        margin: 0;
        overflow: hidden;
        color: #0f172a;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .krl-material-type {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #e0f2fe;
        padding: 3px 8px;
        color: #0369a1;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.06em;
        line-height: 1;
        text-transform: uppercase;
    }

    .krl-material-time {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 6px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
    }

    .krl-material-time svg {
        width: 14px;
        height: 14px;
        flex: none;
    }

    .krl-material-count {
        min-width: 76px;
        border-left: 1px solid #e2e8f0;
        padding-left: 16px;
        text-align: center;
    }

    .krl-material-count strong {
        display: block;
        color: #0891b2;
        font-size: 22px;
        font-weight: 800;
        line-height: 1;
    }

    .krl-material-count span {
        display: block;
        margin-top: 5px;
        color: #64748b;
        font-size: 11px;
        line-height: 1.2;
    }

    .krl-material-empty {
        display: grid;
        justify-items: center;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 38px 24px;
        background: #f8fafc;
        text-align: center;
    }

    .krl-material-empty-icon {
        display: grid;
        width: 48px;
        height: 48px;
        place-items: center;
        border-radius: 999px;
        background: #e0f2fe;
        color: #0284c7;
    }

    .krl-material-empty-icon svg {
        width: 24px;
        height: 24px;
    }

    .krl-material-empty strong {
        margin-top: 14px;
        color: #334155;
        font-size: 14px;
    }

    .krl-material-empty p {
        max-width: 360px;
        margin: 5px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .dark .krl-material-card {
        border-color: rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.035);
        box-shadow: none;
    }

    .dark .krl-material-card:hover {
        border-color: rgba(34, 211, 238, 0.35);
        background: rgba(255, 255, 255, 0.055);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.16);
    }

    .dark .krl-material-icon {
        background: rgba(6, 182, 212, 0.12);
        color: #67e8f9;
    }

    .dark .krl-material-title {
        color: #f8fafc;
    }

    .dark .krl-material-type {
        background: rgba(14, 165, 233, 0.13);
        color: #7dd3fc;
    }

    .dark .krl-material-time,
    .dark .krl-material-count span {
        color: #94a3b8;
    }

    .dark .krl-material-count {
        border-left-color: rgba(255, 255, 255, 0.1);
    }

    .dark .krl-material-count strong {
        color: #67e8f9;
    }

    .dark .krl-material-empty {
        border-color: rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.025);
    }

    .dark .krl-material-empty-icon {
        background: rgba(14, 165, 233, 0.12);
        color: #7dd3fc;
    }

    .dark .krl-material-empty strong {
        color: #e2e8f0;
    }

    .dark .krl-material-empty p {
        color: #94a3b8;
    }

    @media (max-width: 560px) {
        .krl-material-card {
            grid-template-columns: 40px minmax(0, 1fr);
            padding: 14px;
        }

        .krl-material-icon {
            width: 40px;
            height: 40px;
        }

        .krl-material-count {
            grid-column: 2;
            display: flex;
            align-items: baseline;
            gap: 5px;
            min-width: 0;
            border-left: 0;
            padding-left: 0;
            text-align: left;
        }

        .krl-material-count strong,
        .krl-material-count span {
            display: inline;
            margin: 0;
        }
    }
</style>

<div class="krl-material-list">
    @forelse($materials as $material)
        <article class="krl-material-card">
            <div class="krl-material-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.25 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V7.5l-5.25-5.25Z" />
                    <path d="M14.25 2.25V7.5h5.25M8.25 12h7.5M8.25 15.75h7.5" />
                </svg>
            </div>

            <div class="krl-material-copy">
                <div class="krl-material-heading">
                    <h3 class="krl-material-title">{{ $material->material_title }}</h3>
                    <span class="krl-material-type">{{ $material->material_type ?: 'Materi' }}</span>
                </div>
                <p class="krl-material-time">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
                    </svg>
                    Terakhir dibuka {{ \Illuminate\Support\Carbon::parse($material->last_opened_at)->translatedFormat('d M Y, H:i') }}
                </p>
            </div>

            <div class="krl-material-count">
                <strong>{{ number_format($material->open_count) }}</strong>
                <span>kali dibuka</span>
            </div>
        </article>
    @empty
        <div class="krl-material-empty">
            <div class="krl-material-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
                </svg>
            </div>
            <strong>Belum ada materi yang dibuka</strong>
            <p>Aktivitas akan muncul setelah pengunjung membuka materi dalam grup ini.</p>
        </div>
    @endforelse
</div>
