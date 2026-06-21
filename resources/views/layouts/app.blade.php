@vite('resources/css/app.css')
@vite('resources/js/app.js')
<html class="h-full bg-gray-900">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="h-full">
<script src="{{ asset('vendor/tailwindplus-elements/elements.min.js') }}" type="module"></script>
<script src="{{ asset('vendor/jquery/jquery-3.0.0.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/flowbite/flowbite.min.js') }}"></script>
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
<div class="min-h-full">
  <div class="relative bg-gray-800/50 pb-32">
    <nav class="bg-transparent">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="border-b border-white/10">
          <div class="flex h-16 items-center justify-between px-4 sm:px-0">
            <div class="flex items-center">
              <div class="shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="size-10">
              </div>
              <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-4">
                  <!-- Current: "bg-gray-950/50 text-white", Default: "text-gray-300 hover:bg-white/5 hover:text-white" -->
                  @if(request()->is('dashboard'))

                  <a href="/dashboard" aria-current="page" class="rounded-md bg-gray-950/50 px-3 py-2 text-sm font-medium text-white">Dashboard</a>
                  <a href="/status" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Status</a>
                  <a href="/gps" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Tracking</a>
                  @endif
                  @if(request()->is('status'))

                  <a href="/dashboard" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Dashboard</a>
                  <a href="/status" aria-current="page" class="rounded-md bg-gray-950/50 px-3 py-2 text-sm font-medium text-white">Status</a>
                  <a href="/gps" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Tracking</a>
                  @endif
                  @if(request()->is('gps'))

                  <a href="/dashboard" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Dashboard</a>
                  <a href="/status" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-white/5 hover:text-white">Status</a>
                  <a href="/gps" aria-current="page" class="rounded-md bg-gray-950/50 px-3 py-2 text-sm font-medium text-white">Tracking</a>
                  @endif
                </div>
              </div>
            </div>
            <div class="hidden md:block">
              <div class="ml-4 flex items-center md:ml-6">
                <button type="button" class="relative rounded-full p-1 text-gray-400 hover:text-white focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-yellow-500">
                  <span class="absolute -inset-1.5"></span>
                  <span class="sr-only">View notifications</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6">
                    <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>

                <!-- Profile dropdown -->
                <el-dropdown class="relative ml-3">
                  <button class="relative flex max-w-xs items-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">
                    <span class="absolute -inset-1.5"></span>
                    <span class="sr-only">Open user menu</span>
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" class="size-8 rounded-full outline outline-1 -outline-offset-1 outline-white/10" />
                  </button>

                  <el-menu anchor="bottom end" popover class="m-0 w-48 origin-top-right rounded-md bg-gray-800 p-0 py-1 outline outline-1 -outline-offset-1 outline-white/10 transition [--anchor-gap:theme(spacing.2)] [transition-behavior:allow-discrete] data-[closed]:scale-95 data-[closed]:transform data-[closed]:opacity-0 data-[enter]:duration-100 data-[leave]:duration-75 data-[enter]:ease-out data-[leave]:ease-in">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:outline-none">Your profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:outline-none">Settings</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-300 focus:bg-white/5 focus:outline-none">Sign out</a>
                  </el-menu>
                </el-dropdown>
              </div>
            </div>
            <div class="-mr-2 flex md:hidden">
              <!-- Mobile menu button -->
              <button type="button" command="--toggle" commandfor="mobile-menu" class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-white/5 hover:text-white focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-yellow-500">
                <span class="absolute -inset-0.5"></span>
                <span class="sr-only">Open main menu</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 [[aria-expanded='true']_&]:hidden">
                  <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 [&:not([aria-expanded='true']_*)]:hidden">
                  <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <el-disclosure id="mobile-menu" hidden class="border-b border-white/10 md:hidden [&:not([hidden])]:block">
        <div class="space-y-1 px-2 py-3 sm:px-3">
          <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-white/5 hover:text-white" -->
          <a href="#" aria-current="page" class="block rounded-md bg-gray-900 px-3 py-2 text-base font-medium text-white">Dashboard</a>
          <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/5 hover:text-white">Team</a>
          <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/5 hover:text-white">Projects</a>
          <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/5 hover:text-white">Calendar</a>
          <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-300 hover:bg-white/5 hover:text-white">Reports</a>
        </div>
        <div class="border-t border-white/10 pb-3 pt-4">
          <div class="flex items-center px-5">
            <div class="shrink-0">
              <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" class="size-10 rounded-full outline outline-1 -outline-offset-1 outline-white/10" />
            </div>
            <div class="ml-3">
              <div class="text-base/5 font-medium text-white">Tom Cook</div>
              <div class="text-sm font-medium text-gray-400">tom@example.com</div>
            </div>
            <button type="button" class="relative ml-auto shrink-0 rounded-full p-1 text-gray-400 hover:text-white focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-yellow-500">
              <span class="absolute -inset-1.5"></span>
              <span class="sr-only">View notifications</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6">
                <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </div>
          <div class="mt-3 space-y-1 px-2">
            <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Your profile</a>
            <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Settings</a>
            <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Sign out</a>
          </div>
        </div>
      </el-disclosure>
    </nav>
    <header class="py-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if(request()->is('dashboard'))
        <h1 class="text-3xl font-bold tracking-tight text-white">Dashboard</h1>
        @endif
        @if(request()->is('status'))
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold tracking-tight text-white">Status</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('gps'))
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold tracking-tight text-white">Tracking</h1>
          @yield('page-actions')
        </div>
        @endif
      </div>
    </header>
  </div>

  <main class="relative -mt-32">
    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
      <div class="rounded-lg bg-gray-800 px-5 py-6 outline outline-1 -outline-offset-1 outline-white/10 sm:px-6">
     
        @yield('content')

      </div>
    </div>
  </main>
</div>
</body>

<script type="module">
const chartEl = document.getElementById("area-chart");

if (chartEl && typeof ApexCharts !== 'undefined') {
  const computedStyle = getComputedStyle(document.documentElement);
  const brandColor = computedStyle.getPropertyValue('--color-fg-brand').trim() || "#1447E6";

  const options = {
    chart: {
      height: "100%",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: { enabled: false },
      toolbar: { show: false },
      animations: { enabled: true },
    },
    tooltip: {
      enabled: true,
      x: { show: true },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: brandColor,
        gradientToColors: [brandColor],
      },
    },
    dataLabels: { enabled: false },
    stroke: { width: 6 },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: { left: 2, right: 2, top: 0 },
    },
    series: [{ name: "Messages", data: [], color: brandColor }],
    xaxis: {
      categories: [],
      labels: {
        show: true,
        rotate: 0,
        style: { colors: '#9ca3af', fontSize: '11px' },
        formatter: (val) => val,
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: { show: false },
    noData: {
      text: 'Loading…',
      style: { color: '#9ca3af', fontSize: '14px' },
    },
  };

  const chart = new ApexCharts(chartEl, options);
  chart.render();

  const upArrow   = `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/></svg>`;
  const downArrow = `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V6m0 13-4-4m4 4 4-4"/></svg>`;

  function updateTrend(trend) {
    const trendEl = document.getElementById('chart-trend');
    if (!trendEl || !trend) return;
    const isUp = trend.direction === 'up';
    trendEl.className = `flex items-center px-2.5 py-0.5 font-medium text-center ${isUp ? 'text-red-400' : 'text-fg-success'}`;
    trendEl.innerHTML  = (isUp ? upArrow : downArrow) + `<span class="ml-1">${trend.percentage}%</span>`;
    trendEl.title = `Current hour: ${trend.current_hour} msgs · Previous hour: ${trend.previous_hour} msgs`;
  }

  async function loadHourlyData() {
    try {
      const response = await fetch('/dashboard/hourly');
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      chart.updateOptions({
        series: [{ name: "Messages", data: json.data, color: brandColor }],
        xaxis: { categories: json.labels },
        noData: { text: 'No messages today' },
      });
      updateTrend(json.trend);
    } catch (e) {
      console.error('Failed to load hourly chart data:', e);
    }
  }

  loadHourlyData();
  setInterval(loadHourlyData, 60_000);
}
</script>
</html>
