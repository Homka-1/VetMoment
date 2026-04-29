@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div class="md:col-span-2 space-y-6">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            Актуальные запросы
        </h2>
        
        @forelse($requests as $request)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Срочно
                            </span>
                            <span class="text-sm text-gray-500">{{ $request->created_at->diffForHumans() }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $request->title }}</h3>
                    </div>
                    <div class="bg-gray-50 rounded-full px-3 py-1 text-sm font-medium text-gray-600 border border-gray-100">
                        @ {{ $request->user->login }}
                    </div>
                </div>
                
                <p class="text-gray-600 mb-6 leading-relaxed">{{ $request->body }}</p>
                
                @auth
                    @if($request->status === 'active')
                        @if($request->user_id !== auth()->id())
                            <form method="POST" action="{{ route('requests.accept', $request) }}">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-2.5 px-6 rounded-xl transition-colors shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    Я помогу!
                                </button>
                            </form>
                        @else
                            <div class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-50 text-gray-500 text-sm font-medium border border-gray-100">
                                Это ваш запрос (ожидает волонтера)
                            </div>
                        @endif
                    @elseif($request->status === 'in_progress')
                        @if($request->user_id === auth()->id())
                            <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl mb-4 text-blue-800 text-sm">
                                <strong>Вам помогает:</strong> {{ $request->helper->login }} (Тел: {{ $request->helper->phone }})
                            </div>
                            <form method="POST" action="{{ route('requests.close', $request) }}">
                                @csrf
                                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white font-medium py-2.5 px-6 rounded-xl transition-colors shadow-sm">
                                    Завершить заявку
                                </button>
                            </form>
                        @elseif($request->helper_id === auth()->id())
                            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-sm">
                                <strong>Вы помогаете!</strong> Свяжитесь с автором: {{ $request->user->phone }}
                            </div>
                        @else
                            <div class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-50 text-gray-500 text-sm font-medium border border-gray-100">
                                Кто-то уже помогает
                            </div>
                        @endif
                    @endif
                @else
                    <a href="{{ route('login') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Войдите, чтобы откликнуться &rarr;</a>
                @endauth
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <h3 class="text-lg font-medium text-gray-900 mb-1">Все животные в безопасности</h3>
                <p class="text-gray-500">В данный момент нет активных запросов на помощь.</p>
            </div>
        @endforelse
    </div>

    <div class="md:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 sticky top-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Нужна помощь?</h3>
            
            @auth
                <form method="POST" action="{{ route('requests.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Краткая суть</label>
                        <input type="text" name="title" id="title" required placeholder="Например: Нужна машина до клиники" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 border bg-gray-50">
                        @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Детали (район, ситуация)</label>
                        <textarea name="body" id="body" rows="4" required placeholder="Опишите ситуацию подробнее..." class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-4 py-2 border bg-gray-50 resize-none"></textarea>
                        @error('body') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 px-4 rounded-xl shadow-sm transition-colors">
                        Опубликовать запрос
                    </button>
                </form>
            @else
                <div class="text-center py-4 text-gray-600 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="mb-4 text-sm">Только зарегистрированные пользователи могут создавать запросы.</p>
                    <a href="{{ route('login') }}" class="inline-block bg-gray-900 text-white font-medium py-2 px-6 rounded-full text-sm hover:bg-gray-800 transition-colors">Войти в систему</a>
                </div>
            @endauth
        </div>
    </div>
</div>
@endsection
