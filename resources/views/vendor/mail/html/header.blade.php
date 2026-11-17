@props(['url'])
{{-- Шапка письма VetMoment: логотип PNG (SVG почтовые клиенты не показывают), бирочная полоса снизу. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('images/brand/logo-email.png') }}" class="logo" width="184" height="32" alt="{{ $slot }}" style="height: 32px; width: 184px; max-height: 32px;">
</a>
</td>
</tr>
