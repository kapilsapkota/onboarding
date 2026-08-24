<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeChargeBatch;
use App\Models\StripeCustomer;
use App\Models\StripePaymentMethod;
use App\Services\StripeBulkChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StripeBulkChargeController extends Controller
{
    public function __construct(private StripeBulkChargeService $service) {}

    /** Displays the customer selection and amount entry form. */
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $customers = StripeCustomer::query()
            ->whereHas('paymentMethods', fn ($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
            )
            ->with(['paymentMethods' => fn ($q) => $q
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->orderByDesc('is_default')
            ])
            ->when($search, fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        return view('admin.stripe.bulk-charge', compact('customers', 'search'));
    }

    /** Lists all bulk charge batches. */
    public function batches(): View
    {
        $batches = StripeChargeBatch::query()
            ->with(['items.stripeCustomer', 'items.stripePaymentMethod'])
            ->orderByDesc('created_at')
            ->paginate(100);

        return view('admin.stripe.batches', compact('batches'));
    }

    /** Confirms and creates the batch - called directly from the review modal. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'items'                            => ['required', 'array', 'min:1'],
            'items.*.stripe_customer_id'       => ['required', 'integer'],
            'items.*.stripe_payment_method_id' => ['required', 'integer'],
            'items.*.amount'                   => ['required', 'integer', 'min:1'],
            'items.*.description'              => ['nullable', 'string', 'max:255'],
        ]);

        $items = $this->buildValidatedItems($request->input('items'));

        if (empty($items)) {
            return back()->withErrors(['items' => 'No valid items to process.']);
        }

        try {
            $batch = $this->service->createBatch($items, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.stripe.batches.show', $batch)
            ->with('success', "Batch {$batch->reference} created and queued.");
    }

    /** Shows a single batch and its items. */
    public function showBatch(StripeChargeBatch $batch): View
    {
        $batch->load(['items.stripeCustomer', 'items.stripePaymentMethod']);

        return view('admin.stripe.batch-show', compact('batch'));
    }

    /**
     * Validates and normalises the posted items into safe charge records.
     *
     * @return array<int, array{stripe_customer_id: int, stripe_payment_method_id: int, amount: int}>
     */
    private function buildValidatedItems(array $posted): array
    {
        $items = [];

        foreach ($posted as $row) {
            $customerId = (int) ($row['stripe_customer_id'] ?? 0);
            $amountCents = (int) ($row['amount'] ?? 0);

            if ($amountCents < 1) {
                continue;
            }

            // Never trust a PM ID from the browser - verify ownership server-side
            $customer = StripeCustomer::find($customerId);

            if (! $customer) {
                continue;
            }

            $pm = StripePaymentMethod::where('id', (int) ($row['stripe_payment_method_id'] ?? 0))
                ->where('stripe_customer_id', $customer->id)
                ->where('type', 'au_becs_debit')
                ->where('status', 'active')
                ->first();

            if (! $pm) {
                continue;
            }

            $items[] = [
                'stripe_customer_id'       => $customer->id,
                'stripe_payment_method_id' => $pm->id,
                'amount'                   => $amountCents,
                'description'              => isset($row['description']) ? trim($row['description']) : null,
            ];
        }

        return $items;
    }
}
