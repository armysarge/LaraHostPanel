{{--
    Deploy Modal Component
    Usage: <x-deploy-modal />

    Listens for a window-level 'open-deploy' custom event with detail:
      { id, name, branch, isGit, startUrl, commandRunUrl }

    To trigger: $dispatch('open-deploy', { id: ..., name: ..., ... })
    For auto-open on page load, fire the event in a script after alpine:initialized.
--}}
<div x-data="deployModalComponent()" @open-deploy.window="openDeploy($event.detail)">

    <x-modal open="deployOpen" close-expr="deployPhase === 'setup' && (deployOpen = false)">

        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/50">
                <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold text-gray-900 dark:text-white" x-text="'Deploy ' + deployProject.name"></h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Choose setup steps to run after deployment</p>
            </div>
        </div>

        {{-- Body: setup phase --}}
        <div x-show="deployPhase === 'setup'" class="max-h-[60vh] overflow-y-auto p-6 py-5 space-y-5">
            <template x-for="(group, name) in groups" :key="name">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="name"></p>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="preset in group" :key="preset.label">
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3 py-2 transition-colors"
                                   :class="preset.checked
                                       ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-600 dark:bg-indigo-900/30'
                                       : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                <input type="checkbox" x-model="preset.checked"
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="preset.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Body: running phase --}}
        <div x-show="deployPhase === 'running'" class="p-6 py-5 space-y-4">
            <div class="space-y-2">
                <template x-for="step in deploySteps" :key="step.label">
                    <div class="flex items-center gap-2.5">
                        <template x-if="step.status === 'done'">
                            <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <template x-if="step.status === 'running'">
                            <svg class="h-4 w-4 shrink-0 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <template x-if="step.status === 'pending'">
                            <svg class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </template>
                        <template x-if="step.status === 'failed'">
                            <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </template>
                        <span x-text="step.label"
                              :class="step.status === 'pending' ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300'"></span>
                    </div>
                </template>
            </div>
            <div x-show="deployLog" class="max-h-44 overflow-y-auto rounded-lg bg-gray-950 p-3">
                <pre class="whitespace-pre-wrap font-mono text-xs leading-relaxed text-gray-100" x-text="deployLog"></pre>
            </div>
        </div>

        {{-- Body: done phase --}}
        <div x-show="deployPhase === 'done'" class="px-6 py-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                <svg class="h-7 w-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Deployment complete!</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="deployProject.name + ' is now running.'"></p>
        </div>

        {{-- Body: error phase --}}
        <div x-show="deployPhase === 'error'" class="px-6 py-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Deployment failed</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="deployError"></p>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            <template x-if="deployPhase === 'setup'">
                <div class="flex w-full items-center justify-end gap-3">
                    <button type="button" @click="deployOpen = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Skip for now
                    </button>
                    <button type="button" @click="go()"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">
                        Deploy Now
                    </button>
                </div>
            </template>
            <template x-if="deployPhase === 'done' || deployPhase === 'error'">
                <button type="button" @click="deployOpen = false; window.dispatchEvent(new CustomEvent('deploy-done', {detail: deployProject}))"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">
                    Done
                </button>
            </template>
        </div>

    </x-modal>
</div>

@once
<script>
function deployModalComponent() {
    return {
        deployOpen:    false,
        deployProject: { id: 0, name: '', branch: 'main', isGit: true, startUrl: '', commandRunUrl: '' },
        deployPhase:   'setup',
        deploySteps:   [],
        deployLog:     '',
        deployError:   '',
        presets: [
            { group: 'Setup', label: 'Composer Install', command: 'composer install --no-interaction --prefer-dist --optimize-autoloader', checked: false },
            { group: 'Setup', label: 'Migrate',          command: 'php artisan migrate --force',   checked: false },
            { group: 'Setup', label: 'DB Seed',          command: 'php artisan db:seed --force',   checked: false },
            { group: 'Cache', label: 'Config Cache',     command: 'php artisan config:cache',      checked: false },
            { group: 'Cache', label: 'Route Cache',      command: 'php artisan route:cache',       checked: false },
            { group: 'Cache', label: 'View Clear',       command: 'php artisan view:clear',        checked: false },
            { group: 'Cache', label: 'Optimize',         command: 'php artisan optimize',          checked: false },
            { group: 'Build', label: 'NPM Install',      command: 'npm ci',                        checked: false },
            { group: 'Build', label: 'NPM Build',        command: 'npm run build',                 checked: false },
        ],
        get groups() {
            const g = {};
            this.presets.forEach(p => { if (!g[p.group]) g[p.group] = []; g[p.group].push(p); });
            return g;
        },
        openDeploy(project) {
            this.deployProject = project;
            this.deployPhase   = 'setup';
            this.deploySteps   = [];
            this.deployLog     = '';
            this.deployError   = '';
            this.presets.forEach(p => p.checked = false);
            this.deployOpen    = true;
        },
        async go() {
            this.deployPhase = 'running';
            const csrf            = document.querySelector('meta[name="csrf-token"]').content;
            const selected        = this.presets.filter(p => p.checked);
            const deployStepCount = this.deployProject.isGit ? 3 : 1;

            this.deploySteps = [
                ...(this.deployProject.isGit
                    ? [
                        { label: 'Connecting to repository',                                                                              status: 'running' },
                        { label: 'Git pull' + (this.deployProject.branch ? ' (' + this.deployProject.branch + ')' : ''), status: 'pending' },
                        { label: 'Starting container',                                                                                    status: 'pending' },
                      ]
                    : [
                        { label: 'Starting project', status: 'running' },
                      ]
                ),
                ...selected.map(p => ({ label: p.label, status: 'pending' })),
            ];

            // 1. Start / deploy the project
            try {
                if (this.deployProject.isGit) {
                    const t1 = setTimeout(() => { this.deploySteps[0].status = 'done'; this.deploySteps[1].status = 'running'; }, 800);
                    const t2 = setTimeout(() => { this.deploySteps[1].status = 'done'; this.deploySteps[2].status = 'running'; }, 1800);
                    const res = await fetch(this.deployProject.startUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    clearTimeout(t1); clearTimeout(t2);
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        const fi = this.deploySteps.findIndex(s => s.status === 'running');
                        if (fi !== -1) this.deploySteps[fi].status = 'failed';
                        this.deployPhase = 'error';
                        this.deployError = data.message || 'Failed to deploy the project. Check deployment logs.';
                        return;
                    }
                    for (let i = 0; i < deployStepCount; i++) this.deploySteps[i].status = 'done';
                } else {
                    const res = await fetch(this.deployProject.startUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        this.deploySteps[0].status = 'failed';
                        this.deployPhase = 'error';
                        this.deployError = data.message || 'Failed to start the project. Check deployment logs.';
                        return;
                    }
                    this.deploySteps[0].status = 'done';
                }
            } catch {
                const fi = this.deploySteps.findIndex(s => s.status === 'running');
                if (fi !== -1) this.deploySteps[fi].status = 'failed';
                this.deployPhase = 'error';
                this.deployError = 'Network error while starting the project.';
                return;
            }

            // 2. Run selected commands sequentially
            for (let i = 0; i < selected.length; i++) {
                const preset  = selected[i];
                const stepIdx = i + deployStepCount;
                this.deploySteps[stepIdx].status = 'running';
                this.deployLog = '';

                let runId;
                try {
                    const startRes = await fetch(this.deployProject.commandRunUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':      csrf,
                            'Content-Type':       'application/json',
                            'Accept':             'application/json',
                            'X-Requested-With':   'XMLHttpRequest',
                        },
                        body: JSON.stringify({ command: preset.command, label: preset.label }),
                    });
                    if (!startRes.ok) { this.deploySteps[stepIdx].status = 'failed'; continue; }
                    const startData = await startRes.json();
                    runId = startData.id;
                } catch {
                    this.deploySteps[stepIdx].status = 'failed';
                    continue;
                }

                // Poll until the command process finishes
                let polling = true;
                while (polling) {
                    await new Promise(r => setTimeout(r, 1000));
                    try {
                        const outRes = await fetch(`/projects/${this.deployProject.id}/commands/${runId}/output`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await outRes.json();
                        this.deployLog = data.output;
                        if (data.done) {
                            this.deploySteps[stepIdx].status = data.status === 'success' ? 'done' : 'failed';
                            polling = false;
                        }
                    } catch {
                        this.deploySteps[stepIdx].status = 'failed';
                        polling = false;
                    }
                }
            }

            this.deployPhase = 'done';
        },
    };
}
</script>
@endonce
