@props([
    'icon' => 'bi-question-circle',
    'title' => '-',
    'value' => 0,
    'label' => null,

    // jika link diisi, card otomatis jadi clickable (dibungkus <a>)
    'link' => null,

    // currency | number
    'format' => 'currency',

    // true => tampil *** + icon mata
    'masked' => true,

    // enable counter animation
    'animate' => true,
])

@php
    $numericValue = (float) $value;
    $isPositive = $numericValue >= 0;

    $formatted = match ($format) {
        'number' => number_format($numericValue, 0, ',', '.'),
        default => number_format($numericValue, 0, ',', '.'),
    };

    // ID unik untuk target counter (biar aman walau loop)
    $uid = 'cs_' . substr(md5($title . '|' . $numericValue . '|' . ($label ?? '') . '|' . uniqid('', true)), 0, 10);
@endphp

@if ($link)
    <a href="{{ $link }}" class="text-decoration-none">
@endif

<div class="stat-card h-100 {{ $link ? 'stat-card--link' : '' }}" data-cardstat data-cardstat-id="{{ $uid }}"
    data-format="{{ $format }}" data-currency-prefix="{{ $format === 'currency' ? 'Rp ' : '' }}"
    data-value="{{ $numericValue }}" data-animate="{{ $animate ? '1' : '0' }}">
    <div class="stat-card__top">
        <div class="stat-card__icon">
            <i class="bi {{ $icon }}"></i>
        </div>

        @if ($masked)
            <button type="button" class="stat-card__mask-btn" aria-label="Tampilkan/Sembunyikan nilai"
                onclick="event.preventDefault(); event.stopPropagation(); toggleVisibility(this)">
                <i class="bi bi-eye"></i>
            </button>
        @endif
    </div>

    <div class="stat-card__title">
        {{ $title }}
    </div>

    <div class="stat-card__value {{ $isPositive ? 'is-positive' : 'is-negative' }}">
        @if ($masked)
            {{-- real value (hidden) --}}
            <span class="hidden-value" style="display:none;">
                {{ $formatted }}
            </span>

            {{-- masked placeholder --}}
            <span class="masked-value">***</span>

            {{-- animated visible number target (starts as 0, but only shown when unmasked) --}}
            <span id="{{ $uid }}" class="stat-card__number" style="display:none;"
                data-target="{{ $numericValue }}">0</span>
        @else
            {{-- directly show number and animate it --}}
            <span class="stat-card__prefix">{{ $format === 'currency' ? 'Rp ' : '' }}</span>
            <span id="{{ $uid }}" class="stat-card__number"
                data-target="{{ $numericValue }}">{{ $formatted }}</span>
        @endif
    </div>

    @if ($label)
        <div class="stat-card__meta">
            {{ $label }}
        </div>
    @endif
</div>

@if ($link)
    </a>
@endif

@once
    @push('styles')
        <style>
            /* ========= Stat Card (Classy/Elegant) ========= */
            .stat-card {
                position: relative;
                border: 1px solid rgba(0, 0, 0, .08);
                border-radius: 16px;
                background: #fff;
                padding: 14px 14px 12px 14px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
                transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
                overflow: hidden;
            }

            .stat-card::before {
                content: "";
                position: absolute;
                inset: 0;
                background: radial-gradient(600px 200px at 20% -10%, rgba(253, 109, 13, 0.1), transparent 60%);
                pointer-events: none;
            }

            .stat-card--link:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 34px rgba(0, 0, 0, .10);
                border-color: rgba(253, 129, 13, 0.22);
            }

            .stat-card__top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 6px;
                position: relative;
                z-index: 1;
            }

            .stat-card__icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(253, 69, 13, 0.08);
                border: 1px solid rgba(253, 109, 13, 0.12);
                color: #622200;
                flex: 0 0 auto;
            }

            .stat-card__icon i {
                font-size: 18px;
                line-height: 1;
            }

            .stat-card__mask-btn {
                border: 1px solid rgba(0, 0, 0, .10);
                background: rgba(255, 255, 255, .9);
                border-radius: 10px;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: rgba(0, 0, 0, .65);
                transition: background .15s ease, border-color .15s ease, transform .15s ease;
                position: relative;
                z-index: 2;
            }

            .stat-card__mask-btn:hover {
                background: #fff;
                border-color: rgba(0, 0, 0, .18);
                transform: translateY(-1px);
            }

            .stat-card__title {
                font-weight: 700;
                font-size: .95rem;
                color: rgba(0, 0, 0, .78);
                margin-bottom: 6px;
                position: relative;
                z-index: 1;
            }

            .stat-card__value {
                display: flex;
                align-items: baseline;
                gap: 6px;
                position: relative;
                z-index: 1;
            }

            .stat-card__prefix {
                font-weight: 700;
                color: rgba(0, 0, 0, .55);
                letter-spacing: .2px;
            }

            .stat-card__number {
                font-weight: 800;
                font-size: 1.35rem;
                letter-spacing: .2px;
                color: rgba(0, 0, 0, .86);
            }

            .stat-card__value.is-negative .stat-card__number {
                color: #b02a37;
            }

            .stat-card__value.is-positive .stat-card__number {
                color: #146c43;
            }

            .stat-card__meta {
                margin-top: 6px;
                font-size: .82rem;
                color: rgba(0, 0, 0, .50);
                position: relative;
                z-index: 1;
            }

            .stat-card a,
            .stat-card a:hover {
                color: inherit;
            }
        </style>
    @endpush
@endonce
