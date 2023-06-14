@extends('adminlte::page')
@section('title', 'Dashboard Tanggal - Antrian BPJS')
@section('content_header')
    <h1 class="m-0 text-dark">Dashboard Tanggal Antrian BPJS</h1>
@stop
@section('content')
    <div class="row">
        <div class="col-12">
            <x-adminlte-card title="Pencarian Dashboad Tanggal Antrian" theme="secondary" icon="fas fa-info-circle"
                collapsible>
                <form action="">
                    @php
                        $config = ['format' => 'YYYY-MM-DD'];
                    @endphp
                    <x-adminlte-input-date name="tanggal" placeholder="Silahkan Pilih Tanggal" value="{{ $request->tanggal }}"
                        label="Tanggal Periksa" :config="$config" />
                    <x-adminlte-select name="waktu" label="Waktu">
                        <option value="rs">Waktu RS</option>
                        <option value="server">Waktu BPJS</option>
                    </x-adminlte-select>
                    <x-adminlte-button label="Cari Antrian" class="mr-auto withLoad" type="submit" theme="success"
                        icon="fas fa-search" />
                </form>
            </x-adminlte-card>
            <x-adminlte-card title="Data Task ID Antrian" theme="secondary" collapsible>
                @php
                    $heads = ['Poliklinik', 'Total Antrian', 'Checkin', 'Daftar', 'Tunggu Poli', 'Layan Poli', 'Tunggu Farmasi', 'Proses Farmasi', 'Selesai'];
                    $config = ['paging' => false];

                @endphp
                <x-adminlte-datatable id="table2" class="text-xs" :heads="$heads" :config="$config" hoverable bordered
                    compressed>
                    @isset($antrians)
                        @foreach ($antrians->groupBy('namapoli') as $key => $item)
                            <tr>
                                <td>{{ $key }}</td>
                                <td>{{ $item->sum('jumlah_antrean') }}</td>
                                <td>{{ round($item->sum('avg_waktu_task1') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task2') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task3') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task4') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task5') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task6') / 60 / $item->count()) }} menit</td>
                                <td>{{ round($item->sum('avg_waktu_task7') / 60 / $item->count()) }} menit</td>
                            </tr>
                        @endforeach
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th>{{ $antrians->sum('jumlah_antrean') }}</th>
                                <td>{{ round($antrians->sum('avg_waktu_task1') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task2') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task3') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task4') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task5') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task6') / 60 / $antrians->count()) }}
                                    menit</td>
                                <td>{{ round($antrians->sum('avg_waktu_task7') / 60 / $antrians->count()) }}
                                    menit</td>
                            </tr>
                        </tfoot>
                    @endisset
                </x-adminlte-datatable>
            </x-adminlte-card>
        </div>
    </div>
@stop
@section('plugins.Datatables', true)
@section('plugins.TempusDominusBs4', true)
@section('plugins.Select2', true)
