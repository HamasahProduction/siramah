@extends('adminlte::page')

@section('title', 'Referensi Dokter')

@section('content_header')
    <h1>Referensi Dokter</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Data Dokter" theme="info" icon="fas fa-info-circle" collapsible maximizable>
                @php
                    $heads = ['Kode BPJS', 'Kode SIMRS', 'Nama Dokter', 'SIP', 'Status', 'Action'];
                @endphp
                <x-adminlte-datatable id="table2" :heads="$heads" bordered hoverable compressed>
                    @foreach ($dokter as $item)
                        <tr>
                            <td>{{ $item->kodedokter }}</td>
                            <td>{{ $item->paramedis ? $item->paramedis->kode_paramedis : '-' }}</td>
                            <td>{{ $item->namadokter }}</td>
                            <td>{{ $item->paramedis ? $item->paramedis->sip_dr : '-' }}</td>
                            <td>
                                @if ($item->paramedis)
                                    <a href="#" class="btn btn-xs btn-secondary">Sudah
                                        Ada</a>
                                @else
                                    <a href="#" class="btn btn-xs btn-danger">Belum
                                        Ada</a>
                                @endif
                            </td>
                            <td>
                                <x-adminlte-button class="btn-xs btnEdit" label="Edit" theme="warning" icon="fas fa-edit"
                                    data-toggle="tooltip" title="Edit Dokter {{ $item->nama_paramedis }}"
                                    data-id="{{ $item->kode_paramedis }}" />
                            </td>
                        </tr>
                    @endforeach
                </x-adminlte-datatable>
                <a href="{{ route('dokter.create') }}" class="btn btn-success">Refresh User Dokter</a>
            </x-adminlte-card>
        </div>
    </div>
    <x-adminlte-modal id="modalEdit" title="Edit Dokter" theme="warning" icon="fas fa-user-plus">
        <form name="formInput" id="formInput" action="" method="post">
            @csrf
            <input type="hidden" name="antrianid" id="antrianid" value="">
            <x-adminlte-input name="kode_paramedis" placeholder="Kode Dokter" label="Kode Dokter" readonly />
            <x-adminlte-input name="kode_dokter_jkn" placeholder="Kode BPJS" label="Kode BPJS" />
            <x-adminlte-input name="nama_paramedis" placeholder="Nama Dokter" label="Nama Dokter" />
            <x-adminlte-input name="sip_dr" placeholder="SIP" label="SIP" />
            <x-slot name="footerSlot">
                {{-- <x-adminlte-button class="mr-auto btnSuratKontrol" label="Buat Surat Kontrol" theme="primary"
                    icon="fas fa-prescription-bottle-alt" />
                <a href="#" id="lanjutFarmasi" class="btn btn-success withLoad"> <i
                        class="fas fa-prescription-bottle-alt"></i>Farmasi Non-Racikan</a>
                <a href="#" id="lanjutFarmasiRacikan" class="btn btn-success withLoad"> <i
                        class="fas fa-prescription-bottle-alt"></i>Farmasi Racikan</a>
                <a href="#" id="selesaiPoliklinik" class="btn btn-warning withLoad"> <i class="fas fa-check"></i>
                    Selesai</a> --}}
                <x-adminlte-button theme="danger " label="Tutup" icon="fas fa-times" data-dismiss="modal" />
            </x-slot>
        </form>
    </x-adminlte-modal>
@stop

@section('plugins.Select2', true)
@section('plugins.Datatables', true)

@section('js')
    <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $('.btnEdit').click(function() {
                var id = $(this).data('id');
                $.LoadingOverlay("show");
                var url = "{{ route('dokter.index') }}/" + id;
                $.get(url, function(data) {
                    console.log(data);
                    $('#kode_paramedis').val(data.kode_paramedis);
                    $('#kode_dokter_jkn').val(data.kode_dokter_jkn);
                    $('#nama_paramedis').val(data.nama_paramedis);
                    $('#sip_dr').val(data.sip_dr);
                    $('#modalEdit').modal('show');
                })
                $.LoadingOverlay("hide", true);
            });
        });
    </script>
@endsection