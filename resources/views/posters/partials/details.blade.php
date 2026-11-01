{{-- ---------------------------
     Siatka danych plakatu. $rows: lista wierszy, każdy to 1–2 komórki
     [etykieta, wartość, marker|null]. Pojedyncza komórka zajmuje całą szerokość.
     --------------------------- --}}
<table class="details {{ $class ?? '' }}">
    @foreach ($rows as $row)
        <tr>
            @foreach ($row as [$label, $value, $marker])
                <td @if (count($row) === 1) colspan="2" @endif>
                    <div class="label">{{ $label }}</div>
                    <div class="value">
                        @if ($marker === 'dot')<span class="dejavu dot">●</span>&nbsp;@endif{{ $value }}
                    </div>
                </td>
            @endforeach
        </tr>
    @endforeach
</table>
