@extends('adminlte::page')
@section('title', 'Pasien Rawat Inap Aktif')
@section('content_header')
    <h1>Pasien Rawat Inap Aktif </h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Filter Pasien Rawat Inap" theme="secondary" collapsible>
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-8">
                            <x-adminlte-select2 name="kodeunit" label="Ruangan">
                                <option value="-" {{ $request->kodeunit ? '-' : 'selected' }}>SEMUA POLIKLINIK (-)
                                </option>
                                @foreach ($units as $key => $item)
                                    <option value="{{ $key }}"
                                        {{ $key == $request->kodeunit ? 'selected' : null }}>
                                        {{ $item }} ({{ $key }})
                                    </option>
                                @endforeach

                            </x-adminlte-select2>
                        </div>
                        {{-- <div class="col-md-4">
                            <x-adminlte-select2 name="kodedokter" label="Dokter">
                                <option value="">SEMUA DOKTER (-)</option>
                                @foreach ($dokters as $item)
                                    <option value="{{ $item->kode_dokter_jkn }}"
                                        {{ $item->kode_dokter_jkn == $request->kodedokter ? 'selected' : null }}>
                                        {{ $item->nama_paramedis }} ({{ $item->kode_dokter_jkn }})
                                    </option>
                                @endforeach
                            </x-adminlte-select2>
                        </div> --}}
                    </div>
                    <x-adminlte-button type="submit" class="withLoad" theme="primary" label="Submit Pencarian" />
                </form>
            </x-adminlte-card>
        </div>
        @if (isset($kunjungans))

            <div class="col-md-12">
                <div class="row">
                    {{-- <div class="col-md-3">
                        <x-adminlte-small-box title="{{ $antrians->where('taskid', 4)->first()->nomorantrean ?? '0' }}"
                            text="Antrian Saat Ini" theme="primary" icon="fas fa-user-injured" />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-small-box
                            title="{{ $antrians->where('taskid', 3)->where('status_api', 1)->first()->nomorantrean ?? '0' }}"
                            text="Antrian Selanjutnya" theme="success" icon="fas fa-user-injured" />
                    </div> --}}
                    <div class="col-md-3">
                        <x-adminlte-small-box
                            title="{{ $kunjungans->count() - $kunjungans->where('budget.diagnosa_kode', '!=', null)->count() }}"
                            text="Sisa Pasien" theme="warning" icon="fas fa-user-injured" />
                    </div>
                    <div class="col-md-3">
                        <x-adminlte-small-box title="{{ $kunjungans->count() }}" text="Total Pasien" theme="success"
                            icon="fas fa-user-injured" />
                    </div>
                </div>
                <x-adminlte-card theme="secondary" icon="fas fa-info-circle"
                    title="Total Pasien Aktif ({{ $kunjungans->count() }} Orang)">
                    @php
                        $heads = ['Counter', 'Tgl Masuk', 'LOS', 'Pasien', 'Kelas/Jaminan', 'Dokter', 'Ruangan', 'Diagnosa', 'Tarif', 'Status', 'Action'];
                        $config['order'] = ['1', 'asc'];
                        $config['paging'] = false;
                        $config['scrollY'] = '400px';
                    @endphp
                    <x-adminlte-datatable id="table1" class="nowrap text-xs" :heads="$heads" :config="$config" bordered
                        hoverable compressed>
                        @foreach ($kunjungans as $item)
                            @if ($item->budget)
                                @if ($item->budget->diagnosa_kode)
                                    {{-- <tr class="table-warning"> --}}
                                    <tr>
                                    @else
                                    <tr class="table-danger">
                                @endif
                            @else
                                <tr class="table-danger">
                            @endif
                            <td>{{ $item->counter }} / {{ $item->kode_kunjungan }}</td>
                            <td>{{ $item->tgl_masuk }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tgl_masuk)->diffInDays() }}</td>
                            <td>{{ $item->no_rm }} {{ $item->pasien->nama_px }}</td>
                            <td>{{ $item->kelas }} / {{ $item->penjamin_simrs->group_jaminan }}</td>
                            <td>{{ $item->dokter->nama_paramedis }}</td>
                            <td>{{ $item->unit->nama_unit }}</td>
                            <td>{{ $item->budget->diagnosa_kode ?? '-' }}</td>
                            <td class="text-right">
                                {{ $item->budget ? money($item->budget->tarif_inacbg, 'IDR') : '-' }}</td>
                            <td>
                                test
                            </td>
                            <td>
                                <x-adminlte-button class="btn-xs btnPilihKunjungan" label="Groupper" theme="primary"
                                    icon="fas fa-file-medical" data-toggle="tooltop" title="Groupper INACBG"
                                    data-id="{{ $item->kode_kunjungan }}" data-nomorkartu="{{ $item->pasien->no_Bpjs }}"
                                    data-norm="{{ $item->pasien->no_rm }}" data-namapasien="{{ $item->pasien->nama_px }}"
                                    data-nomorsep="{{ $item->no_sep }}" data-tgllahir="{{ $item->pasien->tgl_lahir }}"
                                    data-gender="{{ $item->pasien->jenis_kelamin }}"
                                    data-tglmasuk="{{ $item->tgl_masuk }}" data-kelas="{{ $item->kelas }}"
                                    data-dokter="{{ $item->dokter->nama_paramedis }}"
                                    data-counter="{{ $item->counter }}" />
                            </td>
                            </tr>
                        @endforeach
                    </x-adminlte-datatable>
                </x-adminlte-card>
            </div>
        @endif
    </div>
    <x-adminlte-modal id="modalGroupper" name="modalGroupper" title="Groupper INACBG" theme="success"
        icon="fas fa-file-medical" size="xl" scrollable>
        <form action="{{ route('api.eclaim.claim_ranap') }}" id="formGroupper" method="POST">
            <div class="row">
                <div class="col-md-3">
                    <x-adminlte-input igroup-size="sm" name="counter" label="Counter" value="" readonly />
                    <x-adminlte-input igroup-size="sm" name="kodekunjungan" label="Kode Kunjungan" value=""
                        readonly />
                    <x-adminlte-input igroup-size="sm" name="nomor_kartu" label="Nomor Kartu" value="" readonly />
                    <x-adminlte-input name="nomor_sep" label="Nomor SEP" igroup-size="sm" placeholder="Cari nomor SEP"
                        readonly>
                        <x-slot name="appendSlot">
                            <x-adminlte-button theme="primary" id="btnCariSEP" label="Cari!" />
                        </x-slot>
                        <x-slot name="prependSlot">
                            <div class="input-group-text text-primary">
                                <i class="fas fa-search"></i>
                            </div>
                        </x-slot>
                    </x-adminlte-input>
                    <x-adminlte-input igroup-size="sm" name="nomor_rm" label="Nomor RM" value="" readonly />
                    <x-adminlte-input igroup-size="sm" name="nama_pasien" label="Nama Pasien" value="" readonly />
                    <x-adminlte-input igroup-size="sm" name="tgl_lahir" label="Tgl Lahir" value="" readonly />
                    <x-adminlte-input igroup-size="sm" name="gender" label="Gender" value="" readonly />
                    <br>

                </div>
                <div class="col-md-9">
                    {{-- pelyanan pasien --}}
                    <div class="col-md-12">
                        <h6>Pelayanan Pasien</h6>
                        <div class="row">
                            @php
                                $config = ['format' => 'YYYY-MM-DD HH:mm:ss'];
                            @endphp
                            <x-adminlte-input-date name="tgl_masuk" label="Tgl Masuk" fgroup-class="col-md-4"
                                igroup-size="sm" :config="$config" />
                            <x-adminlte-select name="cara_masuk" label="Cara Masuk" fgroup-class="col-md-4"
                                igroup-size="sm">
                                <option value="gp">Rujukan FKTP</option>
                                <option value="hosp-trans">Rujukan FKRTL</option>
                                <option value="mp">Rujukan Spesialis</option>
                                <option value="outp">Dari Rawat Jalan</option>
                                <option value="inp">Dari Rawat Inap</option>
                                <option value="emd">Dari Rawat Darurat</option>
                                <option value="born">Lahir di RS</option>
                                <option value="nursing">Rujukan Panti Jompo</option>
                                <option value="psych">Rujukan dari RS Jiwa</option>
                                <option value="rehab"> Rujukan Fasilitas Rehab</option>
                                <option value="other">Lain-lain</option>
                            </x-adminlte-select>
                            <x-adminlte-select name="kelas_rawat" label="Kelas Rawat" fgroup-class="col-md-4"
                                igroup-size="sm">
                                <option value="3" selected>Kelas 3</option>
                                <option value="2">Kelas 2</option>
                                <option value="1">Kelas 1</option>
                            </x-adminlte-select>
                            <x-adminlte-select name="discharge_status" label="Cara Pulang" fgroup-class="col-md-4"
                                igroup-size="sm">
                                <option value="1">Atas persetujuan dokter</option>
                                <option value="2">Dirujuk</option>
                                <option value="3">Atas permintaan sendiri</option>
                                <option value="4">Meninggal</option>
                                <option value="5">Lain-lain</option>
                            </x-adminlte-select>
                            <x-adminlte-input name="dokter_dpjp" label="Dokter DPJP" fgroup-class="col-md-4"
                                igroup-size="sm" placeholder="Dokter DPJP" readonly />
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="perawatan_icu" value="1"
                                    onchange="perawatanIcuFunc();">
                                <label for="perawatan_icu" class="custom-control-label">Perawatan ICU</label>
                            </div>
                            <x-adminlte-input name="lama_icu" label="Lama ICU" fgroup-class="masuk_icu" igroup-size="sm"
                                placeholder="Lama hari ICU" type="number" />
                            <div class="custom-control custom-checkbox checkVentilator">
                                <input class="custom-control-input" type="checkbox" id="ventilator" value="1"
                                    onchange="pakeVentilatorFunc();">
                                <label for="ventilator" class="custom-control-label">Ventilator ICU</label>
                            </div>
                            <x-adminlte-input name="intubasi" label="Tgl Intubasi"
                                fgroup-class="col-md-4 masuk_icu pake_ventilator" igroup-size="sm" />
                            <x-adminlte-input name="ekstubasi" label="Tgl Ekstubasi"
                                fgroup-class="col-md-4 masuk_icu pake_ventilator" igroup-size="sm" />
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="bayi" value="1"
                                    onchange="bayiFunc();">
                                <label for="bayi" class="custom-control-label">Bayi</label>
                            </div>
                            <x-adminlte-input name="berat_badan" label="Berat Badan" fgroup-class="formbb"
                                igroup-size="sm" placeholder="Berat Badan" />
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="tb" value="1"
                                    onchange="tbFunc();">
                                <label for="tb" class="custom-control-label">Pasien TB</label>
                            </div>
                            <x-adminlte-input name="no_reg_tb" label="No Register TB" fgroup-class="checkTB"
                                placeholder="No Register TB" igroup-size="sm" />
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="covid" value="1"
                                    onchange="covidFunc();">
                                <label for="covid" class="custom-control-label">Pasien COVID-19</label>
                            </div>
                            <x-adminlte-input name="no_claim_covid" label="No Claim COVID-19" fgroup-class="checkCovid"
                                placeholder="No Claim COVID-19" igroup-size="sm" />
                        </div>
                    </div>
                    {{-- tekanan darah --}}
                    <div class="row">
                        <div class="col-md-12">
                            <br><br>
                            <h6>Tekanan Darah</h6>
                            <div class="row">
                                <x-adminlte-input name="sistole" label="Sistole" fgroup-class="col-md-4"
                                    igroup-size="sm" placeholder="Sistole" type="number" />
                                <x-adminlte-input name="distole" label="Diastole" fgroup-class="col-md-4"
                                    igroup-size="sm" placeholder="Diastole" type="number" />
                            </div>
                        </div>
                    </div>
                    {{-- diagnosa --}}
                    <div class="row">
                        <div class="col-md-12">
                            {{-- multipe diagnosa --}}
                            <br><br>
                            <h6>Diagnosa & Tindakan</h6>
                            <label class=" mb-2">Diagnosa ICD-10</label>
                            <button id="rowAdder" type="button" class="btn btn-xs btn-success  mb-2">
                                <span class="fas fa-plus">
                                </span> Tambah Diagnosa
                            </button>
                            <div id="row">
                                <div class="form-group">
                                    <div class="input-group">
                                        <select name="diagnosa[]" class="form-control diagnosaID ">
                                        </select>
                                        <div class="input-group-append"><button type="button" class="btn btn-warning">
                                                <i class="fas fa-diagnoses "></i> Diagnosa Utama </button></div>
                                    </div>
                                </div>
                            </div>
                            <div id="newinput"></div>
                            {{-- multipe tindakan --}}
                            <label class="mb-2">Tindakan ICD-9</label>
                            <button id="rowAddTindakan" type="button" class="btn btn-xs btn-success  mb-2">
                                <span class="fas fa-plus">
                                </span> Tambah Tindakan
                            </button>
                            <div id="rowTindakan" class="row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="fas fa-hand-holding-medical "></i>
                                                </span>
                                            </div>
                                            <select name="procedure[]" class="form-control procedure ">
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <b>@</b>
                                                </span>
                                            </div>
                                            <input type="number" class="form-control" value="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-warning">
                                        <i class="fas fa-hand-holding-medical "></i> </button>
                                </div>
                            </div>
                            <div id="newTindakan"></div>
                        </div>
                    </div>
                    {{-- tarif --}}
                    <div class="row">
                        <div class="col-md-12">
                            <br><br>
                            <h6>Tekanan Darah</h6>
                        </div>
                        <div class="col-md-4">
                            <x-adminlte-input name="prosedur_non_bedah" label="Prosedur Non Bedah" igroup-size="sm" />
                            <x-adminlte-input name="tenaga_ahli" label="Tenaga Ahli" igroup-size="sm" />
                            <x-adminlte-input name="radiologi" label="Radiologi" igroup-size="sm" />
                            <x-adminlte-input name="rehabilitasi" label="Rehabilitasi" igroup-size="sm" />
                            <x-adminlte-input name="obat" label="Obat" igroup-size="sm" />
                            <x-adminlte-input name="alkes" label="Alkes" igroup-size="sm" />
                        </div>
                        <div class="col-md-4">
                            <x-adminlte-input name="prosedur_bedah" label="Prosedur Bedah" igroup-size="sm" />
                            <x-adminlte-input name="keperawatan" label="Keperawatan" igroup-size="sm" />
                            <x-adminlte-input name="laboratorium" label="Laboratorium" igroup-size="sm" />
                            <x-adminlte-input name="kamar_akomodasi" label="Kamar / Akomodasi" igroup-size="sm" />
                            <x-adminlte-input name="obat_kronis" label="Obat Kronis" igroup-size="sm" />
                            <x-adminlte-input name="bmhp" label="BMHP" igroup-size="sm" />
                        </div>
                        <div class="col-md-4">
                            <x-adminlte-input name="konsultasi" label="Konsultasi" igroup-size="sm" />
                            <x-adminlte-input name="penunjang" label="Penunjang" igroup-size="sm" />
                            <x-adminlte-input name="pelayanan_darah" label="Pelayanan Darah" igroup-size="sm" />
                            <x-adminlte-input name="rawat_intensif" label="Rawat Intensif" igroup-size="sm" />
                            <x-adminlte-input name="obat_kemo" label="Obat Kemoterapi" igroup-size="sm" />
                            <x-adminlte-input name="sewa_alat" label="Sewa Alat" igroup-size="sm" />
                        </div>
                        <x-adminlte-input name="tarif_rs" label="Tarif Rumah Sakit" fgroup-class="col-md-6" />
                    </div>
                    {{-- tarif inacbg --}}
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-3">Kode</dt>
                                <dd class="col-sm-9">: <span id="kode_inacbg"></span></dd>
                                <dt class="col-sm-3">Keterangan</dt>
                                <dd class="col-sm-9">: <span id="description_inacbg"></span></dd>
                                <dt class="col-sm-3">Base Tarif</dt>
                                <dd class="col-sm-9">: <span id="base_tariff"></span></dd>
                                <dt class="col-sm-3">Tarif</dt>
                                <dd class="col-sm-9">: <span id="tariff"></span></dd>
                                <dt class="col-sm-3">Kelas</dt>
                                <dd class="col-sm-9">: <span id="kelas"></span></dd>
                                <dt class="col-sm-3">Tarif Kelas Inacbg</dt>
                                <dd class="col-sm-9">: <span id="tarif_inacbg"></span></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-3">MDC</dt>
                                <dd class="col-sm-9">: <span id="mdc_desc"></span></dd>
                                <dt class="col-sm-3">DRG</dt>
                                <dd class="col-sm-9">: <span id="drg_desc"></span></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button theme="success" class="mr-auto" label="Groupper" type="submit"
                    form="formGroupper" />
                <x-adminlte-button theme="danger" label="Tutup" data-dismiss="modal" />
            </x-slot>
        </form>
    </x-adminlte-modal>
    <x-adminlte-modal id="modalSEP" name="modalSEP" title="SEP Peserta" theme="success" icon="fas fa-file-medical"
        size="xl">
        <table id="tableSEP" class="table table-sm table-hover table-bordered">
            <thead>
                <tr>
                    <th>tglSep</th>
                    <th>tglPlgSep</th>
                    <th>noSep</th>
                    <th>jnsPelayanan</th>
                    <th>poli</th>
                    <th>diagnosa</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </x-adminlte-modal>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)

@section('js')
    <script>
        $(function() {
            $(".masuk_icu").hide();
            $(".naik_kelas").hide();
            $(".pake_ventilator").hide();
            $(".checkVentilator").hide();
            $(".checkTB").hide();
            $(".checkCovid").hide();
            $(".formbb").hide();
            $('.btnPilihKunjungan').click(function() {
                var kodekunjungan = $(this).data('id');
                var nomorkartu = $(this).data('nomorkartu');
                var nomorsep = $(this).data('nomorsep');
                var norm = $(this).data('norm');
                var namapasien = $(this).data('namapasien');
                var tgllahir = $(this).data('tgllahir');
                var gender = $(this).data('gender');
                var tglmasuk = $(this).data('tglmasuk');
                var kelas = $(this).data('kelas');
                var dokter = $(this).data('dokter');
                var counter = $(this).data('counter');
                var id = $(this).data('id');

                $('#kodekunjungan').val(id);
                $('#nomor_kartu').val(nomorkartu);
                $('#nomor_sep').val(nomorsep);
                $('#nomor_rm').val(norm);
                $('#nama_pasien').val(namapasien);
                $('#tgl_lahir').val(tgllahir);
                $('#tgl_masuk').val(tglmasuk);
                $('#gender').val(gender);
                $('#dokter_dpjp').val(dokter);
                $('#counter').val(counter);
                $('#kelas_rawat').val(kelas).change();
                $.LoadingOverlay("show");
                $('#modalGroupper').modal('show');
                $.LoadingOverlay("hide", true);
            });
            $('#btnCariSEP').click(function(e) {
                var nomorkartu = $('#nomor_kartu').val();
                $('#modalSEP').modal('show');
                var table = $('#tableSEP').DataTable();
                table.rows().remove().draw();
                $.LoadingOverlay("show");
                e.preventDefault();
                var url = "{{ route('suratkontrol_sep') }}?nomorkartu=" + nomorkartu;
                $.ajax({
                    url: url,
                    type: "GET",
                    dataType: 'json',
                    success: function(data) {
                        if (data.metadata.code == 200) {
                            $.each(data.response, function(key, value) {
                                if (value.jnsPelayanan == 1) {
                                    var jenispelayanan = "Rawat Inap";
                                }
                                if (value.jnsPelayanan == 2) {
                                    var jenispelayanan = "Rawat Jalan";
                                }
                                table.row.add([
                                    value.tglSep,
                                    value.tglPlgSep,
                                    value.noSep,
                                    jenispelayanan,
                                    value.poli,
                                    value.diagnosa,
                                    "<button class='btnPilihSEP btn btn-success btn-xs' data-id=" +
                                    value.noSep +
                                    ">Pilih</button>",
                                ]).draw(false);

                            });
                            $('.btnPilihSEP').click(function() {
                                var nomorsep = $(this).data('id');
                                $.LoadingOverlay("show");
                                $('#nomor_sep').val(nomorsep);
                                $('#modalSEP').modal('hide');
                                $.LoadingOverlay("hide");
                            });
                        } else {
                            swal.fire(
                                'Error ' + data.metadata.code,
                                data.metadata.message,
                                'error'
                            );
                        }
                        $.LoadingOverlay("hide");
                    },
                    error: function(data) {
                        // swal.fire(
                        //     'Error ' + data.metadata.code,
                        //     data.metadata.message,
                        //     'error'
                        // );
                        $.LoadingOverlay("hide");
                    }
                });
            });
        });
    </script>
    {{-- search select2 --}}
    <script>
        $(function() {
            $(".diagnosaID").select2({
                placeholder: 'Silahkan pilih Diagnosa ICD-10',
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('api.eclaim.search_diagnosis') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 100,
                    data: function(params) {
                        return {
                            keyword: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        console.log(response);
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });
            $(".procedure").select2({
                placeholder: 'Silahkan pilih Tindakan ICD-9',
                theme: "bootstrap4",
                ajax: {
                    url: "{{ route('api.eclaim.search_procedures') }}",
                    type: "get",
                    dataType: 'json',
                    delay: 100,
                    data: function(params) {
                        return {
                            keyword: params.term // search term
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
            // $("#obat").select2({
            //     placeholder: 'Silahkan pilih obat',
            //     theme: "bootstrap4",
            //     ajax: {
            //         url: "{{ route('api.simrs.get_obats') }}",
            //         type: "get",
            //         dataType: 'json',
            //         delay: 100,
            //         data: function(params) {
            //             return {
            //                 search: params.term // search term
            //             };
            //         },
            //         processResults: function(response) {
            //             return {
            //                 results: response
            //             };
            //         },
            //         cache: true
            //     }
            // });
        });
    </script>
    {{-- checkbox --}}
    <script>
        function bayiFunc() {
            if ($('#bayi').is(":checked"))
                $(".formbb").show();
            else
                $(".formbb").hide();
        }

        function covidFunc() {
            if ($('#covid').is(":checked"))
                $(".checkCovid").show();
            else
                $(".checkCovid").hide();
        }


        function tbFunc() {
            if ($('#tb').is(":checked"))
                $(".checkTB").show();
            else
                $(".checkTB").hide();
        }

        function perawatanIcuFunc() {
            if ($('#perawatan_icu').is(":checked")) {
                $(".masuk_icu").show();
                $(".checkVentilator").show();
                $(".pake_ventilator").hide();
            } else {
                $(".masuk_icu").hide();
                $(".checkVentilator").hide();
                $(".pake_ventilator").hide();
            }
        }

        function pakeVentilatorFunc() {
            if ($('#ventilator').is(":checked"))
                $(".pake_ventilator").show();
            else
                $(".pake_ventilator").hide();
        }
    </script>
@endsection
