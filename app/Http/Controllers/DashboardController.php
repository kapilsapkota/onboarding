<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DirectDebitPayment;
use App\Models\XeroInvoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'totalClients' => Client::count(),
            'totalInvoices' => XeroInvoice::count(),
            'successfulDebits' => DirectDebitPayment::where('status', 'success')->count(),
            'failedDebits' => DirectDebitPayment::where('status', 'failed')->count(),
            'postedDebits' => DirectDebitPayment::where('status', 'posted')->count(),
            'pendingDebits' => DirectDebitPayment::where('status', 'pending')->count(),
        ]);
    }
}
