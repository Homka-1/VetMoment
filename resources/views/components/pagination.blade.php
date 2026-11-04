@props(['paginator'])
{{-- Обёртка над $paginator->links(): вид — resources/views/pagination/vetmoment.blade.php --}}
<div {{ $attributes }}>
    {{ $paginator->onEachSide(1)->links('pagination.vetmoment') }}
</div>
