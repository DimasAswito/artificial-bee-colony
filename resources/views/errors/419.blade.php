@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-gray-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(107,114,128,0.1)]"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-gray-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(107,114,128,0.1)]"></div>

    <div class="w-full max-w-md text-center flex flex-col items-center justify-center space-y-8 relative z-10 backdrop-blur-sm bg-white/30 dark:bg-gray-800/30 p-10 rounded-3xl border border-gray-200/50 dark:border-gray-700/50 shadow-2xl">
        
        <div class="relative">
            <h1 class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-gray-400 to-gray-600 drop-shadow-lg tracking-tighter">
                419
            </h1>
            <div class="absolute -top-4 -right-4 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg shadow-gray-500/20 border border-gray-100 dark:border-gray-700 animate-[spin_3s_linear_infinite]">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                Sesi Telah Habis
            </h2>
            <p class="text-base text-gray-600 dark:text-gray-400 max-w-xs mx-auto">
                Halaman ini telah kedaluwarsa karena Anda terlalu lama membiarkannya aktif tanpa aktivitas. Silakan muat ulang halaman.
            </p>
        </div>

        <button onclick="window.location.reload()" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-gray-800 px-8 py-3.5 text-sm font-semibold text-white shadow-xl shadow-gray-500/30 hover:bg-gray-700 hover:shadow-gray-500/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600 transition-all duration-300 transform hover:-translate-y-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21v-5h5"/>
            </svg>
            Refresh Halaman
        </button>
    </div>
</div>
@endsection
