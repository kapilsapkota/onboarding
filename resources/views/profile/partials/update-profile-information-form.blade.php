<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6" x-data="{ avatarPreview: @js($user->avatar_url) }">
        @csrf
        @method('patch')

        <div class="flex items-center gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
            <img x-show="avatarPreview" :src="avatarPreview" alt="Profile photo preview"
                 class="h-16 w-16 rounded-full object-cover ring-2 ring-white dark:ring-gray-600">
            <div x-show="! avatarPreview" class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-lg font-bold text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <x-input-label for="avatar" :value="__('Profile photo')" />
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">JPG, PNG or WebP up to 2MB. Resized to 400×400.</p>
                <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       @change="avatarPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : @js($user->avatar_url)"
                       class="mt-2 block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-gray-700 dark:text-gray-400 dark:file:bg-white dark:file:text-gray-900">
                <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                @if ($user->avatar)
                    <label class="mt-2 inline-flex cursor-pointer items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <input type="checkbox" name="remove_avatar" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        {{ __('Remove current photo') }}
                    </label>
                @endif
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
