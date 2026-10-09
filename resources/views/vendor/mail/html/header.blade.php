{{-- @branding: the email header shows the logo uploaded in Settings → General, or the app name. --}}
@props(['url', 'logo' => null])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logo)
<img src="{{ $logo }}" class="logo" alt="{{ trim($slot) }}" style="height: 48px; width: auto; max-height: 48px;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
