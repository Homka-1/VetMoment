{{-- Письмо VetMoment (markdown-уведомления): логотип в шапке, в подвале — кто пишет и где управлять уведомлениями. --}}
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
VetMoment
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
VetMoment помогает приютам Казахстана найти хозяев, деньги на лечение и волонтёров для прогулок.
Все уведомления — в [личном кабинете]({{ url('/notifications') }}).

© {{ date('Y') }} VetMoment
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
