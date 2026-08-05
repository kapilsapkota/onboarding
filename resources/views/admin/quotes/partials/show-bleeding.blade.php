<x-app-layout>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
        }
        .quote-page {
            width: 100%;
            max-width: 297mm;
            aspect-ratio: 297 / 210;
            contain: strict;
            position: relative;
            background: #ffffff;
            display: block;
        }

        /* terms page flows freely */
        .quote-page--flow {
            aspect-ratio: unset;
            contain: unset;
            height: auto;
            overflow: visible;
        }

        @media print {
            @page { size: A4 landscape; margin: 0; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            nav, header, .print-hidden { display: none !important; }
            body { background: white !important; margin: 0; padding: 0; }

            .quote-page {
                width: 297mm;
                height: 210mm;
                max-width: 297mm;
                aspect-ratio: unset;
                contain: strict;
                position: relative;
                background: #ffffff;
                display: block;
                page-break-after: always;
                break-after: page;
                margin: 0;
                padding: 0;
            }

            .quote-page--flow {
                height: auto !important;
                contain: unset !important;
                overflow: visible !important;
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .term-section { break-inside: avoid !important; }
            .meta-strip, .print-hidden { display: none !important; }
        }

        /* ─── Cover ─── */
        .cover-gradient {
            position:absolute; bottom:0; left:0; right:0; height:60%;
            background: linear-gradient(to top, rgba(0,0,0,0.65) 0%, transparent 100%);
        }
        .cover-panel {
            position:absolute; bottom:0; right:0;
            width:340px; padding:36px; text-align:center;
        }
        .cover-title { font-size:22px; font-weight:bold; color:#fff; margin-bottom:16px; line-height:1.3; }
        .cover-logo-box {
            background:#fff; border-radius:14px; padding:20px;
            height:160px; display:flex; align-items:center; justify-content:center;
            margin-bottom:12px; box-shadow:0 4px 12px rgba(0,0,0,.15);
        }
        .cover-logo-box img { max-width:100%; max-height:120px; object-fit:contain; }
        .cover-logo-name { font-size:13px; font-weight:bold; color:#f97316; }
        .cover-meta { font-size:10px; font-weight:600; color:#fff; }

        /* ─── Bleed (full-page images) ─── */
        .bleed-wrap { position:relative; width:100%; height:100%; overflow:hidden; }
        .bleed-img  { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; }

        /* ─── Category divider ─── */
        .category-divider-image { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
        .category-divider-name {
            position:absolute; left:0; bottom:10%;
            background:#f97316; color:#fff;
            padding:20px 45px; font-size:42px; font-weight:600;
            font-family:Arial,sans-serif; z-index:2;
        }

        /* ─── Stage / Rollout ─── */
        .page-pad { padding:40px; height:100%; box-sizing:border-box; }
        .section-title { font-size:22px; font-weight:bold; color:#f97316; text-align:center; margin:0 0 28px 0; }
        .stage-wrap { background:#f9fafb; border-radius:12px; padding:30px; }
        .stage-table { width:100%; border-collapse:separate; border-spacing:16px 0; }
        .stage-col { width:33.33%; vertical-align:top; }
        .stage-pill {
            color:#fff; text-align:center; border-radius:20px;
            padding:8px 16px; font-weight:700; font-size:12px; margin-bottom:18px;
        }
        .stage-list { list-style:none; padding:0; margin:0; font-size:11px; color:#374151; }
        .stage-list li { margin-bottom:10px; }
        .stage-check {
            display:inline-block; width:14px; height:14px; background:#2563eb;
            border-radius:50%; color:#fff; font-size:8px; text-align:center;
            line-height:14px; margin-right:6px; vertical-align:middle;
        }

        /* ─── Item pages ─── */
        .item-table { width:100%; height:100%; border-collapse:collapse; table-layout:fixed; }
        .item-content-cell { width:50%; vertical-align:top; padding:48px 40px; }
        .item-image-cell {
            width:50%; padding:0; vertical-align:top;
            background-size:cover; background-position:center; background-repeat:no-repeat;
        }
        .item-title { font-size:20px; font-weight:bold; color:#f97316; margin-bottom:18px; }
        .item-scope-label { font-size:12px; font-weight:700; color:#374151; margin-bottom:10px; }
        .item-description { font-size:12px; color:#374151; line-height:1.6; margin-bottom:24px; white-space:pre-line; }
        .item-price { font-size:14px; font-weight:bold; color:#111827; }
        .item-note {
            font-size:10px; color:#92400e; background:#fffbeb;
            border-radius:4px; padding:8px 12px; margin-top:10px;
        }

        /* ─── Terms ─── */
        .terms-page { background:#fff; padding:40px 48px; }
        .terms-heading { font-size:20px; font-weight:bold; color:#f97316; margin:0 0 18px 0; }
        .term-title { font-size:13px; font-weight:bold; color:#1f2937; margin-bottom:4px; }
        .term-sub-title { font-size:12px; font-weight:700; color:#374151; margin:8px 0 3px 0; }
        .term-body { font-size:11px; color:#4b5563; line-height:1.55; margin:2px 0; }
        .term-points { font-size:11px; color:#4b5563; padding-left:18px; margin:4px 0 0 0; line-height:1.6; }
        .term-section { margin-bottom:18px; }

        /* ─── Signature ─── */
        .sig-page { padding:40px; height:100%; box-sizing:border-box; display:flex; flex-direction:column; }
        .sig-badge {
            display:inline-block; background:#f0fdf4; border:1px solid #bbf7d0;
            border-radius:20px; padding:5px 14px; font-size:11px;
            font-weight:600; color:#15803d; margin-bottom:28px; align-self:flex-start;
        }
        .sig-label { font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:1.5px; color:#f97316; white-space:nowrap; }
        .sig-line { border-bottom:1px dotted #9ca3af; padding-bottom:4px; display:inline-block; width:100%; }
        .sig-value { font-size:12px; color:#1f2937; display:inline-block; padding-bottom:4px; }
        .sig-row { width:100%; margin-bottom:28px; border-collapse:collapse; }
        .sig-half { width:50%; vertical-align:bottom; padding-right:48px; }
        .sig-half:last-child { padding-right:0; }
        .sig-box { border:1px dotted #d1d5db; border-radius:4px; height:64px; padding:8px; vertical-align:bottom; }
        .sig-box img { max-height:48px; max-width:100%; object-fit:contain; }
        .sig-disclaimer {
            border-top:1px solid #f3f4f6; padding-top:14px; text-align:center;
            font-size:9px; font-weight:700; font-style:italic;
            text-transform:uppercase; letter-spacing:0.5px; color:#f97316; line-height:1.7;
            margin-top:auto;
        }
    </style>

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
                    {{ $quote->quote_number }}
                </h2>
                @php $badge = $quote->status_badge; @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                    @csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()"
                            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        @foreach(['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','rejected'=>'Rejected'] as $val=>$lbl)
                            <option value="{{ $val }}" @selected($quote->status===$val)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </form>

                <form method="POST" action="{{ route('admin.quotes.send', $quote) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Send Quote
                    </button>
                </form>

                <a href="{{ route('admin.quotes.edit', $quote) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </a>

                <a href="{{ route('admin.quotes.pdf', $quote) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15h6M9 11h6M12 3v6h6"/></svg>
                    PDF
                </a>

                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>

                <a href="{{ route('admin.quotes.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">← Quotes</a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="print-hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @php
        $termsAndConditions = config('quote.default_terms');
        $stageColumns       = config('quote.stage_columns');
        $stageAccents       = ['#f59e0b','#f97316','#c2410c'];
    @endphp

        {{-- ════════════════════════════════════════
             1. COVER
        ════════════════════════════════════════ --}}
            <section class="quote-page"
                     style="background-image: url('{{ asset('images/img.png') }}');
                background-size: cover;
                background-position: center;">

                <div class="cover-gradient"></div>

                <div class="cover-panel">
                    <div class="cover-title">{{ $quote->project_title ?: config('quote.default_project_title') }}</div>
                    <div class="cover-logo-box">
                        @if($quote->logo_url)
                            <img src="{{ asset('storage/'.$quote->logo_url) }}" alt="{{ $quote->client_name }}"
                                 onerror="this.outerHTML='<span class=\'cover-logo-name\'>{{ $quote->client_name }}</span>'">
                        @else
                            <span class="cover-logo-name">{{ $quote->client_name }}</span>
                        @endif
                    </div>
                    <div class="cover-meta">By {{ $quote->prepared_by ?? 'Ali Taufeek' }} &nbsp; {{ now()->format('d-m-y') }}</div>
                </div>

            </section>

        {{-- ════════════════════════════════════════
             2. PARTNERS  (full-bleed)
        ════════════════════════════════════════ --}}
        @if($partnersSrc ?? false)
            <section class="quote-page">
                <div class="bleed-wrap">
                    <img src="{{ $partnersSrc }}" alt="Partners" class="bleed-img">
                </div>
            </section>
        @endif

        {{-- ════════════════════════════════════════
             3. THREE-STEP ROLLOUT  (full-bleed)
        ════════════════════════════════════════ --}}
        @if($threeStepRollOutSrc ?? false)
            <section class="quote-page">
                <div class="bleed-wrap">
                    <img src="{{ $threeStepRollOutSrc }}" alt="Three Step Rollout" class="bleed-img">
                </div>
            </section>
        @endif

        {{-- ════════════════════════════════════════
             4. ROLLOUT COLUMN LAYOUT
        ════════════════════════════════════════ --}}
{{--        <section class="quote-page">--}}
{{--            <div class="page-pad">--}}
{{--                <div class="section-title">{{ $quote->overview_title ?? 'Our 3-Step Rollout Plan' }}</div>--}}
{{--                <div class="stage-wrap">--}}
{{--                    <table class="stage-table">--}}
{{--                        <tr>--}}
{{--                            @foreach($stageColumns as $i => $column)--}}
{{--                                <td class="stage-col">--}}
{{--                                    <div class="stage-pill" style="background:{{ $stageAccents[$i % 3] }};">--}}
{{--                                        {{ $column['title'] }}--}}
{{--                                    </div>--}}
{{--                                    <ul class="stage-list">--}}
{{--                                        @foreach($column['items'] as $name)--}}
{{--                                            <li><span class="stage-check">&#10003;</span>{{ $name }}</li>--}}
{{--                                        @endforeach--}}
{{--                                    </ul>--}}
{{--                                </td>--}}
{{--                            @endforeach--}}
{{--                        </tr>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </section>--}}

        {{-- ════════════════════════════════════════
             5. GROUPED ITEMS  (category divider + items)
        ════════════════════════════════════════ --}}
        @foreach($groupedItems ?? [] as $category)

            @if($category && isset($category['image']))
                <section class="quote-page">
                    <img src="{{ $category['image'] }}" alt="{{ $category['name'] ?? '' }}" class="category-divider-image">
                    <div class="category-divider-name">{{ $category['name'] ?? '' }}</div>
                </section>
            @endif

            @foreach($category['items'] ?? [] as $item)
                <section class="quote-page">
                    <table class="item-table">
                        <tr>
                            <td class="item-content-cell">
                                <div class="item-title">{{ $item->product_name }}</div>
                                <div class="item-scope-label">General Scope of Works</div>

                                @if($item->scope_of_works)
                                    <div class="item-description">{{ trim($item->scope_of_works) }}</div>
                                @elseif($item->scope_list)
                                    <div class="item-description">@foreach($item->scope_list as $s)- {{ $s }}<br>@endforeach</div>
                                @endif

                                <div class="item-price">
                                    Total Price ${{ number_format($item->unit_price, 0) }} + GST
                                    @if(!empty($item->frequency)) {{ ucfirst($item->frequency) }} @endif
                                </div>
                                @if($item->notes)
                                    <div class="item-note"><strong>Note:</strong> {{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="item-image-cell"
                                style="background-image:url('{{ $item->product_image_src ?? ($item->product->image_url ? asset('storage/'.$item->product->image_url) : asset('images/default.png')) }}');">
                            </td>
                        </tr>
                    </table>
                </section>
            @endforeach

        @endforeach

        {{-- Fallback: no $groupedItems --}}
        @if(empty($groupedItems))
            @foreach($quote->items as $item)
                <section class="quote-page">
                    <table class="item-table">
                        <tr>
                            <td class="item-content-cell">
                                <div class="item-title">{{ $item->product_name }}</div>
                                <div class="item-scope-label">General Scope of Works</div>

                                @if($item->scope_of_works ?? false)
                                    <div class="item-description">{{ trim($item->scope_of_works) }}</div>
                                @elseif($item->scope_list)
                                    <div class="item-description">@foreach($item->scope_list as $s)- {{ $s }}<br>@endforeach</div>
                                @endif

                                <div class="item-price">
                                    Total Price ${{ number_format($item->unit_price, 0) }} + GST
                                    @if(!empty($item->frequency)) {{ ucfirst($item->frequency) }} @endif
                                </div>
                                @if($item->notes)
                                    <div class="item-note"><strong>Note:</strong> {{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="item-image-cell"
                                style="background-image:url('{{ $item->product->image_url ? asset('storage/'.$item->product->image_url) : asset('images/default.png') }}');">
                            </td>
                        </tr>
                    </table>
                </section>
            @endforeach
        @endif

        {{-- ════════════════════════════════════════
             6. CONFIG / GALLERY FULL-BLEED PAGES
        ════════════════════════════════════════ --}}
        @foreach(config('quote.images') ?? [] as $image)
            <section class="quote-page">
                <div class="bleed-wrap">
                    <img src="{{ asset($image['image']) }}" alt="{{ $image['placeholder'] }}" class="bleed-img"
                         onerror="this.style.display='none'">
                </div>
            </section>
        @endforeach

        {{-- ════════════════════════════════════════
             7. TERMS & CONDITIONS  (flowing)
        ════════════════════════════════════════ --}}
        <section class="quote-page quote-page--flow">
            <div class="terms-page">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                    <tr><td><div class="terms-heading">Terms &amp; Conditions</div></td></tr>
                    <tr><td style="height:10px;"></td></tr>
                    </thead>
                    <tbody>
                    @foreach($termsAndConditions as $index => $term)
                        <tr style="page-break-inside:avoid;">
                            <td>
                                <div class="term-section">
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
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ════════════════════════════════════════
             8. SIGNATURE PAGE
        ════════════════════════════════════════ --}}
        <section class="quote-page">
            <div class="sig-page">

                @if($quote->signed_at)
                    <div class="sig-badge">&#10003; &nbsp;Signed {{ $quote->signed_at->format('d M Y, h:i A') }}</div>
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
                        <td class="sig-half">
                            <table style="width:100%;border-collapse:collapse;">
                                <tr>
                                    <td style="white-space:nowrap;padding-right:10px;padding-bottom:4px;vertical-align:bottom;">
                                        <span class="sig-label">Authorised Person:</span>
                                    </td>
                                    <td style="vertical-align:bottom;width:100%;">
                                        @if($quote->signed_name)
                                            <span class="sig-value sig-line">{{ $quote->signed_name }}</span>
                                        @else
                                            <span class="sig-line">&nbsp;</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="sig-half">
                            <table style="width:100%;border-collapse:collapse;">
                                <tr>
                                    <td style="white-space:nowrap;padding-right:10px;padding-bottom:4px;vertical-align:bottom;">
                                        <span class="sig-label">Position:</span>
                                    </td>
                                    <td style="vertical-align:bottom;width:100%;">
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
                                    <td style="white-space:nowrap;padding-right:10px;padding-bottom:4px;vertical-align:bottom;">
                                        <span class="sig-label">Date:</span>
                                    </td>
                                    <td style="vertical-align:bottom;width:100%;">
                                        @if($quote->signed_at)
                                            <span class="sig-value sig-line">{{ $quote->signed_at->format('d / m / Y') }}</span>
                                        @else
                                            <span class="sig-line">&nbsp;</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="sig-disclaimer">
                    The person whose name and signature appears above warrants that they are authorised to enter<br>
                    into this agreement with {{ config('app.company_name', 'All in IT Solutions Pty Ltd') }} on behalf of the above company / business.
                </div>

            </div>
        </section>

        {{-- ════════════════════════════════════════
             9. CLOSING PAGE
        ════════════════════════════════════════ --}}
        <section class="quote-page">
            <div class="bleed-wrap">
                <img src="{{ $closingSrc ?? asset('images/media/image67.jpg') }}" alt="All in IT Solutions" class="bleed-img"
                     onerror="this.style.display='none'">
            </div>
        </section>

        {{-- ════════════════════════════════════════
             META STRIP  (screen only)
        ════════════════════════════════════════ --}}
        <div class="meta-strip print-hidden" style="margin-top:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
            <div style="background:#fff;border-radius:8px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);text-align:center;">
                <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Created</div>
                <div style="font-size:13px;font-weight:500;color:#374151;">{{ $quote->created_at->format('d M Y') }}</div>
            </div>
            <div style="background:#fff;border-radius:8px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);text-align:center;">
                <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Last Updated</div>
                <div style="font-size:13px;font-weight:500;color:#374151;">{{ $quote->updated_at->diffForHumans() }}</div>
            </div>
            <div style="background:#fff;border-radius:8px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);text-align:center;">
                <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Items</div>
                <div style="font-size:13px;font-weight:500;color:#374151;">{{ $quote->items->count() }}</div>
            </div>
            <div style="background:#fff;border-radius:8px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);text-align:center;">
                <div style="font-size:11px;color:#9ca3af;margin-bottom:2px;">Total (inc. GST)</div>
                <div style="font-size:13px;font-weight:700;color:#f97316;">${{ number_format($quote->total, 2) }}</div>
            </div>
        </div>
</x-app-layout>
