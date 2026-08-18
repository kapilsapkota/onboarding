<x-mail::message>
# 🎉 New Client Onboarded

A new client has been successfully added to **{{ config('app.name') }}**.

<x-mail::panel>
**{{ $client->company_name ?? '-' }}**

{{ $client->industry ?? 'Industry not specified' }}

{{ $client->address ?? '-' }}, {{ $client->city ?? '-' }}, {{ $client->country ?? '-' }}
</x-mail::panel>

## Client Details

**Website**

@php
    $websites = $client->website ? json_decode($client->website, true) : [];
@endphp

{{ !empty($websites) ? implode(', ', $websites) : '-' }}

## Contacts

**Total Contacts:** {{ $client->contacts->count() }}

@if($client->contacts->pluck('email')->filter()->isNotEmpty())
**Emails**

{{ $client->contacts->pluck('email')->filter()->implode(', ') }}
@endif

@if($client->contacts->pluck('phone')->filter()->isNotEmpty())
**Phones**

{{ $client->contacts->pluck('phone')->filter()->implode(', ') }}
@endif

@if($client->pasted_employees)
## Employees

<x-mail::panel>
{{ $client->pasted_employees }}
</x-mail::panel>
@endif

@if($client->notes)
## Notes

<x-mail::panel>
{{ $client->notes }}
</x-mail::panel>
@endif

<x-mail::button :url="route('clients.show', $client)">
View Client
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
