@php
    $panelId = filament()->getCurrentOrDefaultPanel()?->getId();
    $isServicePanel = $panelId === 'layanankkprl';
    $portalName = $isServicePanel ? 'Kawan Ruang Laut' : 'Data KKPRL';
    $portalDescription = $isServicePanel
        ? 'Portal layanan konsultasi dan pengelolaan dokumen KKPRL.'
        : 'Ruang kerja data, referensi, dan administrasi KKPRL.';
@endphp

<div class="kkprl-auth-root">
<style>
    .kkprl-auth {
        min-height: 100vh;
        background:
            linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(239, 246, 255, 0.96)),
            url("{{ asset('img/kop_surat_lprlsorong.png') }}");
        background-size: 980px auto;
        background-position: center;
        color: #0f172a;
        font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;
        overflow-x: hidden;
    }

    .kkprl-auth,
    .kkprl-auth * {
        box-sizing: border-box;
    }

    .kkprl-auth-root,
    .fi-body:has(.kkprl-auth) {
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
    }

    html.fi:has(.kkprl-auth),
    body.fi-body:has(.kkprl-auth) {
        width: 100%;
        min-width: 0 !important;
    }

    .kkprl-auth__grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(360px, 440px);
        min-height: 100vh;
        width: 100%;
        min-width: 0;
    }

    .kkprl-auth__identity {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 48px;
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        min-width: 0;
    }

    .kkprl-auth__brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .kkprl-auth__logo {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    }

    .kkprl-auth__agency {
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .kkprl-auth__name {
        color: #0f172a;
        font-size: 20px;
        font-weight: 800;
    }

    .kkprl-auth__hero {
        max-width: 720px;
        margin-top: 64px;
    }

    .kkprl-auth__eyebrow {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 0 12px;
        border: 1px solid rgba(0, 87, 255, 0.18);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.64);
        color: #0057ff;
        font-size: 13px;
        font-weight: 800;
    }

    .kkprl-auth__title {
        max-width: 640px;
        margin-top: 20px;
        font-size: 52px;
        line-height: 1.05;
        font-weight: 850;
        color: #0f172a;
        overflow-wrap: break-word;
    }

    .kkprl-auth__description {
        max-width: 560px;
        margin-top: 20px;
        color: #334155;
        font-size: 17px;
        line-height: 1.7;
        overflow-wrap: break-word;
    }

    .kkprl-auth__status {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        max-width: 680px;
        margin-top: 34px;
    }

    .kkprl-auth__status-item {
        min-height: 86px;
        padding: 16px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.72);
    }

    .kkprl-auth__status-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .kkprl-auth__status-value {
        margin-top: 6px;
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
    }

    .kkprl-auth__footer {
        color: #64748b;
        font-size: 13px;
    }

    .kkprl-auth__form-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px;
        background: rgba(255, 255, 255, 0.76);
        backdrop-filter: blur(18px);
        min-width: 0;
    }

    .kkprl-auth__panel {
        width: 100%;
        max-width: 420px;
        padding: 28px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        min-width: 0;
    }

    .kkprl-auth__panel-title {
        color: #0f172a;
        font-size: 26px;
        line-height: 1.2;
        font-weight: 850;
    }

    .kkprl-auth__panel-copy {
        margin-top: 8px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .kkprl-auth__form {
        margin-top: 24px;
    }

    .kkprl-auth .fi-form,
    .kkprl-auth .fi-sc,
    .kkprl-auth .fi-sc-component,
    .kkprl-auth .fi-sc-form {
        gap: 16px;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .kkprl-auth .fi-grid,
    .kkprl-auth .fi-grid-col,
    .kkprl-auth .fi-fo-field,
    .kkprl-auth .fi-fo-field-content-col,
    .kkprl-auth .fi-input-wrp,
    .kkprl-auth .fi-input-wrp-content-ctn,
    .kkprl-auth .fi-ac {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .kkprl-auth .fi-fo-field-label,
    .kkprl-auth .fi-fo-field-label-content {
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .kkprl-auth .fi-input-wrp {
        min-height: 46px;
        border-radius: 8px;
        background: #f8fafc;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.12);
        display: flex;
        align-items: center;
    }

    .kkprl-auth .fi-input-wrp-content-ctn {
        display: flex;
        align-items: center;
        flex: 1 1 auto;
    }

    .kkprl-auth .fi-input-wrp-suffix {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
        padding-inline-end: 8px;
    }

    .kkprl-auth .fi-input {
        width: 100%;
        min-height: 46px;
        border: 0;
        outline: 0;
        background: transparent;
        color: #0f172a;
        box-shadow: none;
    }

    .kkprl-auth .fi-input:focus {
        outline: 0;
        box-shadow: none;
    }

    .kkprl-auth .fi-input-wrp:focus-within {
        background: #ffffff;
        box-shadow: inset 0 0 0 2px #0057ff;
    }

    .kkprl-auth .fi-checkbox-input {
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }

    .kkprl-auth .fi-btn {
        display: inline-flex;
        width: 100%;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 8px;
        background: #0057ff;
        color: #ffffff;
        font-weight: 800;
        box-shadow: 0 12px 28px rgba(0, 87, 255, 0.22);
    }

    .kkprl-auth .fi-btn:hover {
        background: #0049d8;
    }

    .kkprl-auth .fi-btn .fi-icon {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
    }

    .kkprl-auth .fi-icon-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .kkprl-auth .fi-ac {
        margin-top: 4px;
    }

    @media (max-width: 900px) {
        .kkprl-auth {
            background-size: 760px auto;
            background-position: top center;
        }

        .kkprl-auth__grid {
            grid-template-columns: 1fr;
        }

        .kkprl-auth__identity {
            padding: 28px 24px 0;
            border-right: 0;
        }

        .kkprl-auth__hero {
            margin-top: 32px;
        }

        .kkprl-auth__title {
            font-size: 31px;
            line-height: 1.1;
        }

        .kkprl-auth__description {
            font-size: 15px;
        }

        .kkprl-auth__status,
        .kkprl-auth__footer {
            display: none;
        }

        .kkprl-auth__form-wrap {
            align-items: flex-start;
            padding: 24px;
            background: transparent;
            backdrop-filter: none;
        }

        .kkprl-auth__panel {
            width: 100%;
            max-width: none;
            padding: 28px;
        }
    }

    @media (max-width: 520px) {
        .kkprl-auth-root,
        .kkprl-auth,
        .kkprl-auth__grid,
        .kkprl-auth__identity,
        .kkprl-auth__form-wrap {
            width: 100%;
            max-width: 100%;
        }

        .kkprl-auth__identity {
            padding-inline: 24px;
        }

        .kkprl-auth__name {
            font-size: 18px;
        }

        .kkprl-auth__title {
            font-size: 29px;
            max-width: calc(100vw - 48px);
        }

        .kkprl-auth__form-wrap {
            padding: 18px 12px;
            justify-content: flex-start;
        }

        .kkprl-auth__panel {
            width: min(330px, calc(100vw - 24px)) !important;
            max-width: min(330px, calc(100vw - 24px)) !important;
            padding: 24px;
        }

        .kkprl-auth__hero,
        .kkprl-auth__description {
            max-width: 330px;
        }

        .kkprl-auth .fi-input-wrp,
        .kkprl-auth .fi-btn {
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<section class="kkprl-auth">
    <div class="kkprl-auth__grid">
        <div class="kkprl-auth__identity">
            <div>
                <div class="kkprl-auth__brand">
                    <img src="{{ asset('img/logokkp.jpg') }}" alt="KKP" class="kkprl-auth__logo">
                    <div>
                        <div class="kkprl-auth__agency">LPSPL Sorong</div>
                        <div class="kkprl-auth__name">{{ $portalName }}</div>
                    </div>
                </div>

                <div class="kkprl-auth__hero">
                    <div class="kkprl-auth__eyebrow">Akses internal</div>
                    <h1 class="kkprl-auth__title">Ruang kerja aman untuk layanan ruang laut.</h1>
                    <p class="kkprl-auth__description">{{ $portalDescription }}</p>

                    <div class="kkprl-auth__status" aria-label="Status keamanan">
                        <div class="kkprl-auth__status-item">
                            <div class="kkprl-auth__status-label">Autentikasi</div>
                            <div class="kkprl-auth__status-value">API terhubung</div>
                        </div>
                        <div class="kkprl-auth__status-item">
                            <div class="kkprl-auth__status-label">Verifikasi</div>
                            <div class="kkprl-auth__status-value">Captcha aktif</div>
                        </div>
                        <div class="kkprl-auth__status-item">
                            <div class="kkprl-auth__status-label">Data sensitif</div>
                            <div class="kkprl-auth__status-value">Terenkripsi</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kkprl-auth__footer">
                Kementerian Kelautan dan Perikanan Republik Indonesia
            </div>
        </div>

        <div class="kkprl-auth__form-wrap">
            <div class="kkprl-auth__panel">
                <h2 class="kkprl-auth__panel-title">Masuk ke portal</h2>
                <p class="kkprl-auth__panel-copy">Gunakan akun resmi yang telah terhubung dengan sistem Timur Bersinar.</p>

                <div class="kkprl-auth__form">
                    {{ $this->content }}
                </div>
            </div>
        </div>
    </div>
</section>
</div>
