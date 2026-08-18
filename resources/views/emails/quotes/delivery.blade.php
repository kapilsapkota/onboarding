<x-mail::message>

Hi {{ $clientName }},

Thank you for choosing All in IT Solutions, see attached quote as discussed.

@if(!empty($publicUrl))
<x-mail::button :url="$publicUrl">
    Review Quote & Sign Online
</x-mail::button>
@endif

@if(!empty($extraMessage))
<x-mail::panel>
{{ $extraMessage }}
</x-mail::panel>
@endif

Feel free to call me anytime to discuss this further.

Kind regards,<br>
Ali Taufeek | Growth & Strategy Director
allinit.solutions
Unit 3, 7-29 Bridge Rd, Stanmore NSW 2048
</x-mail::message>
