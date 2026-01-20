@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Custom Breadcrumb -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                {{ $history->judul }}
            </h2>
            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ url('/') }}">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('riwayat.index') }}">
                            Riwayat Penjadwalan
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90">
                        {{ $history->judul }}
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Info Card -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tahun Ajaran</div>
                <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $history->tahun_ajaran }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Semester</div>
                <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $history->semester }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Final Fitness (Konflik)</div>
                <div class="mt-1 text-lg font-semibold {{ $history->best_fitness_value == 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $history->best_fitness_value }}
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total Jadwal</div>
                <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white">{{ $history->jadwalKuliahs->count() }}</div>
            </div>
        </div>

        <!-- Schedule Table -->
        <div class="rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Header -->
            <div class="flex flex-col gap-2 px-5 mb-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Detail Jadwal Kuliah</h3>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden">
                <div class="max-w-full px-5 overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-gray-200 border-y dark:border-gray-700">
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Hari</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Jam</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Semester</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Mata Kuliah</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">SKS</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Dosen Pengampu</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($history->jadwalKuliahs as $jadwal)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $jadwal->hari->nama_hari ?? '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        @php
                                            $jamMulai = \Carbon\Carbon::parse($jadwal->jam->jam_mulai);
                                            $jamSelesai = \Carbon\Carbon::parse($jadwal->jam->jam_selesai);
                                            
                                            // Jika 4 SKS, durasi dianggap 4 jam (karena 1 slot = 2 jam, 4 SKS = 2 slot)
                                            if ($jadwal->mataKuliah->sks == 4) {
                                                $jamSelesai = $jamMulai->copy()->addHours(4);
                                            }
                                        @endphp
                                        {{ $jamMulai->format('H:i') . ' - ' . $jamSelesai->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $jadwal->mataKuliah->semester ?? '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-white">
                                        {{ $jadwal->mataKuliah->nama_matkul ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $jadwal->mataKuliah->sks ?? '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $jadwal->dosen->nama_dosen ?? 'Belum Ada Dosen' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $jadwal->ruangan->nama_ruangan ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada data jadwal.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection