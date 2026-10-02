<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAction('view-activity-log');

        $logs = ActivityLog::query()
            ->with('causer')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhereJsonContains('properties->causer_name', $search);
                });
            })
            ->when($request->filled('log_name'), fn ($query) => $query->where('log_name', $request->input('log_name')))
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->input('event')))
            ->when($request->filled('causer_id'), fn ($query) => $query->where('causer_id', $request->input('causer_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'logNames' => ActivityLog::distinct()->orderBy('log_name')->pluck('log_name'),
            'events' => ActivityLog::distinct()->whereNotNull('event')->orderBy('event')->pluck('event'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $this->authorizeAction('view-activity-log');

        $activityLog->load('causer');

        return view('admin.activity-logs.show', ['log' => $activityLog]);
    }

    public function destroy(ActivityLog $activityLog): RedirectResponse
    {
        $this->authorizeAction('delete-activity-log');

        $activityLog->delete();

        return back()->with('success', 'Activity log entry deleted.');
    }

    private function authorizeAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
