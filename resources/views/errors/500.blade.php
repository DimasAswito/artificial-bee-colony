@extends('layouts.fullscreen-layout')

@section('content')
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden">
    
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(168,85,247,0.1)]"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl shadow-[0_0_100px_50px_rgba(168,85,247,0.1)]"></div>

    <div class="w-full max-w-md text-center flex flex-col items-center justify-center space-y-8 relative z-10 backdrop-blur-sm bg-white/30 dark:bg-gray-800/30 p-10 rounded-3xl border border-gray-200/50 dark:border-gray-700/50 shadow-2xl">
        
        <div class="relative">
            <h1 class="text-9xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-purple-600 drop-shadow-lg tracking-tighter">
                500
            </h1>
            <div class="absolute -top-4 -right-4 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg shadow-purple-500/20 border border-gray-100 dark:border-gray-700 animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                    <path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
        </div>

        <div class="space-y-3">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                Server Mengalami Gangguan
            </h2>
            <p class="text-base text-gray-600 dark:text-gray-400 max-w-xs mx-auto">
                Ups, terjadi kesalahan pada sisi server kami. Tim teknis mungkin sedang memperbaikinya. Coba lagi beberapa saat.
            </p>
        </div>

        <a href="{{ url('/') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-8 py-3.5 text-sm font-semibold text-white shadow-xl shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-500/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-all duration-300 transform hover:-translate-y-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Kembali Indeks Utama
        </a>
    </div>
</div>
@endsection
