<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripePayoutController extends Controller
{
    public function index(Request $request)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $params = [
            'limit' => 25,
        ];

        if ($request->filled('starting_after')) {
            $params['starting_after'] = $request->starting_after;
        }

        if ($request->filled('ending_before')) {
            $params['ending_before'] = $request->ending_before;
        }

        $payouts = $stripe->payouts->all($params);

        return view('admin.payouts.index', [
            'payouts' => $payouts->data,
            'hasMore' => $payouts->has_more,
            'firstId' => $payouts->data[0]->id ?? null,
            'lastId' => end($payouts->data)->id ?? null,
        ]);
    }

    public function show(Request $request, string $payoutId)
    {
        $stripe = new StripeClient(config('services.stripe.secret'));

        $payout = $stripe->payouts->retrieve($payoutId);
        $params = [
            'payout' => $payoutId,
            'limit' => 100,
        ];

        if ($request->filled('starting_after')) {
            $params['starting_after'] = $request->starting_after;
        }

        if ($request->filled('ending_before')) {
            $params['ending_before'] = $request->ending_before;
        }

        $transactions = $stripe->balanceTransactions->all($params);

        return view('admin.payouts.show', [
            'payout' => $payout,
            'transactions' => $transactions,
        ]);
    }
}
