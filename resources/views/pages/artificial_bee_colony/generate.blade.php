@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Generate Jadwal (Artificial Bee Colony)" />

    <div class="space-y-6" x-data="generatePageData()">

        <!-- Section 1: Data Verification (Tabs) -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    1. Verifikasi Data Master
                </h3>
                <p class="mt-1 text-m text-gray-500 dark:text-gray-400">
                    Pastikan data di bawah ini sudah benar sebelum melakukan generate jadwal. status <span class="text-success-500 font-bold">Active</span> saja yang akan diproses.
                </p>
            </div>
            
            <div class="p-5">
                <!-- Tab Headers -->
                <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button 
                            @click="activeTab = tab.id"
                            :class="activeTab === tab.id ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="px-4 py-2 border-b-2 text-sm font-medium transition-colors duration-200"
                            x-text="tab.label + ' (' + tab.count + ')'">
                        </button>
                    </template>
                </div>

                <!-- Tab Contents (Dynamic Table) -->
                <div>
                     <!-- Header & Search per Tab -->
                    <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                             <a :href="currentTabUrl" class="text-m font-medium text-brand-500 hover:underline">
                                Edit Data <span x-text="currentTabLabel"></span> &rarr;
                            </a>
                        </div>
                         <div class="relative">
                            <input type="text" x-model="search" placeholder="Search data..." 
                                class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                        </div>
                    </div>

                    <!-- Generic Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <template x-for="header in currentTabHeaders" :key="header.key">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400" x-text="header.label"></th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                <template x-for="item in paginatedData" :key="item.id">
                                    <tr>
                                        <template x-for="header in currentTabHeaders" :key="header.key">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300" x-text="getValue(item, header.key)"></td>
                                        </template>
                                    </tr>
                                </template>
                                <tr x-show="paginatedData.length === 0">
                                    <td :colspan="currentTabHeaders.length" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada data yang ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing <span x-text="(currentPage - 1) * itemsPerPage + 1"></span> to <span x-text="Math.min(currentPage * itemsPerPage, filteredData.length)"></span> of <span x-text="filteredData.length"></span> results
                        </div>
                         <div class="flex items-center gap-2">
                            <button @click="currentPage > 1 ? currentPage-- : null" :disabled="currentPage === 1" 
                                class="px-3 py-1 text-sm font-medium border rounded-lg text-gray-700 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                Prev
                            </button>
                            <button @click="currentPage < totalPages ? currentPage++ : null" :disabled="currentPage === totalPages" 
                                class="px-3 py-1 text-sm font-medium border rounded-lg text-gray-700 bg-white border-gray-300 hover:bg-gray-50 disabled:opacity-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Configuration & Generate -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                 <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    2. Konfigurasi ABC & Generate
                </h3>
            </div>
            <div class="p-5">
                <form @submit.prevent="submitGenerate">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Left: Schedule Info -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase">Info Jadwal</h4>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Judul Jadwal <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.judul" placeholder="Contoh: Jadwal Semester Ganjil 2025/2026" required
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tahun Ajaran <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.tahun_ajaran" placeholder="Contoh: 2025/2026" required
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Semester <span class="text-red-500">*</span></label>
                                <select x-model="form.semester" required
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="" disabled>Pilih Semester</option>
                                    <option value="Ganjil">Ganjil</option>
                                    <option value="Genap">Genap</option>
                                </select>
                            </div>
                        </div>

                        <!-- Right: Algorithm Params -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase">Parameter Algoritma</h4>
                             <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Jumlah Populasi (Lebah) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" x-model="form.population" min="10" max="200" required
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <p class="mt-1 text-xs text-gray-500">Semakin banyak, semakin akurat tapi lambat. Rekomendasi: 30-100.</p>
                            </div>
                             <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Maksimal Iterasi (Siklus) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" x-model="form.max_cycles" min="100" max="10000" required
                                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <p class="mt-1 text-xs text-gray-500">Batas pengulangan pencarian solusi. Rekomendasi: 1000.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 p-4 bg-yellow-50 text-yellow-800 border-l-4 border-yellow-400 rounded-r dark:bg-yellow-900/20 dark:text-yellow-200 dark:border-yellow-600">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">
                                    Pastikan <strong>Tabel Data Master</strong> di atas sudah benar. Proses generate akan memakan waktu beberapa detik hingga menit tergantung kompleksitas data.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" 
                            :disabled="isGenerating"
                            class="flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 font-medium text-white hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-brand-500/20">
                            <span x-show="!isGenerating">Mulai Generate Jadwal</span>
                            <span x-show="isGenerating">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sedang Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: History -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                 <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    3. Riwayat Generate Terakhir
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
                        @forelse ($history as $h)
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function generatePageData() {
            return {
                // Data from Controller
                dosen: @json($dosen),
                mataKuliah: @json($mataKuliah),
                ruangan: @json($ruangan),
                hari: @json($hari),
                jam: @json($jam),
                
                // Tabs Config
                activeTab: 'dosen',
                tabs: [
                    { id: 'dosen', label: 'Dosen', count: @json($dosen->count()) },
                    { id: 'matkul', label: 'Mata Kuliah', count: @json($mataKuliah->count()) },
                    { id: 'ruangan', label: 'Ruangan', count: @json($ruangan->count()) },
                    { id: 'hari', label: 'Hari', count: @json($hari->count()) },
                    { id: 'jam', label: 'Jam', count: @json($jam->count()) },
                ],
                
                // Form Config
                form: {
                    judul: '',
                    tahun_ajaran: '{{ date("Y") . "/" . (date("Y")+1) }}',
                    semester: 'Ganjil',
                    population: 50,
                    max_cycles: 1000
                },
                isGenerating: false,
                
                // Table State
                search: '',
                currentPage: 1,
                itemsPerPage: 5, // Limit 5 items to verify

                get currentTabData() {
                    switch(this.activeTab) {
                        case 'dosen': return this.dosen;
                        case 'matkul': return this.mataKuliah;
                        case 'ruangan': return this.ruangan;
                        case 'hari': return this.hari;
                        case 'jam': return this.jam;
                        default: return [];
                    }
                },

                get currentTabUrl() {
                     switch(this.activeTab) {
                        case 'dosen': return "{{ route('dosen.index') }}";
                        case 'matkul': return "{{ route('mata-kuliah.index') }}";
                        case 'ruangan': return "{{ route('ruangan.index') }}";
                        case 'hari': return "{{ route('hari.index') }}";
                        case 'jam': return "{{ route('jam.index') }}";
                        default: return "#";
                    }
                },

                 get currentTabLabel() {
                    const tab = this.tabs.find(t => t.id === this.activeTab);
                    return tab ? tab.label : '';
                },

                get currentTabHeaders() {
                     switch(this.activeTab) {
                        case 'dosen': return [
                            { key: 'nama_dosen', label: 'Nama Dosen' }, 
                            { key: 'nip', label: 'NIP' }, 
                            { key: 'email', label: 'Email' }
                        ];
                        case 'matkul': return [
                            { key: 'nama_matkul', label: 'Mata Kuliah' }, 
                            { key: 'sks', label: 'SKS' }, 
                            { key: 'semester', label: 'Sem' },
                            { key: 'dosen.nama_dosen', label: 'Dosen Pengampu' }
                        ];
                        case 'ruangan': return [
                            { key: 'nama_ruangan', label: 'Nama Ruangan' }
                        ];
                        case 'hari': return [
                            { key: 'nama_hari', label: 'Hari' }
                        ];
                        case 'jam': return [
                            { key: 'jam_mulai', label: 'Mulai' },
                            { key: 'jam_selesai', label: 'Selesai' }
                        ];
                        default: return [];
                    }
                },

                get filteredData() {
                    const data = this.currentTabData;
                    if (!this.search) return data;
                    
                    const q = this.search.toLowerCase();
                    return data.filter(item => {
                         // Simple check all values
                         return Object.values(item).some(val => {
                             if(typeof val === 'string') return val.toLowerCase().includes(q);
                             if(typeof val === 'number') return val.toString().includes(q);
                             if(typeof val === 'object' && val !== null) { // Handle nested like dosen.nama_dosen
                                  return Object.values(val).some(nestedVal => 
                                    typeof nestedVal === 'string' && nestedVal.toLowerCase().includes(q)
                                  );
                             }
                             return false;
                         });
                    });
                },
                
                get totalPages() {
                   return Math.ceil(this.filteredData.length / this.itemsPerPage); 
                },

                get paginatedData() {
                    // Reset page if out of bounds
                    if (this.currentPage > this.totalPages && this.totalPages > 0) {
                        this.currentPage = 1;
                    }
                    const start = (this.currentPage - 1) * this.itemsPerPage;
                    return this.filteredData.slice(start, start + this.itemsPerPage);
                },

                getValue(item, key) {
                    if (key.includes('.')) {
                        const parts = key.split('.');
                        return item[parts[0]] ? item[parts[0]][parts[1]] : '-';
                    }
                    return item[key] || '-';
                },

                // Watchers
                init() {
                    this.$watch('activeTab', () => {
                        this.currentPage = 1;
                        this.search = '';
                    });
                },

                async submitGenerate() {
                    const result = await Swal.fire({
                        title: 'Konfirmasi Generate',
                        text: "Proses ini akan mencari jadwal terbaik dengan algoritma Artificial Bee Colony. Lanjutkan?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Mulai!',
                        cancelButtonText: 'Batal'
                    });

                    if (result.isConfirmed) {
                        this.isGenerating = true;
                        
                        try {
                            const response = await fetch('{{ route('generate.process') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(this.form)
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Terjadi kesalahan saat generate');
                            }

                            Swal.fire({
                                title: 'Berhasil!',
                                text: `Jadwal berhasil digenerate dengan nilai fitness conflict: ${data.fitness}`,
                                icon: 'success'
                            }).then(() => {
                                window.location.reload(); // Reload to show history
                            });

                        } catch (error) {
                            console.error('Generate error:', error);
                            Swal.fire('Gagal', error.message, 'error');
                        } finally {
                            this.isGenerating = false;
                        }
                    }
                }
            }
        }
    </script>
@endsection