<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DirectDebitPayment;
use App\Models\StripeChargeBatch;
use App\Models\StripeChargeBatchItem;
use App\Models\XeroInvoice;
use App\Services\StripeAccountResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StripeAccountResolver $accounts)
    {
        $recentBatches = StripeChargeBatch::query()
            ->with(['stripeAccount.company'])
            ->withCount('items')
            ->withCount(['items as succeeded_items_count' => fn ($query) => $query->where('status', 'succeeded')])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'totalClients' => Client::count(),
            'totalInvoices' => XeroInvoice::count(),
            'successfulDebits' => DirectDebitPayment::where('status', 'settled')->count(),
            'failedDebits' => DirectDebitPayment::where('status', 'failed')->count(),
            'bulkCollectedCents' => (int) StripeChargeBatchItem::where('status', 'succeeded')->sum('amount'),
            'bulkCollectedCount' => StripeChargeBatchItem::where('status', 'succeeded')->count(),
            'bulkProcessingCount' => StripeChargeBatchItem::whereIn('status', ['pending', 'processing'])->count(),
            'bulkFailedCount' => StripeChargeBatchItem::whereIn('status', ['failed', 'disputed'])->count(),
            'bulkBatchCount' => StripeChargeBatch::count(),
            'recentBatches' => $recentBatches,
            'stripeAccounts' => $accounts->visibleAccounts($request->user()),
        ]);
    }
}
