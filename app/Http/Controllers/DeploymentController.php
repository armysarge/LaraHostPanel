<?php

namespace App\Http\Controllers;

use App\Models\DeploymentLog;
use App\Models\Project;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function index(Request $request)
    {
        $query = DeploymentLog::with('project')->orderByDesc('created_at');

        if ($request->filled('project')) {
            $query->where('project_id', $request->input('project'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $deployments = $query->paginate(20)->withQueryString();
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('deployments.index', compact('deployments', 'projects'));
    }

    public function show(DeploymentLog $deployment)
    {
        $deployment->load('project');

        return view('deployments.show', compact('deployment'));
    }
}
