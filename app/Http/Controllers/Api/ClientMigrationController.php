<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ClientMigrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_id' => ['required', 'integer'],

            'client' => ['required', 'array'],

            'contacts' => ['nullable', 'array'],

            'contacts.*.source_id' => [
                'required',
                'integer',
            ],
        ]);

        try {

            $client = DB::transaction(function () use ($data) {

                /*
                 * Find existing migrated client.
                 *
                 * This makes the migration safe to run again.
                 */
                $client = Client::where(
                    'migration_source_id',
                    $data['source_id']
                )->first();

                if (!$client) {
                    $client = new Client();

                    $client->migration_source_id =
                        $data['source_id'];
                }

                /*
                 * Client fields.
                 */
                foreach ($data['client'] as $field => $value) {
                    $client->{$field} = $value;
                }

                $client->save();

                /*
                 * Contacts.
                 */
                foreach ($data['contacts'] ?? [] as $contactData) {

                    $contact = $client->contacts()
                        ->where(
                            'migration_source_id',
                            $contactData['source_id']
                        )
                        ->first();

                    if (!$contact) {
                        $contact = $client->contacts()->make();

                        $contact->migration_source_id =
                            $contactData['source_id'];
                    }

                    $contact->full_name =
                        $contactData['full_name'] ?? null;

                    $contact->role =
                        $contactData['role'] ?? null;

                    $contact->contact_type =
                        $contactData['contact_type'] ?? null;

                    $contact->email =
                        $contactData['email'] ?? null;

                    $contact->phone =
                        $contactData['phone'] ?? null;

                    $contact->whatsapp =
                        $contactData['whatsapp'] ?? null;

                    $contact->linkedin_url =
                        $contactData['linkedin_url'] ?? null;

                    $contact->birthday =
                        $contactData['birthday'] ?? null;

                    $contact->email_opt_in =
                        $contactData['email_opt_in'] ?? false;

                    $contact->sms_opt_in =
                        $contactData['sms_opt_in'] ?? false;

                    $contact->is_primary =
                        $contactData['is_primary'] ?? false;

                    $contact->save();
                }

                return $client;
            });

            return response()->json([
                'success' => true,
                'message' => 'Client imported successfully.',
                'source_id' => $data['source_id'],
                'client_id' => $client->id,
                'contacts' => count($data['contacts'] ?? []),
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Client import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
