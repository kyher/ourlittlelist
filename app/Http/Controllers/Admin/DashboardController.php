<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Couple;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Show system-wide totals. These are the only cross-couple queries allowed (Constitution
     * Principle III), so nothing beyond the aggregate counts may be read here.
     */
    public function __invoke(): View
    {
        return view('pages::admin.dashboard', [
            'totals' => [
                'users' => User::count(),
                'couples' => Couple::count(),
                'reviews' => Review::count(),
            ],
        ]);
    }
}
