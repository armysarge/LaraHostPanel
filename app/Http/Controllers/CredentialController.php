<?php

namespace App\Http\Controllers;

use App\Models\GitCredential;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CredentialController extends Controller
{
    public function index()
    {
        $credentials = GitCredential::withCount('projects')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('credentials.index', compact('credentials'));
    }

    public function create()
    {
        return view('credentials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', Rule::in(['ssh_key', 'token'])],
            'credential' => ['required', 'string', 'max:10000'],
        ]);

        GitCredential::create($validated);

        return redirect()->route('credentials.index')
            ->with('success', 'Credential created successfully.');
    }

    public function edit(GitCredential $credential)
    {
        return view('credentials.edit', compact('credential'));
    }

    public function update(Request $request, GitCredential $credential)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', Rule::in(['ssh_key', 'token'])],
            'credential' => ['nullable', 'string', 'max:10000'],
        ]);

        // Only update credential if a new value was provided
        if (empty($validated['credential'])) {
            unset($validated['credential']);
        }

        $credential->update($validated);

        return redirect()->route('credentials.index')
            ->with('success', 'Credential updated successfully.');
    }

    public function destroy(GitCredential $credential)
    {
        if ($credential->projects()->exists()) {
            return redirect()->route('credentials.index')
                ->with('error', 'Cannot delete credential — it is in use by one or more projects.');
        }

        $credential->delete();

        return redirect()->route('credentials.index')
            ->with('success', 'Credential deleted.');
    }
}
