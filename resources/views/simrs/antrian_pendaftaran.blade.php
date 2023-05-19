@extends('adminlte::page')

@section('title', 'Antrian Pendaftaran')

@section('content_header')
    <h1>Antrian Pendaftaran Loket {{ $request->loket ?? '' }} Lantai {{ $request->lantai ?? '' }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Filter Data Antrian" theme="secondary" collapsible>
                <form action="{{ route('antrian.pendaftaran') }}" method="get">
                    <div class="row">
                        <div class="col-md-3">
                            <x-adminlte-input name="user" label="User" readonly value="{{ auth()->user()->name }}" />
                        </div>
                        <div class="col-md-3">
                            @php
                                $config = ['format' => 'YYYY-MM-DD'];
                            @endphp
                            <x-adminlte-input-date name="tanggal" label="Tanggal Antrian" :config="$config"
                                value="{{ \Carbon\Carbon::parse($request->tanggal)->format('Y-m-d') }}">
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-primary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input-date>
                        </div>
                        <div class="col-md-3">
                            <x-adminlte-select name="loket" label="Loket">
                                <x-adminlte-options :options="[
                                    1 => 'Loket 1',
                                    2 => 'Loket 2',
                                    3 => 'Loket 3',
                                    4 => 'Loket 4',
                                    5 => 'Loket 5',
                                ]" :selected="$request->loket ?? 1" />
                            </x-adminlte-select>
                        </div>
                        <div class="col-md-3">
                            <x-adminlte-select name="lantai" label="Lantai">
                                <x-adminlte-options :options="[
                                    1 => 'Lantai 1',
                                    2 => 'Lantai 2',
                                    3 => 'Lantai 3',
                                    4 => 'Lantai 4',
                                    5 => 'Lantai 5',
                                ]" :selected="$request->lantai ?? 1" />
                            </x-adminlte-select>
                        </div>
                    </div>
                    <x-adminlte-button type="submit" class="withLoad" theme="primary" label="Submit Antrian" />
                </form>
            </x-adminlte-card>
            @if (isset($request->loket) && isset($request->lantai) && isset($request->tanggal))
                <div class="row">
                    {{-- info box --}}
                    <div class="col-md-3">
                        <x-adminlte-small-box title="{{ $antrians->where('taskid', 2)->first()->angkaantrean ?? '0' }}"
                            text="Antrian Saat Ini" theme="primary" class="withLoad" icon="fas fa-sign-in-alt"
                            url="" url-text="Batalkan Antrian" />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-small-box title="{{ $antrians->where('taskid', 1)->first()->angkaantrean ?? '0' }}"
                            class="withLoad" text="Antrian Selanjutnya" theme="success" icon="fas fa-sign-in-alt"
                            url="{{ route('antrian.panggil_pendaftaran', [$antrians->where('taskid', 1)->first()->kodebooking ?? '0', $request->loket, $request->lantai]) }}"
                            url-text="Panggil Antrian Selanjutnya" />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-small-box
                            title="{{ $antrians->where('taskid', '<', 2)->where('taskid', '>=', 1)->count() }}"
                            text="Sisa Antrian" theme="warning" icon="fas fa-sign-in-alt" />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-small-box title="{{ $antrians->count() }}" text="Total Antrian" theme="success"
                            icon="fas fa-sign-in-alt" />
                    </div>
                    {{-- antrian sedang dipanggil --}}
                    <div class="col-md-12">
                        <x-adminlte-card
                            title="Antrian Pendaftaran Sedang Dilayani ({{ $antrians->where('taskid', 2)->count() }} Orang)"
                            theme="primary" icon="fas fa-info-circle" collapsible>
                            @if ($errors->any())
                                <x-adminlte-alert title="Ops Terjadi Masalah !" theme="danger" dismissable>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </x-adminlte-alert>
                            @endif
                            @php
                                $heads = ['No', 'Kode', 'Poliklinik / Dokter', 'No RM / NIK', 'Jenis / Pasien', 'No Kartu / Rujukan', 'Status', 'Action'];
                                $config['order'] = ['7', 'asc'];
                            @endphp
                            <x-adminlte-datatable id="table3" class="nowrap" :heads="$heads" :config="$config" striped
                                bordered hoverable compressed>
                                @foreach ($antrians->where('taskid', '==', 2) as $item)
                                    <tr>
                                        <td>{{ $item->angkaantrean }}</td>
                                        <td>{{ $item->kodebooking }}<br>
                                            {{ $item->nomorantrean }}
                                        </td>
                                        <td>{{ $item->namapoli }} {{ $item->jampraktek }}<br>{{ $item->namadokter }}</td>
                                        <td>
                                            {{ $item->norm }} <br>
                                            {{ $item->nik }}
                                        </td>
                                        <td>
                                            {{ $item->nama }}<br>
                                            {{ $item->jenispasien }}
                                            @if ($item->pasienbaru == 1)
                                                <span class="badge bg-secondary">{{ $item->pasienbaru }}. Baru</span>
                                            @endif
                                            @if ($item->pasienbaru == 0)
                                                <span class="badge bg-secondary">{{ $item->pasienbaru }}. Lama</span>
                                            @endif
                                        </td>
                                        <td>
                                            @isset($item->nomorkartu)
                                                {{ $item->nomorkartu }}
                                            @endisset
                                            @isset($item->nomorkartu)
                                                <br> {{ $item->nomorreferensi }}
                                            @endisset
                                        </td>
                                        <td>
                                            {{-- {{ $item->taskid }} --}}
                                            @if ($item->taskid == 0)
                                                <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                            @endif
                                            @if ($item->taskid == 1)
                                                <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                            @endif
                                            @if ($item->taskid == 2)
                                                <span class="badge bg-primary">{{ $item->taskid }}. Proses
                                                    Pendaftaran</span>
                                            @endif
                                            @if ($item->taskid == 3)
                                                @if ($item->status_api == 0)
                                                    <span class="badge bg-warning">2. Belum Pembayaran</span>
                                                @else
                                                    <span class="badge bg-success">{{ $item->taskid }}. Tunggu Poli</span>
                                                @endif
                                            @endif
                                            @if ($item->taskid >= 4 && $item->taskid <= 7)
                                                <span class="badge bg-success">{{ $item->taskid }}. Pelayanan Poli</span>
                                            @endif
                                            @if ($item->taskid == 99)
                                                <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                            @endif

                                        </td>
                                        <td>
                                            @if ($item->taskid <= 2)
                                                @if ($item->taskid == 1)
                                                    {{-- panggil pertama --}}
                                                    <x-adminlte-button class="btn-xs" label="Panggil" theme="success"
                                                        icon="fas fa-volume-down" data-toggle="tooltip" title="Panggil"
                                                        onclick="window.location='{{ route('antrian.panggil_pendaftaran', [$item->kodebooking, $request->loket, $request->lantai]) }}'" />
                                                @endif
                                                @if ($item->taskid == 2)
                                                    {{-- panggil ulang --}}
                                                    <x-adminlte-button class="btn-xs withLoad" label="Panggil"
                                                        theme="primary" icon="fas fa-volume-down" data-toggle="tooltip"
                                                        title="Panggil Ulang"
                                                        onclick="window.location='{{ route('antrian.panggil_pendaftaran', [$item->kodebooking, $request->loket, $request->lantai]) }}'" />
                                                    @if ($item->pasienbaru == 1)
                                                        <x-adminlte-button class="btn-xs btnDaftarOnline" label="Daftar"
                                                            theme="success" icon="fas fa-hand-holding-medical"
                                                            data-toggle="tooltip" title="Daftar Online"
                                                            data-id="{{ $item->id }}" />
                                                    @endif
                                                    @if ($item->pasienbaru == 0)
                                                        <x-adminlte-button class="btn-xs btnDaftarOnline" label="Daftar"
                                                            theme="success" icon="fas fa-hand-holding-medical"
                                                            data-toggle="tooltip" title="Daftar Online"
                                                            data-id="{{ $item->id }}" />
                                                    @endif
                                                    @if ($item->pasienbaru == 2)
                                                        <x-adminlte-button class="btn-xs btnDaftarOfflineJKN withLoad"
                                                            label="Daftar" theme="success"
                                                            icon="fas fa-hand-holding-medical" data-toggle="tooltip"
                                                            title="Daftar" data-id="{{ $item->id }}" />
                                                        {{-- <x-adminlte-button class="btn-xs btnDaftarOfflineUmum withLoad"
                                                            label="UMUM" theme="success"
                                                            icon="fas fa-hand-holding-medical" data-toggle="tooltip"
                                                            title="Daftar Pasien Umum" data-id="{{ $item->id }}" /> --}}
                                                    @endif
                                                @endif
                                            @endif
                                            <x-adminlte-button class="btn-xs" theme="danger" icon="fas fa-times"
                                                data-toggle="tooltip" title="Batal Antrian"
                                                onclick="window.location='{{ route('antrian.batal_antrian', $item->kodebooking) }}'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-adminlte-datatable>
                        </x-adminlte-card>
                    </div>
                    {{-- antrian belum dipanggil --}}
                    <div class="col-md-6">
                        <x-adminlte-card
                            title="Antrian Pendaftaran Belum Dilayani ({{ $antrians->where('taskid', 1)->count() }} Orang)"
                            theme="warning" icon="fas fa-info-circle" collapsible="collapsed">
                            @php
                                $heads = ['No', 'Kodebooking', 'Nama', 'Poliklinik / Dokter', 'Status', 'Action'];
                            @endphp
                            <x-adminlte-datatable id="table1" class="nowrap" :heads="$heads" striped bordered
                                hoverable compressed>
                                @foreach ($antrians->where('taskid', '==', 1) as $item)
                                    <tr>
                                        <td>{{ $item->angkaantrean }}</td>
                                        <td>{{ $item->kodebooking }}</td>
                                        <td>{{ $item->nama }} <span
                                            class="badge bg-secondary">{{ $item->jenispasien }}</span></td>
                                        <td>{{ $item->namapoli }}</td>
                                        <td>
                                            {{-- {{ $item->taskid }} --}}
                                            @if ($item->taskid == 0)
                                                <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                            @endif
                                            @if ($item->taskid == 1)
                                                <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                            @endif
                                            @if ($item->taskid == 2)
                                                <span class="badge bg-primary">{{ $item->taskid }}. Proses
                                                    Pendaftaran</span>
                                            @endif
                                            @if ($item->taskid == 3)
                                                @if ($item->status_api == 0)
                                                    <span class="badge bg-warning">2. Belum Pembayaran</span>
                                                @else
                                                    <span class="badge bg-success">{{ $item->taskid }}. Tunggu Poli</span>
                                                @endif
                                            @endif
                                            @if ($item->taskid >= 4 && $item->taskid <= 7)
                                                <span class="badge bg-success">{{ $item->taskid }}. Pelayanan Poli</span>
                                            @endif
                                            @if ($item->taskid == 99)
                                                <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                            @endif

                                        </td>
                                        <td>
                                            <x-adminlte-button class="btn-xs" label="Panggil" theme="success"
                                                icon="fas fa-volume-down" data-toggle="tooltip" title="Panggil"
                                                onclick="window.location='{{ route('antrian.panggil_pendaftaran', [$item->kodebooking, $request->loket, $request->lantai]) }}'" />
                                            <x-adminlte-button class="btn-xs" theme="danger" icon="fas fa-times"
                                                data-toggle="tooltip" title="Batal Antrian"
                                                onclick="window.location='{{ route('antrian.batal_antrian', $item->kodebooking) }}'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-adminlte-datatable>
                        </x-adminlte-card>
                    </div>
                    {{-- antrian belum checkin --}}
                    <div class="col-md-6">
                        <x-adminlte-card
                            title="Antrian Pendaftaran Belum Checkin ({{ $antrians->where('taskid', 0)->count() }} Orang)"
                            theme="secondary" icon="fas fa-info-circle" collapsible="collapsed">
                            @php
                                $heads = ['No', 'Kodebooking', 'Nama', 'Poliklinik', 'Status'];
                            @endphp
                            <x-adminlte-datatable id="table2" class="nowrap" :heads="$heads" striped bordered
                                hoverable compressed>
                                @foreach ($antrians->where('taskid', 0) as $item)
                                    <tr>
                                        <td>{{ $item->angkaantrean }}</td>
                                        <td>{{ $item->kodebooking }}</td>
                                        <td>{{ $item->nama }} <span
                                                class="badge bg-secondary">{{ $item->jenispasien }}</span></td>
                                        <td>{{ $item->namapoli }}</td>
                                        <td>
                                            @if ($item->taskid == 0)
                                                <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                            @endif
                                            @if ($item->taskid == 1)
                                                <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                            @endif
                                            @if ($item->taskid == 99)
                                                <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                            @endif
                                            <x-adminlte-button class="btn-xs" theme="danger" icon="fas fa-times"
                                                data-toggle="tooltip" title="Batal Antrian"
                                                onclick="window.location='{{ route('antrian.batal_antrian', $item->kodebooking) }}'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-adminlte-datatable>
                        </x-adminlte-card>
                    </div>
                    {{-- antrian belum dipanggil --}}
                    <div class="col-md-12">
                        <x-adminlte-card
                            title="Antrian Pendaftaran Sudah Dilayani ({{ $antrians->where('taskid', '>', 2)->count() }} Orang)"
                            theme="success" icon="fas fa-info-circle" collapsible="collapsed">
                            @php
                                $heads = ['No', 'Kode', 'Tanggal', 'No RM / NIK', 'Jenis / Pasien', 'No Kartu / Rujukan', 'Poliklinik / Dokter', 'Status', 'Action'];
                                $config['order'] = ['7', 'asc'];
                            @endphp
                            <x-adminlte-datatable id="table8" class="nowrap" :heads="$heads" :config="$config"
                                striped bordered hoverable compressed>
                                @foreach ($antrians->where('taskid', '>', 2) as $item)
                                    <tr>
                                        <td>{{ $item->angkaantrean }}</td>
                                        <td>{{ $item->kodebooking }}<br>
                                            {{ $item->nomorantrean }}
                                        </td>
                                        <td>{{ $item->tanggalperiksa }}</td>
                                        <td>
                                            {{ $item->norm }} <br>
                                            {{ $item->nik }}
                                        </td>
                                        <td>
                                            {{ $item->nama }}<br>
                                            {{ $item->jenispasien }}
                                            @if ($item->pasienbaru == 1)
                                                <span class="badge bg-secondary">{{ $item->pasienbaru }}. Baru</span>
                                            @endif
                                            @if ($item->pasienbaru == 0)
                                                <span class="badge bg-secondary">{{ $item->pasienbaru }}. Lama</span>
                                            @endif
                                        </td>
                                        <td>
                                            @isset($item->nomorkartu)
                                                {{ $item->nomorkartu }}
                                            @endisset
                                            @isset($item->nomorkartu)
                                                <br> {{ $item->nomorreferensi }}
                                            @endisset
                                        </td>
                                        <td>{{ $item->namapoli }}<br>{{ $item->namadokter }}
                                        </td>
                                        <td>
                                            {{-- {{ $item->taskid }} --}}
                                            @if ($item->taskid == 0)
                                                <span class="badge bg-secondary">{{ $item->taskid }}. Belum Checkin</span>
                                            @endif
                                            @if ($item->taskid == 1)
                                                <span class="badge bg-warning">{{ $item->taskid }}. Checkin</span>
                                            @endif
                                            @if ($item->taskid == 2)
                                                <span class="badge bg-primary">{{ $item->taskid }}. Proses
                                                    Pendaftaran</span>
                                            @endif
                                            @if ($item->taskid == 3)
                                                @if ($item->status_api == 0)
                                                    <span class="badge bg-warning">2. Belum Pembayaran</span>
                                                @else
                                                    <span class="badge bg-success">{{ $item->taskid }}. Tunggu
                                                        Poli</span>
                                                @endif
                                            @endif
                                            @if ($item->taskid >= 4 && $item->taskid <= 7)
                                                <span class="badge bg-success">{{ $item->taskid }}. Pelayanan Poli</span>
                                            @endif
                                            @if ($item->taskid == 99)
                                                <span class="badge bg-danger">{{ $item->taskid }}. Batal</span>
                                            @endif

                                        </td>
                                        <td>
                                            <x-adminlte-button class="btn-xs" theme="danger" icon="fas fa-times"
                                                data-toggle="tooltip" title="Batal Antrian"
                                                onclick="window.location='{{ route('antrian.batal_antrian', $item->kodebooking) }}'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-adminlte-datatable>
                        </x-adminlte-card>
                    </div>
                </div>
            @endif
        </div>
    </div>

@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)
@section('js')
    <script>
        $(function() {
            // $.ajaxSetup({
            //     headers: {
            //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            //     }
            // });
            // klim daftar pasien offline
            $('.btnDaftarOfflineJKN').click(function() {
                var antrianid = $(this).data('id');
                $.LoadingOverlay("show");
                $.get("{{ route('antrian.index') }}" + '/' + antrianid + '/edit', function(data) {
                    // console.log($data);
                    $('#kodebooking').html(data.kodebooking);
                    $('#angkaantrean').html(data.angkaantrean);
                    $('#nomorantrean').html(data.nomorantrean);
                    $('#user').html(data.user);
                    $('#antrianid').val(antrianid);
                    $('#namapoli').val(data.namapoli);
                    $('#namadokter').val(data.namadokter);
                    $('#kodepoli').val(data.kodepoli);
                    $('#kodedokter').val(data.kodedokter);
                    $('#jampraktek').val(data.jampraktek);
                    // $('#kodepoli').val(data.kodepoli).trigger('change');
                    $('#modalDaftarOfflineJKN').modal('show');
                    $.LoadingOverlay("hide", true);
                })
            });
            // klim daftar pasien online
            $('.btnDaftarOnline').click(function() {
                var antrianid = $(this).data('id');
                $.LoadingOverlay("show");
                $.get("{{ route('antrian.index') }}" + '/' + antrianid + '/edit', function(data) {
                    $('#kodebookingOn').html(data.kodebooking);
                    $('#angkaantreanOn').html(data.angkaantrean);
                    $('#nomorantreanOn').html(data.nomorantrean);
                    $('#userOn').html(data.user);
                    $('#tanggalperiksaOn').html(data.tanggalperiksa);
                    $('#jeniskunjunganOn').html(data.jeniskunjungan);
                    $('#kodepoliOn').html(data.kodepoli);
                    $('#namapoliOn').html(data.namapoli);
                    $('#kodedokterOn').html(data.kodedokter);
                    $('#namadokterOn').html(data.namadokter);
                    $('#jampraktekOn').html(data.jampraktek);

                    $('#antrianidOn').val(antrianid);
                    $('#nikOn').val(data.nik);
                    $('#normOn').val(data.norm);
                    if (data.pasienbaru == 1) {
                        $('#statuspasienOn').val('BARU');
                    } else {
                        $('#statuspasienOn').val('LAMA');
                    }
                    $('#modalDaftarOnline').modal('show');
                    $.LoadingOverlay("hide", true);
                })
            });
        });
    </script>
    {{-- js cari pasien baru / lama offline --}}
    <script>
        $(function() {
            $('#formPasien').hide();
            $('#cariNIK').on('click', function() {
                var nik = $('#nik').val();
                if (nik == '') {
                    alert('NIK tidak boleh kosong');
                } else {
                    $.LoadingOverlay("show");
                    $.get("{{ route('antrian.index') }}" + "/cari_pasien/" + nik, function(data) {
                        console.log(data.metadata.code);
                        if (data.metadata.code == 200) {
                            $('#pasienDitemukan').html(data.metadata.message);
                            $('#pasienTidakDitemukan').html('');
                            $('#namaPasien').html('');
                            $('#nohpPasien').html('');
                            $('#nohp').val(data.response.no_tlp);
                            $('#nama').val(data.response.nama_px);
                            $('#norm').val(data.response.no_rm);
                            $('#nomorkartu').val(data.response.no_Bpjs);
                            $('#statuspasien').val('LAMA');
                            $('#formPasien').hide();
                        } else {
                            $('#pasienTidakDitemukan').html(data.metadata.message);
                            $('#pasienDitemukan').html('');
                            $('#namaPasien').html('Wajib disi');
                            $('#nohpPasien').html('Wajib disi');
                            $('#nohp').val('');
                            $('#nama').val('');
                            $('#norm').val('');
                            $('#nomorkartu').val('');
                            $('#statuspasien').val('BARU');
                            $('#formPasien').show();
                        }
                        $.LoadingOverlay("hide", true);
                    })
                }
            });
        });
    </script>
    {{-- js cari pasien baru / lama online --}}
    <script>
        $(function() {
            $('#formPasienOn').hide();
            $('#cariNIKOn').on('click', function() {
                var nik = $('#nikOn').val();
                if (nik == '') {
                    alert('NIK tidak boleh kosong');
                } else {
                    $.LoadingOverlay("show");
                    $.get("{{ route('antrian.index') }}" + "/cari_pasien/" + nik, function(data) {
                        console.log(data.metadata.code);
                        if (data.metadata.code == 200) {
                            $('#pasienDitemukanOn').html(data.metadata.message +
                                ", Silahkan lengkapi data pasien");
                            $('#pasienTidakDitemukanOn').html('');

                            $('#nomorkkOn').val(data.response.nik_bpjs);
                            $('#namaOn').val(data.response.nama_px);
                            $('#nohpOn').val(data.response.no_tlp);
                            $('#normOn').val(data.response.no_rm);
                            $('#nomorkartuOn').val(data.response.no_Bpjs);
                            $('#jeniskelaminOn').val(data.response.jenis_kelamin).change();
                            $('#tanggallahirOn').val(data.response.tgl_lahir);
                            $('#alamatOn').val(data.response.alamat);
                            // $('#rtOn').val(data.response.rt);
                            // $('#rwOn').val(data.response.rw);
                            $('#kodepropOn').val(data.response.kode_propinsi).change();
                            $('#kodedati2On').val(data.response.kode_kabupaten).change();
                            $('#kodekecOn').val(data.response.kode_kecamatan).change();
                            $('#namakelOn').val(data.response.namakel);
                            // $('#kodepoli').val(data.kodepoli).trigger('change');

                            $('#formPasienOn').show();
                        } else {
                            $('#pasienTidakDitemukanOn').html(data.metadata.message);
                            $('#pasienDitemukanOn').html('');
                            $('#nomorkkOn').val('');
                            $('#nohpOn').val('');
                            $('#namaOn').val('');
                            $('#nomorkartuOn').val('');
                            $('#formPasienOn').show();
                        }
                        $.LoadingOverlay("hide", true);
                    })
                }
            });
        });
    </script>
    {{-- cari nomor rujukan --}}
    <script>
        $(function() {
            $('#cekRujukan').on('click', function() {
                var nomorreferensi = $('#nomorreferensi').val();
                var url = "{{ route('api.rujukan_nomor') }}" + "?nomorreferensi=" + nomorreferensi;
                $.LoadingOverlay("show");
                $.get(url, function(data1) {
                    console.log(data1);
                    if (data1.metaData.code == 200) {
                        if (data1.response.rujukan.peserta.nik != $('#nik').val()) {
                            $.LoadingOverlay("hide", true);
                            $('#rujukancek').html('NIK Pasien dengan NIK Rujukan Berbeda');
                            return alert("NIK Pasien dengan NIK Rujukan Berbeda");
                        }
                        if (data1.response.rujukan.peserta.noKartu != $('#nomorkartu').val()) {
                            $.LoadingOverlay("hide", true);
                            $('#rujukancek').html(
                                'Nomor Kartu Pasien dengan Nomor Kartu Rujukan Berbeda');
                            return alert("Nomor Kartu Pasien dengan Nomor Kartu Rujukan Berbeda");
                        }
                        $('#rujukancek').html('');
                        $('#rujukanok').html('Rujukan ditemukan');
                        // var namapoli1 =$('#kodepoli').val();
                        // var namapoli2=  data1.response.rujukan.poliRujukan.kode;
                        // alert(namapoli1 === namapoli2);
                        if (data1.response.rujukan.poliRujukan.kode != $('#kodepoli').val()) {
                            $('#suratkontrolcek').html('');
                            $('#suratkontrolok').html(
                                'Beda poliklinik tanpa menggunakan Surat Kontrol');
                            $('#nomorsuratkontrol').prop('readonly', true);
                            alert('Kunjungan Beda Poliklinik dengan Rujukan');
                        }
                        // cek rujukan ke berapa
                        else {
                            var url = "{{ route('api.rujukan_jumlah_sep') }}" +
                                "?jenisrujukan=1&nomorreferensi=" +
                                nomorreferensi;
                            $.get(url, function(data2) {
                                if (data2.metaData.code == 200) {
                                    var jumlahKunjunganRujukan = parseInt(data2.response
                                        .jumlahSEP) + 1;
                                    // jika kunjungan kedua atau lebih
                                    if (jumlahKunjunganRujukan > 1) {
                                        $('#rujukancek').html('');
                                        $('#suratkontrolok').html('');
                                        $('#rujukanok').html(
                                            'OK! Kunjungan Rujukan saat ini ke : ' +
                                            jumlahKunjunganRujukan);
                                        $('#nomorsuratkontrol').prop('readonly', false);
                                        alert(
                                            'Kunjungan kedua atau lebih harus menggunakan surat kontrol'
                                        );
                                    }
                                    // jika kunjungan pertama
                                    else {
                                        $('#suratkontrolcek').html('');
                                        $('#suratkontrolok').html(
                                            'Kunjungan pertama tidak menggunakan Surat Kontrol'
                                        );
                                        $('#nomorsuratkontrol').prop('readonly', true);
                                        alert(
                                            'Kunjungan pertama tidak menggunakan Surat Kontrol'
                                        );
                                    }
                                } else {
                                    alert("Error Jumlah SEP Rujukan : " + data2.metaData
                                        .message);
                                }
                            });
                            $('#suratkontrolcek').html('Masukan Nomor Surat Kontrol');
                            $('#nomorsuratkontrol').prop('readonly', false);
                        }
                    } else {
                        alert('Error Rujukan : ' + data1.metaData.message);
                        $('#rujukancek').html(data1.metaData
                            .message);
                        $('#rujukanok').html('');
                    }
                    $.LoadingOverlay("hide", true);
                })
            });
        });
    </script>
    {{-- js provinsi offline --}}
    <script>
        $(function() {
            $('#kodeprop').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: "{{ route('ref_kabupaten') }}",
                    method: 'POST',
                    data: {
                        provinsi: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodedati2').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodedati2').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
            $('#kodedati2').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: "{{ route('ref_kecamatan') }}",
                    method: 'POST',
                    data: {
                        kabupaten: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodekec').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodekec').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
        });
    </script>
    {{-- js provinsi online --}}
    <script>
        $(function() {
            $('#kodepropOn').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: "{{ route('ref_kabupaten') }}",
                    method: 'POST',
                    data: {
                        provinsi: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodedati2On').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodedati2On').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
            $('#kodedati2On').on('change', function() {
                $.LoadingOverlay("show");
                $.ajax({
                    url: "{{ route('ref_kecamatan') }}",
                    method: 'POST',
                    data: {
                        kabupaten: $(this).val()
                    },
                    success: function(data) {
                        console.log(data);
                        $.LoadingOverlay("hide", true);
                        $('#kodekecOn').empty();
                        $.each(data.response.list, function(item) {
                            $('#kodekecOn').append($('<option>', {
                                value: data.response.list[item].kode,
                                text: data.response.list[item].nama
                            }));
                        })
                    }
                })
            });
        });
    </script>
@endsection
