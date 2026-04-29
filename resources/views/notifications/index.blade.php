@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            Уведомления
        </h2>
    </div>

    <div class="space-y-4">
        @forelse($notifications as $notification)
            <div class="p-6 rounded-2xl border transition-colors {{ is_null($notification->read_at) ? 'bg-emerald-50 border-emerald-100' : 'bg-white border-gray-100' }}">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ is_null($notification->read_at) ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-900 font-medium leading-relaxed">{{ $notification->data['message'] }}</p>
                            @if(isset($notification->data['phone']))
                                <p class="mt-2 text-emerald-700 font-bold bg-white inline-block px-3 py-1 rounded border border-emerald-100">
                                    📞 {{ $notification->data['phone'] }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-500 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">У вас пока нет уведомлений</h3>
                <p class="text-gray-500">Здесь будут появляться отклики на ваши запросы о помощи.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
