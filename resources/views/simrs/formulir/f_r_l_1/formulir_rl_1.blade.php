@extends('adminlte::page')
@section('title', 'Formulir RL 1')
@section('content_header')
    <h1>Data Formulir Belum di Perbaharui !!</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Filter Formulir RL 1" theme="secondary" id="hide_div" collapsible>
                <form id="formFilter" action="" method="get">
                    <div class="row">
                        <div class="col-md-3">
                            @php
                                $config = ['format' => 'YYYY-MM-DD'];
                            @endphp
                            <x-adminlte-input-date name="dari" id="from" label="Tanggal Mulai" :config="$config"
                                {{-- value="{{ $from == null ? \Carbon\Carbon::parse($request->dari)->format('Y-m-d') : $from }}"> --}}
                                value="2023-06-01">
                                <x-slot name="prependSlot">
                                    <div class="input-group-text bg-primary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input-date>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4">
                                    <x-adminlte-input-date name="sampai" id="to" label="Tanggal Selesai" :config="$config"
                                        {{-- value="{{ $to == null ? \Carbon\Carbon::parse($request->sampai)->format('Y-m-d') : $to }}"> --}}
                                        value="2023-06-30">
                                        <x-slot name="prependSlot">
                                            <div class="input-group-text bg-primary">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        </x-slot>
                                    </x-adminlte-input-date>
                                </div>
                                <div class="col-md-8">
                                    <div class="row mt-4 float-right">
                                        <x-adminlte-button type="submit" class="withLoad btn btn-sm m-1" theme="primary" label="Lihat Laporan" />
                                        <x-adminlte-button label="Excel" class="bg-purple btn btn-sm m-1" id="export" />
                                        <button class="btn btn-success btn btn-sm m-1" onclick="printDiv('printMe')">Print <i class="fas fa-print"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </x-adminlte-card>
            @if (isset($laporanDS))
                <div id="printMe">
                    <section class="invoice p-3 mb-3">
                        <div class="row p-3 kop-surat">
                            <img src="{{ asset('vendor/adminlte/dist/img/rswaledico.png') }}" style="width: 100px">
                            <div class="col">
                                <b>RUMAH SAKIT UMUM DAERAH WALED KABUPATEN CIREBON</b><br>
                                Jalan Raden Walangsungsang Kecamatan Waled Kabupaten Cirebon 45188<br>
                                www.rsudwaled.id - brsud.waled@gmail.com - Call Center (0231) 661126
                            </div>
                            <div class="col"><i class="fas fa-hospital float-right" style="color: #fcf7f7e7"></i></div>
                            <hr width="100%" hight="20px" class="m-1 " color="black" size="50px" />
                        </div>
                        <div class="row invoice-info p-3">
                            <div class="row">
                                <dt class="col-sm-4 m-0">Judul</dt>
                                <dd class="col-sm-8 m-0"> :
                                    <b> Formulir RL 1</b>
                                </dd>
                                <dt class="col-sm-4 m-0">Periode</dt>
                                <dd class="col-sm-8 m-0"> :
                                    <b> 2023 s.d 2023</b>
                                </dd>
                            </div>
                        </div>
                        <div class="col-12 table-responsive table-laporan">
                            <table class="table table-sm" id="table-laporan">
                                <thead>
                                    <th>No</th>
                                    <th>Keterangan</th>
                                    <th>Data</th>
                                </thead>
                                <tbody>

                                </tbody>

                            </table>
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('plugins.Select2', true)
@section('plugins.Datatables', true)
@section('plugins.DatatablesPlugins', true)
@section('plugins.TempusDominusBs4', true)

@section('js')
    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            window.print(printContents);
        }
        $(document).on('click', '#export', function(e) {
            $.LoadingOverlay("show");
            var data = $('#formFilter').serialize();
            var url = "{{ route('laporan-survailans.export') }}?" + data;
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
    </script>
@endsection
@section('css')
    <style type="text/css" media="print">
        @media print {
            @page {
                size: Legal landscape;
            }
        }

        hr {
            color: #333333 !important;
            border: 1px solid #333333 !important;
            line-height: 1.5;
        }

        .table-laporan {
            font-size: 9px;
            margin-left: -5px;
        }

        .table-laporan #golumr {
            font-size: 6px;
        }

        .table-laporan #alamat {
            width: 80px;
        }

        .table-laporan #terapi {
            width: 90px;
        }

        .table-laporan #hide_print {
            display: none;
        }

        .table-laporan #show_print {
            display: block;
        }

        .table-laporan #terapi {
            width: 45px;
        }

        .table-laporan #deskripsi {
            width: 40px;
        }

        .table-laporan #alamat {
            width: 40px;
        }

        .table-laporan #no {
            width: 10px;
        }

        #hide_div {
            display: none;
        }
    </style>
    <style>
        #golumr {
            font-size: 7px;
        }

        #show_print {
            display: none;
        }
        thead, th, tbody {
            border: 1px solid #dfdcdc !important;
        }
    </style>

@endsection

