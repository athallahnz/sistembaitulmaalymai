@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center">
        <div class="card shadow-lg mx-auto" style="width: 350px; border-radius: 15px;">
            <div class="card-body text-center">
                <div class="card-title">
                    <h3 class="mb-3">Settings</h3>
                </div>
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ route('admin.sidebar_setting.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- LOGO --}}
                    <div class="mb-3 text-start">
                        <label for="logo" class="form-label">Logo Sidebar</label>
                        @if ($setting->logo_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="Logo"
                                    style="height: 80px;">
                            </div>
                        @endif
                        <input type="file" name="logo" class="form-control">
                    </div>

                    {{-- BG LOGIN --}}
                    <div class="mb-3 text-start">
                        <label for="background_login" class="form-label">Gambar Background Login</label>
                        <input type="file" class="form-control" name="background_login" id="background_login">
                        @if ($setting->background_login)
                            <img src="{{ asset('storage/' . $setting->background_login) }}" alt="Preview"
                                class="img-fluid mt-2" style="max-height: 120px;">
                        @endif
                    </div>

                    {{-- TITLE --}}
                    <div class="mb-3 text-start">
                        <label for="title" class="form-label">Judul Utama</label>
                        <input type="text" name="title" class="form-control"
                            value="{{ old('title', $setting->title) }}" required>
                    </div>

                    {{-- SUBTITLE --}}
                    <div class="mb-3 text-start">
                        <label for="subtitle" class="form-label">Sub Judul</label>
                        <input type="text" name="subtitle" class="form-control"
                            value="{{ old('subtitle', $setting->subtitle) }}">
                    </div>

                    <div class="mb-3 text-start">
                        <label for="title" class="form-label">Primary Colour</label>
                        <input type="color" name="background_color" id="background_color"
                            class="form-control form-control-color" value="{{ $setting->background_color ?? '#7A3E16' }}">
                    </div>
                    <div class="mb-3 text-start">
                        <label for="title" class="form-label">Pallete Colour</label>
                        <div class="row mb-3">
                            <div class="col">
                                <input type="color" name="cta_background_color" id="cta_background_color"
                                    class="form-control form-control-color"
                                    value="{{ $setting->cta_background_color ?? '#8D4720' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="link_color" id="link_color"
                                    class="form-control form-control-color"
                                    value="{{ $setting->link_color ?? '#e9ecef80' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="link_hover_color" class="form-control form-control-color"
                                    value="{{ $setting->link_hover_color ?? '#e9ecefBF' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="link_active_color" class="form-control form-control-color"
                                    value="{{ $setting->link_active_color ?? '#e9ecef' }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <input type="color" name="link_active_border_color"
                                    class="form-control form-control-color"
                                    value="{{ $setting->link_active_border_color ?? '#f2c89d' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="cta_button_color" class="form-control form-control-color"
                                    value="{{ $setting->cta_button_color ?? '#81431E' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="cta_button_hover_color" class="form-control form-control-color"
                                    value="{{ $setting->cta_button_hover_color ?? '#984F23' }}">
                            </div>
                            <div class="col">
                                <input type="color" name="cta_button_text_color" class="form-control form-control-color"
                                    value="{{ $setting->cta_button_text_color ?? '#fff5e1' }}">
                            </div>
                        </div>

                        {{-- BUTTON --}}
                        <div class="mb-3 text-start d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-secondary" id="resetColors">Reset Default</button>
                        </div>
                </form>

            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinycolor/1.4.2/tinycolor.min.js"></script>
    <script>
        const backgroundInput = document.getElementById('background_color');
        const ctaInput = document.getElementById('cta_background_color');
        const linkInput = document.getElementById('link_color');

        const sidebar = document.querySelector('.sidebar');
        const sidebarContent = document.querySelector('.sidebar-content');
        const ctaBox = document.querySelector('.sidebar-cta-content-box');
        const ctaButton = document.querySelector('.sidebar-cta-content-box .btn');

        const ctaButtonColorInput = document.querySelector('[name="cta_button_color"]');
        const ctaButtonHoverColorInput = document.querySelector('[name="cta_button_hover_color"]');
        const ctaButtonTextColorInput = document.querySelector('[name="cta_button_text_color"]');

        const linkHoverInput = document.querySelector('[name="link_hover_color"]');
        const linkActiveInput = document.querySelector('[name="link_active_color"]');
        const linkActiveBorderInput = document.querySelector('[name="link_active_border_color"]');

        const sidebarLinks = document.querySelectorAll('.sidebar-link, .sidebar-link i, .sidebar-link svg');
        const sidebarItems = document.querySelectorAll('.sidebar-item');

        function generatePalette(baseHex) {
            const base = tinycolor(baseHex);
            return {
                link: base.clone().lighten(90).toHexString(),
                hover: base.clone().lighten(50).toHexString(),
                active: tinycolor.mostReadable(base, ['#ffffff', '#000000']).toHexString(),
                border: base.clone().saturate(20).lighten(40).toHexString(),
                cta: base.clone().darken(5).toHexString(),
                ctaHover: base.clone().darken(15).toHexString(),
                ctaText: tinycolor.mostReadable(base, ['#ffffff', '#000000']).toHexString()
            };
        }

        function applySidebarColors() {
            sidebar.style.backgroundColor = backgroundInput.value;
            sidebarContent.style.backgroundColor = backgroundInput.value;
            ctaBox.style.backgroundColor = ctaInput.value;
            ctaBox.style.color = linkInput.value;

            sidebarItems.forEach(item => {
                item.style.backgroundColor = backgroundInput.value;
            });

            sidebarLinks.forEach(link => {
                link.style.color = linkInput.value;
            });

            if (ctaButton) {
                ctaButton.style.backgroundColor = ctaButtonColorInput.value;
                ctaButton.style.color = ctaButtonTextColorInput.value;

                ctaButton.onmouseover = () => {
                    ctaButton.style.backgroundColor = ctaButtonHoverColorInput.value;
                };
                ctaButton.onmouseout = () => {
                    ctaButton.style.backgroundColor = ctaButtonColorInput.value;
                };
            }
        }

        // Auto update semua turunan warna saat background diubah
        backgroundInput.addEventListener('input', () => {
            const palette = generatePalette(backgroundInput.value);
            linkInput.value = palette.link;
            linkHoverInput.value = palette.hover;
            linkActiveInput.value = palette.active;
            linkActiveBorderInput.value = palette.border;
            ctaButtonColorInput.value = palette.cta;
            ctaButtonHoverColorInput.value = palette.ctaHover;
            ctaButtonTextColorInput.value = palette.ctaText;
            ctaInput.value = palette.cta;
            applySidebarColors();
        });

        // Reset ke default
        document.getElementById('resetColors').addEventListener('click', () => {
            backgroundInput.value = '#793715';
            const palette = generatePalette('#793715');

            linkInput.value = palette.link;
            linkHoverInput.value = palette.hover;
            linkActiveInput.value = palette.active;
            linkActiveBorderInput.value = palette.border;
            ctaButtonColorInput.value = palette.cta;
            ctaButtonHoverColorInput.value = palette.ctaHover;
            ctaButtonTextColorInput.value = palette.ctaText;
            ctaInput.value = palette.cta;

            applySidebarColors();
        });

        // Apply langsung saat load
        applySidebarColors();
    </script>
@endpush
