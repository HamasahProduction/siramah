@extends('adminlte::page')
@section('title', 'Data Pegawai Nonaktif')
@section('content_header')
    <h1>Informasi Pegawai Nonaktif</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="col-md-12">
                <x-adminlte-card theme="success" icon="fas fa-info-circle" collapsible
                    title="List Data Pegawai">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-md-3">
                                <x-adminlte-small-box
                                    theme="success" 
                                    text="Tambah Data Baru"
                                    url="#"
                                    url-text="Buat Data Baru" />
                            </div>
                        </div>
                    </div>
                    <form id="formFilter" action="" method="get">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="unit-group" id="tingkatpendidikan">
                                <x-adminlte-select2 name="tingkat" label="Tingkat Pendidikan">
                                    <option value="" >--Pilih Tingkat Pendidikan--</option>
                                    @foreach ($tingkat as $item)
                                        <option {{ $item->id_tingkat == $id_tingkat ?  'selected':'' }} value="{{ $item->id_tingkat }}">{{$item->nama_tingkat}}</option>
                                    @endforeach
                                </x-adminlte-select2>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <a href="{{route('data-kepeg.get')}}" class="btn btn-sm btn-success float-right mt-4">Pegawai Aktif</a>
                            <x-adminlte-button type="submit" id="lihatData" class="withLoad float-right btn btn-sm m-1 mt-4 bg-purple" label="Lihat Data" />
                        </div>
                    </div>
                </form>
                    @php
                        $heads = ['NIK', ' Nama', 'Jenis Kelamin', 'Jenjang', 'Jurusan','Format Pendidikan','Action'];
                        $config['order'] = ['0', 'asc'];
                        $config['paging'] = false;
                        $config['info'] = false;
                        $config['scrollY'] = '500px';
                        $config['scrollCollapse'] = true;
                        $config['scrollX'] = true;
                    @endphp
                    <x-adminlte-datatable id="table1" class="nowrap text-xs" :heads="$heads" :config="$config"
                        striped bordered hoverable compressed>
                            @foreach ($data as $item)
                                <tr>
                                    <td>{{$item->nik}}</td>
                                    <td>{{$item->nama_lengkap}}</td>
                                    <td>{{$item->jenis_kelamin == "L" ? 'LAKI-LAKI' : 'Perempuan'}}</td>
                                    <td>{{$item->sPendidikan->nama_tingkat}}</td>
                                    <td style="width: 50px;">{{$item->jurusan}}</td>
                                    <td>{{$item->format_pendidikan}}</td>
                                    <td>
                                        <x-adminlte-button class="btn-xs" theme="success" icon="fas fa-check" label="Aktifkan Karyawan"
                                        onclick="ActiveConfirmation({{$item->id}})" />
                                    </td>
                                </tr>
                            @endforeach
                    </x-adminlte-datatable>
                </x-adminlte-card>
            </div>
        </div>
    </div>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)
@section('plugins.BsCustomFileInput', true)
@section('plugins.Sweetalert2', true)

@section('js')
    <script>
        $(document).on('click', '#lihatData', function(e) {
            $.LoadingOverlay("show");
            var data = $('#formFilter').serialize();
            var url = "{{ route('jabatan-kepeg.get') }}?" + data;
            window.location = url;
            $.ajax({
                    data: data,
                    url: url,
                    type: "GET",
                    success: function(data) {
                        setInterval(() => {
                            $.LoadingOverlay("hide");
                        }, 7000);
                    },
                }).then(function() {
                    setInterval(() => {
                        $.LoadingOverlay("hide");
                    }, 2000);
                });
        })

        function ActiveConfirmation(id) {
        swal.fire({
            icon: 'warning',
            title: "Apakah Anda Yakin?",
            text: "Mengaktifkan Pegawai ini!",
            type: "warning",
            showCancelButton: !0,
            confirmButtonText: "IYA, Aktifkan!",
            cancelButtonText: "Batal!",
            reverseButtons: !0
        }).then(function (e) {

            if (e.value === true) {
                var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    type: 'POST',
                    url: "{{url('data-pegawai/set-pegawai-aktif/')}}/" + id,
                    data: {_token: CSRF_TOKEN},
                    dataType: 'JSON',
                    success: function (results) {
                        if (results.success === true) {
                            swal.fire("Done!", results.message, "success");
                            setTimeout(function(){
                                $.LoadingOverlay("show");
                                location.reload();
                            },1500);
                            $.LoadingOverlay("hide");
                        } else {
                            swal.fire("Error!", results.message, "error");
                        }
                    }
                });

            } else {
                e.dismiss;
            }

        }, function (dismiss) {
            return false;
        })
    }
    </script>
@endsection


