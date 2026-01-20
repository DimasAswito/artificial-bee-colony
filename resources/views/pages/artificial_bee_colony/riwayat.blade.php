@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <x-common.page-breadcrumb pageTitle="Riwayat Penjadwalan" />

        <div class="rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Header -->
            <div class="flex flex-col gap-2 px-5 mb-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Riwayat Penjadwalan</h3>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden">
                <div class="max-w-full px-5 overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-gray-200 border-y dark:border-gray-700">
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">No</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Judul Jadwal</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Tahun Ajaran</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Semester</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Iterasi</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Konflik</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Waktu Generate</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-end text-theme-sm dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($history as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $index + 1 }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-800 dark:text-white">{{ $item->judul }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $item->tahun_ajaran }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $item->semester == 'Ganjil' ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-500' : 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-500' }}">
                                            {{ $item->semester }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $item->jumlah_iterasi }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $item->best_fitness_value == 0 ? 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500' }}">
                                            {{ $item->best_fitness_value }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $item->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('riwayat.export', $item->id) }}" class="flex items-center gap-1 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 hover:text-green-800 dark:border-green-800 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20" title="Export Excel">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sheet">
                                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                    <line x1="3" x2="21" y1="9" y2="9"/>
                                                    <line x1="3" x2="21" y1="15" y2="15"/>
                                                    <line x1="9" x2="9" y1="9" y2="21"/>
                                                    <line x1="15" x2="15" y1="9" y2="21"/>
                                                </svg>
                                                Export
                                            </a>
                                            <a href="{{ route('riwayat.detail', $item->id) }}" class="flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
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
                                    <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat penjadwalan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection