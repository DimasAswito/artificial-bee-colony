@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.tailwindcss.css">
@endpush

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    <!-- Metrics -->
    <div class="col-span-12 space-y-6">
      <x-ecommerce.ecommerce-metrics 
        :totalDosen="$totalDosen" 
        :totalMataKuliah="$totalMataKuliah" 
        :totalRuangan="$totalRuangan" 
        :totalRiwayat="$totalRiwayat"
      />
    </div>

    <!-- Charts Row -->
    <div class="col-span-12 grid grid-cols-12 gap-4 md:gap-6">
        <!-- Bar Chart: Jumlah Kuliah per Hari -->
        <div class="col-span-12 xl:col-span-8">
            <div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6 h-full">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">
                    Statistik Jadwal per Hari
                </h3>
                <div id="chartHari" class="-ml-4 min-w-[300px] pl-2 xl:min-w-full"></div>
            </div>
        </div>

        <!-- Donut Chart: Semester Ratio -->
        <div class="col-span-12 xl:col-span-4">
            <div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6 h-full">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-4">
                    Rasio Mata Kuliah (Aktif)
                </h3>
                <div id="chartSemester" class="flex justify-center"></div>
            </div>
        </div>
    </div>

    <!-- Table: Riwayat Generate Terakhir -->
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                 <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Riwayat Generate Terakhir
                </h3>
                <a href="{{ route('riwayat.index') }}" class="text-sm font-medium text-brand-500 hover:text-brand-600">Lihat Semua &rarr;</a>
            </div>
             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Judul</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Tahun Ajaran / Semester</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Konflik</th>
                             <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                        @forelse ($recentHistory as $h)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $h->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $h->judul ?? 'Jadwal #' . $h->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $h->tahun_ajaran }} - {{ $h->semester }}
                                </td>
                                 <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $h->best_fitness_value == 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $h->best_fitness_value }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('riwayat.detail', $h->id) }}" class="flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                                                    <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                Detail
                                            </a>
                                        </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada riwayat generate.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        $(document).ready(function() {
            // 1. Bar Chart: Jumlah Kuliah per Hari
            var optionsHari = {
                series: [{
                    name: 'Jumlah Mata Kuliah',
                    data: @json($chartHariData)
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false, // Vertical
                        columnWidth: '55%',
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: @json($chartHariLabels),
                },
                yaxis: {
                    title: {
                        text: 'Jumlah Mata Kuliah'
                    }
                },
                colors: ['#3C50E0'],
                title: {
                    text: undefined,
                    align: 'left'
                }
            };
            var chartHari = new ApexCharts(document.querySelector("#chartHari"), optionsHari);
            chartHari.render();

            // 2. Donut Chart: Semester Ratio
            var optionsSemester = {
                series: @json($chartSemesterData),
                labels: @json($chartSemesterLabels),
                chart: {
                    type: 'donut',
                    height: 350
                },
                colors: ['#3C50E0', '#80CAEE'],
                dataLabels: {
                    enabled: true,
                    style: {
                        colors: ['#FFFFFF'],
                        fontWeight: 'bold',
                    },
                    dropShadow: {
                        enabled: true,
                        top: 1,
                        left: 1,
                        blur: 1,
                        color: '#000',
                        opacity: 0.45
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Active',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return a + b
                                        }, 0)
                                    }
                                }
                            }
                        }
                    }
                }
            };
            var chartSemester = new ApexCharts(document.querySelector("#chartSemester"), optionsSemester);
            chartSemester.render();
        });
    </script>
@endpush
