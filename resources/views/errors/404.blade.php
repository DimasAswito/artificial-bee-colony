@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    
    <!-- Decorative background elements -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(251,146,60,0.1)]"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(251,146,60,0.1)]"></div>

    <div class="w-full max-w-md text-center flex flex-col items-center justify-center space-y-8 relative z-10 backdrop-blur-sm bg-white/30 dark:bg-gray-800/30 p-10 rounded-3xl border border-gray-200/50 dark:border-gray-700/50 shadow-2xl">
        
        <div class="relative">
            <h1 class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-400 to-brand-600 drop-shadow-lg tracking-tighter">
                404
            </h1>
            <div class="absolute -top-4 -right-4 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg shadow-brand-500/20 border border-gray-100 dark:border-gray-700 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-brand-500">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                Halaman Tidak Ditemukan
            </h2>
            <p class="text-base text-gray-600 dark:text-gray-400 max-w-xs mx-auto">
                Maaf, halaman atau data jadwal yang Anda cari tidak ada atau mungkin sudah dihapus.
            </p>
        </div>

        <a href="{{ url('/') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-8 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-500/30 hover:bg-brand-600 hover:shadow-brand-500/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 transition-all duration-300 transform hover:-translate-y-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
