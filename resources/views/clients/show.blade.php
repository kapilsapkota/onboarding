<x-app-layout>
    @section('title', $client->company_name ?? 'Client Profile')

    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('clients.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $client->company_name ?: 'Client Profile' }}
                </h2>
                @if($client->status === 'active')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Active</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                @endif
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <a href="{{ route('clients.edit', $client) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <x-message></x-message>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- ── Left col: Company + Social + Notes ── --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Company Details --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Company Details</h3>
                            <span class="text-xs text-gray-400 dark:text-gray-500">Added {{ $client->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach([
                                ['Company',       $client->company_name,                                                                    'text'],
                                ['Industry',      $client->industry,                                                                        'text'],
                                ['Service Providers',      collect(json_decode($client->service_providers))->filter()->join(', '),  'text'],
                                ['Website',      collect(json_decode($client->website))->filter()->join(', '),                                                                         'link'],
                                ['Address',       collect([$client->address, $client->city, $client->country])->filter()->join(', '),        'text'],
                                ['ABN',           $client->abn ?? null,                                                                     'text'],
                                ['Billing Email', $client->billing_email ?? null,                                                           'copy'],
                                ['Budget',        $client->monthly_budget ?? null,                                                          'text'],
                                ['Referred By',   $client->referred_by ?? null,                                                             'text'],
                                ['WhatsApp Group',$client->whatsapp_group ?? null,                                                          'link'],
                                ['Brand Assets',  $client->logo_path ?? null,                                                               'link'],
                            ] as [$key, $val, $type])
                                <div class="flex items-start gap-4 px-5 py-3">
                                    <span class="w-32 flex-shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide pt-0.5">{{ $key }}</span>
                                    <span class="flex-1 text-sm text-gray-800 dark:text-gray-200">
                                    @if(!$val)
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @elseif($type === 'link')
                                            <a href="{{ $val }}" target="_blank" class="text-yellow-600 dark:text-yellow-400 hover:underline break-all">{{ $val }}</a>
                                        @elseif($type === 'copy')
                                            <span class="flex items-center gap-2">
                                            <span class="break-all">{{ $val }}</span>
                                            <button onclick="copyText('{{ $val }}', this)"
                                                    class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition" title="Copy">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </span>
                                        @else
                                            {{ $val }}
                                        @endif
                                </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Contacts --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                Contacts
                                <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ $client->contacts->count() }}</span>
                            </h3>
                            <a href="{{ route('clients.edit', $client) }}" class="text-xs font-medium hover:underline" style="color:#C9A84C">+ Add Contact</a>
                        </div>

                        @forelse($client->contacts as $contact)
                            <div class="border-b border-gray-100 dark:border-gray-700 last:border-0">
                                {{-- Contact header --}}
                                <div class="flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-700/50">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="8" r="4" stroke-width="2"/>
                                                <path stroke-linecap="round" stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $contact->full_name ?: '—' }}</span>
                                            @if($contact->role)
                                                <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $contact->role }}</span>
                                            @endif
                                        </div>
                                        @if($contact->is_primary)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                                  style="background:#FBF6E9;color:#C9A84C">Primary</span>
                                        @endif
                                        @if($contact->contact_type)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                            {{ $contact->contact_type }}
                                        </span>
                                        @endif
                                    </div>
                                    {{-- Consent badges --}}
                                    <div class="flex gap-1.5">
                                        @if($contact->email_opt_in)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">✉ Email</span>
                                        @endif
                                        @if($contact->sms_opt_in)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">💬 SMS</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Contact details --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700 px-0">
                                    @foreach([
                                        ['Email',    $contact->email,    'copy-email'],
                                        ['Phone',    $contact->phone,    'copy'],
                                        ['WhatsApp', $contact->whatsapp, 'copy'],
                                    ] as [$label, $value, $copyType])
                                        <div class="px-5 py-3">
                                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">{{ $label }}</p>
                                            @if($value)
                                                <div class="flex items-center gap-1.5">
                                                    @if($copyType === 'copy-email')
                                                        <a href="mailto:{{ $value }}" class="text-sm text-yellow-600 dark:text-yellow-400 hover:underline truncate">{{ $value }}</a>
                                                    @else
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $value }}</span>
                                                    @endif
                                                    <button onclick="copyText('{{ $value }}', this)"
                                                            class="flex-shrink-0 text-gray-300 hover:text-yellow-500 transition" title="Copy">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if($contact->linkedin_url || $contact->birthday)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700 border-t border-gray-100 dark:border-gray-700">
                                        <div class="px-5 py-3">
                                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">LinkedIn</p>
                                            @if($contact->linkedin_url)
                                                <a href="{{ $contact->linkedin_url }}" target="_blank" class="text-sm text-yellow-600 dark:text-yellow-400 hover:underline truncate block">{{ $contact->linkedin_url }}</a>
                                            @else
                                                <span class="text-sm text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </div>
                                        <div class="px-5 py-3">
                                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Birthday</p>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $contact->birthday ? \Carbon\Carbon::parse($contact->birthday)->format('d M Y') : '—' }}
                                    </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                No contacts yet.
                                <a href="{{ route('clients.edit', $client) }}" class="hover:underline" style="color:#C9A84C">Add one →</a>
                            </div>
                        @endforelse
                    </div>

                    {{-- Notes --}}
                    @if($client->notes)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Notes</h3>
                            </div>
                            <div class="px-5 py-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $client->notes }}</p>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- ── Right col: Social + Actions ── --}}
                <div class="space-y-6">

                    {{-- Social Media --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Social Media</h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php
                                $socials = [
                                    ['Instagram', $client->instagram,  'https://instagram.com/',           '@'],
                                    ['Facebook',  $client->facebook,   'https://facebook.com/',            ''],
                                    ['TikTok',    $client->tiktok,     'https://tiktok.com/@',             '@'],
                                    ['LinkedIn',  $client->linkedin,   'https://linkedin.com/company/',    ''],
                                    ['X / Twitter',$client->twitter,   'https://x.com/',                  '@'],
                                ];
                            @endphp
                            @foreach($socials as [$name, $handle, $baseUrl, $prefix])
                                <div class="flex items-center justify-between px-5 py-3">
                                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide w-20 flex-shrink-0">{{ $name }}</span>
                                    @if($handle)
                                        <a href="{{ $baseUrl . ltrim($handle, '@') }}" target="_blank"
                                           class="text-sm hover:underline flex-1 truncate" style="color:#C9A84C">
                                            {{ $prefix }}{{ ltrim($handle, '@') }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-300 dark:text-gray-600 flex-1">—</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Status & Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Status & Actions</h3>
                        </div>
                        <div class="p-5 space-y-3">

                            {{-- Status toggle --}}
                            <form action="{{ route('clients.status', $client) }}" method="POST" class="flex gap-2">
                                @csrf @method('PATCH')
                                <select name="status"
                                        class="flex-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400 dark:bg-gray-700 dark:text-gray-200">
                                    <option value="active"   {{ $client->status === 'active'   ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $client->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <button type="submit"
                                        class="px-3 py-2 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 text-xs font-semibold rounded-lg hover:bg-gray-700 transition">
                                    Save
                                </button>
                            </form>

                            {{-- Edit --}}
                            <a href="{{ route('clients.edit', $client) }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit Profile
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('clients.destroy', $client) }}" method="POST"
                                  onsubmit="return confirm('Delete {{ addslashes($client->company_name ?? 'this client') }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-2.5 border border-red-200 dark:border-red-800/50 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                    Delete Client
                                </button>
                            </form>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script>
        function copyText(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const orig = btn.style.color;
                btn.style.color = '#C9A84C';
                setTimeout(() => btn.style.color = orig, 2000);
            });
        }
    </script>

</x-app-layout>
