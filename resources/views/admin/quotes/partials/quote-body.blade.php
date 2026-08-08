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

                            @if($item->key_scope_keyword)
                                <div class="item-scope-label">Keyed Scope of Works</div>
                                <div class="item-description">
                                    {!! nl2br(e(trim($item->key_scope_keyword))) !!}
                                </div>
                            @endif

                            <div class="item-price">
                                Total Price ${{ number_format($item->unit_price, 0) }} + GST @if($item->frequency_label) {{ $item->frequency_label }}@endif
                            </div>

                            @if($item->setup_fee && $item->setup_fee > 0)
                                <div class="item-price">
                                    Setup Fee ${{ number_format($item->setup_fee, 0) }} + GST (Once off)
                                </div>
                            @endif

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
