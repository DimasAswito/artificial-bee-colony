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
                @php
                    $isJumat = $hari->nama_hari === 'Jumat';
                    $jamMulaiStr = \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i');
                @endphp

                @if($isJumat && ($jamMulaiStr == '11:00' || $jamMulaiStr == '11:30'))
                    <tr>
                        @if($jamMulaiStr == '11:00')
                            <td rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; background-color: #d3d3d3; font-weight: bold;">
                                11.00 - 13.00
                            </td>
                            <td rowspan="2" colspan="{{ $ruangans->count() }}" style="border: 1px solid #000000; text-align: center; vertical-align: middle; background-color: #d3d3d3; font-weight: bold;">
                                SHOLAT JUMAT
                            </td>
                        @endif
                    </tr>
                    @continue
                @endif

                <tr>
                    {{-- Render Hari Column only for the first Jam of the day --}}
                    @if($index === 0)
                        <td rowspan="{{ $hari->nama_hari === 'Jumat' ? $jams->count() : $jams->count() + 1 }}" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">
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
                            @php
                                $rowSpan = $durations[$cellData->id] ?? 2;
                            @endphp
                            <td rowspan="{{ $rowSpan }}" style="background-color: {{ $bgColor }}; border: 1px solid #000000; text-align: center; vertical-align: middle;">
                                <b>{{ $cellData->mataKuliah->nama_matkul }}</b>
                                <br>
                                <i>{{ $cellData->dosen->nama_dosen }}</i>
                            </td>
                        @else
                            <td style="border: 1px solid #000000;"></td>
                        @endif
                    @endforeach
                </tr>
                {{-- Inject Break Row automatically after the 12:00 ending slot --}}
                @if(\Carbon\Carbon::parse($jam->jam_selesai)->format('H:i') == '12:00' && $hari->nama_hari !== 'Jumat')
                    <tr>
                        <td style="border: 1px solid #000000; text-align: center; vertical-align: middle; background-color: #d3d3d3; font-weight: bold;">
                            12.00 - 13.00
                        </td>
                        <td colspan="{{ $ruangans->count() }}" style="border: 1px solid #000000; text-align: center; vertical-align: middle; background-color: #d3d3d3; font-weight: bold;">
                            ISTIRAHAT
                        </td>
                    </tr>
                @endif
                
            @endforeach
            <!-- Separator Row Between Days -->
            <tr>
                <td colspan="{{ 2 + $ruangans->count() }}" style="border-top: 2px solid #000000;"></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr></tr>
        <tr><td colspan="{{ 2 + $ruangans->count() }}" style="background-color: #FCE883; border: 1px solid #000000;">Semester 1-2 : Kuning</td></tr>
        <tr><td colspan="{{ 2 + $ruangans->count() }}" style="background-color: #90EE90; border: 1px solid #000000;">Semester 3-4 : Hijau</td></tr>
        <tr><td colspan="{{ 2 + $ruangans->count() }}" style="background-color: #ADD8E6; border: 1px solid #000000;">Semester 5-6 : Biru</td></tr>
    </tfoot>
</table>
