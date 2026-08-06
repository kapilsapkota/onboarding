<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* ─────────────────────────────────────────
           GLOBAL RESET & BASE
        ───────────────────────────────────────── */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family:  Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        /* ─────────────────────────────────────────
           PAGE CONTAINERS  (A4 landscape 297×210mm)
        ───────────────────────────────────────── */
        .quote-page {
            width: 297mm;
            height: 210mm;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            background: #ffffff;
        }

        .quote-page:last-child {
            page-break-after: auto;
        }

        /* ─────────────────────────────────────────
           SHARED HELPERS
        ───────────────────────────────────────── */
        .page-pad { padding: 40px; }

        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #f97316;
            text-align: center;
            margin-bottom: 28px;
            margin-top: 0;
        }

        .orange { color: #f97316; }

        /* ─────────────────────────────────────────
           1. COVER
        ───────────────────────────────────────── */
        .cover-img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        .cover-gradient {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 60%;
            background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, transparent 100%);
        }

        .cover-panel {
            position: absolute;
            bottom: 0; right: 0;
            width: 340px;
            padding: 36px;
            text-align: center;
        }

        .cover-title {
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .cover-logo-box {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px;
            height: 160px;
            display: block;
            text-align: center;
            margin-bottom: 12px;
        }

        .cover-logo-box img {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
        }

        .cover-logo-name {
            font-size: 13px;
            font-weight: bold;
            color: #f97316;
        }

        .cover-meta {
            font-size: 10px;
            font-weight: 600;
            color: #ffffff;
        }

        /* ─────────────────────────────────────────
           2. PARTNERS
        ───────────────────────────────────────── */
        .partners-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .partner-cell {
            width: 33.33%;
            height: 90px;
            text-align: center;
            vertical-align: middle;
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px 8px;
        }

        .partner-cell img {
            max-width: 90%;
            max-height: 60px;
            object-fit: contain;
        }

        /* ─────────────────────────────────────────
           3. ROLLOUT
        ───────────────────────────────────────── */
        .stage-wrap {
            background: #f9fafb;
            border-radius: 12px;
            padding: 30px;
            margin-top: 4px;
        }

        .stage-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 16px 0;
        }

        .stage-col { width: 33.33%; vertical-align: top; }

        .stage-pill {
            color: #ffffff;
            text-align: center;
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .stage-list {
            list-style: none;
            padding: 0; margin: 0;
            font-size: 11px;
            color: #374151;
        }

        .stage-list li {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }

        .stage-check {
            display: inline-block;
            width: 14px; height: 14px;
            background: #2563eb;
            border-radius: 50%;
            color: #ffffff;
            font-size: 8px;
            text-align: center;
            line-height: 14px;
            margin-right: 6px;
            vertical-align: middle;
        }

        /* ─────────────────────────────────────────
           4. ITEM PAGES
        ───────────────────────────────────────── */
        /* FIX: explicit 210mm instead of 100% — DomPDF ignores % height */
        .item-table {
            width: 100%;
            height: 210mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .item-content-cell {
            width: 50%;
            vertical-align: top;
            padding: 48px 40px;
        }

        .item-image-cell {
            width: 50%;
            height: 210mm;
            padding: 0;
            vertical-align: top;
            background: white;

            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        .item-image-cell img {
            width: 100%;
            height: 210mm;
            display: block;
            object-fit: cover;
        }

        .item-title {
            font-size: 20px;
            font-weight: bold;
            color: #f97316;
            margin-bottom: 18px;
        }

        .item-scope-label {
            font-weight: bolder;
            color: #374151;
            margin-bottom: 10px;
        }


        .item-scope-list li { margin-bottom: 6px; }

        .item-price {
            font-weight: bolder;
            color: #111827;
            margin-top: 10px;
        }

        .item-note {
            font-size: 10px;
            color: #92400e;
            background: #fffbeb;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 10px;
        }

        /* ─────────────────────────────────────────
           5. BLEED IMAGES
        ───────────────────────────────────────── */
        .bleed-wrap {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
        }

        .bleed-img {
            position: absolute;
            top: 0; left: 0;
            min-width: 100%;
            min-height: 100%;
            width: 100%;
            height: 100%;
        }

        .terms-page {
            background: #ffffff;
            padding: 40px 48px;
        }

        .terms-table {
            width: 100%;
            border-collapse: collapse;
        }
        .terms-table thead {
            display: table-header-group;
        }

        .terms-thead-cell {
            padding-bottom: 8px;
        }

        .terms-heading {
            font-size: 20px;
            font-weight: bold;
            color: #f97316;
            margin: 20px 0 6px 0;
        }

        /* spacer row between thead and first term */
        .terms-spacer { height: 18px; }

        .term-td {
            vertical-align: top;
            padding-bottom: 18px;
        }

        .term-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .term-sub-title {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-top: 8px;
            margin-bottom: 3px;
        }

        .term-body {
            font-size: 11px;
            color: #4b5563;
            line-height: 1.55;
            margin: 2px 0;
        }

        .term-points {
            font-size: 11px;
            color: #4b5563;
            padding-left: 18px;
            margin: 4px 0 0 0;
            line-height: 1.6;
        }

        .term-footer-td {
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
        }

        .sig-page { padding: 40px; }

        .sig-badge {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 600;
            color: #15803d;
            margin-bottom: 28px;
        }

        .sig-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #f97316;
            white-space: nowrap;
        }

        .sig-line {
            border-bottom: 1px dotted #9ca3af;
            min-width: 140px;
            padding-bottom: 4px;
            display: inline-block;
            width: 100%;
        }

        .sig-value {
            font-size: 12px;
            color: #1f2937;
            display: inline-block;
            padding-bottom: 4px;
        }

        .sig-row {
            width: 100%;
            margin-bottom: 28px;
            border-collapse: collapse;
        }

        .sig-half {
            width: 50%;
            vertical-align: bottom;
            padding-right: 48px;
        }

        .sig-half:last-child { padding-right: 0; }

        .sig-box {
            border: 1px dotted #d1d5db;
            border-radius: 4px;
            height: 64px;
            padding: 8px;
            vertical-align: bottom;
        }

        .sig-box img {
            max-height: 48px;
            max-width: 100%;
            object-fit: contain;
        }

        .sig-disclaimer {
            border-top: 1px solid #f3f4f6;
            padding-top: 14px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            font-style: italic;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #f97316;
            line-height: 1.7;
        }
        .category-divider-page {
            position: relative;
            width: 100%;
            height: 100vh; /* full page height */
            overflow: hidden;
            page-break-after: always;
        }

        .category-divider-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* fills page without distortion */
        }

        .category-divider-name {
            position: absolute;
            left: 0;
            bottom: 80px; /* adjust vertical position */
            background: #f97316; /* orange banner */
            color: #fff;
            padding: 20px 45px;
            font-size: 42px;
            font-weight: 600;
            font-family: Arial, sans-serif;
            z-index: 2;
        }
    </style>
    <title>{{ $quote->quote_number }}</title>
</head>
<body>

<div class="quote-page">
    @if($coverSrc)
        <img src="{{ $coverSrc }}" class="cover-img" alt="Cover">
    @endif

    <div class="cover-gradient"></div>

    <div class="cover-panel">
        <div class="cover-title">
            {{ $quote->project_title ?: config('quote.default_project_title') }}
        </div>

        <div class="cover-logo-box">
            @if(!empty($clientLogoSrc))
                <img src="{{ $clientLogoSrc }}" alt="{{ $quote->client_name }}">
            @else
                <span class="cover-logo-name">{{ $quote->client_name }}</span>
            @endif
        </div>

        <div class="cover-meta">
            By {{ $quote->prepared_by ?? 'Ali Taufeek' }} &nbsp;{{ now()->format('d-m-y') }}
        </div>
    </div>
</div>

@if($partnersSrc)
    <div class="quote-page">
        <div class="bleed-wrap">
            <img src="{{ $partnersSrc }}" alt="Partners" class="bleed-img">
        </div>
    </div>
@endif

@if($threeStepRollOutSrc)
    <div class="quote-page">
        <div class="bleed-wrap">
            <img src="{{ $threeStepRollOutSrc }}" alt="Three Step" class="bleed-img">
        </div>
    </div>
@endif

{{--<div class="quote-page">--}}
{{--    <div style="padding: 30px 40px 40px 40px;">--}}
{{--        <div class="section-title">Our Partner Network</div>--}}

{{--        @php--}}
{{--            $chunks = $partners->chunk(3);--}}
{{--            $sizeMap = [0=>'60px',1=>'72px',2=>'56px',3=>'96px',4=>'80px',--}}
{{--                        5=>'40px',6=>'64px',7=>'40px',8=>'40px',9=>'80px'];--}}
{{--        @endphp--}}

{{--        <table class="partners-grid">--}}
{{--            @forelse($chunks as $chunkIndex => $row)--}}
{{--                <tr>--}}
{{--                    @foreach($row as $i => $partner)--}}
{{--                        @php $h = $sizeMap[$partners->search($partner)] ?? '50px'; @endphp--}}
{{--                        <td class="partner-cell">--}}
{{--                            @if($partner['src'])--}}
{{--                                <img src="{{ $partner['src'] }}"--}}
{{--                                     alt="{{ $partner['name'] }}"--}}
{{--                                     style="max-height:{{ $h }};">--}}
{{--                            @else--}}
{{--                                <span style="font-size:11px;color:#6b7280;">{{ $partner['name'] }}</span>--}}
{{--                            @endif--}}
{{--                        </td>--}}
{{--                    @endforeach--}}
{{--                    @for($pad = $row->count(); $pad < 3; $pad++)--}}
{{--                        <td class="partner-cell"></td>--}}
{{--                    @endfor--}}
{{--                </tr>--}}
{{--            @empty--}}
{{--                <tr>--}}
{{--                    <td colspan="3" style="text-align:center;color:#9ca3af;font-size:12px;">--}}
{{--                        No partner logos configured.--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            @endforelse--}}
{{--        </table>--}}
{{--    </div>--}}
{{--</div>--}}

{{--<div class="quote-page">--}}
{{--    <div class="page-pad">--}}
{{--        <div class="section-title">--}}
{{--            {{ $quote->overview_title ?? 'Our 3-Step Rollout Plan' }}--}}
{{--        </div>--}}

{{--        <div class="stage-wrap">--}}
{{--            <table class="stage-table">--}}
{{--                <tr>--}}
{{--                    @foreach($stageColumns as $i => $column)--}}
{{--                        <td class="stage-col">--}}
{{--                            <div class="stage-pill" style="background:{{ $stageAccents[$i % 3] }};">--}}
{{--                                {{ $column['title'] }}--}}
{{--                            </div>--}}
{{--                            <ul class="stage-list">--}}
{{--                                @foreach($column['items'] as $name)--}}
{{--                                    <li>--}}
{{--                                        <span class="stage-check">&#10003;</span>--}}
{{--                                        {{ $name }}--}}
{{--                                    </li>--}}
{{--                                @endforeach--}}
{{--                            </ul>--}}
{{--                        </td>--}}
{{--                    @endforeach--}}
{{--                </tr>--}}
{{--            </table>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}

@foreach($groupedItems as $key => $category)

    {{-- CATEGORY DIVIDER PAGE --}}
    @if($category && isset($category['image']))
        <div class="quote-page category-divider-page">
            <img
                src="{{ $category['image'] }}"
                alt="{{ $category['name'] ?? '' }}"
                class="category-divider-image"
            >

            <div class="category-divider-name">
                {{ $category['name'] ?? '' }}
            </div>
        </div>
    @endif

    {{-- ITEMS IN THIS CATEGORY --}}
    @if($category && isset($category['items']))
        @foreach($category['items'] as $item)
            <div class="quote-page">
                <table class="item-table">
                    <tr>
                        <td class="item-content-cell">
                            <div class="item-title">{{ $item->product_name }}</div>
                            <div class="item-scope-label">General Scope of Works</div>

                            @if($item->scope_of_works)
                                <div class="item-description">
                                    {!! nl2br(e(trim($item->scope_of_works))) !!}
                                </div>
                            @endif

                            <div class="item-price">
                                Total Price ${{ number_format($item->unit_price, 0) }} + GST @if($item->frequency_label) {{ $item->frequency_label }}@endif
                            </div>

                            @if($item->notes)
                                <div class="item-note">
                                    <strong>Note:</strong> {{ $item->notes }}
                                </div>
                            @endif
                        </td>

                        <td class="item-image-cell"
                            style="background-image: url('{{ $item->product_image_src ?? $defaultSrc }}');">
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif
@endforeach

{{--@foreach($items as $item)--}}
{{--    <div class="quote-page">--}}
{{--        <table class="item-table">--}}
{{--            <tr>--}}
{{--                <td class="item-content-cell">--}}
{{--                    <div class="item-title">{{ $item->product_name }}</div>--}}
{{--                    <div class="item-scope-label">General Scope of Works</div>--}}

{{--                    @if($item->scope_of_works)--}}
{{--                        <div class="item-description">--}}
{{--                            {!! nl2br(e(trim($item->scope_of_works))) !!}--}}
{{--                        </div>--}}
{{--                    @endif--}}

{{--                    <div class="item-price">--}}
{{--                        Total Price ${{ number_format($item->unit_price, 0) }} + GST {{ ucfirst($item->frequency) }}--}}
{{--                    </div>--}}
{{--                    @if($item->notes)--}}
{{--                        <div class="item-note">--}}
{{--                            <strong>Note:</strong> {{ $item->notes }}--}}
{{--                        </div>--}}
{{--                    @endif--}}
{{--                </td>--}}

{{--                <td class="item-image-cell">--}}
{{--                    @if($item->product_image_src)--}}
{{--                        <img src="{{ $item->product_image_src }}" alt="{{ $item->product_name }}">--}}
{{--                    @else--}}
{{--                        <img src="{{ $defaultSrc }}" alt="{{ $item->product_name }}">--}}
{{--                    @endif--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        </table>--}}
{{--    </div>--}}
{{--@endforeach--}}

{{-- Config / gallery full-bleed pages --}}
@foreach($configImages as $image)
    <div class="quote-page">
        <div class="bleed-wrap">
            <img src="{{ $image['src'] }}"
                 alt="{{ $image['placeholder'] }}"
                 class="bleed-img">
        </div>
    </div>
@endforeach

<div class="terms-page">
    <table class="terms-table">

        <thead>
        <tr>
            <td class="terms-thead-cell">
                <div class="terms-heading">Terms &amp; Conditions</div>
            </td>
        </tr>
        {{-- Spacer between header and first term on every page --}}
        <tr><td class="terms-spacer"></td></tr>
        </thead>

        <tbody>
        @foreach($termsAndConditions as $index => $term)
            <tr style="page-break-inside: avoid;">
                <td class="term-td">

                    <div class="term-title">{{ $index + 1 }}. {{ $term['title'] }}</div>

                    @if(!empty($term['content']))
                        <p class="term-body">{{ $term['content'] }}</p>
                    @endif

                    @if(!empty($term['subsections']))
                        @foreach($term['subsections'] as $sub)
                            <div class="term-sub-title">{{ $sub['title'] }}</div>
                            <p class="term-body">{{ $sub['content'] }}</p>
                        @endforeach
                    @endif

                    @if(!empty($term['points']))
                        <ul class="term-points">
                            @foreach($term['points'] as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if(!empty($term['footer']))
                        <p class="term-body" style="margin-top:6px;">{{ $term['footer'] }}</p>
                    @endif

                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
</div>

<div class="quote-page" style="page-break-before: always;">
    <div class="sig-page">

        @if($quote->signed_at)
            <div class="sig-badge">
                &#10003; &nbsp;Signed {{ $quote->signed_at->format('d M Y, h:i A') }}
            </div>
        @endif

        <table class="sig-row">
            <tr>
                <td style="vertical-align:bottom; padding-bottom:4px; white-space:nowrap; padding-right:12px;">
                    <span class="sig-label">Company / Business Name:</span>
                </td>
                <td style="vertical-align:bottom; width:100%;">
                    @if($quote->signed_company_name)
                        <span class="sig-value sig-line">{{ $quote->signed_company_name }}</span>
                    @else
                        <span class="sig-line">&nbsp;</span>
                    @endif
                </td>
            </tr>
        </table>

        <table class="sig-row">
            <tr>
                <td class="sig-half" style="vertical-align:bottom;">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="white-space:nowrap; padding-right:10px; padding-bottom:4px; vertical-align:bottom;">
                                <span class="sig-label">Authorised Person:</span>
                            </td>
                            <td style="vertical-align:bottom; width:100%;">
                                @if($quote->signed_name)
                                    <span class="sig-value sig-line">{{ $quote->signed_name }}</span>
                                @else
                                    <span class="sig-line">&nbsp;</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="sig-half" style="vertical-align:bottom;">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="white-space:nowrap; padding-right:10px; padding-bottom:4px; vertical-align:bottom;">
                                <span class="sig-label">Position:</span>
                            </td>
                            <td style="vertical-align:bottom; width:100%;">
                                @if($quote->signed_position)
                                    <span class="sig-value sig-line">{{ $quote->signed_position }}</span>
                                @else
                                    <span class="sig-line">&nbsp;</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="sig-row">
            <tr>
                <td class="sig-half" style="vertical-align:top;">
                    <div class="sig-label" style="margin-bottom:8px;">Signature:</div>
                    <div class="sig-box">
                        @if(isset($quote->signature))
                            <img src="{{ $quote->signature->signature_image }}" alt="Signature">
                        @endif
                    </div>
                </td>
                <td class="sig-half" style="vertical-align:bottom;">
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="white-space:nowrap; padding-right:10px; padding-bottom:4px; vertical-align:bottom;">
                                <span class="sig-label">Date:</span>
                            </td>
                            <td style="vertical-align:bottom; width:100%;">
                                @if($quote->signed_at)
                                    <span class="sig-value sig-line">
                                        {{ $quote->signed_at->format('d / m / Y') }}
                                    </span>
                                @else
                                    <span class="sig-line">&nbsp;</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="position:absolute; bottom:40px; left:40px; right:40px;">
            <div class="sig-disclaimer">
                The person whose name and signature appears above warrants that they are authorised to enter<br>
                into this agreement with {{ config('app.company_name', 'All in IT Solutions Pty Ltd') }}
                on behalf of the above company / business.
            </div>
        </div>

    </div>
</div>

@if($closingSrc)
    <div class="quote-page">
        <div class="bleed-wrap">
            <img src="{{ $closingSrc }}" alt="All in IT Solutions" class="bleed-img">
        </div>
    </div>
@endif

</body>
</html>
