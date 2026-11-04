@props(['title' => 'Проверьте форму'])
{{-- Сводка ошибок над формой: ссылки ведут к полям. Сами ошибки показываются и под полями. --}}
@if ($errors->any())
    <x-alert tone="danger" :title="$title" {{ $attributes }}>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
