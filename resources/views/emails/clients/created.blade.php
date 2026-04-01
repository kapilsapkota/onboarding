<x-mail::message>
# 🎉 New Client Onboarded

A new client has been successfully added to the system.

---

## 🏢 Company Details
- **Name:** {{ $client->company_name ?? '-' }}

- **Industry:** {{ $client->industry ?? '-' }}

- **Website:**
@php
$websites = $client->website ? json_decode($client->website, true) : [];
@endphp
{{ !empty($websites) ? implode(', ', $websites) : '-' }}

- **Location:**
{{ $client->address }}, {{ $client->city }}, {{ $client->country }}

---

## 👥 Contacts Summary
- **Total Contacts:** {{ $client->contacts->count() }}
- **Emails:**
```
{{ $client->contacts->pluck('email')->filter()->implode(', ') ?: '-' }}
```
- **Phones:**
<x-mail::panel>
{{ $client->contacts->pluck('phone')->filter()->implode(', ') ?: '-' }}
</x-mail::panel>
---

@if($client->pasted_employees)
## 📋 Pasted Employees
<x-mail::panel>
{{ $client->pasted_employees }}
</x-mail::panel>
@endif

@if($client->notes)
## 📝 Notes
<x-mail::panel>
{{ $client->notes }}
</x-mail::panel>
@endif

---

<x-mail::button :url="route('clients.show', $client)">
View Client
</x-mail::button>

Thanks,
{{ config('app.name') }}
</x-mail::message>
