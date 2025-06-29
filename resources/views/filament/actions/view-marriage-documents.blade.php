<div class="space-y-6">
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
        $recordId = $record->id;
    @endphp

    @foreach ($documents as $field => $label)
        @php
            $files = $record->{$field};

            if (is_string($files)) {
                $files = json_decode($files, true);
            }

            $files = is_array($files) ? $files : [];
        @endphp

        @if (!empty($files))
            <div>
                <p class="font-semibold text-lg mb-2">{{ $label }}</p>
                <div class="flex flex-wrap gap-6">
                    @foreach ($files as $filename)
                        @php
                            $relativePath = "marriages/{$recordId}/{$field}/{$filename}";
                            $url = asset('storage/' . $relativePath);
                        @endphp
                        <a href="{{ $url }}" target="_blank">
                            <img src="{{ $url }}" class="w-60 h-60 object-cover rounded shadow-md hover:scale-105 transition duration-300" alt="{{ $label }}">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
