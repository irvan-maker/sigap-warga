<!doctype html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 18mm 22mm 20mm 22mm;
        }

        body {
            font-family: "DejaVu Serif", serif;
            font-size: 11.5px;
            line-height: 1.55;
            color: #111;
        }

        .kop {
            text-align: center;
            border-bottom: 3px double #111;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }

        .kop .pemerintah {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .kop .kecamatan {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }

        .kop .desa {
            font-size: 19px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 1px 0;
        }

        .kop .alamat {
            font-size: 10px;
            margin: 2px 0 0;
        }

        .judul {
            text-align: center;
            margin: 18px 0 20px;
        }

        .judul .nama-surat {
            display: inline-block;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .judul .nomor {
            margin-top: 2px;
            font-size: 11px;
        }

        .paragraph {
            text-align: justify;
            margin: 10px 0;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 16px;
        }

        table.data td {
            padding: 2px 3px;
            vertical-align: top;
        }

        table.data td.label {
            width: 31%;
        }

        table.data td.colon {
            width: 2%;
        }

        .dynamic-title {
            font-weight: bold;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .signature {
            width: 46%;
            margin-left: auto;
            text-align: center;
            margin-top: 28px;
        }

        .signature p {
            margin: 2px 0;
        }

        .signature-space {
            height: 65px;
        }

        .signer {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            border-top: 1px solid #999;
            padding-top: 4px;
            font-size: 8px;
            text-align: center;
            color: #555;
        }
    </style>
</head>

<body>

    <div class="kop">
        <p class="pemerintah">
            PEMERINTAH {{ strtoupper(config('village.regency', 'KABUPATEN TANGERANG')) }}
        </p>

        <p class="kecamatan">
            KECAMATAN {{ strtoupper(config('village.district', 'KELAPA DUA')) }}
        </p>

        <p class="desa">
            {{ strtoupper(config('village.name', 'DESA CURUG SANGERENG')) }}
        </p>

        @if(config('village.address'))
            <p class="alamat">
                {{ config('village.address') }}
            </p>
        @endif
    </div>

    <div class="judul">
        <div class="nama-surat">
            {{ $letter->typeLabel() }}
        </div>

        <div class="nomor">
            Nomor: {{ $letter->letter_number ?: '-' }}
        </div>
    </div>

    <p class="paragraph">
        Yang bertanda tangan di bawah ini menerangkan bahwa:
    </p>

    <table class="data">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td><strong>{{ $letter->citizen->name }}</strong></td>
        </tr>

        <tr>
            <td class="label">NIK</td>
            <td class="colon">:</td>
            <td>{{ $letter->citizen->nik ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Nomor KK</td>
            <td class="colon">:</td>
            <td>{{ $letter->citizen->familyCard?->family_number ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Kepala Keluarga</td>
            <td class="colon">:</td>
            <td>{{ $letter->citizen->familyCard?->headCitizen?->name ?: '-' }}</td>
        </tr>

        <tr>
            <td class="label">Alamat</td>
            <td class="colon">:</td>
            <td>
                {{ $letter->citizen->address
                    ?: $letter->citizen->familyCard?->address
                    ?: '-' }}
            </td>
        </tr>

        <tr>
            <td class="label">RT / RW</td>
            <td class="colon">:</td>
            <td>
                {{ $letter->rt?->code ?: '-' }}
                /
                {{ $letter->rt?->rw?->code ?: '-' }}
            </td>
        </tr>
    </table>

    @if($letter->submission?->fieldValues?->isNotEmpty())
        <div class="dynamic-title">
            Keterangan Pengajuan
        </div>

        <table class="data">
            @foreach($letter->submission->fieldValues->sortBy('sequence') as $field)
                <tr>
                    <td class="label">{{ $field->field_label }}</td>
                    <td class="colon">:</td>

                    <td>
                        @php
                            $value = $field->submitted_value;
                        @endphp

                        @if($value === null || $value === '')
                            -
                        @elseif(is_bool($value))
                            {{ $value ? 'Ya' : 'Tidak' }}
                        @elseif(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($letter->purpose)
        <p class="paragraph">
            Surat keterangan ini diberikan kepada yang bersangkutan untuk keperluan:
            <strong>{{ $letter->purpose }}</strong>.
        </p>
    @endif

    @if($letter->notes)
        <p class="paragraph">
            Keterangan tambahan: {{ $letter->notes }}
        </p>
    @endif

    <p class="paragraph">
        Demikian surat keterangan ini dibuat dengan sebenarnya agar dapat
        dipergunakan sebagaimana mestinya.
    </p>

    <div class="signature">
        <p>
            {{ config('village.name', 'Curug Sangereng') }},
            {{ $letter->issued_at?->locale('id')->isoFormat('D MMMM Y') ?: now()->locale('id')->isoFormat('D MMMM Y') }}
        </p>

        <p>
            {{ config('village.signatory_position', 'Kepala Desa') }}
        </p>

        <div class="signature-space"></div>

        <div class="signer">
            {{ $signer?->name
                ?: $letter->approver?->name
                ?: config('village.signatory_name', 'KEPALA DESA') }}
        </div>
    </div>

    <div class="footer">
        Dokumen administrasi {{ config('village.name', 'Desa Curug Sangereng') }}.
        Nomor surat dapat diverifikasi melalui arsip resmi SIGAP WARGA.
    </div>

</body>
</html>