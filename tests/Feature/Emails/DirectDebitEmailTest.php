<?php

use App\Mail\DirectDebitConfigured;
use App\Mail\NewClientCreated;
use App\Models\Client;
use App\Models\Company;
use App\Models\StripeAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDdrCompany(string $name = 'Ryze IT'): Company
{
    return Company::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(4),
        'logo' => 'Ryze-IT.webp',
    ]);
}

function makeDdrAccount(Company $company): StripeAccount
{
    return StripeAccount::create([
        'company_id' => $company->id,
        'display_name' => $company->name.' DDR',
        'status' => 'active',
    ]);
}

test('ddr submission sends its own branded confirmation email', function () {
    Mail::fake();

    $company = makeDdrCompany();
    $account = makeDdrAccount($company);

    $this->post(route('ddr.account.store', $account->public_id), [
        'company_name' => 'Acme Pty Ltd',
        'account_name' => 'Acme Trading',
        'bsb' => '123456',
        'account_number' => '12345678',
        'contacts' => [
            ['full_name' => 'Jane Doe', 'email' => 'jane@acme.test', 'phone' => '0400000000'],
        ],
    ])->assertRedirect(route('onboarding.thanks'));

    Mail::assertQueued(DirectDebitConfigured::class, function (DirectDebitConfigured $mail) {
        return $mail->envelope()->subject === 'Direct Debit Configured Successfully for Acme Pty Ltd'
            && $mail->company?->id === Company::firstWhere('name', 'Ryze IT')->id;
    });
    Mail::assertNotQueued(NewClientCreated::class);

    $html = (string) (new DirectDebitConfigured(
        Client::firstWhere('company_name', 'Acme Pty Ltd'),
        $company,
        $account->display_name,
    ))->render();

    expect($html)->toContain('images/Ryze-IT.webp')
        ->and($html)->toContain('•••• 5678')
        ->and($html)->toContain('Acme Pty Ltd');
});

test('legacy direct debit form sends the ddr confirmation email', function () {
    Mail::fake();

    $this->post(route('onboarding.direct-debit'), [
        'full_name' => 'Bob Smith',
        'company_name' => 'Bob Co',
        'email' => 'bob@bobco.test',
        'mobile' => '0411111111',
        'account_name' => 'Bob Trading',
        'bsb' => '654321',
        'account_number' => '87654321',
    ])->assertRedirect(route('onboarding.thanks'));

    Mail::assertQueued(DirectDebitConfigured::class, function (DirectDebitConfigured $mail) {
        return $mail->envelope()->subject === 'Direct Debit Configured Successfully for Bob Co';
    });
    Mail::assertNotQueued(NewClientCreated::class);
});

test('full onboarding form keeps the original notification email', function () {
    Mail::fake();

    $this->post(route('onboarding.store'), [
        'company_name' => 'Onboard Co',
    ])->assertRedirect(route('onboarding.thanks'));

    Mail::assertQueued(NewClientCreated::class, function (NewClientCreated $mail) {
        return $mail->envelope()->subject === 'New Client Onboarded: Onboard Co';
    });
    Mail::assertNotQueued(DirectDebitConfigured::class);
});
