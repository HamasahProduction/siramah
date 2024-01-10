@extends('adminlte::page')

@section('title', 'RANAP BPJS')
@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h5>Form Daftar</h5>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><b>RAWAT INAP</b></li>
                    <li class="breadcrumb-item"><b>PASIEN BPJS</b></li>
                    <li class="breadcrumb-item"><a href="{{ route('list-assesment.ranap') }}"
                            class="btn btn-sm btn-flat btn-secondary">kembali</a></li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="row">
                <div class="col-md-12 ">
                    <div class="card card-primary card-outline">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <div class="row bg-primary">
                                        <div class="col-sm-3 col-6">
                                            <div class="description-block border-right">
                                                <a href="{{ route('edit-pasien', ['rm' => $pasien->no_rm]) }}"
                                                    target="__blank" class="form-group text-white">
                                                    <h5 class="description-headers">{{ $pasien->nama_px }}</h5>
                                                    <small>{{ $pasien->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' }}</small>
                                                    <br>
                                                    <span class="description-text">-Pasien-</span> <br>
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-sm-3 col-6">
                                            <div class="description-block border-right">
                                                <h5 class="description-headers">
                                                    {{ date('d F Y', strtotime($pasien->tgl_lahir)) }}</h5>
                                                <span class="description-text">-Tanggal Lahir-</span>
                                            </div>
                                        </div>

                                        <div class="col-sm-3 col-6">
                                            <div class="description-block border-right">
                                                <h5 class="description-headers">{{ $pasien->no_rm }}</h5>
                                                <span class="description-text">-No RM-</span>
                                            </div>
                                        </div>

                                        <div class="col-sm-3 col-6">
                                            <div class="description-block">
                                                <h5 class="description-headers">
                                                    NIK : {{ $pasien->nik_bpjs == null ? 'tidak ada' : $pasien->nik_bpjs }}
                                                    <br>
                                                    BPJS : {{ $pasien->no_Bpjs == null ? 'tidak ada' : $pasien->no_Bpjs }}
                                                </h5>
                                                <span class="description-text">-NIK & BPJS-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    @php
                                        $heads = ['Tgl Masuk / Unit','Kunjungan', 'Diagnosa',  'Penjamin', 'Status'];
                                        $config['order'] = ['0', 'asc'];
                                        $config['paging'] = false;
                                        $config['info'] = false;
                                        $config['scrollY'] = '300px';
                                        $config['scrollCollapse'] = true;
                                        $config['scrollX'] = true;
                                    @endphp
                                    <x-adminlte-datatable id="table" class="text-xs" :heads="$heads" :config="$config"
                                        striped bordered hoverable compressed>
                                        @foreach ($kunjungan as $item)
                                            <tr>
                                                <td>
                                                    <b>
                                                        Tgl Masuk : {{ $item->tgl_masuk }} <br>
                                                        Unit : {{ $item->unit->nama_unit }}
                                                    </b>
                                                </td>
                                                <td>
                                                    <b>
                                                        Kode : {{ $item->kode_kunjungan }} <br>
                                                        Counter : {{ $item->counter }} <br>
                                                    </b>
                                                </td>
                                                <td>{{$item->diagx ?? 'BELUM MELAKUKAN SINGKRONISASI DIAGNOSA'}}</td>
                                                <td>{{ $item->penjamin->nama_penjamin_bpjs }}</td>
                                                <td>{{$item->status->status_kunjungan}}</td>
                                            </tr>
                                        @endforeach
                                    </x-adminlte-datatable>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-success card-outline">
                                <div class="card-body">
                                    <a href="#" class="btn bg-danger mb-2" id="infoRuangan"><i
                                        class="fas fa-exclamation-triangle"></i> SAAT INI RUANGAN BELUM DIPILIH</a>
                                    <div class="col-md-12">
                                        <x-adminlte-select2 name="unitTerpilih" id="unitTerpilih" label="Ruangan">
                                            @foreach ($unit as $item)
                                                <option value="{{ $item->kode_unit }}">
                                                    {{ $item->nama_unit }}</option>
                                            @endforeach
                                        </x-adminlte-select2>
                                    </div>
                                    <div class="col-md-12">
                                        <x-adminlte-select name="kelas_rawat" id="r_kelas_id" label="Kelas Rawat"
                                            disabled>
                                            <option value="1" {{ $kodeKelas == 1 ? 'selected' : '' }}>KELAS 1
                                            </option>
                                            <option value="2" {{ $kodeKelas == 2 ? 'selected' : '' }}>KELAS 2
                                            </option>
                                            <option value="3" {{ $kodeKelas == 3 ? 'selected' : '' }}>KELAS 3
                                            </option>
                                            <option value="4" {{ $kodeKelas == 4 ? 'selected' : '' }}>VIP</option>
                                            <option value="5" {{ $kodeKelas == 5 ? 'selected' : '' }}>VVIP</option>
                                        </x-adminlte-select>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="icheck-primary d-inline ml-2">
                                            <input type="checkbox" value="0" name="naikKelasRawat"
                                                id="naikKelasRawat">
                                            <label for="naikKelasRawat"></label>
                                        </div>

                                        <span class="text text-red"><b id="textDescChange">ceklis apabila pasien naik
                                                kelas
                                                rawat</b></span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <x-adminlte-button label="Cari Ruangan" data-toggle="modal"
                                        data-target="#pilihRuangan" id="cariRuangan" class="bg-purple btn-block" />
                                    <a href="#" class="btn bg-teal btn-block" id="showBed"
                                        style="display: none">
                                        <i class="fas fa-bed"></i>
                                    </a>
                                    <a href="#" class="btn btn-primary btn-block" id="showRuangan"
                                        style="display: none">
                                        <i class="fas fa-bed"></i> Tidak ada
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="row">
                        <div class="col-lg-12">
                            <x-adminlte-card theme="success" id="div_ranap" icon="fas fa-info-circle" collapsible
                                title="Daftarkan : {{ $pasien->nama_px }} ( {{ $pasien->no_rm }} )">
                                <form action="{{route('create-sepigd.ranap-bpjs')}}" method="post" id="submitRanap">
                                    @csrf
                                    <input type="hidden" name="noMR" value=" {{ $pasien->no_rm }}">
                                    <input type="hidden" name="idRuangan" id="ruanganSend">
                                    <input type="hidden" name="crad" id="c_rad">
                                    <input type="hidden" name="noKartuBPJS" id="noKartuBPJS"
                                        value="{{ $pasien->no_Bpjs }}">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                @php
                                                    $config = ['format' => 'YYYY-MM-DD'];
                                                @endphp
                                                <x-adminlte-input-date name="tanggal_daftar" id="tanggal_daftar"
                                                    value="{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                                                    label="Tanggal Masuk" :config="$config" />
                                                <x-adminlte-input name="noTelp" label="No Telp"
                                                    placeholder="masukan no telp" label-class="text-black">
                                                </x-adminlte-input>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <x-adminlte-input name="ruangan" label="Ruangan" id="ruanganTerpilih" readonly
                                                    disabled />
                                                    </div>
                                                    <div class="col-md-4">
                                                        <x-adminlte-input name="bed" label="No Bed" id="bedTerpilih" readonly
                                                    disabled />
                                                    </div>
                                                    <div class="col-md-4">
                                                        <x-adminlte-input name="hak_kelas" label="Hak Kelas" id="hakKelas"
                                                            disabled />
                                                    </div>
                                                </div>
                                                <x-adminlte-select name="alasan_masuk_id" label="Alasan Masuk">
                                                    <option value="">--Pilih Alasan--</option>
                                                    @foreach ($alasanmasuk as $item)
                                                        <option value="{{ $item->id }}">
                                                            {{ $item->alasan_masuk }}</option>
                                                    @endforeach
                                                </x-adminlte-select>
                                                <div class="form-group mb-2">
                                                    <div class="icheck-primary d-inline ml-2">
                                                        <input type="checkbox" value="0" name="katarak">
                                                        <label for="katarak"></label>
                                                    </div>
                                                    <span class="text text-red"><b>ceklis
                                                            apabila pasien katarak, dan akan dioperasi</b></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <x-adminlte-select name="penjamin_id" label="Pilih Penjamin">
                                                    <option value="">--Pilih Penjamin--</option>
                                                    @foreach ($penjamin as $item)
                                                        <option value="{{ $item->kode_penjamin }}">
                                                            {{ $item->nama_penjamin }}</option>
                                                    @endforeach
                                                </x-adminlte-select>
                                                
                                                <x-adminlte-select2 name="diagAwal" id="diagnosa"
                                                    label="Pilih Diagnosa">
                                                </x-adminlte-select2>
                                                <x-adminlte-input label="Pilih DPJP" name="dpjpBySPRI" value=""
                                                    label-class="text-black" disabled>
                                                </x-adminlte-input>

                                                <x-adminlte-select name="lakaLantas" id="status_kecelakaan"
                                                    label="Status Kecelakaan">
                                                    <option value="">--Status Kecelakaan--</option>
                                                    <option value="0">BUKAN KECELAKAAN LALU LINTAS (BKLL)
                                                    </option>
                                                    <option value="1">KLL & BUKAN KECELAKAAN KERJA (BKK)
                                                    </option>
                                                    <option value="2">KLL & KK</option>
                                                    <option value="3">KECELAKAAN KERJA</option>
                                                </x-adminlte-select>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-6">

                                                        </div>
                                                        <div class="col-md-12" id="div_stts_kecelakaan"
                                                            style="display: none;">
                                                            <div class="card card-danger card-outline">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <x-adminlte-input name="noLP"
                                                                                label="NO LP"
                                                                                placeholder="no laporan polisi"
                                                                                disable-feedback />
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <x-adminlte-input name="keterangan"
                                                                                label="Keterangan"
                                                                                placeholder="keterangan kecelakaan"
                                                                                disable-feedback />
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            @php
                                                                                $config = ['format' => 'YYYY-MM-DD'];
                                                                            @endphp
                                                                            <x-adminlte-input-date name="tglKejadian"
                                                                                value="{{ Carbon\Carbon::now()->format('Y-m-d') }}"
                                                                                label="Tanggal Kejadian"
                                                                                :config="$config" />
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <x-adminlte-select2 name="provinsi"
                                                                                label="Provinsi">
                                                                                <option selected disabled>Cari Provinsi
                                                                                </option>
                                                                            </x-adminlte-select2>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <x-adminlte-select2 name="kabupaten"
                                                                                label="Kota / Kabupaten">
                                                                                <option selected disabled>Cari Kota /
                                                                                    Kabupaten</option>
                                                                            </x-adminlte-select2>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <x-adminlte-select2 name="kecamatan"
                                                                                label="Kecamatan">
                                                                                <option selected disabled>Cari Kecamatan
                                                                                </option>
                                                                            </x-adminlte-select2>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6" id="naikKelasDesc1">
                                                            <x-adminlte-select name="pembiayaan"
                                                                label="pembiayaan (pasien naik kelas)" id="pembiayaan">
                                                                <option value="1">PRIBADI</option>
                                                                <option value="2">PEMBERI KERJA</option>
                                                                <option value="3">ASURANSI KESEHATAN TAMBAHAN</option>
                                                            </x-adminlte-select>
                                                        </div>
                                                        <div class="col-md-6" id="naikKelasDesc2">
                                                            <x-adminlte-input name="penanggungJawab"
                                                                label="Penanggung Jawab (pasien naik kelas)"
                                                                placeholder="jika pembiayaan oleh pemberi kerja atau tambahan layanan kesehatan"
                                                                label-class="text-black">
                                                            </x-adminlte-input>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <x-adminlte-button type="submit"
                                            class="withLoad btn btn-sm m-1 bg-green float-right btn-flat"
                                            form="submitRanap" label="Simpan Data" />
                                        <a href="#"
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

    <x-adminlte-modal id="pilihRuangan" title="List Ruangan Tersedia" theme="success" icon="fas fa-bed" size='xl'
        disable-animations>
        <div class="row listruangan" id="idRuangan"></div>
        <x-slot name="footerSlot">
            <x-adminlte-button theme="danger" label="batal pilih" onclick="batalPilih()" data-dismiss="modal" />
        </x-slot>
    </x-adminlte-modal>
@stop

@section('plugins.Datatables', true)
@section('plugins.Select2', true)
@section('plugins.TempusDominusBs4', true)
@section('plugins.DateRangePicker', true)
@section('plugins.Sweetalert2', true)

@section('js')
    <script>
        const select = document.getElementById('status_kecelakaan');
        const pilihUnit = document.getElementById('div_stts_kecelakaan');
        $(select).on('change', function() {
            if (select.value > 0 || select.value == null) {
                document.getElementById('div_stts_kecelakaan').style.display = "block";
            } else {
                document.getElementById('div_stts_kecelakaan').style.display = "none";
            }

        });
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $("#poliklinik").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_poliklinik_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        console.log(params);
                        return {
                            poliklinik: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $("#dokter").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_dpjp_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            jenispelayanan: $("#jenispelayanan").val(),
                            kodespesialis: $("#poliklinik option:selected").val(),
                            tanggal: $("#tanggal").val(),
                            nama: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $('.btnModalSPRI').click(function(e) {
                var kunjungan = $(this).data('id');
                var noKartu = $(this).data('nomorkartu');
                $('#noKartu').val(noKartu);
                $('#kodeKunjungan').val(kunjungan);
                $('.lanjutkanPROSESDAFTAR').hide();
                if ($('#modalSPRI').show()) {
                    var url = "{{ route('cekprosesdaftar.spri') }}?noKartu=" + noKartu;
                    $.ajax({
                        type: "GET",
                        url: url,
                        dataType: 'JSON',
                        success: function(data) {
                            console.log(data.cekSPRI);
                            if (data.cekSPRI == null) {
                                Swal.fire('PASIEN BELUM PUNYA SPRI. SILAHKAN BUAT SPRI', '',
                                    'info');
                            } else {
                                $('.lanjutkanPROSESDAFTAR').show();
                                $('.btnCreateSPRI').hide();
                                $('.btnCreateSPRIBatal').hide();
                                $('.lanjutkanPROSESDAFTAR').click(function(e) {
                                    location.href =
                                        "{{ route('ranapbpjs') }}/?no_kartu=" + data
                                        .cekSPRI.noKartu;
                                });
                            }
                        }
                    });
                }
                $('#modalSPRI').modal('toggle');
            });

            $('.btnCreateSPRI').click(function(e) {
                var kodeKunjungan = $("#kodeKunjungan").val();
                var noKartu = $("#noKartu").val();
                var kodeDokter = $("#dokter").val();
                var poliKontrol = $("#poliklinik option:selected").val();
                var tglRencanaKontrol = $("#tanggal").val();
                var user = $("#user").val();
                var url = "{{ route('pasien-ranap.createspri') }}";
                $.LoadingOverlay("show");
                $.ajax({
                    type: 'POST',
                    url: url,
                    dataType: 'json',
                    data: {
                        noKartu: noKartu,
                        kodeDokter: kodeDokter,
                        poliKontrol: poliKontrol,
                        tglRencanaKontrol: tglRencanaKontrol,
                        kodeKunjungan: kodeKunjungan,
                        user: user,
                    },
                    success: function(data) {

                        if (data.metadata.code == 200) {
                            Swal.fire('SPRI BERHASIL DIBUAT', '', 'success');
                            $("#createSPRI").modal('toggle');
                            location.href = "{{ route('ranapbpjs') }}/?no_kartu=" + noKartu;
                            $.LoadingOverlay("hide");
                        } else {
                            Swal.fire(data.metadata.message + '( ERROR : ' + data.metadata
                                .code + ')', '', 'error');
                            $.LoadingOverlay("hide");
                        }
                    },

                });
            });
            $("#diagnosa").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_diagnosa_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            diagnosa: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $('#cariRuangan').on('click', function() {
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
                                        value.id_ruangan + ', `' + value
                                    .nama_kamar + '`, `' + value.no_bed +
                                    '`)" style="height: 100px; width: 150px; margin=5px; border-radius: 2%;"><div class="ribbon-wrapper ribbon-sm"><div class="ribbon bg-warning text-sm">KOSONG</div></div><h6 class="text-left">"' +
                                        value.nama_kamar +
                                        '"</h6> <br> NO BED : "' + value
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
            $("#naikKelasDesc1").hide();
            $("#naikKelasDesc2").hide();
            $('#naikKelasRawat').click(function(e) {
                if (this.checked) {
                    $("#r_kelas_id").removeAttr("disabled");
                    $("#textDescChange").text("pasien memilih naik kelas rawat");
                    $("#c_rad").val(1);
                    $("#naikKelasDesc1").show();
                    $("#naikKelasDesc2").show();
                } else {
                    $("#r_kelas_id").attr("disabled", true);
                    $("#textDescChange").text("ceklis apabila pasien naik kelas rawat / edit kelas");
                    $("#c_rad").val(0);
                    $("#naikKelasDesc1").hide();
                    $("#naikKelasDesc2").hide();
                }
            });
            $('#editSPRI').click(function(e) {
                var nomorsuratkontrol = $('#noSPRI').val();
                var url = "{{ route('spri.get') }}?noSuratKontrol=" + nomorsuratkontrol;
                $.LoadingOverlay("show");
                $.get(url, function(data) {
                    $('#tglRencanaKontrol').val(data.spri.tglRencanaKontrol);
                    $('#poliKontrol').val(data.spri.poliKontrol).trigger('change');
                    $('#dokter').val(data.spri.kodeDokter).trigger('change');
                    $('#updateSPRI').modal('show');
                    $.LoadingOverlay("hide", true);
                });


            });
            // $('.ranapCreateData').click(function(e) {
            //     var noKartu = $('#noKartuBPJS').val();
            //     var tglSep = $('#tanggal_daftar').val();
            //     var klsRawatHak = $('#klsRawatHak').val();
            //     var idRuangan = $('#ruanganSend').val();
            //     var diagAwal = $('#diagAwal').val();
            //     var tujuan = $('').val();
            //     var eksekutif = $('').val();
            //     var dpjpLayan = $('').val();
            //     var noTelp = $('').val();
            //     var user = $('').val();
            //     var noSurat = $('').val();
            //     var kodeDPJP = $('').val();
            //     $.ajax({
            //         data: $('#submitRanap').serialize(),
            //         url: url,
            //         type: "POST",
            //         data: {
            //             noKartu = required,
            //             tglSep = required,
            //             klsRawatHak = required,
            //             catatan = required,
            //             diagAwal = required,
            //             tujuan = required,
            //             eksekutif = required,
            //             dpjpLayan = required,
            //             noTelp = required,
            //             user = required,
            //             noSurat = required,
            //             kodeDPJP = required,
            //         },
            //         success: function(data) {
            //             console.log(data);

            //             $.LoadingOverlay("hide");
            //         },
            //         error: function(data) {
            //             console.log(data);
            //             alert('error jaringan');
            //             $.LoadingOverlay("hide");
            //         }
            //     });
            // });
            $('#btnUpdateSPRI').click(function(e) {
                var noSPRI = $('#noSPRI').val();
                var tglRencanaKontrol = $('#tglRencanaKontrol').val();
                var poliKontrol = $('#poliKontrol').val();
                var kodeDokter = $('#dokter').val();
                var user = $('#user').val();
                swal.fire({
                    icon: 'question',
                    title: 'ANDA YAKIN UPDATE SPRI ' + noSPRI + ' ?',
                    showDenyButton: true,
                    confirmButtonText: 'YA',
                    denyButtonText: `Tidak`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('spri.update') }}?noSPRI=" + noSPRI;
                        $.LoadingOverlay("show");
                        $.ajax({
                            type: 'PUT',
                            url: url,
                            data: {
                                noSPRI: noSPRI,
                                tglRencanaKontrol: tglRencanaKontrol,
                                poliKontrol: poliKontrol,
                                kodeDokter: kodeDokter,
                                user: user,
                            },
                            success: function(data) {
                                console.log(data)
                                if (data.res.metadata.code == 200) {
                                    Swal.fire('SPRI BERHASIL DIUPDATE', '', 'success');
                                    location.reload();
                                    $.LoadingOverlay("hide");
                                } else {
                                    Swal.fire(data.res.metadata.message + '( ERROR : ' +
                                        data.res.metadata.code + ')', '', 'error');
                                    $.LoadingOverlay("hide");
                                }

                            },

                        });
                    }
                })
            });
            $("#hapusSPRI").hide();
            $('#hapusSPRI').click(function(e) {
                var noSPRI = $('#noSuratKontrol').val();
                swal.fire({
                    icon: 'question',
                    title: 'ANDA YAKIN HAPUS NO SPRI ' + noSPRI + ' ?',
                    showDenyButton: true,
                    confirmButtonText: 'Hapus',
                    denyButtonText: `Batal`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        var url = "{{ route('spri_delete') }}";
                        $.ajax({
                            type: 'DELETE',
                            url: url,
                            data: {
                                noSuratKontrol: noSPRI,
                                user: 'coba',
                            },
                            success: function(data) {

                                if (data.metadata.code == 200) {
                                    Swal.fire('SPRI BERHASIL DIHAPUS', '', 'success');
                                    location.reload();
                                } else {
                                    Swal.fire(data.metadata.message + '( ERROR : ' +
                                        data.metadata
                                        .code + ')', '', 'error');
                                }
                            },

                        });
                    }
                })
            });

            $("#provinsi").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_provinsi_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            nama: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $("#kabupaten").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_kabupaten_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            kodeprovinsi: $("#provinsi option:selected").val(),
                            nama: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $("#kecamatan").select2({
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('ref_kecamatan_api') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            kodekabupaten: $("#kabupaten option:selected").val(),
                            nama: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });


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
                    $("#ruanganTerpilih").val(nama);
                    $("#bedTerpilih").val(bed);
                    $('#pilihRuangan').modal('toggle');
                    $(".ruanganCheck").remove();

                    $("#infoRuangan").css("display", "none");
                }
            })
        }

        function batalPilih() {
            $(".ruanganCheck").remove();
        }
    </script>
@endsection
