@props(['align' => 'left'])
<th scope="col" {{ $attributes->class(['h-control px-4 font-semibold whitespace-nowrap first:pl-6 last:pr-6', 'text-right' => $align === 'right', 'text-left' => $align === 'left']) }}>{{ $slot }}</th>
