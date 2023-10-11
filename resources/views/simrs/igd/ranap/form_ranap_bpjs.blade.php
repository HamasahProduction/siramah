@extends('adminlte::page')

@section('title', 'RANAP BPJS')
@section('content_header')
    <h1>RANAP BPJS : {{ $pasien->nama_px }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-md-3">
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <h3 class="profile-username text-center">{{ $pasien->nama_px }}</h3>
                            <p class="text-muted text-center">RM : {{ $pasien->no_rm }}</p>
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item"><b>Jrnis Kelamin :
                                        {{ $pasien->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' }}</b></li>
                                <li class="list-group-item"><b>Alamat : {{ $pasien->alamat }}</b></li>
                                <li class="list-group-item"><b>NIK : {{ $pasien->nik_bpjs }}</b></li>
                                <li class="list-group-item"><b>BPJS :
                                        {{ $pasien->no_Bpjs == null ? 'tidak punya bpjs' : $pasien->no_Bpjs }}</b></li>
                            </ul>
                            <a class="btn btn-primary bg-gradient-primary btn-block"><b>--</b></a>
                        </div>
                    </div>

                </div>
                <div class="col-md-9">
                    <x-adminlte-card theme="primary" collapsible title="Riwayat Kunjungan :">
                        @php
                            $heads = ['Kunjungan', 'Unit', 'Tanggal Masuk', 'Tanggal keluar', 'Penjamin', 'Petugas'];
                            $config['order'] = ['0', 'asc'];
                            $config['paging'] = false;
                            $config['info'] = false;
                            $config['scrollY'] = '450px';
                            $config['scrollCollapse'] = true;
                            $config['scrollX'] = true;
                        @endphp
                        <x-adminlte-datatable id="table" class="text-xs" :heads="$heads" :config="$config" striped
                            bordered hoverable compressed>
                            @foreach ($kunjungan as $item)
                                <tr>
                                    <td>{{ $item->counter }}</td>
                                    <td>{{ $item->kode_kunjungan }}</td>
                                    <td>{{ $item->unit->nama_unit }}</td>
                                    <td>{{ $item->tgl_masuk }}</td>
                                    <td>{{ $item->tgl_keluar == null ? 'pasien belum keluar' : $item->tgl_keluar }}</td>
                                    <td>{{ $item->nama_penjamin }}</td>
                                    <td>{{ $item->nama_user }}</td>
                                </tr>
                            @endforeach
                        </x-adminlte-datatable>
                    </x-adminlte-card>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <button type="button" class="btn btn-block bg-gradient-success btn-sm mb-2">BUAT
                                SEP</button>
                        </div>
                        <div class="card-body">
                            <p>Cari Data <code>Rujukan</code> atau <code>Riwayat SEP</code> pasien: </p>
                            <a class="btn btn-app bg-success"><i class="fas fa-external-link-alt"></i> Rujukan </a>
                            <a class="btn btn-app bg-danger"><i class="fas fa-clipboard-list"></i> Riwayat SEP </a>
                            <p>Surat Kontrol <code>(SK)</code> :</p>
                            <a class="btn btn-app bg-warning"><i class="fas fa-edit"></i>Buat <code>(SK)</code> </a>
                            <a class="btn btn-app bg-info"><i class="fas fa-search"></i>Cari <code>(SK)</code> </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="row">
                        <div class="col-lg-12">
                            <x-adminlte-card theme="success" id="div_ranap" icon="fas fa-info-circle" collapsible
                                title="Form Pendaftaran">
                                <form action="{{ route('pasienranap.store') }}" method="post" id="submitRanap">
                                    @csrf
                                    <input type="hidden" name="kodeKunjungan" value=" {{ $refKunj }}">
                                    <input type="hidden" name="noMR" value=" {{ $pasien->no_rm }}">
                                    <input type="hidden" name="idRuangan" id="ruanganSend">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <x-adminlte-input name="nama_pasien"
                                                                value="{{ $pasien->nama_px }}" disabled label="Nama Pasien"
                                                                enable-old-support>
                                                                <x-slot name="prependSlot">
                                                                    <div class="input-group-text text-olive">
                                                                        {{ $pasien->no_rm }}</div>
                                                                </x-slot>
                                                            </x-adminlte-input>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-input name="nik_pasien"
                                                                value="{{ $pasien->nik_bpjs }}" disabled label="NIK Pasien"
                                                                enable-old-support>
                                                                <x-slot name="prependSlot">
                                                                    <div class="input-group-text text-olive">
                                                                        NIK PASIEN</div>
                                                                </x-slot>
                                                            </x-adminlte-input>
                                                        </div>
                                                        <div class="col-md-6">
                                                            @php
                                                                $config = ['format' => 'YYYY-MM-DD'];
                                                            @endphp
                                                            <x-adminlte-input-date name="tanggal_daftar"
                                                                value="{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                                                                label="Tanggal Masuk" :config="$config" />
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-input name="noTelp" label="No Telp"
                                                                placeholder="masukan no telp" label-class="text-black">
                                                                <x-slot name="prependSlot">
                                                                    <div class="input-group-text">
                                                                        <i class="fas fa-phone text-black"></i>
                                                                    </div>
                                                                </x-slot>
                                                            </x-adminlte-input>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-input name="hak_kelas" value={{$kodeKelas}} placeholder="{{$kelas}}" label="Hak Kelas"
                                                                id="hakKelas" disabled />
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-select name="penjamin_id" label="Pilih Penjamin">
                                                                <option value="">--Pilih Penjamin--</option>
                                                                @foreach ($penjamin as $item)
                                                                    <option value="{{ $item->kode_penjamin }}">
                                                                        {{ $item->nama_penjamin }}</option>
                                                                @endforeach
                                                            </x-adminlte-select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-select name="alasan_masuk_id"
                                                                label="Alasan Pendaftaran">
                                                                <option value="">--Pilih Alasan--</option>
                                                                @foreach ($alasanmasuk as $item)
                                                                    <option value="{{ $item->id }}">
                                                                        {{ $item->alasan_masuk }}</option>
                                                                @endforeach
                                                            </x-adminlte-select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <x-adminlte-select name="dpjp" label="pilih dpjp">
                                                                <option value="">--Pilih Dpjp--</option>
                                                                @foreach ($paramedis as $item)
                                                                    <option value="{{ $item->kode_paramedis }}">
                                                                        {{ $item->nama_paramedis }}</option>
                                                                @endforeach
                                                            </x-adminlte-select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <x-adminlte-button type="submit"
                                            class="withLoad btn btn-sm m-1 bg-green float-right" form="submitRanap"
                                            label="Simpan Data" />
                                        <a href="{{ route('kunjungan.ranap') }}"
                                            class="btn btn-secondary btn-flat m-1 btn-sm float-right">Kembali</a>
                                    </div>
                                </form>
                            </x-adminlte-card>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.TempusDominusBs4', true)
@section('plugins.Sweetalert2', true)
@section('js')
    <script>
        const select = document.getElementById('pilihPendaftaran');
        const pilihUnit = document.getElementById('pilihUnit');

        function showDiv(select) {
            if (select.value == 0) {
                document.getElementById('div_rajal').style.display = "block";
                document.getElementById('div_ranap').style.display = "none";
                document.getElementById('div_ruangan').style.display = "none";
            } else {
                document.getElementById('div_ranap').style.display = "block";
                document.getElementById('div_ruangan').style.display = "block";
                document.getElementById('div_rajal').style.display = "none";
            }
        }

        function showUnit(pilihUnit) {
            if (pilihUnit.value == 0) {
                document.getElementById('ugd').style.display = "block";
                document.getElementById('ugd_keb').style.display = "none";
                document.getElementById('umum').style.display = "none";
            } else if (pilihUnit.value == 1) {
                document.getElementById('ugd').style.display = "none";
                document.getElementById('ugd_keb').style.display = "block";
                document.getElementById('umum').style.display = "none";
            } else if (pilihUnit.value == 2) {
                document.getElementById('ugd').style.display = "none";
                document.getElementById('ugd_keb').style.display = "none";
                document.getElementById('umum').style.display = "block";
            } else {
                document.getElementById('ugd').style.display = "none";
                document.getElementById('ugd_keb').style.display = "none";
                document.getElementById('umum').style.display = "none";
            }
        }
    </script>
@endsection
@section('js')
    <script>
        $('#cariRuangan').on('click', function() {
            // $("#pilihRuangan").show();
            var unit = $('#unitTerpilih').val();
            var kelas = $('#r_kelas_id').val();
            $('#hakKelas').val(kelas);
            $('#hakKelas').text('Kelas ' + kelas);
            if (kelas) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('bed-ruangan.get') }}?unit=" + unit + '&kelas=' + kelas,
                    dataType: 'JSON',
                    success: function(res) {
                        if (res) {
                            $.each(res.bed, function(key, value) {
                                $("#idRuangan").append(
                                    '<div class="position-relative p-3 m-2 bg-green ruanganCheck" onclick="chooseRuangan(' +
                                    value.id_ruangan + ', `' + value.nama_kamar + '`, ' +
                                    value.no_bed +
                                    ')" style="height: 100px; width: 150px; margin=5px; border-radius: 2%;"><div class="ribbon-wrapper ribbon-sm"><div class="ribbon bg-warning text-sm">KOSONG</div></div><h6 class="text-left">"' +
                                    value.nama_kamar + '"</h6> <br> NO BED : "' + value
                                    .no_bed + '"<br></div></div></div>');
                            });
                        } else {
                            $("#bed_byruangan").empty();
                        }
                    }
                });
            } else {
                $("#bed_byruangan").empty();
            }
        });

        function chooseRuangan(id, nama, bed) {
            swal.fire({
                icon: 'question',
                title: 'ANDA YAKIN PILIH RUANGAN ' + nama + ' No ' + bed + ' ?',
                showDenyButton: true,
                confirmButtonText: 'Pilih',
                denyButtonText: `Batal`,
            }).then((result) => {
                if (result.isConfirmed) {
                    //
                    $("#ruanganSend").val(id);
                    $('#pilihRuangan').modal('toggle');
                    $("#showRuangan").text('RUANGAN : ' + nama);
                    $("#showBed").text('NO : ' + bed);
                    $("#showRuangan").css("display", "block");
                    $("#showBed").css("display", "block");
                    $(".ruanganCheck").remove();
                }
            })
        }

        function batalPilih() {
            $(".ruanganCheck").remove();
        }
    </script>
@endsection
