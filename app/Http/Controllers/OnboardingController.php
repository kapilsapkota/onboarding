<?php

namespace App\Http\Controllers;

use App\Mail\NewClientCreated;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OnboardingController extends Controller
{
    public function show()
    {
        return view('clients.onboarding');
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $staffFilePath = null;
        if ($request->hasFile('staff_contacts_file')) {
            $staffFilePath = $request->file('staff_contacts_file')->store('staff-contacts', 'public');
        }

        $websites = collect($data['websites'] ?? [])->filter()->values()->toArray();

        $client = Client::create([
            'company_name'   => $data['company_name']   ?? null,
            'industry'       => $data['industry']       ?? null,
            'website'        =>  !empty($websites) ? json_encode($websites) : null,
            'address'        => $data['address']        ?? null,
            'city'           => $data['city']           ?? null,
            'post_code'           => $data['post_code']           ?? null,
            'country'        => $data['country']        ?? null,
            'abn'            => $data['abn']            ?? null,
            'instagram'      => $data['instagram']      ?? null,
            'facebook'       => $data['facebook']       ?? null,
            'tiktok'         => $data['tiktok']         ?? null,
            'linkedin'       => $data['linkedin']       ?? null,
            'twitter'        => $data['twitter']        ?? null,
            'whatsapp_group' => $data['whatsapp_group'] ?? null,
            'logo_path'      => $data['logo_path']      ?? null,
            'contacts_file_path' => $staffFilePath,
            'pasted_employees'   => $data['pasted_employees'] ?? null,

            'notes'          => $data['notes']          ?? null,
            'status'         => 'active',
        ]);

        if (!empty($data['contacts'])) {
            foreach ($data['contacts'] as $index => $contact) {
                $filled = array_filter(array_diff_key($contact, ['contact_type' => true, 'is_primary' => true]));
                if (!empty($filled)) {
                    $client->contacts()->create([
                        'full_name'    => $contact['full_name']    ?? null,
                        'role'         => $contact['role']         ?? null,
                        'contact_type' => $contact['contact_type'] ?? null,
                        'email'        => $contact['email']        ?? null,
                        'phone'        => $contact['phone']        ?? null,
                        'whatsapp'     => $contact['whatsapp']     ?? null,
                        'linkedin_url' => $contact['linkedin_url'] ?? null,
                        'email_opt_in' => $contact['email_opt_in'] ?? 0,
                        'sms_opt_in'   => $contact['sms_opt_in']   ?? 0,
                        'is_primary'   => $index === 0,
                    ]);
                }
            }
        }

        if (!empty($data['employees'])) {
            foreach ($data['employees'] as $employee) {
                if (array_filter($employee)) {
                    $client->contacts()->create([
                        'full_name'    => $employee['name']  ?? null,
                        'email'        => $employee['email'] ?? null,
                        'phone'        => $employee['phone'] ?? null,
                        'contact_type' => 'Employee',
                        'is_primary'   => false,
                    ]);
                }
            }
        }

        if (!empty($data['pasted_employees'])) {

            $lines = preg_split('/\r\n|\r|\n/', $data['pasted_employees']);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Split by space or comma
                $parts = preg_split('/[\s,|]+/', $line);

                $firstName = $parts[0] ?? null;
                $lastName  = $parts[1] ?? null;
                $email     = filter_var($parts[2] ?? null, FILTER_VALIDATE_EMAIL) ? $parts[2] : null;
                $phone     = $parts[3] ?? null;

                if ($email || $phone) {
                    $client->contacts()->create([
                        'full_name'    => trim("$firstName $lastName"),
                        'email'        => $email,
                        'phone'        => $phone,
                        'contact_type' => 'Employee',
                        'is_primary'   => false,
                    ]);
                }
            }
        }

        Mail::to('kapils@allinit.com.au')->queue(new NewClientCreated($client));


        return redirect()->route('onboarding.thanks');
    }

    public function thanks()
    {
        return view('clients.thanks');
    }
}
