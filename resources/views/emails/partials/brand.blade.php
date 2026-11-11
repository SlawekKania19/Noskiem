{{-- ---------------------------
     Napis "noskiem.org" w mailach. Klienci poczty (Gmail, Outlook) sami zamieniają go
     na niebieski, podkreślony link - dajemy własny link z jawnym kolorem i bez podkreślenia.
     Parametry: $color (domyślnie kolor tekstu treści), $extra (dodatkowy styl, np. rozmiar)
     --------------------------- --}}
<a href="{{ url('/') }}" style="color:{{ $color ?? '#616657' }}; text-decoration:none;{{ $extra ?? '' }}"><span style="color:{{ $color ?? '#616657' }}; text-decoration:none;">noskiem.org</span></a>
