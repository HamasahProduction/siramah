@extends('adminlte::page') @section('title', 'Antrian Pendaftaran IGD')
@section('content')
    <div class="row mt-3">
        <div class="col-12">
            <div class="row">
                <div class="col-lg-12">
                    <h6>silahkan cari pasien disini:</h6>
                    <p>pencarian berdasarkan prioritas memudahkan dalam proses efektifitas pencarian. semakin kecil
                        prioritas semakin akurat</p>
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-lg-4 mt-2">
                                <span class="bg-pink disabled color-palette p-1" style="font-size: 12px"><b><i>*Prioritas
                                            Pencarian 1</i></b></span>
                                <input type="text" minlength="16" maxlength="16"class="form-control mr-2"
                                    placeholder="cari NIK Pasien" id="nik_search">
                            </div>
                            <div class="col-lg-4 mt-2">
                                <span class="bg-pink disabled color-palette p-1" style="font-size: 12px"><b><i>*Prioritas
                                            Pencarian 2</i></b></span>
                                <input type="text" class="form-control mr-2"
                                    placeholder="cari berdasarkan nama lengkap pasien" id="nama_search">
                            </div>
                            <div class="col-lg-4 mt-2">
                                <span class="bg-pink disabled color-palette p-1" style="font-size: 12px"><b><i>*Prioritas
                                            Pencarian 3</i></b></span>
                                <input type="date" class="form-control mr-2" id="tgl_lahir_search">
                            </div>
                            <div class="col-lg-4 mt-2">
                                <span class="bg-pink disabled color-palette p-1" style="font-size: 12px"><b><i>*Prioritas
                                            Pencarian 4</i></b></span>
                                <input type="text" class="form-control mr-2" placeholder="cari berdasarkan alamat"
                                    id="alamat_search">
                            </div>
                            <div class="col-lg-4 mt-2">
                                <span class="bg-pink disabled color-palette p-1" style="font-size: 12px"><b><i>*Prioritas
                                            Pencarian 5</i></b></span>
                                <input type="text" class="form-control mr-2" placeholder="cari berdasarkan no bpjs"
                                    id="no_bpjs_search">
                            </div>
                            <div class="col-lg-4 mt-4">
                                <x-adminlte-button label="Cari Pasien" class="btn btn-flat" theme="primary"
                                    icon="fas fa-search" id="search" />
                                <x-adminlte-button label="Refresh" class="btn btn-flat" theme="danger" icon="fas fa-retweet"
                                    onClick="window.location.reload();" />
                                <a  class="btn btn-flat btn-warning" icon="fas fa-retweet"
                                    href="{{route('pendaftaran-pasien-igdbpjs')}}" >Pasien BPJS</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="invoice p-3 mb-3">
                <div class="row">
                    <div class="col-12">
                        <h4>
                            <i class="fas fa-globe"></i> Data Pasien : <small class="float-right">tanggal :
                                {{ \Carbon\Carbon::now()->format('Y-m-d') }}!</small>
                        </h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 table-responsive">
                        <div class="row">
                            <div class="col-lg-9">
                                @php
                                    $heads = ['NO RM', 'NIK', 'NO BPJS', 'NAMA', 'TGL Lahir', 'ALAMAT'];
                                    $config['order'] = ['0', 'asc'];
                                    $config['ordering'] = false;
                                    $config['paging'] = true;
                                    $config['info'] = false;
                                    $config['searching'] = false;
                                    $config['scrollY'] = '600px';
                                    $config['scrollCollapse'] = true;
                                    $config['scrollX'] = true;
                                @endphp
                                <x-adminlte-datatable id="table1" class="nowrap text-xs" :heads="$heads"
                                    :config="$config" striped bordered hoverable compressed></x-adminlte-datatable>
                            </div>
                            <div class="col-lg-3">
                                <div class="col-lg-12">
                                    <div class="col-lg-12">
                                        <x-adminlte-info-box title="klik untuk" data-toggle="modal"
                                            data-target="#modalAntrian" text="Lihat Antrian"
                                            icon="fas fa-lg fa-window-restore text-primary" class="bg-gradient-primary"
                                            icon-theme="white" />
                                        <x-adminlte-modal id="modalAntrian" title="DAFTAR ANTRIAN" theme="info"
                                            icon="fas fa-bolt" size='xl' disable-animations>
                                            <div class="row">
                                                @foreach ($antrian as $item)
                                                    <a class="btn btn-app bg-warning" id="pilihAntrian"
                                                        onclick="pilihAntrian({{ $item->id }})">
                                                        <i class="fas fa-users"></i> {{ $item->no_antri }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </x-adminlte-modal>
                                    </div>
                                    <div class="col-lg-12">
                                        <form action="{{ route('pasien-didaftarkan') }}" method="get">
                                            <input type="hidden" id="send_id_antri" name="no_antri">
                                            <input type="hidden" id="no_rm" name="pasien_id">
                                            <input type="hidden" id="tanggal" name="tanggal"
                                                value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                            <div class="card" id="formdaftar">
                                                <div class="card-header">
                                                    <span class="badge badge-danger">
                                                        <h3 class="card-title" id="no_antrian">No Antrian Belum dipilih?
                                                        </h3>
                                                    </span>

                                                    <span class="badge badge-success">
                                                        <h3 class="card-title" id="tujuan_daftar">-</h3>
                                                    </span>
                                                    <div class="card-tools">
                                                        <button type="button" class="btn btn-tool">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="card-body p-0" style="display: block;">
                                                    <div class="col-lg-12">
                                                        <ul class="nav nav-pills flex-column">
                                                            <li class="nav-item active">
                                                                <p id="rm_pasien_selected">RM : </p>
                                                            </li>
                                                            <li class="nav-item">
                                                                <p id="nama_pasien_selected">NAMA : </p>
                                                            </li>
                                                            <li class="nav-item">
                                                                <p id="desa_pasien_selected">DESA : </p>
                                                            </li>
                                                            <li class="nav-item">
                                                                <p id="kec_pasien_selected">KECAMATAN : </p>
                                                            </li>
                                                            <li class="nav-item">
                                                                <p id="kab_pasien_selected">KABUPATEN : </p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-lg-12">
                                                        <x-adminlte-select name="pendaftaran_id" id="pilihPendaftaran"
                                                            label="Pilih Pendaftaran">
                                                            <option value="0">IGD UMUM</option>
                                                            <option value="1">IGD KEBIDANAN</option>
                                                        </x-adminlte-select>
                                                    </div>
                                                </div>
                                                <x-adminlte-button type="submit" theme="primary"
                                                    label="Lanjutkan Pendaftaran" />
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="alert alert-warning alert-dismissible">
                                            <h5>
                                                <i class="icon fas fa-info-circle"></i> form untuk mendaftarkan pasien baru
                                                :
                                            </h5> jika pasien tidak terdaftar dalam sistem, silahkan masukan data pasien
                                            baru dengan cara klik tombol berikut : <br>
                                            {{-- <x-adminlte-button label="Tambah Pasien Baru" data-toggle="modal" data-target="#tambahPasien" class="btn btn-info bg-info btn-xs" /> --}}
                                            <button type="button" class="btn btn-block bg-gradient-success btn-sm"
                                                data-toggle="modal" data-target="#tambahPasien">Tambah Pasien
                                                Baru</button>
                                            <form action="{{ route('pasien-baru.create') }}" method="post">
                                                @csrf
                                                <x-adminlte-modal id="tambahPasien" title="Tambah Pasien Baru"
                                                    size="xl" theme="info" icon="fas fa-user-plus" v-centered
                                                    static-backdrop scrollable>
                                                    <div class="modal-body">
                                                        <div class="col-lg-12">
                                                            <div class="row">
                                                                <div class="col-lg-8">
                                                                    <div class="row">
                                                                        <div class="col-lg-12">
                                                                            <div
                                                                                class="alert alert-success alert-dismissible">
                                                                                <h5>
                                                                                    <i
                                                                                        class="icon fas fa-users"></i>Informasi
                                                                                    Pasien :
                                                                                </h5>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="row">
                                                                                <x-adminlte-input name="nik_pasien_baru"
                                                                                    label="NIK"
                                                                                    placeholder="masukan nik"
                                                                                    fgroup-class="col-md-6"
                                                                                    disable-feedback />
                                                                                <x-adminlte-input name="no_bpjs"
                                                                                    label="BPJS"
                                                                                    placeholder="masukan bpjs"
                                                                                    fgroup-class="col-md-6"
                                                                                    disable-feedback />
                                                                                <x-adminlte-input name="nama_pasien_baru"
                                                                                    label="Nama"
                                                                                    placeholder="masukan nama pasien"
                                                                                    fgroup-class="col-md-12"
                                                                                    disable-feedback />
                                                                                <x-adminlte-input name="tempat_lahir"
                                                                                    label="Tempat lahir"
                                                                                    placeholder="masukan tempat"
                                                                                    fgroup-class="col-md-6"
                                                                                    disable-feedback />
                                                                                <x-adminlte-select name="jk"
                                                                                    label="Jenis Kelamin"
                                                                                    fgroup-class="col-md-6">
                                                                                    <option value="L">Laki-Laki
                                                                                    </option>
                                                                                    <option value="P">Perempuan
                                                                                    </option>
                                                                                </x-adminlte-select> @php $config = ['format' => 'DD-MM-YYYY']; @endphp
                                                                                <x-adminlte-input-date name="tgl_lahir"
                                                                                    fgroup-class="col-md-6"
                                                                                    label="Tanggal Lahir"
                                                                                    :config="$config">
                                                                                    <x-slot name="prependSlot">
                                                                                        <div
                                                                                            class="input-group-text bg-primary">
                                                                                            <i
                                                                                                class="fas fa-calendar-alt"></i>
                                                                                        </div>
                                                                                    </x-slot>
                                                                                </x-adminlte-input-date>
                                                                                <x-adminlte-select name="agama"
                                                                                    label="Agama"
                                                                                    fgroup-class="col-md-6">
                                                                                    @foreach ($agama as $item)
                                                                                        <option
                                                                                            value="{{ $item->ID }}">
                                                                                            {{ $item->agama }}</option>
                                                                                    @endforeach
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select name="pekerjaan"
                                                                                    label="Pekerjaan"
                                                                                    fgroup-class="col-md-6">
                                                                                    @foreach ($pekerjaan as $item)
                                                                                        <option
                                                                                            value="{{ $item->ID }}">
                                                                                            {{ $item->pekerjaan }}</option>
                                                                                    @endforeach
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select name="pendidikan"
                                                                                    label="Pendidikan"
                                                                                    fgroup-class="col-md-6">
                                                                                    @foreach ($pendidikan as $item)
                                                                                        <option
                                                                                            value="{{ $item->ID }}">
                                                                                            {{ $item->pendidikan }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </x-adminlte-select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-lg-6">
                                                                            <div class="row">
                                                                                <x-adminlte-input name="no_telp"
                                                                                    label="No Telpon"
                                                                                    placeholder="masukan no tlp"
                                                                                    fgroup-class="col-md-6"
                                                                                    disable-feedback />
                                                                                <x-adminlte-input name="no_hp"
                                                                                    label="No Hp"
                                                                                    placeholder="masukan hp"
                                                                                    fgroup-class="col-md-6"
                                                                                    disable-feedback />
                                                                                <x-adminlte-select name="provinsi_pasien"
                                                                                    label="Provinsi" id="provinsi_pasien"
                                                                                    fgroup-class="col-md-6">
                                                                                    @foreach ($provinsi as $item)
                                                                                        <option
                                                                                            value="{{ $item->kode_provinsi }}">
                                                                                            {{ $item->nama_provinsi }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select name="kabupaten_pasien"
                                                                                    label="Kabupaten" id="kab_pasien"
                                                                                    fgroup-class="col-md-6">
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select name="kecamatan_pasien"
                                                                                    label="Kecamatan" id="kec_pasien"
                                                                                    fgroup-class="col-md-6">
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select name="desa_pasien"
                                                                                    label="Desa" id="desa_pasien"
                                                                                    fgroup-class="col-md-6">
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-select2 name="negara"
                                                                                    label="Negara" id="negara_pasien"
                                                                                    fgroup-class="col-md-6">
                                                                                    @foreach ($negara as $item)
                                                                                        <option
                                                                                            value="{{ $item->id }}">
                                                                                            {{ $item->nama_negara }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </x-adminlte-select2>
                                                                                <x-adminlte-select name="kewarganegaraan"
                                                                                    id="kewarganegaraan_pasien"
                                                                                    label="Kewarganegaraan"
                                                                                    fgroup-class="col-md-6">
                                                                                    <option value="1">WNI</option>
                                                                                    <option value="0">WNA</option>
                                                                                </x-adminlte-select>
                                                                                <x-adminlte-textarea
                                                                                    name="alamat_lengkap_pasien"
                                                                                    label="Alamat Lengkap (RT/RW)"
                                                                                    placeholder="Alamat Lengkap (RT/RW)"
                                                                                    fgroup-class="col-md-12" />
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-4">
                                                                    <div class="alert alert-warning alert-dismissible">
                                                                        <h5>
                                                                            <i class="icon fas fa-users"></i>Info Keluarga
                                                                            Pasien :
                                                                        </h5>
                                                                    </div>
                                                                    <div class="row">
                                                                        <x-adminlte-input name="nama_keluarga"
                                                                            label="Nama Keluarga"
                                                                            placeholder="masukan nama keluarga"
                                                                            fgroup-class="col-md-12" disable-feedback />
                                                                        <x-adminlte-input name="kontak" label="Kontak"
                                                                            placeholder="no tlp" fgroup-class="col-md-6"
                                                                            disable-feedback />
                                                                        <x-adminlte-select name="hub_keluarga"
                                                                            label="Hubungan Dengan Pasien"
                                                                            fgroup-class="col-md-6">
                                                                            @foreach ($hb_keluarga as $item)
                                                                                <option value="{{ $item->kode }}">
                                                                                    {{ $item->nama_hubungan }}</option>
                                                                            @endforeach
                                                                        </x-adminlte-select>
                                                                        <x-adminlte-select name="provinsi_klg"
                                                                            label="Provinsi" id="klg_provinsi_pasien"
                                                                            fgroup-class="col-md-6">
                                                                            <option value="" selected>--PROVINSI--
                                                                            </option>
                                                                            @foreach ($provinsi_klg as $item)
                                                                                <option
                                                                                    value="{{ $item->kode_provinsi }}">
                                                                                    {{ $item->nama_provinsi }}</option>
                                                                            @endforeach
                                                                        </x-adminlte-select>
                                                                        <x-adminlte-select name="kabupaten_klg"
                                                                            label="Kabupaten" id="klg_kab_pasien"
                                                                            fgroup-class="col-md-6"></x-adminlte-select>
                                                                        <x-adminlte-select name="kecamatan_klg"
                                                                            label="Kecamatan" id="klg_kec_pasien"
                                                                            fgroup-class="col-md-6"></x-adminlte-select>
                                                                        <x-adminlte-select name="desa_klg" label="Desa"
                                                                            id="klg_desa_pasien"
                                                                            fgroup-class="col-md-6"></x-adminlte-select>
                                                                        <x-adminlte-textarea name="alamat_lengkap_sodara"
                                                                            label="Alamat Lengkap (RT/RW)"
                                                                            placeholder="Alamat Lengkap (RT/RW)"
                                                                            fgroup-class="col-md-12" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <x-slot name="footerSlot">
                                                        <x-adminlte-button theme="danger" class="mr-auto" label="batal"
                                                            data-dismiss="modal" />
                                                        <x-adminlte-button type="submit" class="float-right"
                                                            theme="success" label="simpan data" />
                                                    </x-slot>
                                                </x-adminlte-modal>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
        $(document).ready(function() {
            $('#search').click(function(e) {
                $.LoadingOverlay("show");
                search();
                $.LoadingOverlay("hide");
            });
        });
        search();

        function search() {
            var nik = $('#nik_search').val();
            var nama = $('#nama_search').val();
            var alamat = $('#alamat_search').val();
            var tglLahir = $('#tgl_lahir_search').val();
            var nobpjs = $('#no_bpjs_search').val();
            if (nik == '' && nama == '' && alamat == '' && tglLahir == '' && nobpjs == '') {
                Swal.fire('silahkan pilih pencarian pasien berdasarkan kolom inputan yang tersedia', '', 'info')
            }
            $.post('{{ route('pasien-igd-search') }}', {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    nik: nik,
                    nama: nama,
                    alamat: alamat,
                    tglLahir: tglLahir,
                    nobpjs: nobpjs,
                },
                function(data) {
                    $.LoadingOverlay("show");
                    table_post_row(data);
                    console.log(data);
                    $.LoadingOverlay("hide");
                });
        }
        // table row with ajax
        function table_post_row(res) {
            let htmlView = '';
            if (res.pasien.length <= 0) {
                Swal.fire('data yang dicari tidak tersedia', '', 'info')
                htmlView += `
              
                      <tr>
                          <td colspan="6">No data.</td>
                      </tr>`;
            }
            for (let i = 0; i < res.pasien.length; i++) {
                var rm = res.pasien[i].no_rm;
                var tgl_lahir = res.pasien[i].tgl_lahir;
                var tgl = tgl_lahir.substr(0, 10);
                var tgl_ind = new Date(tgl).toLocaleDateString('en-GB');

                htmlView += `
                  
                      <tr class="nowrap">
                          <td>
                              <button type="button" onclick="pilihPasien(` + rm +
                    `)" class="btn btn-block bg-maroon btn-sm">` + rm + `</button>
                          </td>
                          <td>` + res.pasien[i].nik_bpjs + `</td>
                          <td>` + res.pasien[i].no_Bpjs + `</td>
                          <td>` + res.pasien[i].nama_px + `</td>
                          <td>` + res.pasien[i].tempat_lahir + `,` + tgl_ind + `</td>
                          <td>` + res.pasien[i].alamat + `</td>
                      </tr>`;
            }
            $('tbody').html(htmlView);
        }

        function pilihPasien(norm) {
            var rm = norm;
            swal.fire({
                icon: 'question',
                title: 'ANDA YAKIN PILIH RM : ' + rm,
                showDenyButton: true,
                confirmButtonText: 'Pilih',
                denyButtonText: `Batal`,
            }).then((result) => {
                if (result.isConfirmed) {
                    var getPasienUrl = "{{ route('pasien-terpilih.get') }}?rm=" + rm;
                    $.get(getPasienUrl, function(data) {
                        console.log(data);
                        $('#rm_pasien_selected').text('NO RM : ' + data.pasien['no_rm']);
                        $('#nama_pasien_selected').text('NAMA : ' + data.pasien['nama_px']);
                        $('#desa_pasien_selected').text('DESA : ' + data.pasien['desas'][
                            'nama_desa_kelurahan'
                        ]);
                        $('#kec_pasien_selected').text('KEC. : ' + data.pasien['kecamatans'][
                            'nama_kecamatan'
                        ]);
                        $('#kab_pasien_selected').text('KAB. : ' + data.pasien['kabupatens'][
                            'nama_kabupaten_kota'
                        ]);
                    })
                    Swal.fire('pasien berhasil dipilih', '', 'success')
                    $('#no_rm').val(rm);
                }
                // else if (result.isDenied) {
                //   Swal.fire('Pilih Ruangan dibatalkan', '', 'info')
                // }
            })
        }

        function pilihAntrian(antrianID) {
            var antrian_id = antrianID;
            swal.fire({
                icon: 'question',
                title: 'ANDA YAKIN PILIH NO ANTRIAN INI ?',
                showDenyButton: true,
                confirmButtonText: 'Pilih Sekarang',
                denyButtonText: `Batal`,
            }).then((result) => {
                if (result.isConfirmed) {
                    var getNoAntrian = "{{ route('get-no-antrian') }}?id=" + antrian_id;
                    $.get(getNoAntrian, function(data) {
                        $('#no_antrian').text('No. Antrian : ' + data['no_antri']);
                        var jenis_antrian = data['no_antri'];
                        var jp = jenis_antrian.substring(0, 1);
                        if (jp === 'A') {
                            $('#tujuan_daftar').text('Untuk Pasien IGD');
                            $("#pilihPendaftaran option:selected").val(0);
                        } else {
                            $('#tujuan_daftar').text('Untuk Pasien IGD Kebidanan');
                            $("#pilihPendaftaran option:selected").val(1);
                        }
                        $('#tujuan_daftar').val(jp);
                        $('#send_id_antri').val(antrian_id);
                    })

                    Swal.fire('no antrian sudah dipilih', '', 'success')
                }
            })
            $('#modalAntrian').modal('hide')
        }
        // alamat pasien
        $(document).ready(function() {
            $('#provinsi_pasien').change(function() {
                var prov_pasien = $(this).val();
                if (prov_pasien) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('kab-pasien.get') }}?kab_prov_id=" + prov_pasien,
                        dataType: 'JSON',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(kabupatenpasien) {
                            if (kabupatenpasien) {
                                $('#kab_pasien').empty();
                                $("#kab_pasien").append(
                                    ' < option > --Pilih Kabupaten-- < /option>');
                                $.each(kabupatenpasien, function(key, value) {
                                    $('#kab_pasien').append('<option value="' + value
                                        .kode_kabupaten_kota + '">' + value
                                        .nama_kabupaten_kota + '</option>');
                                });
                            } else {
                                $('#kab_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#kab_pasien").empty();
                }
            });
            $('#kab_pasien').change(function() {
                var kec_kab_id = $("#kab_pasien").val();
                if (kec_kab_id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('kec-pasien.get') }}?kec_kab_id=" + kec_kab_id,
                        dataType: 'JSON',
                        success: function(kecamatanpasien) {
                            console.log(kecamatanpasien);
                            if (kecamatanpasien) {
                                $('#kec_pasien').empty();
                                $("#kec_pasien").append(
                                    ' < option > --Pilih Kecamatan-- < /option>');
                                $.each(kecamatanpasien, function(key, value) {
                                    $('#kec_pasien').append('<option value="' + value
                                        .kode_kecamatan + '">' + value
                                        .nama_kecamatan + '</option>');
                                });
                            } else {
                                $('#kec_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#kec_pasien").empty();
                }
            });
            $('#kec_pasien').change(function() {
                var desa_kec_id = $("#kec_pasien").val();
                if (desa_kec_id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('desa-pasien.get') }}?desa_kec_id=" + desa_kec_id,
                        dataType: 'JSON',
                        success: function(desapasien) {
                            console.log(desapasien);
                            if (desapasien) {
                                $('#desa_pasien').empty();
                                $("#desa_pasien").append(
                                    ' < option > --Pilih Desa-- < /option>');
                                $.each(desapasien, function(key, value) {
                                    $('#desa_pasien').append('<option value="' + value
                                        .kode_desa_kelurahan + '">' + value
                                        .nama_desa_kelurahan + '</option>');
                                });
                            } else {
                                $('#desa_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#desa_pasien").empty();
                }
            });
        });

        // alamat keluarga pasien
        $(document).ready(function() {
            $('#klg_provinsi_pasien').change(function() {
                var klg_prov_pasien = $(this).val();
                if (klg_prov_pasien) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('klg-kab-pasien.get') }}?klg_kab_prov_id=" +
                            klg_prov_pasien,
                        dataType: 'JSON',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(kabkeluarga) {
                            if (kabkeluarga) {
                                $('#klg_kab_pasien').empty();
                                $("#klg_kab_pasien").append(
                                    ' < option > --Pilih Kabupaten-- < /option>');
                                $.each(kabkeluarga, function(key, value) {
                                    $('#klg_kab_pasien').append('<option value="' +
                                        value.kode_kabupaten_kota + '">' + value
                                        .nama_kabupaten_kota + '</option>');
                                });
                            } else {
                                $('#klg_kab_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#klg_kab_pasien").empty();
                }
            });
            $('#klg_kab_pasien').change(function() {
                var klg_kec_kab_id = $("#klg_kab_pasien").val();
                if (klg_kec_kab_id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('klg-kec-pasien.get') }}?klg_kec_kab_id=" + klg_kec_kab_id,
                        dataType: 'JSON',
                        success: function(keckeluarga) {
                            console.log(keckeluarga);
                            if (keckeluarga) {
                                $('#klg_kec_pasien').empty();
                                $("#klg_kec_pasien").append(
                                    ' < option > --Pilih Kecamatan-- < /option>');
                                $.each(keckeluarga, function(key, value) {
                                    $('#klg_kec_pasien').append('<option value="' +
                                        value.kode_kecamatan + '">' + value
                                        .nama_kecamatan + '</option>');
                                });
                            } else {
                                $('#klg_kec_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#klg_kec_pasien").empty();
                }
            });
            $('#klg_kec_pasien').change(function() {
                var klg_desa_kec_id = $("#klg_kec_pasien").val();
                if (klg_desa_kec_id) {
                    $.ajax({
                        type: "GET",
                        url: "{{ route('klg-desa-pasien.get') }}?klg_desa_kec_id=" +
                            klg_desa_kec_id,
                        dataType: 'JSON',
                        success: function(desakeluarga) {
                            console.log(desakeluarga);
                            if (desakeluarga) {
                                $('#klg_desa_pasien').empty();
                                $("#klg_desa_pasien").append(
                                    ' < option > --Pilih Kecamatan-- < /option>');
                                $.each(desakeluarga, function(key, value) {
                                    $('#klg_desa_pasien').append('<option value="' +
                                        value.kode_desa_kelurahan + '">' + value
                                        .nama_desa_kelurahan + '</option>');
                                });
                            } else {
                                $('#klg_desa_pasien').empty();
                            }
                        }
                    });
                } else {
                    $("#klg_desa_pasien").empty();
                }
            });
        });
    </script>
@endsection
