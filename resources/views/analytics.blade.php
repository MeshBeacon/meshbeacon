@extends('layouts.app')

@section('page-actions')
<div class="flex items-center gap-4">
    <div class="text-sm text-gray-400" id="last-updated">{{ __('Last updated: Just now') }}</div>
    <button type="button" onclick="loadAnalyticsData()" class="inline-flex items-center gap-1.5 rounded-md bg-white/5 px-3 py-2 text-sm font-medium text-gray-300 ring-1 ring-inset ring-white/10 hover:bg-white/10 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
          <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd" />
        </svg>
        {{ __('Refresh') }}
    </button>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Battery Chart -->
        <div class="bg-gray-900 rounded-lg p-5 border border-white/10 shadow-sm">
            <h3 class="text-base font-medium text-white mb-4">{{ __('Battery Levels Over Time') }}</h3>
            <div id="battery-chart" class="w-full h-72"></div>
        </div>

        <!-- Signal Strength (RSSI) Chart -->
        <div class="bg-gray-900 rounded-lg p-5 border border-white/10 shadow-sm">
            <h3 class="text-base font-medium text-white mb-4">{{ __('Signal Strength (RSSI)') }}</h3>
            <div id="rssi-chart" class="w-full h-72"></div>
        </div>
    </div>
</div>

<script type="module">
    let batteryChart, rssiChart;

    const computedStyle = getComputedStyle(document.documentElement);
    const brandColor = computedStyle.getPropertyValue('--color-fg-brand').trim() || "#1447E6";
    const successColor = computedStyle.getPropertyValue('--color-fg-success').trim() || "#10B981";

    const commonOptions = {
        chart: {
            height: 280,
            type: "line",
            fontFamily: "Inter, sans-serif",
            dropShadow: { enabled: false },
            toolbar: { show: false },
            animations: { enabled: true },
            background: 'transparent'
        },
        stroke: { width: 3, curve: 'smooth' },
        grid: {
            show: true,
            borderColor: '#374151',
            strokeDashArray: 4,
            padding: { left: 10, right: 10, top: 0, bottom: 0 },
        },
        xaxis: {
            type: 'datetime',
            labels: {
                style: { colors: '#9ca3af', fontSize: '11px' },
                datetimeFormatter: {
                    year: 'yyyy',
                    month: 'MMM \'yy',
                    day: 'dd MMM',
                    hour: 'HH:mm'
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { colors: '#9ca3af', fontSize: '11px' },
            }
        },
        legend: {
            labels: { colors: '#d1d5db' },
            position: 'top',
        },
        theme: { mode: 'dark' },
        tooltip: {
            theme: 'dark',
            x: { format: 'dd MMM HH:mm' }
        },
        noData: {
            text: 'Loading...',
            style: { color: '#9ca3af', fontSize: '14px' },
        }
    };

    batteryChart = new ApexCharts(document.getElementById("battery-chart"), {
        ...commonOptions,
        colors: [successColor, '#F59E0B', '#3B82F6', '#EC4899', '#8B5CF6'],
        yaxis: {
            ...commonOptions.yaxis,
            min: 0,
            max: 100,
            tickAmount: 5,
        }
    });
    batteryChart.render();

    rssiChart = new ApexCharts(document.getElementById("rssi-chart"), {
        ...commonOptions,
        colors: [brandColor, '#10B981', '#F59E0B', '#EC4899', '#8B5CF6'],
        yaxis: {
            ...commonOptions.yaxis,
            min: -130,
            max: 0,
            tickAmount: 5,
            reversed: false
        }
    });
    rssiChart.render();

    window.loadAnalyticsData = async function() {
        try {
            const response = await fetch('/analytics/data');
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const json = await response.json();
            
            if (json.batterySeries && json.batterySeries.length > 0) {
                batteryChart.updateSeries(json.batterySeries);
            } else {
                batteryChart.updateOptions({ noData: { text: 'No battery data available' }});
            }

            if (json.rssiSeries && json.rssiSeries.length > 0) {
                rssiChart.updateSeries(json.rssiSeries);
            } else {
                rssiChart.updateOptions({ noData: { text: 'No RSSI data available' }});
            }

            document.getElementById('last-updated').textContent = `{{ __('Last updated') }}: ${new Date().toLocaleTimeString()}`;
        } catch (e) {
            console.error('Failed to load analytics data:', e);
        }
    };

    loadAnalyticsData();
    setInterval(loadAnalyticsData, 60_000);
</script>
@endsection
