<table>
    <thead>
        <tr>
            <th colspan="{{ 2 + $ruangans->count() }}" style="text-align: center; font-size: 14px; font-weight: bold;">
                JADWAL KEGIATAN PERKULIAHAN DAN PRAKTIKUM
            </th>
        </tr>
        <tr>
            <th colspan="{{ 2 + $ruangans->count() }}" style="text-align: center; font-size: 14px; font-weight: bold;">
                {{ $history->judul }} - {{ $history->tahun_ajaran }} ({{ $history->semester }})
            </th>
        </tr>
        <tr>
            <!-- Spacer Row -->
        </tr>
        <tr>
            <th rowspan="2" style="background-color: #ff4d4d; border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">Hari</th>
            <th rowspan="2" style="background-color: #ff4d4d; border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">Jam</th>
            <th colspan="{{ $ruangans->count() }}" style="background-color: #ff4d4d; border: 1px solid #000000; text-align: center; font-weight: bold;">PSDKU SIDOARJO</th>
        </tr>
        <tr>
            @foreach($ruangans as $ruangan)
                <th style="background-color: #ff4d4d; border: 1px solid #000000; text-align: center; font-weight: bold;">{{ $ruangan->nama_ruangan }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($haris as $hari)
            @foreach($jams as $index => $jam)
                <tr>
                    {{-- Render Hari Column only for the first Jam of the day --}}
                    @if($index === 0)
                        <td rowspan="{{ $jams->count() }}" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">
                            {{ $hari->nama_hari }}
                        </td>
                    @endif

                    {{-- Jam Column --}}
                    <td style="border: 1px solid #000000; text-align: center; vertical-align: middle;">
                        {{ \Carbon\Carbon::parse($jam->jam_mulai)->format('H.i') }} - {{ \Carbon\Carbon::parse($jam->jam_selesai)->format('H.i') }}
                    </td>

                    {{-- Room Columns --}}
                    @foreach($ruangans as $ruangan)
                        @php
                            $cellData = $grid[$hari->id][$jam->id][$ruangan->id] ?? null;
                            $bgColor = '#ffffff'; // Default White

                            if (is_object($cellData)) {
                                $sem = $cellData->mataKuliah->semester;
                                if ($sem == 1 || $sem == 2) $bgColor = '#FCE883'; // Yellow
                                elseif ($sem == 3 || $sem == 4) $bgColor = '#90EE90'; // Green
                                elseif ($sem == 5 || $sem == 6) $bgColor = '#ADD8E6'; // Blue
                            }
                        @endphp

                        @if($cellData === 'SKIP')
                            {{-- Do nothing, covered by rowspan --}}
                        @elseif(is_object($cellData))
                            <td rowspan="{{ $cellData->mataKuliah->sks == 4 ? 3 : 2 }}" style="background-color: {{ $bgColor }}; border: 1px solid #000000; text-align: center; vertical-align: middle;">
                                <b>{{ $cellData->mataKuliah->nama_matkul }}</b>
                                <br>
                                <i>{{ $cellData->dosen->nama_dosen }}</i>
                            </td>
                        @else
                            <td style="border: 1px solid #000000;"></td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            <!-- Separator Row Between Days (Optional, mimiks border style) -->
            <tr>
                <td colspan="{{ 2 + $ruangans->count() }}" style="border-top: 2px solid #000000;"></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr></tr>
        <tr>
            <td colspan="2">Legend:</td>
            <td style="background-color: #FCE883; border: 1px solid #000000; text-align: center;">Semester 1-2</td>
            <td style="background-color: #90EE90; border: 1px solid #000000; text-align: center;">Semester 3-4</td>
            <td style="background-color: #ADD8E6; border: 1px solid #000000; text-align: center;">Semester 5-6</td>
        </tr>
    </tfoot>
</table>
