<?php

namespace App\Http\Controllers;

use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total'     => Project::count(),
            'running'   => Project::where('status', 'running')->count(),
            'stopped'   => Project::where('status', 'stopped')->count(),
            'errors'    => Project::where('status', 'error')->count(),
        ];

        $recentProjects = Project::orderByDesc('updated_at')->limit(5)->get();

        return view('dashboard', compact('stats', 'recentProjects'));
    }
}
