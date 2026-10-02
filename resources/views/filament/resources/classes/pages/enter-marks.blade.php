<x-filament-panels::page>
    <div class="space-y-6">
        {{-- The Filters Section --}}
        <div>
            {{ $this->form }}
        </div>

        <br>

        {{-- The sortable Table Section --}}
        <div class="border-t pt-6">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
