@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Data Hari" />

    <div class="space-y-6" x-data="hariPageData()">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
            <!-- Card 1: Hari Aktif -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
                    <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M19.0012 5.00078C19.0012 3.89621 18.1058 3.00078 17.0012 3.00078H7.00122C5.89665 3.00078 5.00122 3.89621 5.00122 5.00078V19.0008C5.00122 20.1054 5.89665 21.0008 7.00122 21.0008H17.0012C18.1058 21.0008 19.0012 20.1054 19.0012 19.0008V5.00078ZM7.00122 5.00078H17.0012V19.0008H7.00122V5.00078Z" fill=""/>
                        <path d="M9.00122 9.00078H15.0012" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12.0012 8.00078V16.0008" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="flex items-end justify-between mt-5">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Hari Aktif</span>
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="activeCount">0</h4>
                    </div>
                    
                    <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                         <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94562 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill="" />
                        </svg>
                        Active
                    </span>
                </div>
            </div>

            <!-- Card 2: Hari Tidak Aktif -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
                    <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M19.0012 5.00078C19.0012 3.89621 18.1058 3.00078 17.0012 3.00078H7.00122C5.89665 3.00078 5.00122 3.89621 5.00122 5.00078V19.0008C5.00122 20.1054 5.89665 21.0008 7.00122 21.0008H17.0012C18.1058 21.0008 19.0012 20.1054 19.0012 19.0008V5.00078ZM7.00122 5.00078H17.0012V19.0008H7.00122V5.00078Z" fill=""/>
                        <path d="M9.00122 9.00078H15.0012" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12.0012 8.00078V16.0008" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="flex items-end justify-between mt-5">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Data Hari Tidak Aktif</span>
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="inactiveCount">0</h4>
                    </div>
                    
                    <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                        <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z" fill=""/>
                        </svg>
                        Inactive
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 3: Data Hari Table (Basic Table 3 Style) -->
        <div>
            <div class="rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <!-- Header -->
                <div class="flex flex-col gap-2 px-5 mb-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Data Hari</h3>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden">
                    <div class="max-w-full px-5 overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-gray-200 border-y dark:border-gray-700">
                                    <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Nama Hari</th>
                                    <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Status</th>
                                    <th scope="col" class="relative px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="day in paginatedData" :key="day.id">
                                    <tr>
                                        <td class="py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="day.name"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(day.status)" x-text="day.status"></span>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-right whitespace-nowrap">
                                            <div class="flex justify-end">
                                                <label :for="'toggle-' + day.id" class="flex cursor-pointer select-none items-center">
                                                    <div class="relative">
                                                        <input type="checkbox" :id="'toggle-' + day.id" class="sr-only" 
                                                            @click.prevent="toggleStatus(day.id, day.status)"
                                                            :checked="day.status === 'Active'" />
                                                        <div class="block h-6 w-11 rounded-full bg-gray-200 dark:bg-gray-700" :class="day.status === 'Active' && '!bg-brand-500 dark:!bg-brand-500'"></div>
                                                        <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-in-out" :class="day.status === 'Active' ? 'translate-x-full' : 'translate-x-0'"></div>
                                                    </div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination (Disabled for Hari since it's only 7) -->
            </div>
        </div>

        <!-- Card 4: History Transaksi (Basic Table 4 Style) -->
        @php
            $history = [
                [
                    'user' => 'Admin (You)',
                    'action' => 'Changed Status',
                    'detail' => 'Sabtu set to Inactive',
                    'time' => '1 week ago',
                    'status' => 'Success',
                ],
                [
                    'user' => 'Admin (You)',
                    'action' => 'Changed Status',
                    'detail' => 'Minggu set to Inactive',
                    'time' => '1 week ago',
                    'status' => 'Success',
                ],
                 [
                    'user' => 'System',
                    'action' => 'System Check',
                    'detail' => 'Day Configuration',
                    'time' => '2 weeks ago',
                    'status' => 'Success',
                ],
            ];
            
            function getHistoryStatusClass($status) {
                $baseClasses = 'rounded-full px-2 text-theme-xs font-medium';
                switch ($status) {
                    case 'Success':
                        return "$baseClasses bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500";
                    case 'Pending':
                        return "$baseClasses bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400";
                    case 'Failed':
                        return "$baseClasses bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500";
                    default:
                        return $baseClasses;
                }
            }
        @endphp

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
            <div class="flex justify-between gap-2 mb-4 sm:items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Histori Data Hari</h3>
                </div>
            </div>

            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-gray-100 border-y dark:border-gray-800">
                           <th class="py-3 font-normal text-left">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">User</p>
                            </th>
                            <th class="py-3 font-normal text-left">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">Action</p>
                            </th>
                             <th class="py-3 font-normal text-left">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">Time</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="log in logs" :key="log.time">
                            <tr>
                                <td class="py-3">
                                    <div class="flex items-center gap-[18px]">
                                        <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="log.user"></p>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center">
                                       <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="log.action"></p>
                                    </div>
                                </td>
                                <td class="py-3">
                                     <div class="flex items-center">
                                        <p class="text-gray-700 text-theme-sm dark:text-gray-400" x-text="log.time"></p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="logs.length === 0">
                            <td colspan="3" class="py-4 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada log aktivitas terbaru.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function hariPageData() {
            return {
                dayData: [],
                logs: [],
                itemsPerPage: 7, // Show all 7 days mostly
                currentPage: 1,
                
                init() {
                    this.fetchHari();
                    this.fetchLogs();
                },

                get totalPages() {
                   return Math.ceil(this.dayData.length / this.itemsPerPage);
                },

                get paginatedData() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.dayData.slice(start, end);
                },

                get activeCount() {
                    return this.dayData.filter(d => (d.status || 'Active') === 'Active').length;
                },

                get inactiveCount() {
                    return this.dayData.filter(d => (d.status || 'Active') === 'Inactive').length;
                },

                async fetchHari() {
                    try {
                        const response = await fetch('{{ route("hari.data") }}');
                        if (!response.ok) throw new Error('Failed to fetch data');
                        this.dayData = await response.json();
                    } catch (error) {
                        console.error('Error fetching hari data:', error);
                        Swal.fire('Error', 'Gagal memuat data hari', 'error');
                    }
                },

                async fetchLogs() {
                    try {
                        const response = await fetch('/logs/data?type=Data Hari');
                        if (!response.ok) throw new Error('Failed to fetch logs');
                        this.logs = await response.json();
                    } catch (error) {
                        console.error('Error fetching logs:', error);
                    }
                },

                getStatusClass(status) {
                    const classes = {
                        'Active': 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
                        'Inactive': 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500',
                    };
                    return classes[status] || 'bg-gray-50 text-gray-600';
                },

                async toggleStatus(id, currentStatus) {
                    const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                    const actionText = newStatus === 'Active' ? 'mengaktifkan' : 'menonaktifkan';
                    
                    const result = await Swal.fire({
                        title: 'Konfirmasi Perubahan Status',
                        text: `Apakah Anda yakin ingin ${actionText} hari ini?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Ya, Ubah!',
                        cancelButtonText: 'Batal'
                    });

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch(`/hari/${id}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    nama_hari: this.dayData.find(d => d.id === id).name, // Send name back as validation requires it
                                    status: newStatus
                                })
                            });

                            if (!response.ok) throw new Error('Failed to update status');

                            await this.fetchHari();
                            await this.fetchLogs();

                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'bottom-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Status berhasil diperbarui'
                            });

                        } catch (error) {
                            console.error('Error updating status:', error);
                            Swal.fire('Error', 'Gagal mengubah status', 'error');
                        }
                    }
                }
            };
        }
    </script>
@endsection
