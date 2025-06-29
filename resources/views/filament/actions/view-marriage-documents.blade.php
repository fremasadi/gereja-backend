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
        @php
            $files = is_array($record->{$field}) ? $record->{$field} : json_decode($record->{$field}, true);
        @endphp

        @if (!empty($files))
            <div>
                <p class="font-semibold mb-2">{{ $label }}</p>
                <div class="flex flex-wrap gap-4">
                    @foreach ($files as $file)
                    <a href="{{ Storage::url($file) }}" target="_blank">
                        <img src="{{ Storage::url($file) }}" class="w-32 h-32 object-cover rounded shadow" alt="{{ $label }}">
                    </a>
                    
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
