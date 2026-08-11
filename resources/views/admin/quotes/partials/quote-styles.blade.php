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
        font-size: 12px;
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
        color: #111827;
        margin-top: 18px;
        margin-bottom: 18px;
    }

    .item-scope-list li { margin-bottom: 6px; }

    .item-price {
        font-weight: bolder;
        color: #111827;
        margin-top: 18px;
    }
    .item-setup-fee{
        font-weight: bolder;
        color: #111827;
    }

    .item-note {
        font-weight: bold;
        color: #111827;
        margin-top: 18px;
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
        width: auto;
        max-width: none;
        background: #ffffff;
        padding: 40px !important;
        margin: 0;
    }

    .terms-table {
        width: 100%;
        max-width: 100%;
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

    /* Spacer */
    .terms-spacer {
        height: 18px;
    }

    .term-td {
        vertical-align: top;
        padding: 0 0 18px 0;
        text-align: justify;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .term-title {
        text-align: left;
        font-size: 13px;
        font-weight: bold;
        color: #1f2937;
        margin-bottom: 4px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .term-sub-title {
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #374151;
        margin-top: 8px;
        margin-bottom: 3px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .term-body {
        font-size: 11px;
        color: #4b5563;
        line-height: 1.55;
        margin: 2px 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .term-points {
        font-size: 11px;
        color: #4b5563;
        padding-left: 18px;
        margin: 4px 0 0 0;
        line-height: 1.6;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .term-points li {
        word-wrap: break-word;
        overflow-wrap: break-word;
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
