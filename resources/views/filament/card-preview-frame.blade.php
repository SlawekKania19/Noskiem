{{-- ---------------------------
     Ramka z podglądem karty ogłoszenia 1:1 (route admin.card-preview) w infoliście Filament.
     Parametr "v" zmienia się po każdej zmianie zdjęć/kadru (np. akcja "Kadry miniaturek") -
     Livewire podmienia wtedy src i ramka sama się przeładowuje.
     --------------------------- --}}
@php
    $record = $getRecord();
    $type = $record instanceof \App\Models\AnimalEdit ? 'edit' : 'animal';

    $version = md5($record->photos
        ->map(fn ($p) => [$p->id, $p->is_main, $p->focus, $p->focus_manual, $p->width])
        ->toJson());

    $src = route('admin.card-preview', ['type' => $type, 'id' => $record->id, 'v' => $version]);
@endphp

<iframe
    src="{{ $src }}"
    title="Podgląd karty ogłoszenia"
    loading="lazy"
    style="width: 100%; height: 470px; border: 0; border-radius: .75rem; background: #fff;"
></iframe>
