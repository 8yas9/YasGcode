<div
    class="space-y-8"
    x-data="{
        activeTab: 'matrix',
        status: null,
        statusType: null,
        init() {
            this.$watch('status', (val) => { if (val) setTimeout(() => { this.status = null; }, 6000); });
            Livewire.on('g-status', (e) => { this.statusType = e.type; this.status = e.message; });
        }
    }"
>
    <template x-if="status">
        <div
            x-show="status"
            x-transition:enter.duration.200
            x-transition:leave.duration.300
            class="rounded-lg px-4 py-3 text-sm font-medium shadow-sm"
            :class="{
                'bg-green-50 text-green-800 border border-green-200': statusType === 'success',
                'bg-red-50 text-red-800 border border-red-200': statusType === 'error',
                'bg-blue-50 text-blue-800 border border-blue-200': statusType === 'info'
            }"
            x-text="status"
        ></div>
    </template>

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Generator Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500">Select a table, configure columns, and generate a complete CRUD module.</p>
        </div>
        <button
            type="button"
            wire:click="autoFillValidation"
            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
            </svg>
            Auto-fill Validation
        </button>
    </div>

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6">
            <button
                @click="activeTab = 'matrix'"
                :class="activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium"
            >Matrix</button>
            <button
                @click="activeTab = 'settings'"
                :class="activeTab === 'settings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium"
            >Messages & Actions</button>
        </nav>
    </div>

    <div x-show="activeTab === 'matrix'" x-transition:enter.duration.200ms>
        <div class="grid grid-cols-1 gap-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">1. Tech Stack</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php $stacks = [
                        'ajax' => ['label' => 'AJAX Controllers', 'desc' => 'Laravel Controllers + Blade views with JSON responses'],
                        'livewire-v3' => ['label' => 'Livewire v3', 'desc' => 'Livewire 3 full-page components with Alpine.js'],
                    ]; @endphp
                    @foreach ($stacks as $key => $stack)
                        <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition-all
                            {{ $techStack === $key ? 'border-indigo-600 bg-indigo-50 ring-1 ring-indigo-600' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="techStack" value="{{ $key }}" class="sr-only">
                            <div class="flex w-full items-start gap-3">
                                <div class="shrink-0 mt-0.5">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors
                                        {{ $techStack === $key ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300' }}">
                                        @if ($techStack === $key)
                                            <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold {{ $techStack === $key ? 'text-indigo-900' : 'text-gray-900' }}">{{ $stack['label'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $stack['desc'] }}</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">2. Select Table</h3>
                <select
                    wire:model.live="selectedTable"
                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
                    <option value="">— Choose a database table —</option>
                    @foreach ($tables as $table)
                        <option value="{{ $table }}">{{ $table }}</option>
                    @endforeach
                </select>
            </div>

            @if ($selectedTable && count($columns) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">3. Column Configuration</h3>
                        <span class="inline-flex items-center gap-x-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">{{ count($columns) }} columns</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold uppercase text-gray-500">Column</th>
                                    <th class="px-3 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" wire:model.live="selectAllIndex" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Index</span>
                                        </label>
                                    </th>
                                    <th class="px-3 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" wire:model.live="selectAllCreate" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Create</span>
                                        </label>
                                    </th>
                                    <th class="px-3 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" wire:model.live="selectAllEdit" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Edit</span>
                                        </label>
                                    </th>
                                    <th class="px-3 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" wire:model.live="selectAllPrint" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span>Print</span>
                                        </label>
                                    </th>
                                    <th class="px-3 py-3.5 text-left text-xs font-semibold uppercase text-gray-500">Input Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($columns as $index => $column)
                                    @php $colName = $column['name']; @endphp
                                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50/50' }} hover:bg-indigo-50/50 transition-colors">
                                        <td class="whitespace-nowrap py-3 pl-6 pr-3 text-sm font-medium text-gray-900">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $colName }}</span>
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $column['type'] }}</span>
                                                @if ($column['nullable'])<span class="text-xs text-amber-500 font-medium">nullable</span>@endif
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_index" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_create" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_edit" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_print" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <select wire:model.live="columnConfigs.{{ $colName }}.input_type" class="block w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="text">Text</option>
                                                <option value="number">Number</option>
                                                <option value="email">Email</option>
                                                <option value="password">Password</option>
                                                <option value="date">Date</option>
                                                <option value="datetime-local">Datetime</option>
                                                <option value="select">Select</option>
                                                <option value="textarea">Textarea</option>
                                                <option value="file">File</option>
                                                <option value="checkbox">Checkbox</option>
                                                <option value="radio">Radio</option>
                                                <option value="hidden">Hidden</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif ($selectedTable && count($columns) === 0)
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-6 text-center">
                    <p class="text-sm font-semibold text-amber-800">No columns available</p>
                    <p class="mt-1 text-xs text-amber-600">Auto-increment and timestamp columns are filtered out.</p>
                </div>
            @endif
        </div>
    </div>

    <div x-show="activeTab === 'settings'" x-transition:enter.duration.200ms>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Messages</h3>
                <div class="space-y-5">
                    <div>
                        <label for="successMessage" class="block text-sm font-medium text-gray-700 mb-1.5">Success Message</label>
                        <input id="successMessage" type="text" wire:model.blur="successMessage" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="deleteConfirmationMessage" class="block text-sm font-medium text-gray-700 mb-1.5">Delete Confirmation Message</label>
                        <textarea id="deleteConfirmationMessage" wire:model.blur="deleteConfirmationMessage" rows="2" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                        <p class="mt-1.5 text-xs text-gray-500">Use <code class="text-indigo-600 bg-indigo-50 px-1 rounded text-[11px] font-mono">{name}</code> for the record display name.</p>
                    </div>
                    <div>
                        <label for="displayNameColumn" class="block text-sm font-medium text-gray-700 mb-1.5">Display Name Column</label>
                        <select id="displayNameColumn" wire:model.live="displayNameColumn" class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @foreach ($columns as $column)
                                <option value="{{ $column['name'] }}">{{ $column['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">Used in delete warnings and notifications.</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Folder Architecture</h3>
                <label class="relative inline-flex cursor-pointer items-center gap-3">
                    <input type="checkbox" wire:model.live="modularFolders" class="sr-only peer">
                    <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                    <span class="text-sm font-medium text-gray-900">Group files in table-named directories</span>
                </label>
                @if ($selectedTable && $modularFolders)
                    @php
                        $modelName = str_replace('_', '', \Illuminate\Support\Str::title($selectedTable));
                        $dirName = \Illuminate\Support\Str::studly($selectedTable);
                    @endphp
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 mt-4">
                        <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">Generated File Structure</p>
                        <div class="space-y-1.5 text-xs font-mono">
                            @if ($techStack === 'ajax')
                                <p class="text-gray-800">app/Http/Controllers/<span class="text-indigo-600 font-semibold">{{ $dirName }}</span>/</p>
                                <p class="text-gray-500 pl-4">└── {{ $modelName }}Controller.php</p>
                            @else
                                <p class="text-gray-800">app/Livewire/<span class="text-indigo-600 font-semibold">{{ $dirName }}</span>/</p>
                                <p class="text-gray-500 pl-4">├── Index{{ $modelName }}.php</p>
                                <p class="text-gray-500 pl-4">├── Create{{ $modelName }}.php</p>
                                <p class="text-gray-500 pl-4">├── Edit{{ $modelName }}.php</p>
                                <p class="text-gray-500 pl-4">└── Show{{ $modelName }}.php</p>
                            @endif
                            <p class="text-gray-800 mt-2">resources/views/<span class="text-indigo-600 font-semibold">{{ $selectedTable }}</span>/</p>
                            <p class="text-gray-500 pl-4">├── index.blade.php</p>
                            <p class="text-gray-500 pl-4">├── create.blade.php</p>
                            <p class="text-gray-500 pl-4">├── edit.blade.php</p>
                            <p class="text-gray-500 pl-4">├── show.blade.php</p>
                            <p class="text-gray-500 pl-4">└── print.blade.php</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 bg-white border-t border-gray-200 rounded-t-xl shadow-lg px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3 text-sm text-gray-500">
            @if ($selectedTable)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                    {{ count($columns) }} columns configured
                </span>
            @else
                <span class="text-gray-400">Select a table to begin</span>
            @endif
        </div>
        <button
            type="button"
            wire:click="generate"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        >
            <span wire:loading.remove wire:target="generate">Generate CRUD</span>
            <span wire:loading wire:target="generate" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Generating...
            </span>
        </button>
    </div>

    @if (count($generatedFiles) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Generated Output</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($generatedFiles as $file)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">{{ $file['type'] }}</span>
                            <span class="text-gray-700 font-mono text-xs">{{ $file['path'] }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $file['written'] ? 'text-green-600' : 'text-gray-400' }}">
                            @if ($file['written'])
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                Written
                            @else
                                Pending
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
