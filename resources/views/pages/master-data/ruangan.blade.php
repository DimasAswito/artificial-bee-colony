@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Data Ruangan" />

    <div x-data="ruanganPageData()" x-init="init()" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Card 1: Input Data Ruangan -->
            <div class="lg:col-span-2">
                <x-common.component-card title="Input Data Ruangan">
                    <form @submit.prevent="saveRuangan">
                        <div class="space-y-4">
                            <!-- Nama Ruangan -->
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Nama Ruangan <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="form.nama_ruangan" placeholder="Masukkan Nama Ruangan"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                        </div>

                         <div class="mt-4">
                            <button type="submit" class="flex w-full justify-center rounded-lg bg-brand-500 p-3 font-medium text-gray-100 hover:bg-opacity-90" :disabled="isLoading">
                                <span x-show="!isLoading">Simpan</span>
                                <span x-show="isLoading">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </x-common.component-card>
            </div>

            <!-- Card Metric: Data Ruangan -->
            <div class="lg:col-span-1">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                    <div class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-xl dark:bg-gray-800">
                         <!-- Icon: Building/Room related -->
                        <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C12.5523 2 13 2.44772 13 3V21C13 21.5523 12.5523 22 12 22C11.4477 22 11 21.5523 11 21V3C11 2.44772 11.4477 2 12 2Z" fill=""/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M19 6C19.5523 6 20 6.44772 20 7V17C20 17.5523 19.5523 18 19 18C18.4477 18 18 17.5523 18 17V7C18 6.44772 18.4477 6 19 6Z" fill=""/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5 6C5.55228 6 6 6.44772 6 7V17C6 17.5523 5.55228 18 5 18C4.44772 18 4 17.5523 4 17V7C4 6.44772 4.44772 6 5 6Z" fill=""/>
                            <path d="M2 22H22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <div class="flex items-end justify-between mt-5">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Data Ruangan</span>
                            <div class="flex items-baseline gap-1 mt-2">
                                <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="activeRuanganCount"></h4>
                                <span class="text-sm text-gray-400" x-text="'/' + filteredData.length + ' Ruangan'"></span>
                            </div>
                        </div>
                        
                        <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                             <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94562 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill="" />
                            </svg>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Data Ruangan Table -->
        <div class="rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Header -->
            <div class="flex flex-col gap-2 px-5 mb-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Data Ruangan</h3>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative">
                        <button type="button" class="absolute -translate-y-1/2 left-4 top-1/2">
                            <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z" fill=""/>
                            </svg>
                        </button>
                        <input type="text" x-model="search" placeholder="Search..." class="h-[42px] w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-[42px] pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-blue-800 xl:w-[300px]"/>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden">
                <div class="max-w-full px-5 overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-gray-200 border-y dark:border-gray-700">
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Nama Ruangan</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-start text-theme-sm dark:text-gray-400">Status</th>
                                <th scope="col" class="px-4 py-3 font-normal text-gray-500 text-end text-theme-sm dark:text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="room in paginatedData" :key="room.id">
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="room.name"></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusClass(room.status)" x-text="room.status"></span>
                                    </td>
                                    <td class="px-4 py-4 text-sm font-medium text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openEditModal(room)" class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400">
                                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M15.2322 5.23223L18.7677 8.76777M16.7322 3.73223C17.7085 2.75592 19.2915 2.75592 20.2678 3.73223C21.2441 4.70854 21.2441 6.29146 20.2678 7.26777L6.5 21.0355H3V17.5355L16.7322 3.73223Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                            <button @click="confirmDelete(room.id)" class="text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400">
                                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-white/[0.05]">
                <div class="flex items-center justify-between">
                    <button @click="prevPage" :disabled="currentPage === 1" :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''" class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:px-3.5">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2.58301 9.99868C2.58272 10.1909 2.65588 10.3833 2.80249 10.53L7.79915 15.5301C8.09194 15.8231 8.56682 15.8233 8.85981 15.5305C9.15281 15.2377 9.15297 14.7629 8.86018 14.4699L5.14009 10.7472L16.6675 10.7472C17.0817 10.7472 17.4175 10.4114 17.4175 9.99715C17.4175 9.58294 17.0817 9.24715 16.6675 9.24715L5.14554 9.24715L8.86017 5.53016C9.15297 5.23717 9.15282 4.7623 8.85983 4.4695C8.56684 4.1767 8.09197 4.17685 7.79917 4.46984L2.84167 9.43049C2.68321 9.568 2.58301 9.77087 2.58301 9.99715C2.58301 9.99766 2.58301 9.99817 2.58301 9.99868Z" fill="currentColor"/>
                        </svg>
                        <span class="hidden sm:inline">Previous</span>
                    </button>

                    <ul class="hidden items-center gap-0.5 sm:flex">
                        <template x-for="page in displayedPages" :key="page">
                            <li>
                                <button x-show="page !== '...'" @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-500/[0.08] hover:text-blue-500 dark:text-gray-400 dark:hover:text-blue-500'" class="flex h-10 w-10 items-center justify-center rounded-lg text-theme-sm font-medium" x-text="page"></button>
                                <span x-show="page === '...'" class="flex h-10 w-10 items-center justify-center text-gray-500">...</span>
                            </li>
                        </template>
                    </ul>

                    <button @click="nextPage" :disabled="currentPage === totalPages" :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''" class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-3 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 sm:px-3.5">
                        <span class="hidden sm:inline">Next</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.4175 9.9986C17.4178 10.1909 17.3446 10.3832 17.198 10.53L12.2013 15.5301C11.9085 15.8231 11.4337 15.8233 11.1407 15.5305C10.8477 15.2377 10.8475 14.7629 11.1403 14.4699L14.8604 10.7472L3.33301 10.7472C2.91879 10.7472 2.58301 10.4114 2.58301 9.99715C2.58301 9.58294 2.91879 9.24715 3.33301 9.24715L14.8549 9.24715L11.1403 5.53016C10.8475 5.23717 10.8477 4.7623 11.1407 4.4695C11.4336 4.1767 11.9085 4.17685 12.2013 4.46984L17.1588 9.43049C17.3173 9.568 17.4175 9.77087 17.4175 9.99715C17.4175 9.99763 17.4175 9.99812 17.4175 9.9986Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- History Transaksi -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
            <div class="flex justify-between gap-2 mb-4 sm:items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Histori Data Ruangan</h3>
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
                        <template x-for="log in logs" :key="log.time + log.action">
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
                            <td colspan="3" class="py-3 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada log aktivitas terbaru.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <!-- Edit Modal -->
    <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800"
             @click.away="closeModal"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
             
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Edit Ruangan</h3>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="saveRuangan">
                <div class="space-y-4">
                    <!-- Nama Ruangan -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Nama Ruangan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.nama_ruangan"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <!-- Status Toggle -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Status
                        </label>
                        <div class="flex items-center gap-3">
                            <div>
                                <input type="checkbox" id="status-switch" class="sr-only" 
                                    :checked="form.status === 'Active'" 
                                    @change="form.status = (form.status === 'Active' ? 'Inactive' : 'Active')">
                                <label for="status-switch" 
                                    class="flex items-center cursor-pointer select-none text-theme-sm text-gray-600 dark:text-gray-400">
                                    <div class="relative">
                                        <div class="block h-6 w-10 rounded-full bg-gray-200 dark:bg-gray-700" :class="{ '!bg-brand-500': form.status === 'Active' }"></div>
                                        <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition" 
                                            :class="{ 'translate-x-full': form.status === 'Active' }"></div>
                                    </div>
                                    <span class="ml-3" x-text="form.status"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function ruanganPageData() {
            return {
                roomData: [],
                logs: [],
                itemsPerPage: 5,
                currentPage: 1,
                editModalOpen: false,
                isLoading: false,
                search: '',
                form: {
                    id: null,
                    nama_ruangan: '',
                    status: 'Active'
                },
                isEditing: false,

                init() {
                     this.fetchRuangan();
                     this.fetchLogs();
                },

                async fetchLogs() {
                    try {
                        const response = await fetch('{{ route('logs.data') }}?type=Data Ruangan');
                        const data = await response.json();
                        this.logs = data;
                    } catch (error) {
                        console.error('Error fetching logs:', error);
                    }
                },

                async fetchRuangan() {
                    try {
                        const response = await fetch('{{ route('ruangan.data') }}');
                        const data = await response.json();
                        this.roomData = data;
                    } catch (error) {
                         console.error('Error fetching ruangan:', error);
                    }
                },

                get filteredData() {
                    if (!this.search) return this.roomData;
                    return this.roomData.filter(d => 
                        d.name.toLowerCase().includes(this.search.toLowerCase())
                    );
                },

                get activeRuanganCount() {
                     return this.filteredData.filter(d => (d.status || 'Active') === 'Active').length;
                },

                get totalPages() {
                    return Math.ceil(this.filteredData.length / this.itemsPerPage);
                },

                get paginatedData() {
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    const end = start + this.itemsPerPage;
                    return this.filteredData.slice(start, end);
                },

                get displayedPages() {
                    const range = [];
                    for (let i = 1; i <= this.totalPages; i++) {
                        if (
                            i === 1 ||
                            i === this.totalPages ||
                            (i >= this.currentPage - 1 && i <= this.currentPage + 1)
                        ) {
                            range.push(i);
                        } else if (range[range.length - 1] !== '...') {
                            range.push('...');
                        }
                    }
                    return range;
                },

                prevPage() {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                    }
                },

                nextPage() {
                    if (this.currentPage < this.totalPages) {
                        this.currentPage++;
                    }
                },

                goToPage(page) {
                    if (typeof page === 'number' && page >= 1 && page <= this.totalPages) {
                        this.currentPage = page;
                    }
                },

                getStatusClass(status) {
                    const classes = {
                        'Active': 'bg-green-50 text-green-600 dark:bg-green-500/15 dark:text-green-500',
                        'Inactive': 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500',
                    };
                    return classes[status] || 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-500';
                },

                openEditModal(room) {
                    this.form = {
                        id: room.id,
                        nama_ruangan: room.name,
                        status: room.status || 'Active'
                    };
                    this.isEditing = true;
                    this.editModalOpen = true;
                },

                closeModal() {
                    this.editModalOpen = false;
                    this.resetForm();
                },

                resetForm() {
                    this.form = { id: null, nama_ruangan: '', status: 'Active' };
                    this.isEditing = false;
                },

                async saveRuangan() {
                    if (!this.form.nama_ruangan) {
                        Swal.fire('Error', 'Nama Ruangan wajib diisi', 'error');
                        return;
                    }

                    const url = this.isEditing ? `/ruangan/${this.form.id}` : '{{ route('ruangan.store') }}';
                    const method = this.isEditing ? 'PUT' : 'POST';

                    this.isLoading = true;
                    
                    try {
                         const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                nama_ruangan: this.form.nama_ruangan,
                                status: this.form.status
                            })
                        });

                        if (!response.ok) {
                             throw new Error('Failed to save data');
                        }

                        await this.fetchRuangan();
                        await this.fetchLogs();
                        this.closeModal();

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: this.isEditing ? 'Data ruangan berhasil diperbarui' : 'Data ruangan berhasil ditambahkan'
                        });

                        if (!this.isEditing) {
                            this.resetForm();
                        }

                    } catch (error) {
                        console.error('Error saving data:', error);
                        Swal.fire('Error', 'Gagal menyimpan data', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                confirmDelete(id) {
                    Swal.fire({
                        title: 'Apakah anda yakin?',
                        text: 'Data yang dihapus tidak dapat dikembalikan!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#3b82f6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                             try {
                                const response = await fetch(`/ruangan/${id}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                });

                                if (response.ok) {
                                    await this.fetchRuangan();
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
                                        title: 'Data ruangan berhasil dihapus'
                                    });
                                } else {
                                    throw new Error('Failed to delete');
                                }
                             } catch (error) {
                                 Swal.fire('Error', 'Gagal menghapus data', 'error');
                             }
                        }
                    });
                }
            };
        }
    </script>
@endsection
