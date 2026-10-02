<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; justify-content: space-between; gap: 2rem;">

            <div style="flex: 1;">
                <h3 style="font-size: 0.875rem; font-weight: 700; color: #16a34a; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-m-trophy style="width: 1.25rem; height: 1.25rem;"/>
                    Academic Strengths
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($topSubjects as $subject)
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.25rem;">
                                <span style="color: #4b5563;">{{ $subject['name'] }}</span>
                                <span style="font-weight: 700; color: #16a34a;">{{ $subject['avg'] }}%</span>
                            </div>
                            <div style="width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 0.5rem; overflow: hidden;">
                                <div style="width: {{ $subject['avg'] }}%; background-color: #22c55e; height: 100%; border-radius: 9999px; transition: width 0.5s ease-in-out;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div style="width: 1px; background-color: #f3f4f6; align-self: stretch;"></div>

            <div style="flex: 1;">
                <h3 style="font-size: 0.875rem; font-weight: 700; color: #dc2626; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-m-exclamation-triangle style="width: 1.25rem; height: 1.25rem;"/>
                    Areas for Improvement
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($bottomSubjects as $subject)
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.25rem;">
                                <span style="color: #4b5563;">{{ $subject['name'] }}</span>
                                <span style="font-weight: 700; color: #dc2626;">{{ $subject['avg'] }}%</span>
                            </div>
                            <div style="width: 100%; background-color: #e5e7eb; border-radius: 9999px; height: 0.5rem; overflow: hidden;">
                                <div style="width: {{ $subject['avg'] }}%; background-color: #ef4444; height: 100%; border-radius: 9999px; transition: width 0.5s ease-in-out;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
