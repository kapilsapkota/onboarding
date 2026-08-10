<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $quote->pdf_file_name ?? $quote->quote_number }}</title>
    @include('admin.quotes.partials.quote-styles')
</head>
<body>
{{--@php dd($configImages); @endphp--}}
@include('admin.quotes.partials.quote-body')
</body>
</html>
