<div class="space-y-4">
    @php
        $documents = [
            'fotocopy_ktp' => 'KTP',
            'fotocopy_kk' => 'KK',
            'fotocopy_akte_kelahiran' => 'Akte Kelahiran',
            'fotocopy_akte_baptis_selam' => 'Akte Baptis Selam',
            'akte_nikah_orang_tua' => 'Akte Nikah Orang Tua',
            'fotocopy_n1_n4' => 'Fotocopy N1-N4',
            'foto_berdua' => 'Foto Berdua',
        ];
    @endphp

    @foreach ($documents as $field => $label)
        @if ($record->{$field})
            <div>
                <p class="font-semibold">{{ $label }}</p>
                <img src="{{ Storage::url($record->{$field}) }}" class="rounded shadow-md max-w-full h-auto" />
            </div>
        @endif
    @endforeach
</div>
