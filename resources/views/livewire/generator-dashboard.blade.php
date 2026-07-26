<div
    class="space-y-8"
    x-data="{
        activeTab: 'matrix',
        expandedRow: null,
        status: null,
        statusType: null,
        showImport: false,
        importJson: '',
        progress: { current: 0, total: 0, message: '' },
        showHelp: false,
        helpLang: 'en',
        init() {
            this.$watch('status', (val) => { if (val) setTimeout(() => { this.status = null; }, 6000); });
            Livewire.on('g-status', (e) => { this.statusType = e.type; this.status = e.message; });
            Livewire.on('g-progress', (e) => { this.progress = e; });
            Livewire.on('copy-to-clipboard', (e) => { navigator.clipboard.writeText(e.content).then(() => { this.status = 'Config copied to clipboard!'; this.statusType = 'info'; }); });
        },
        toggleRow(name) { this.expandedRow = this.expandedRow === name ? null : name; }
    }"
>
    {{-- Status Messages --}}
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

    {{-- Progress Bar --}}
    <div x-show="progress.total > 0 && progress.current < progress.total"
         x-transition:enter.duration.200
         class="rounded-lg bg-indigo-50 border border-indigo-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-indigo-800" x-text="progress.message"></span>
            <span class="text-xs text-indigo-600" x-text="`${progress.current} / ${progress.total}`"></span>
        </div>
        <div class="w-full bg-indigo-200 rounded-full h-2">
            <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                 :style="`width: ${progress.total > 0 ? (progress.current / progress.total * 100) : 0}%`"></div>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Generator Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500">Select a table, configure columns, and generate a complete CRUD module.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="exportConfig"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-white border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Export Config
            </button>
            <button type="button" @click="showImport = !showImport; if(!showImport) importJson = ''"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-white border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Import Config
            </button>
            <button type="button" wire:click="autoFillValidation"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Auto-fill Validation
            </button>
        </div>
    </div>

    {{-- Import Panel --}}
    <div x-show="showImport" x-transition:enter.duration.200 class="rounded-xl bg-yellow-50 border border-yellow-200 p-4">
        <label class="block text-sm font-medium text-yellow-800 mb-2">Paste Config JSON to Import</label>
        <div class="flex gap-2">
            <input type="text" x-model="importJson" placeholder='{"techStack":"livewire-v3",...}'
                class="flex-1 rounded-lg border-yellow-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            <button type="button" @click="if(importJson) { $wire.importConfig(importJson); showImport = false; importJson = ''; }"
                class="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                Import
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6">
            <button @click="activeTab = 'matrix'"
                :class="activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium">Matrix</button>
            <button @click="activeTab = 'settings'"
                :class="activeTab === 'settings' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium">Messages & Actions</button>
            <button @click="activeTab = 'advanced'"
                :class="activeTab === 'advanced' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium">Advanced</button>
        </nav>
    </div>

    {{-- TAB: MATRIX --}}
    <div x-show="activeTab === 'matrix'" x-transition:enter.duration.200ms>
        <div class="grid grid-cols-1 gap-8">
            {{-- Tech Stack --}}
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
                                            <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
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

            {{-- Table Selector with Search --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">2. Select Table</h3>
                <div class="space-y-3">
                    <input type="text" wire:model.live="searchQuery" placeholder="Search tables..."
                        class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <select wire:model.live="selectedTable" size="8"
                        class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Choose a database table —</option>
                        @forelse ($this->filteredTables as $table)
                            <option value="{{ $table }}" @if ($selectedTable === $table) selected @endif>{{ $table }}</option>
                        @empty
                            <option value="" disabled>No tables match your search</option>
                        @endforelse
                    </select>
                    @if ($searchQuery)
                        <p class="text-xs text-gray-500">{{ count($this->filteredTables) }} of {{ count($tables) }} tables match</p>
                    @endif
                </div>
            </div>

            {{-- Column Matrix --}}
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
                                    <th class="px-2 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" wire:model.live="selectAllIndex" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span>Index</span></label>
                                    </th>
                                    <th class="px-2 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" wire:model.live="selectAllCreate" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span>Create</span></label>
                                    </th>
                                    <th class="px-2 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" wire:model.live="selectAllEdit" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span>Edit</span></label>
                                    </th>
                                    <th class="px-2 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">
                                        <label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" wire:model.live="selectAllPrint" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> <span>Print</span></label>
                                    </th>
                                    <th class="px-3 py-3.5 text-left text-xs font-semibold uppercase text-gray-500">Input Type</th>
                                    <th class="px-3 py-3.5 text-center text-xs font-semibold uppercase text-gray-500">Advanced</th>
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
                                                @if (Str::endsWith($colName, '_id'))<span class="text-xs text-purple-500 font-medium">FK</span>@endif
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_index" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_create" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_edit" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-2 py-3 text-center">
                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.show_print" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            <select wire:model.live="columnConfigs.{{ $colName }}.input_type"
                                                class="block w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
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
                                        <td class="whitespace-nowrap px-3 py-3 text-center">
                                            <button @click="toggleRow('{{ $colName }}')"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                                </svg>
                                                Config
                                            </button>
                                        </td>
                                    </tr>
                                    {{-- EXPANDED ROW: Advanced Config --}}
                                    <tr x-show="expandedRow === '{{ $colName }}'" x-collapse>
                                        <td colspan="7" class="bg-gray-50 px-6 py-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                                {{-- Label --}}
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Custom Label</label>
                                                    <input type="text" wire:model.blur="columnConfigs.{{ $colName }}.label"
                                                        class="w-full rounded-lg border-gray-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                        placeholder="e.g. {{ ucwords(str_replace('_', ' ', $colName)) }}">
                                                </div>
                                                {{-- Validation --}}
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Validation Rules</label>
                                                    <input type="text" wire:model.blur="columnConfigs.{{ $colName }}.validation_rules"
                                                        class="w-full rounded-lg border-gray-300 px-2.5 py-1.5 text-xs font-mono shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                        placeholder="required|string|max:255">
                                                </div>

                                                {{-- Select Options (only shown when input_type === 'select') --}}
                                                @if (($columnConfigs[$colName]['input_type'] ?? 'text') === 'select')
                                                    <div class="md:col-span-2 lg:col-span-3 border-t border-gray-200 pt-3 mt-1">
                                                        <p class="text-xs font-semibold text-gray-700 mb-3">Select / Dropdown Options</p>
                                                        <label class="inline-flex items-center gap-2 cursor-pointer mb-3">
                                                            <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.searchable_select" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span class="text-xs font-medium text-gray-700">Searchable (Tom Select)</span>
                                                        </label>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            {{-- Static Options --}}
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Static Options (one per line, format: value|Label)</label>
                                                                <textarea wire:model.blur="columnConfigs.{{ $colName }}.select_options" rows="3"
                                                                    class="w-full rounded-lg border-gray-300 px-2.5 py-1.5 text-xs font-mono shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                                    placeholder="male|Male&#10;female|Female&#10;other|Other"></textarea>
                                                            </div>
                                                            {{-- From Database --}}
                                                            <div>
                                                                <label class="inline-flex items-center gap-2 cursor-pointer mb-3">
                                                                    <input type="checkbox" wire:model.live="columnConfigs.{{ $colName }}.select_from_table" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                                    <span class="text-xs font-medium text-gray-700">Load options from database table</span>
                                                                </label>
                                                                @if ($columnConfigs[$colName]['select_from_table'])
                                                                    <div class="space-y-2">
                                                                        <select wire:model.live="columnConfigs.{{ $colName }}.select_table"
                                                                            class="w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                                            <option value="">— Select table —</option>
                                                                            @foreach ($tables as $t)
                                                                                <option value="{{ $t }}">{{ $t }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        @if ($columnConfigs[$colName]['select_table'] && isset($relatedTableColumns[$columnConfigs[$colName]['select_table']]))
                                                                            <div class="flex gap-2">
                                                                                <select wire:model.live="columnConfigs.{{ $colName }}.select_value_column"
                                                                                    class="w-1/2 rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                                                    <option value="">— Value —</option>
                                                                                    @foreach ($relatedTableColumns[$columnConfigs[$colName]['select_table']] as $rc)
                                                                                        <option value="{{ $rc }}">{{ $rc }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <select wire:model.live="columnConfigs.{{ $colName }}.select_display_column"
                                                                                    class="w-1/2 rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                                                    <option value="">— Display —</option>
                                                                                    @foreach ($relatedTableColumns[$columnConfigs[$colName]['select_table']] as $rc)
                                                                                        <option value="{{ $rc }}">{{ $rc }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Foreign Key / Relationship --}}
                                                @php
                                                    $isFk = Str::endsWith($colName, '_id');
                                                @endphp
                                                @if ($isFk)
                                                    <div class="md:col-span-2 lg:col-span-3 border-t border-gray-200 pt-3 mt-1">
                                                        <p class="text-xs font-semibold text-gray-700 mb-3">Relationship (Foreign Key)</p>
                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Related Table</label>
                                                                <select wire:model.live="columnConfigs.{{ $colName }}.foreign_table"
                                                                    class="w-full rounded-lg border-gray-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                                    <option value="">— Select —</option>
                                                                    @foreach ($tables as $t)
                                                                        <option value="{{ $t }}">{{ $t }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-600 mb-1">Related Column</label>
                                                                <input type="text" wire:model.blur="columnConfigs.{{ $colName }}.foreign_column"
                                                                    class="w-full rounded-lg border-gray-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                                    placeholder="id">
                                                            </div>
                                                            <div class="flex items-end">
                                                                <p class="text-xs text-gray-400 italic">Detected FK column: <code class="text-indigo-600 bg-indigo-50 px-1 rounded">{{ $colName }}</code></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
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

    {{-- TAB: SETTINGS --}}
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
                            @if ($generateFormRequest)
                                <p class="text-gray-800 mt-2">app/Http/Requests/</p>
                                <p class="text-gray-500 pl-4">└── {{ $modelName }}Request.php</p>
                            @endif
                            @if ($generatePolicy)
                                <p class="text-gray-800 mt-2">app/Policies/</p>
                                <p class="text-gray-500 pl-4">└── {{ $modelName }}Policy.php</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TAB: ADVANCED --}}
    <div x-show="activeTab === 'advanced'" x-transition:enter.duration.200ms>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Model Options --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Model Options</h3>
                <div class="space-y-5">
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="softDeletes" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Soft Deletes</span>
                            <p class="text-xs text-gray-500">Adds <code class="text-indigo-600 bg-indigo-50 px-1 rounded">SoftDeletes</code> trait and <code class="text-indigo-600 bg-indigo-50 px-1 rounded">deleted_at</code> column support</p>
                        </div>
                    </label>
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="timestamps" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Timestamps</span>
                            <p class="text-xs text-gray-500">Sets <code class="text-indigo-600 bg-indigo-50 px-1 rounded">public $timestamps = true/false</code> in the model</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Code Generation --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Code Generation</h3>
                <div class="space-y-5">
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="generateFormRequest" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">FormRequest Class</span>
                            <p class="text-xs text-gray-500">Generates <code class="text-indigo-600 bg-indigo-50 px-1 rounded">app/Http/Requests/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : '' }}Request.php</code></p>
                        </div>
                    </label>
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="generatePolicy" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Policy Class</span>
                            <p class="text-xs text-gray-500">Generates <code class="text-indigo-600 bg-indigo-50 px-1 rounded">app/Policies/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : '' }}Policy.php</code></p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- API Routes --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">API Routes</h3>
                <div class="space-y-5">
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="generateApi" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Generate API Routes</span>
                            <p class="text-xs text-gray-500">Appends RESTful API routes to <code class="text-indigo-600 bg-indigo-50 px-1 rounded">routes/api.php</code></p>
                        </div>
                    </label>
                    @if ($generateApi)
                        <div>
                            <label for="apiPrefix" class="block text-sm font-medium text-gray-700 mb-1.5">API Route Prefix</label>
                            <input id="apiPrefix" type="text" wire:model.blur="apiPrefix" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="api">
                            <p class="mt-1 text-xs text-gray-500">Routes will be prefixed with <code class="text-indigo-600 bg-indigo-50 px-1 rounded">/{{ $apiPrefix }}/{{ $selectedTable ?: '{table}' }}</code></p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Menu Item --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Menu Item</h3>
                <div class="space-y-5">
                    <label class="relative inline-flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="generateMenuItem" class="sr-only peer">
                        <span class="relative h-6 w-11 rounded-full bg-gray-200 peer-checked:bg-indigo-600 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:after:translate-x-full"></span>
                        <div>
                            <span class="text-sm font-medium text-gray-900">Add to Sidebar Menu</span>
                            <p class="text-xs text-gray-500">Appends a menu item to the layout file (e.g. <code class="text-indigo-600 bg-indigo-50 px-1 rounded">contentNavbarLayout.blade.php</code>)</p>
                        </div>
                    </label>
                    @if ($generateMenuItem)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label for="menuLabel" class="block text-sm font-medium text-gray-700 mb-1.5">Menu Label</label>
                                <input id="menuLabel" type="text" wire:model.blur="menuLabel" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="menuRoutePrefix" class="block text-sm font-medium text-gray-700 mb-1.5">Route Prefix</label>
                                <input id="menuRoutePrefix" type="text" wire:model.blur="menuRoutePrefix" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Used for active state: <code class="text-indigo-600 bg-indigo-50 px-1 rounded">{{ $menuRoutePrefix ?: '{prefix}' }}.*</code></p>
                            </div>
                            <div>
                                <label for="menuIcon" class="block text-sm font-medium text-gray-700 mb-1.5">Icon Class (optional)</label>
                                <input id="menuIcon" type="text" wire:model.blur="menuIcon" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="e.g. ti ti-building">
                            </div>
                            <div>
                                <label for="menuLayoutPath" class="block text-sm font-medium text-gray-700 mb-1.5">Layout File Path</label>
                                <input id="menuLayoutPath" type="text" wire:model.blur="menuLayoutPath" class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="resources/views/layouts/contentNavbarLayout.blade.php">
                            </div>
                        </div>
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 mt-2">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Preview:</p>
                            <pre class="text-[11px] font-mono text-gray-600 leading-relaxed overflow-x-auto">&lt;li class="menu-item {{ request()->routeIs('{{ $menuRoutePrefix ?: '{prefix}' }}.*') ? 'active' : '' }}"&gt;
    &lt;a href="{{ route('{{ $menuRoutePrefix ?: '{prefix}' }}.index') }}" class="menu-link"&gt;
        @if ($menuIcon)&lt;i class="{{ $menuIcon }}"&gt;&lt;/i&gt;@endif
        &lt;div class="fw-normal"&gt;{{ $menuLabel ?: 'Label' }}&lt;/div&gt;
    &lt;/a&gt;
&lt;/li&gt;</pre>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Generated File Preview --}}
            @if ($selectedTable)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Files to Generate</h3>
                    @php
                        $modelName = $selectedTable ? Str::studly(Str::singular($selectedTable)) : '';
                        $fileCount = 0;
                        $fileList = [];
                        if ($techStack === 'ajax') {
                            $fileList[] = ['type' => 'Controller', 'path' => "app/Http/Controllers" . ($modularFolders ? "/$modelName" : '') . "/{$modelName}Controller.php"];
                            $fileList[] = ['type' => 'Model', 'path' => "app/Models/{$modelName}.php"];
                            $fileCount = 2;
                        } else {
                            $fileList[] = ['type' => 'Livewire', 'path' => "app/Livewire" . ($modularFolders ? "/$modelName" : '') . "/Index{$modelName}.php"];
                            $fileList[] = ['type' => 'Livewire', 'path' => "app/Livewire" . ($modularFolders ? "/$modelName" : '') . "/Create{$modelName}.php"];
                            $fileList[] = ['type' => 'Livewire', 'path' => "app/Livewire" . ($modularFolders ? "/$modelName" : '') . "/Edit{$modelName}.php"];
                            $fileList[] = ['type' => 'Livewire', 'path' => "app/Livewire" . ($modularFolders ? "/$modelName" : '') . "/Show{$modelName}.php"];
                            $fileList[] = ['type' => 'Model', 'path' => "app/Models/{$modelName}.php"];
                            $fileCount = 5;
                        }
                        $fileList[] = ['type' => 'View', 'path' => "resources/views/{$selectedTable}/index.blade.php"];
                        $fileList[] = ['type' => 'View', 'path' => "resources/views/{$selectedTable}/create.blade.php"];
                        $fileList[] = ['type' => 'View', 'path' => "resources/views/{$selectedTable}/edit.blade.php"];
                        $fileList[] = ['type' => 'View', 'path' => "resources/views/{$selectedTable}/show.blade.php"];
                        $fileList[] = ['type' => 'View', 'path' => "resources/views/{$selectedTable}/print.blade.php"];
                        $fileCount += 5;
                        if ($generateFormRequest) {
                            $fileList[] = ['type' => 'FormRequest', 'path' => "app/Http/Requests/{$modelName}Request.php"];
                            $fileCount++;
                        }
                        if ($generatePolicy) {
                            $fileList[] = ['type' => 'Policy', 'path' => "app/Policies/{$modelName}Policy.php"];
                            $fileCount++;
                        }
                        if ($generateMenuItem) {
                            $fileList[] = ['type' => 'Menu', 'path' => $menuLayoutPath . ' (+ menu item)'];
                            $fileCount++;
                        }
                    @endphp
                    <p class="text-xs text-gray-500 mb-3">{{ $fileCount }} files will be generated</p>
                    <div class="space-y-1 text-xs font-mono max-h-48 overflow-y-auto">
                        @foreach ($fileList as $f)
                            <div class="flex items-center gap-2 text-gray-700">
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-medium text-indigo-700 shrink-0">{{ $f['type'] }}</span>
                                <span class="truncate">{{ $f['path'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Generate Bar --}}
    <div class="sticky bottom-0 bg-white border-t border-gray-200 rounded-t-xl shadow-lg px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3 text-sm text-gray-500">
            @if ($selectedTable)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">{{ count($columns) }} columns configured</span>
            @else
                <span class="text-gray-400">Select a table to begin</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if ($selectedTable)
                <button type="button" wire:click="deleteGenerated" wire:confirm="Are you sure you want to delete all generated files, routes, and menu items for this table?"
                    class="inline-flex items-center gap-x-2 rounded-lg bg-red-50 border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Delete Generated
                </button>
            @endif
            <button type="button" wire:click="generate" wire:loading.attr="disabled"
                class="inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                <span wire:loading.remove wire:target="generate">Generate CRUD</span>
                <span wire:loading wire:target="generate" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Generating...
                </span>
            </button>
        </div>
    </div>

    {{-- Generated Output Log --}}
    @if (count($generatedFiles) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Generated Output</h3>
                <span class="text-xs text-gray-500">{{ count($generatedFiles) }} files</span>
            </div>
            <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @foreach ($generatedFiles as $file)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700">{{ $file['type'] }}</span>
                            <span class="text-gray-700 font-mono text-xs">{{ $file['path'] }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $file['written'] ? 'text-green-600' : 'text-gray-400' }}">
                            @if ($file['written'])<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Written
                            @else Pending
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Deleted Output Log --}}
    @if (count($deletedFiles) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-red-900">Deletion Output</h3>
                <span class="text-xs text-gray-500">{{ count($deletedFiles) }} items</span>
            </div>
            <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @foreach ($deletedFiles as $file)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">{{ $file['type'] }}</span>
                            <span class="text-gray-700 font-mono text-xs">{{ $file['path'] }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-medium {{ $file['deleted'] ? 'text-red-600' : 'text-gray-400' }}">
                            @if ($file['deleted'])<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg> Deleted
                            @else Not Found
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Help / Tutorial Button --}}
    <div class="fixed bottom-6 left-6 z-50">
        <button @click="showHelp = true"
            class="inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-3 text-white shadow-lg hover:bg-indigo-700 transition-colors"
            title="Help">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
        </button>
    </div>

    {{-- Help Modal --}}
    <div x-show="showHelp" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.away="showHelp = false">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[85vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">🧙 Magic Generator — Help</h2>
                <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex border-b border-gray-200">
                <button @click="helpLang = 'en'" :class="helpLang === 'en' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium transition-colors">English</button>
                <button @click="helpLang = 'ar'" :class="helpLang === 'ar' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium transition-colors">العربية</button>
            </div>

            <div class="overflow-y-auto flex-1 p-6">
                {{-- English Content --}}
                <div x-show="helpLang === 'en'" x-transition:enter.duration.200>
                    <h3 class="font-bold text-lg text-gray-900 mb-3">📖 How It Works</h3>
                    <p class="text-gray-600 text-sm mb-4 leading-relaxed">Magic Generator creates complete CRUD modules from your database tables. Select a table, configure its columns, choose your tech stack, and generate — everything is built for you: controllers, models, Livewire components, Blade views, routes, and more.</p>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🔧 Tabs Overview</h3>
                    <ul class="space-y-2 text-sm text-gray-600 mb-6">
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Matrix</span> — Select tech stack (AJAX or Livewire v3), choose a table, and toggle column visibility (Index, Create, Edit, Print). Use the Advanced Config button per column to set custom labels, validation rules, input types, select options, and foreign key relationships.</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Messages & Actions</span> — Set success/delete messages, choose the display name column, and toggle modular folder architecture.</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Advanced</span> — Soft Deletes, Timestamps, FormRequest class, Policy class, API Routes, Sidebar Menu Item, file preview, and more.</li>
                    </ul>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🎛️ Buttons & Actions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mb-4">
                            <thead class="text-xs uppercase bg-gray-50"><tr><th class="px-3 py-2">Button</th><th class="px-3 py-2">Action</th></tr></thead>
                            <tbody class="divide-y">
                                <tr><td class="px-3 py-2 font-mono text-xs">Auto-fill Validation</td><td class="px-3 py-2 text-gray-600">Fills all validation rules based on column types</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Generate CRUD</td><td class="px-3 py-2 text-gray-600">Creates all files, appends routes, and builds the module</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Delete Generated</td><td class="px-3 py-2 text-gray-600">Removes all generated files, routes, and menu items for the selected table</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Export Config</td><td class="px-3 py-2 text-gray-600">Copies current column configuration to clipboard as JSON</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Import Config</td><td class="px-3 py-2 text-gray-600">Opens the import panel — paste previously exported JSON</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">📋 Search tables</td><td class="px-3 py-2 text-gray-600">Filters the table list by typing</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🛡️ Safety Features</h3>
                    <ul class="space-y-1 text-sm text-gray-600 mb-4">
                        <li>✅ <strong>Rollback on failure</strong> — If generation fails mid-way, all created files are automatically deleted</li>
                        <li>✅ <strong>Delete Generated</strong> — Cleanly removes all files, routes, and menu items with one click</li>
                        <li>✅ <strong>Route markers</strong> — Routes are flagged with <code class="bg-gray-100 px-1 rounded text-xs">// MAGIC-GENERATOR</code> so they can be cleanly removed</li>
                        <li>✅ <strong>Duplicate prevention</strong> — Won't append routes or menu items twice</li>
                    </ul>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">📦 Generated Files</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        @if ($techStack === 'ajax')
                            <p>🔹 <strong>Controller</strong> — <code>app/Http/Controllers/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Controller.php</code></p>
                        @else
                            <p>🔹 <strong>Livewire Index</strong> — <code>app/Livewire/Index{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}.php</code></p>
                            <p>🔹 <strong>Livewire Create</strong> — same pattern with <code>Create</code> prefix</p>
                            <p>🔹 <strong>Livewire Edit</strong> — same pattern with <code>Edit</code> prefix</p>
                            <p>🔹 <strong>Livewire Show</strong> — same pattern with <code>Show</code> prefix</p>
                        @endif
                        <p>🔹 <strong>Model</strong> — <code>app/Models/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}.php</code></p>
                        <p>🔹 <strong>Views</strong> — <code>resources/views/{{ $selectedTable ?: 'table' }}/index|create|edit|show|print.blade.php</code></p>
                        <p>🔹 <strong>FormRequest</strong> — (if enabled) <code>app/Http/Requests/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Request.php</code></p>
                        <p>🔹 <strong>Policy</strong> — (if enabled) <code>app/Policies/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Policy.php</code></p>
                        <p>🔹 <strong>API Routes</strong> — (if enabled) appended to <code>routes/api.php</code></p>
                        <p>🔹 <strong>Menu Item</strong> — (if enabled) appended to <code>contentNavbarLayout.blade.php</code></p>
                    </div>
                </div>

                {{-- Arabic Content --}}
                <div x-show="helpLang === 'ar'" x-transition:enter.duration.200>
                    <h3 class="font-bold text-lg text-gray-900 mb-3">📖 كيف يعمل</h3>
                    <p class="text-gray-600 text-sm mb-4 leading-relaxed">مولد Magic ينشئ وحدات CRUD كاملة من جداول قاعدة البيانات. اختر جدولاً، هيّئ أعمدةه، اختر التقنية، واضغط Generate — كل شيء يتم إنشاؤه لك: المتحكمات، النماذج، مكونات Livewire، عروض Blade، المسارات، والمزيد.</p>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🔧 التبويبات</h3>
                    <ul class="space-y-2 text-sm text-gray-600 mb-6">
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Matrix</span> — اختر التقنية (AJAX أو Livewire v3)، اختر جدولاً، وفعّل/عطّل ظهور الأعمدة في Index/Create/Edit/Print. استخدم زر Config لكل عمود لتعيين التسمية المخصصة، قواعد التحقق، نوع الإدخال، خيارات القائمة، والعلاقات الخارجية.</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Messages & Actions</span> — اضبط رسائل النجاح والحذف، اختر عمود الاسم المعروض، وفعّل تجميع الملفات في مجلدات.</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 font-medium">Advanced</span> — Soft Deletes، Timestamps، صنف FormRequest، صنف Policy، مسارات API، عنصر قائمة الشريط الجانبي، معاينة الملفات، والمزيد.</li>
                    </ul>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🎛️ الأزرار</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left mb-4">
                            <thead class="text-xs uppercase bg-gray-50"><tr><th class="px-3 py-2">الزر</th><th class="px-3 py-2">الوظيفة</th></tr></thead>
                            <tbody class="divide-y">
                                <tr><td class="px-3 py-2 font-mono text-xs">Auto-fill Validation</td><td class="px-3 py-2 text-gray-600">يعبئ قواعد التحقق تلقائياً حسب أنواع الأعمدة</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Generate CRUD</td><td class="px-3 py-2 text-gray-600">ينشئ جميع الملفات، يضيف المسارات، ويبني الوحدة</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Delete Generated</td><td class="px-3 py-2 text-gray-600">يحذف جميع الملفات والمسارات وعناصر القائمة المنتجة</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Export Config</td><td class="px-3 py-2 text-gray-600">ينسخ الإعدادات الحالية JSON إلى الحافظة</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">Import Config</td><td class="px-3 py-2 text-gray-600">يفتح لوحة الاستيراد — الصق JSON الذي تم تصديره سابقاً</td></tr>
                                <tr><td class="px-3 py-2 font-mono text-xs">📋 Search tables</td><td class="px-3 py-2 text-gray-600">يُصفّي قائمة الجداول أثناء الكتابة</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">🛡️ ميزات الأمان</h3>
                    <ul class="space-y-1 text-sm text-gray-600 mb-4">
                        <li>✅ <strong>التراجع عند الفشل</strong> — إذا فشل التوليد، تُحذف جميع الملفات المنشأة تلقائياً</li>
                        <li>✅ <strong>حذف المولّد</strong> — يزيل جميع الملفات والمسارات وعناصر القائمة بنقرة واحدة</li>
                        <li>✅ <strong>علامات المسارات</strong> — المسارات مُوسومة بـ <code class="bg-gray-100 px-1 rounded text-xs">// MAGIC-GENERATOR</code> ليمكن حذفها بوضوح</li>
                        <li>✅ <strong>منع التكرار</strong> — لا يضيف المسارات أو عناصر القائمة مرتين</li>
                    </ul>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">📦 الملفات المولّدة</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        @if ($techStack === 'ajax')
                            <p>🔹 <strong>المتحكم</strong> — <code>app/Http/Controllers/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Controller.php</code></p>
                        @else
                            <p>🔹 <strong>Livewire Index</strong> — <code>app/Livewire/Index{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}.php</code></p>
                            <p>🔹 <strong>Livewire Create/Edit/Show</strong> — نفس النمط مع بادئة</p>
                        @endif
                        <p>🔹 <strong>الموديل</strong> — <code>app/Models/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}.php</code></p>
                        <p>🔹 <strong>العروض</strong> — <code>resources/views/{{ $selectedTable ?: 'table' }}/index|create|edit|show|print.blade.php</code></p>
                        <p>🔹 <strong>FormRequest</strong> — (إذا مفعّل) <code>app/Http/Requests/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Request.php</code></p>
                        <p>🔹 <strong>Policy</strong> — (إذا مفعّل) <code>app/Policies/{{ $selectedTable ? Str::studly(Str::singular($selectedTable)) : 'Model' }}Policy.php</code></p>
                        <p>🔹 <strong>مسارات API</strong> — (إذا مفعّل) تُضاف إلى <code>routes/api.php</code></p>
                        <p>🔹 <strong>عنصر القائمة</strong> — (إذا مفعّل) يُضاف إلى <code>contentNavbarLayout.blade.php</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        Livewire.on('g-status', (e) => {
            // Global status handler — managed by Alpine
        });
    </script>
</div>
