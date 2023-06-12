<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalDokter;
use App\Models\Kunjungan;
use App\Models\Layanan;
use App\Models\LayananDetail;
use App\Models\Paramedis;
use App\Models\Pasien;
use App\Models\Penjamin;
use App\Models\Poliklinik;
use App\Models\SuratKontrol;
use App\Models\TarifLayananDetail;
use App\Models\Token;
use App\Models\Tracer;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use RealRashid\SweetAlert\Facades\Alert;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AntrianController extends APIController
{
    public function edit($id)
    {
        $antrian = Antrian::find($id);
        return response()->json($antrian);
    }
    public function antrianFarmasi(Request $request)
    {
        $antrians = null;
        if ($request->tanggal) {
            $request['tanggal'] = Carbon::now()->format('Y-m-d');
            $antrians = Antrian::whereDate('tanggalperiksa', $request->tanggal)
                ->where('taskid', '>=', 3)->get();
        }
        // $polis = PoliklinikDB::where('status', 1)->get();
        // $dokters = Dokter::get();
        return view('simrs.antrian_farmasi', [
            'antrians' => $antrians,
            'request' => $request,
            // 'polis' => $polis,
            // 'dokters' => $dokters,
        ]);
    }
    public function racikFarmasi($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        if ($antrian) {
            $request['kodebooking'] = $antrian->kodebooking;
            $request['taskid'] = 6;
            $request['keterangan'] = "Proses peracikan obat";
            $request['waktu'] = Carbon::now()->timestamp * 1000;

            $api = new AntrianController();
            $response = $api->update_antrean($request);
            if ($response->metadata->code == 200) {
                $antrian->update([
                    'taskid' => $request->taskid,
                    'status_api' => 1,
                    'keterangan' => $request->keterangan,
                    'user' => Auth::user()->name,
                ]);
                // try {
                //     // notif wa
                //     $wa = new WhatsappController();
                //     $request['message'] = "Resep obat atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " telah diterima farmasi. Silahkan menunggu peracikan obat.";
                //     $request['number'] = $antrian->nohp;
                //     $wa->send_message($request);
                // } catch (\Throwable $th) {
                //     //throw $th;
                // }
                Alert::success('Success', 'Resep Obat Pasien Diterima Farmasi');
            } else {
                Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
            }
            return redirect()->back();
        } else {
            Alert::error('Error', 'Kodebooking tidak ditemukan');
            return redirect()->back();
        }
    }
    public function selesaiFarmasi($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 7;
        $request['keterangan'] = "Selesai peracikan obat";
        $request['waktu'] = Carbon::now()->timestamp * 1000;
        $api = new AntrianController();
        $response = $api->update_antrean($request);
        if ($response->metadata->code == 200) {
            $antrian->update([
                'taskid' => $request->taskid,
                'status_api' => 1,
                'keterangan' => $request->keterangan,
                'user' => Auth::user()->name,
            ]);
            // try {
            //     // notif wa
            //     $wa = new WhatsappController();
            //     $request['message'] = "Resep obat atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " telah diterima farmasi. Silahkan menunggu peracikan obat.";
            //     $request['number'] = $antrian->nohp;
            //     $wa->send_message($request);
            // } catch (\Throwable $th) {
            //     //throw $th;
            // }
            Alert::success('Success', 'Selesai Peracikan Obat.');
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        return redirect()->back();
    }
    // VIEW SIMRS
    public function daftarOnline(Request $request)
    {
        $rujukans = null;
        $suratkontrols = null;
        $polikliniks = Poliklinik::where('status', 1)->orderBy('namasubspesialis', 'asc')->get();
        $jadwals = null;
        $pasien = null;
        $vclaim = new VclaimController();
        $request['method'] = 'Whatsapp';
        if ($request->nik) {
            $pasien = Pasien::firstWhere('nik_bpjs', $request->nik);
            if ($pasien) {
                $pasien['no_hp'] = $request->nohp;
                // $pasien->update([]);
                Alert::success('Success', 'Data pasien ditemukan');
            } else {
                Alert::error('Maaf', 'Data pasien tidak ditemukan, silahkan daftar offline untuk pasien baru');
            }
        }
        if ($request->norm && $request->kodepoli && $request->tanggalperiksa && $request->jenispasien) {
            $hari = Carbon::parse($request->tanggalperiksa)->dayOfWeek;
            $jadwals = JadwalDokter::where('kodesubspesialis', $request->kodepoli)
                ->where('hari', $hari)->get();
            if ($jadwals->count() != 0) {
                Alert::success('Success', 'Tersedia jadwal dokter poliklinik dihari tsb.');
            } else {
                $jadwals = null;
                Alert::error('Maaf', 'Data jadwal poliklinik tidak tersedia dihari tsb.');
            }
            // pasien umum
            if ($request->jenispasien == "NON-JKN" && $request->kodedokter) {
                if ($jadwals) {
                    $jadwal = $jadwals->firstWhere('kodedokter', $request->kodedokter);
                    $request['jampraktek'] = $jadwal->jadwal;
                    $request['jeniskunjungan'] = 3;
                    $res = $this->ambil_antrian($request);
                    if ($res->metadata->code == 200) {
                        $kodebooking = $res->response->kodebooking;
                        Alert::success('Berhasil', 'Anda berhasil daftar rawat jalan dengan kodebooking ' . $kodebooking);
                        return redirect()->route('checkAntrian', [
                            'kodebooking' => $kodebooking
                        ]);
                    } else {
                        Alert::error('Maaf', $res->metadata->message);
                    }
                } else {
                    Alert::error('Maaf', 'Data jadwal poliklinik tidak tersedia dihari tsb.');
                }
            }
            // pasien bpjs
            if ($request->jenispasien == "JKN" && $request->jeniskunjungan) {
                switch ($request->jeniskunjungan) {
                    case '1':
                        $res = $vclaim->rujukan_peserta($request);
                        if ($res->metadata->code == 200) {
                            $rujukansx = $res->response->rujukan;
                            foreach ($rujukansx as  $rujukan) {
                                $hari = Carbon::parse($rujukan->tglKunjungan)->diffInDays(now());
                                if ($hari < 90) {
                                    $rujukans[] = $rujukan;
                                }
                            }
                            Alert::success('Success', 'Ditemukan surat rujukan');
                        }
                        break;

                    case '3':
                        $request['bulan'] = Carbon::parse($request->tanggalperiksa)->month;
                        $request['tahun'] = Carbon::parse($request->tanggalperiksa)->year;
                        $request['formatfilter'] = 2;
                        $res = $vclaim->suratkontrol_peserta($request);
                        if ($res->metadata->code == 200) {
                            $rujukansx = $res->response->list;
                            foreach ($rujukansx as  $rujukan) {
                                // $hari = Carbon::parse($rujukan->tglKunjungan)->diffInDays(now());
                                if ($rujukan->terbitSEP == 'Belum') {
                                    $suratkontrols[] = $rujukan;
                                }
                            }
                            Alert::success('Success', 'Ditemukan surat kontrol');
                        }
                        break;

                    case '4':
                        $res = $vclaim->rujukan_rs_peserta($request);
                        if ($res->metadata->code == 200) {
                            $rujukansx = $res->response->rujukan;
                            foreach ($rujukansx as  $rujukan) {
                                $hari = Carbon::parse($rujukan->tglKunjungan)->diffInDays(now());
                                if ($hari < 90) {
                                    $rujukans[] = $rujukan;
                                }
                            }
                            Alert::success('Success', 'Ditemukan surat rujukan');
                        }
                        break;

                    default:
                        Alert::error('Maaf', 'Silahkan pilih jenis kunjungan.');
                        break;
                }
                // rujukan
                if ($jadwals && $request->jeniskunjungan == 1  &&  $request->nomorreferensi && $request->kodedokter || $jadwals && $request->jeniskunjungan == 5  &&  $request->nomorreferensi && $request->kodedokter) {
                    $jadwal = $jadwals->firstWhere('kodedokter', $request->kodedokter);
                    $rujukan = collect($rujukans)->where('noKunjungan', $request->nomorreferensi)->first();
                    if ($jadwal->kodesubspesialis == $rujukan->poliRujukan->kode) {
                        $request['jampraktek'] = $jadwal->jadwal;
                        $res = $this->ambil_antrian($request);
                        if ($res->metadata->code == 200) {
                            $kodebooking = $res->response->kodebooking;
                            Alert::success('Berhasil', 'Anda berhasil daftar rawat jalan dengan kodebooking ' . $kodebooking);
                            return redirect()->route('checkAntrian', [
                                'kodebooking' => $kodebooking
                            ]);
                        } else {
                            Alert::error('Maaf', $res->metadata->message);
                        }
                    } else {
                        Alert::error('Maaf', 'Poliklinik rujukan anda berbeda dengan poliklinik pilihan anda.');
                    }
                }
                // surat kontrol
                else if ($jadwals && $request->jeniskunjungan == 3 &&  $request->nomorreferensi && $request->kodedokter) {
                    $jadwal = $jadwals->firstWhere('kodedokter', $request->kodedokter);
                    if ($jadwal) {
                        $suratkontrol = collect($suratkontrols)->where('noSuratKontrol', $request->nomorreferensi)->first();
                        if ($suratkontrol->tglRencanaKontrol == $request->tanggalperiksa) {
                            if ($jadwal->kodesubspesialis == $suratkontrol->poliTujuan) {
                                $request['jampraktek'] = $jadwal->jadwal;
                                $res = $this->ambil_antrian($request);
                                if ($res->metadata->code == 200) {
                                    $kodebooking = $res->response->kodebooking;
                                    Alert::success('Berhasil', 'Anda berhasil daftar rawat jalan dengan kodebooking ' . $kodebooking);
                                    return redirect()->route('checkAntrian', [
                                        'kodebooking' => $kodebooking
                                    ]);
                                } else {
                                    Alert::error('Maaf', $res->metadata->message);
                                }
                            } else {
                                Alert::error('Maaf', 'Poliklinik rujukan anda berbeda dengan poliklinik pilihan anda.');
                            }
                        } else {
                            Alert::error('Maaf', 'Tanggal kunjungan surat kontrol (' . $suratkontrol->tglRencanaKontrol . ') berbeda dengan tanggal periksa pilihan anda. Silahkan rubah tanggal kontrol anda jika telah terlewati waktunya.');
                        }
                    } else {
                        Alert::error('Maaf', 'Jadwal dokter tidak tersedia.');
                    }
                } else {
                    Alert::error('Maaf', 'Silahkan pilih nomor referensi dan jadwal dokter.');
                }
            }
        } else {
            if ($request->tanggalperiksa && $request->kodepoli == null ||  $request->tanggalperiksa && $request->jenispasien == null) {
                Alert::error('Maaf', 'Silahkan pilih jenis pasien dan poliklinik');
            }
        }

        return view('simrs.daftar_online', compact([
            'request',
            'rujukans',
            'polikliniks',
            'jadwals',
            'pasien',
            'suratkontrols'
        ]));
    }
    public function ambilAntrianWeb(Request $request)
    {
        $base = new BaseController();
        $validator = Validator::make(request()->all(), [
            "nomorkartu" => "required|numeric|digits:13",
            "nik" => "required|numeric|digits:16",
            "nohp" => "required",
            "kodepoli" => "required",
            "norm" => "required",
            "tanggalperiksa" => "required",
            "kodedokter" => "required",
            // "nomorreferensi" => "numeric",
        ]);
        if ($validator->fails()) {
            return $base->sendError($validator->errors()->first(), 400);
        }
        if ($request->jenispasien == 'NON-JKN') {
            $request['jeniskunjungan'] = 3;
        }
        $request['method'] = 'Web';
        $hari = Carbon::parse($request->tanggalperiksa)->dayOfWeek;
        $jadwal = JadwalDokter::where('hari', $hari)
            ->where('kodesubspesialis', $request->kodepoli)
            ->where('kodedokter', $request->kodedokter)
            ->first();
        $request['jampraktek'] = $jadwal->jadwal;
        $res =  $this->ambil_antrian($request);

        if ($res->metadata->code == 200) {
            return $base->sendResponse($res->response, 200);
        } else {
            return $base->sendError($res->metadata->message, $res->metadata->code);
        }
    }
    public function cekKodebooking(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $base = new BaseController();
        if ($antrian) {
            $taskid = [
                'Belum Checkin',
                'Menunggu Pendaftaran',
                'Proses Pendaftaran',
                'Menunggu Poliklinik',
                'Proses Poliklinik',
                'Menunggu Farmasi',
                'Proses Farmasi',
                'Selesai',
                99 => 'Batal'
            ];
            $response = [
                "nomorantrean" => $antrian->nomorantrean,
                "angkaantrean" => $antrian->angkaantrean,
                "kodebooking" => $antrian->kodebooking,
                "norm" =>  substr($antrian->norm, 2),
                "namapasien" => $antrian->nama,
                "status" => $taskid[$antrian->taskid],
                "namapoli" => $antrian->namapoli,
                "namadokter" => $antrian->namadokter,
                "estimasidilayani" => $antrian->estimasidilayani,
                "keterangan" => $antrian->keterangan,
            ];
            return $base->sendResponse($response, 200);
        } else {
            return $base->sendError('Kodebooking tidak ditemukan', 404);
        }
    }
    public function checkAntrian(Request $request)
    {
        $antrian = null;
        if ($request->kodebooking) {
            $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        }
        return view('simrs.check_antrian', compact([
            'request',
            'antrian',
        ]));
    }

    public function statusAntrianBpjs()
    {
        $token = Token::latest()->first();
        return view('bpjs.antrian.status', compact([
            'token'
        ]));
    }
    public function laporanAntrianPoliklinik(Request $request)
    {
        if ($request->tanggal == null) {
            $tanggal_awal = now()->startOfDay()->format('Y-m-d');
            $tanggal_akhir = now()->endOfDay()->format('Y-m-d');
        } else {
            $tanggal = explode(' - ', $request->tanggal);
            $tanggal_awal = Carbon::parse($tanggal[0])->format('Y-m-d');
            $tanggal_akhir = Carbon::parse($tanggal[1])->format('Y-m-d');
        }
        $antrians = Antrian::whereBetween('tanggalperiksa', [$tanggal_awal, $tanggal_akhir])
            ->get();
        $kunjungans = Kunjungan::whereBetween('tgl_masuk', [Carbon::parse($tanggal_awal)->startOfDay(), Carbon::parse($tanggal_akhir)->endOfDay()])
            ->where('kode_unit', "!=", null)
            ->where('kode_unit', 'LIKE', '10%')
            ->where('kode_unit', '!=', 1002)
            ->where('kode_unit', "!=", 1023)
            ->where('kode_unit', "!=", 1015)
            ->get();
        $units = Unit::where('KDPOLI', '!=', null)->get();
        return view('simrs.laporan_kunjungan', [
            'antrians' => $antrians,
            'request' => $request,
            'kunjungans' => $kunjungans,
            'units' => $units,
        ]);
    }
    public function checkinUpdate(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "waktu" => "required|numeric",
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => $validator->errors()->first(),
                ],
            ];
            return $response;
        }
        // cari antrian
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (isset($antrian)) {
            $api = new AntrianController();
            $response = $api->checkin_antrian($request);
            return $response;
        }
        // jika antrian tidak ditemukan
        else {
            return $response = [
                'metadata' => [
                    'code' => 400,
                    'message' => "Antrian tidak ditemukan",
                ],
            ];
        }
    }
    public function antrian(Request $request)
    {
        // get poli
        $response = $this->ref_poli();
        if ($response->metadata->code == 200) {
            $polikliniks = $response->response;
        } else {
            $polikliniks = null;
        }
        // get antrian
        $antrians = null;
        if (isset($request->kodepoli)) {
            $antrians = Antrian::whereDate('tanggalperiksa', $request->tanggal)->get();
            if ($request->kodepoli != '000') {
                $antrians = $antrians->where('kodepoli', $request->kodepoli);
            }
            Alert::success('OK', 'Antrian BPJS Total : ' . $antrians->count());
        }
        return view('bpjs.antrian.antrian', compact([
            'request',
            'polikliniks',
            'antrians',
        ]));
    }
    public function listTaskID(Request $request)
    {
        // get antrian
        $taskid = null;
        if (isset($request->kodebooking)) {
            $response =  $this->taskid_antrean($request);
            if ($response->metadata->code == 200) {
                $taskid = $response->response;
            }
            Alert::success($response->metadata->message . ' ' . $response->metadata->code);
        }
        return view('bpjs.antrian.list_task', compact([
            'request',
            'taskid',
        ]));
    }
    public function antrianCapaian(Request $request)
    {
        $antrians_total = Antrian::select(
            DB::raw("count(*) as total"),
            DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
        )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();

        $tanggal_awal = Antrian::orderBy('tanggalperiksa', 'ASC')->first()->tanggalperiksa;
        $kunjungans = Kunjungan::whereBetween('tgl_masuk', [Carbon::parse($tanggal_awal)->startOfDay(), Carbon::now()->endOfDay()])
            ->where('kode_unit', "!=", null)
            ->where('kode_unit', 'LIKE', '10%')
            ->where('kode_unit', '!=', 1002)
            ->where('kode_unit', "!=", 1023)
            ->where('kode_unit', "!=", 1015)
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(tgl_masuk, '%Y-%m')) as bulan")
            )
            ->orderBy('tgl_masuk')
            ->groupBy(DB::raw("DATE_FORMAT(tgl_masuk, '%Y-%m')"))
            ->get();
        $antrian_nobatal = Antrian::where('taskid', '!=', 99)
            ->where('method', '!=', 'Offline')
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();

        $antrian_selesai = Antrian::whereIn('taskid',  [5, 7])
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();

        $antrian_whatsapp = Antrian::where('taskid', '!=', 99)
            ->whereIn('method', ['Whatsapp', 'ON'])
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();


        $antrian_jkn = Antrian::where('taskid', '!=', 99)
            ->whereIn('method', ['JKN Mobile'])
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();

        $antrian_lainnya = Antrian::where('taskid', '!=', 99)
            ->whereNotIn('method', ['JKN Mobile', 'Whatsapp', 'ON', 'OFF', 'Offline'])
            ->select(
                DB::raw("count(*) as total"),
                DB::raw("(DATE_FORMAT(created_at, '%Y-%m')) as bulan")
            )
            ->orderBy('created_at')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->get();

        return view('simrs.pendaftaran.capaian_antrian', compact([
            // 'antrians_total',
            'antrian_nobatal',
            'antrian_selesai',
            'antrian_whatsapp',
            'antrian_jkn',
            'antrian_lainnya',
            'kunjungans',
            'request',
        ]));
    }
    public function dashboardTanggalAntrian(Request $request)
    {
        $antrians = null;
        if (isset($request->waktu)) {
            $response =  $this->dashboard_tanggal($request);
            if ($response->metadata->code == 200) {
                $antrians = $response->response->list;
                Alert::success($response->metadata->message . ' ' . $response->metadata->code);
            } else {
                Alert::error($response->metadata->message . ' ' . $response->metadata->code);
            }
        }
        return view('bpjs.antrian.dashboard_tanggal_index', compact([
            'request',
            'antrians',
        ]));
    }
    public function dashboardBulanAntrian(Request $request)
    {
        $antrians = null;
        if (isset($request->tanggal)) {
            $tanggal = explode('-', $request->tanggal);
            $request['tahun'] = $tanggal[0];
            $request['bulan'] = $tanggal[1];
            $response =  $this->dashboard_bulan($request);
            if ($response->metadata->code == 200) {
                $antrians = $response->response->list;
                Alert::success($response->metadata->message . ' ' . $response->metadata->code);
            } else {
                Alert::error($response->metadata->message . ' ' . $response->metadata->code);
            }
        }
        return view('bpjs.antrian.dashboard_bulan_index', compact([
            'request',
            'antrians',
        ]));
    }
    public function antrianPerTanggal(Request $request)
    {
        $antrians = null;
        if (isset($request->tanggal)) {
            $response = $this->antrian_tanggal($request);
            if ($response->metadata->code == 200) {
                $antrians = $response->response;
            } else {
                Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
                return redirect()->route('bpjs.antrian.antrian_per_tanggal');
            }
        }
        return view('bpjs.antrian.antrian_per_tanggal', compact(['request', 'antrians']));
    }
    public function antrianPerKodebooking(Request $request)
    {
        $antrian = null;
        if ($request->kodebooking) {
            $request['kodeBooking'] = $request->kodebooking;
            $response = $this->antrian_kodebooking($request);
            if ($response->metadata->code == 200) {
                $antrian = $response->response[0];
            } else {
                Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
                return redirect()->route('bpjs.antrian.antrian_per_tanggal');
            }
        }
        return view('bpjs.antrian.antrian_per_kodebooking', compact([
            'request', 'antrian'
        ]));
    }
    public function antrianBelumDilayani(Request $request)
    {
        $request['tanggal'] = now()->format('Y-m-d');
        $response = $this->antrian_belum_dilayani($request);
        if ($response->metadata->code == 200) {
            $antrians = $response->response;
        } else {
            $antrians = null;
            Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
            return redirect()->route('antrian.laporan_tanggal');
        }
        return view('bpjs.antrian.antrian_belum_dilayani', compact(['request', 'antrians']));
    }
    public function antrianPerDokter(Request $request)
    {
        $antrians = null;
        $jadwaldokter = JadwalDokter::orderBy('hari', 'ASC')->get();
        if (isset($request->jadwaldokter)) {
            $jadwal = JadwalDokter::find($request->jadwaldokter);
            $request['kodePoli'] = $jadwal->kodesubspesialis;
            $request['kodeDokter'] = $jadwal->kodedokter;
            $request['hari'] = $jadwal->hari;
            $request['jamPraktek'] = $jadwal->jadwal;
            $response = $this->antrian_poliklinik($request);
            if ($response->metadata->code == 200) {
                $antrians = $response->response;
            } else {
                Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
            }
        }
        return view('bpjs.antrian.antrian_per_dokter', [
            'antrians' => $antrians,
            'jadwaldokter' => $jadwaldokter,
            'request' => $request,
        ]);
    }
    public function antrianPoliklinik(Request $request)
    {
        $antrians = null;
        if ($request->tanggal) {
            $antrians = Antrian::whereDate('tanggalperiksa', $request->tanggal);
            if ($request->kodepoli != null) {
                $antrians = $antrians->where('method', '!=', 'Offline')->where('kodepoli', $request->kodepoli)->get();
            }
            if ($request->kodedokter != null) {
                $antrians = $antrians->where('method', '!=', 'Offline')->where('kodedokter', $request->kodedokter)->get();
            }
            if ($request->kodepoli == null && $request->kodedokter == null) {
                $antrians = $antrians->where('method', '!=', 'Offline')->get();
            }
        }
        $polis = Poliklinik::where('status', 1)->get();
        $dokters = Paramedis::where('kode_dokter_jkn', "!=", null)
            ->where('unit', "!=", null)
            ->get();
        if (isset($request->kodepoli)) {
            $poli = Unit::firstWhere('KDPOLI', $request->kodepoli);
            $dokters = Paramedis::where('unit', $poli->kode_unit)
                ->where('kode_dokter_jkn', "!=", null)
                ->get();
        }
        return view(
            'simrs.poliklinik.poliklinik_antrian',
            compact([
                'antrians',
                'request',
                'polis',
                'dokters'
            ])
        );
    }
    public function batalAntrian(Request $request)
    {
        $request['taskid'] = 99;
        $request['keterangan'] = "Antrian dibatalkan di poliklinik oleh " . Auth::user()->name;
        $response = $this->batal_antrian($request);
        if ($response->metadata->code == 200) {
            Alert::success('Success ' . $response->metadata->code, $response->metadata->message);
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        return redirect()->back();
    }
    public function batalPendaftaran(Request $request)
    {
        $request['taskid'] = 99;
        $request['keterangan'] = "Antrian dibatalkan oleh pasien";
        $response = $this->batal_antrian($request);
        if ($response->metadata->code == 200) {
            Alert::success('Success ' . $response->metadata->code, $response->metadata->message);
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        return redirect()->back();
    }

    public function panggilPoliklinik(Request $request)
    {
        $request['taskid'] = 4;
        $request['keterangan'] = "Panggilan ke poliklinik yang anda pilih";
        $request['waktu'] = now()->timestamp * 1000;
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $vclaim = new AntrianController();
        $response = $vclaim->update_antrean($request);
        if ($response->metadata->code == 200) {
            // try {
            //     // notif wa
            //     $wa = new WhatsappController();
            //     $request['message'] = "Panggilan antrian atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " untuk segera dilayani di POLIKLINIK " . $antrian->namapoli;
            //     $request['number'] = $antrian->nohp;
            //     $wa->send_message($request);
            // } catch (\Throwable $th) {
            //     //throw $th;
            // }
            $antrian->update([
                'taskid' => $request->taskid,
                'status_api' => 1,
                'keterangan' => $request->keterangan,
                'user' => Auth::user()->name,
            ]);
            Alert::success('Success', 'Panggil Pasien Berhasil');
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        return redirect()->back();
    }
    public function panggilUlangPoliklinik(Request $request)
    {
        $request['taskid'] = 4;
        $request['keterangan'] = "Panggilan ke poliklinik yang anda pilih";
        $request['waktu'] = now()->timestamp * 1000;
        // try {
        //     // notif wa
        //     $wa = new WhatsappController();
        //     $request['message'] = "Panggilan ulang antrian atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " untuk segera dilayani di POLIKLINIK " . $antrian->namapoli;
        //     $request['number'] = $antrian->nohp;
        //     $wa->send_message($request);
        // } catch (\Throwable $th) {
        //     //throw $th;
        // }
        Alert::success('Success', 'Panggil Pasien Berhasil');
        return redirect()->back();
    }
    public function lanjutFarmasi(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $request['kodebooking'] = $antrian->kodebooking;
        $request['jenisresep'] = 'Non Racikan';
        $request['taskid'] = 5;
        $request['keterangan'] = "Silahkan tunggu di farmasi untuk pengambilan obat.";
        $request['waktu'] = Carbon::now()->timestamp * 1000;
        $api = new AntrianController();
        $response = $api->update_antrean($request);
        if ($response->metadata->code == 200) {
            $antrian->update([
                'taskid' => $request->taskid,
                'status_api' => 0,
                'keterangan' => $request->keterangan,
                'user' => Auth::user()->name,
            ]);
            // try {
            //     // notif wa
            //     $wa = new WhatsappController();
            //     $request['message'] = "Pelayanan di poliklinik atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " telah selesai. " . $request->keterangan;
            //     $request['number'] = $antrian->nohp;
            //     $wa->send_message($request);
            // } catch (\Throwable $th) {
            //     //throw $th;
            // }
            Alert::success('Success', 'Pasien Dilanjutkan Ke Farmasi');
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        $response = $api->ambil_antrian_farmasi($request);
        // if ($response->metadata->code == 200) {
        //     Alert::success('Success', 'Pasien Dilanjutkan Ke Farmasi');
        // } else {
        //     Alert::error('Error Tambah Antrian Farmasi ' . $response->metadata->code, $response->metadata->message);
        // }
        return redirect()->back();
    }
    public function lanjutFarmasiRacikan(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $request['kodebooking'] = $antrian->kodebooking;
        $request['jenisresep'] = 'racikan';
        $request['taskid'] = 5;
        $request['keterangan'] = "Silahkan tunggu di farmasi untuk pengambilan obat.";
        $request['waktu'] = Carbon::now()->timestamp * 1000;
        $api = new AntrianController();
        $response = $api->update_antrean($request);
        if ($response->metadata->code == 200) {
            $antrian->update([
                'taskid' => $request->taskid,
                'status_api' => 0,
                'keterangan' => $request->keterangan,
                'user' => Auth::user()->name,
            ]);
            // try {
            //     // notif wa
            //     $wa = new WhatsappController();
            //     $request['message'] = "Pelayanan di poliklinik atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " telah selesai. " . $request->keterangan;
            //     $request['number'] = $antrian->nohp;
            //     $wa->send_message($request);
            // } catch (\Throwable $th) {
            //     //throw $th;
            // }
            Alert::success('Success', 'Pasien Dilanjutkan Ke Farmasi');
        } else {
            Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        }
        $response = $api->ambil_antrian_farmasi($request);
        // if ($response->metadata->code == 200) {
        //     Alert::success('Success', 'Pasien Dilanjutkan Ke Farmasi');
        // } else {
        //     Alert::error('Error Tambah Antrian Farmasi ' . $response->metadata->code, $response->metadata->message);
        // }
        return redirect()->back();
    }
    public function antrianBpjs(Request $request)
    {
        // get poli
        $response = $this->ref_poli();
        if ($response->metadata->code == 200) {
            $polikliniks = $response->response;
        } else {
            $polikliniks = null;
        }
        // get antrian
        $antrians = null;
        if (isset($request->kodepoli)) {
            $antrians = Antrian::whereDate('tanggalperiksa', $request->tanggal)->get();
            if ($request->kodepoli != '000') {
                $antrians = $antrians->where('kodepoli', $request->kodepoli);
            }
            Alert::success('OK', 'Antrian BPJS Total : ' . $antrians->count());
        }
        return view('bpjs.antrian.antrian', compact([
            'request',
            'polikliniks',
            'antrians',
        ]));
    }
    // pendaftaran
    public function antrianConsole()
    {
        // $poliklinik = Poliklinik::with(['antrians', 'jadwals'])->where('status', 1)->get();
        $jadwals = JadwalDokter::with(['antrians'])->where('hari',  now()->dayOfWeek)
            ->orderBy('namasubspesialis', 'asc')->get();
        $antrian_terakhir1 = Antrian::where('tanggalperiksa', now()->format('Y-m-d'))->where('method', 'Offline')->where('lantaipendaftaran', 1)->count();
        $antrian_terakhir2 = Antrian::where('tanggalperiksa', now()->format('Y-m-d'))->where('method', 'Offline')->where('lantaipendaftaran', 2)->where('jenispasien', 'JKN')->count();
        $antrian_terakhir3 = Antrian::where('tanggalperiksa', now()->format('Y-m-d'))->where('method', '!=', 'Offline')->where('method', '!=', 'Bridging')->count();
        $antrian_terakhir4 = Antrian::where('tanggalperiksa', now()->format('Y-m-d'))->where('method', '!=', 'Bridging')->count();
        return view('simrs.antrian_console', compact(
            [
                'jadwals',
                'antrian_terakhir1',
                'antrian_terakhir2',
                'antrian_terakhir3',
                'antrian_terakhir4',
            ]
        ));
    }
    public function antrianPendaftaran(Request $request)
    {
        $antrians = null;
        $antrian = null;
        $polikliniks = null;
        $dokters = null;
        $pasiens = null;
        $pasien = null;
        // daftar antrian
        if ($request->tanggal && $request->loket && $request->jenispasien  && $request->lantai) {
            $antrians = Antrian::whereDate('tanggalperiksa', $request->tanggal)
                ->where('method', 'Offline')
                ->where('jenispasien', $request->jenispasien)
                ->where('lantaipendaftaran', $request->lantai)
                ->get();
            if ($request->kodepoli != null) {
                $antrians = $antrians->where('kodepoli', $request->kodepoli);
            }
        }
        // layanan antrian
        if ($request->kodebooking) {
            $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
            $dokters = Dokter::where('status', 1)->get();
            $pasiens = Pasien::with(['kecamatans'])
                ->where('no_rm', 'LIKE', "%{$request->search}%")
                ->orWhere('nama_px', 'LIKE', "%{$request->search}%")
                ->orWhere('nik_bpjs', 'LIKE', "%{$request->search}%")
                ->orWhere('no_Bpjs', 'LIKE', "%{$request->search}%")
                ->orderBy('tgl_entry', 'DESC')->paginate(10);
            // pilih pasien
            if ($request->norm) {
                $pasien = Pasien::firstWhere('no_rm', $request->norm);
                $request['nomorkartu'] = $pasien->no_Bpjs;
                $request['tanggal'] = $antrian->tanggalperiksa;
                toast('Pasien telah dipilih', 'success');
            }
        }
        $polikliniks = Poliklinik::where('status', 1)->get();
        return view('simrs.pendaftaran.pendaftaran_antrian', compact([
            'antrians',
            'antrian',
            'request',
            'polikliniks',
            'dokters',
            'pasiens',
            'pasien',
        ]));
    }
    public function daftarBridgingAntrian(Request $request)
    {
        $request['method'] = 'Bridging';
        $res =  $this->ambil_antrian($request);
        if ($res->metadata->code == 200) {
            $request['taskid'] = 3;
            $request['waktu'] = now()->timestamp * 1000;
            $res = $this->update_antrean_pendaftaran($request);
            return redirect()->route('antrianPendaftaran', [
                'loket' => $request->loket,
                'lantai' => $request->lantai,
                'tanggal' => $request->tanggalperiksa,
                'jenispasien' => $request->jenispasien,
            ]);
        } else {
            Alert::error('Error', $res->metadata->message);
            return redirect()->back()->withErrors($res->metadata->message);
        }
    }
    public function selanjutnyaPendaftaran($loket, $lantai, $jenispasien, $tanggal, Request $request)
    {
        $antrian = Antrian::whereDate('tanggalperiksa', $tanggal)
            ->where('jenispasien', $jenispasien)
            ->where('method', 'Offline')
            ->where('taskid', 0)
            ->where('lantaipendaftaran', $request->lantai)
            ->first();
        if ($antrian) {
            $request['kodebooking'] = $antrian->kodebooking;
            $request['taskid'] = 2;
            $request['waktu'] = Carbon::now()->timestamp * 1000;
            $antrian->update([
                'taskid' => 2,
                'loket' => $request->loket,
                'status_api' => 0,
                'keterangan' => "Panggilan ke loket pendaftaran",
                'taskid2' => now(),
                'user' => Auth::user()->name,
            ]);
            //panggil urusan mesin antrian
            try {
                // notif wa
                // $wa = new WhatsappController();
                // $request['message'] = "Panggilan antrian atas nama pasien " . $antrian->nama . " dengan nomor antrian " . $antrian->angkaantrean . "/" . $antrian->nomorantrean . " untuk melakukan pendaftaran di Loket " . $loket . " Lantai " . $lantai;
                // $request['number'] = $antrian->nohp;
                // $wa->send_message($request);
                $tanggal = now()->format('Y-m-d');
                $urutan = $antrian->angkaantrean;
                if ($antrian->jenispasien == 'JKN') {
                    $tipeloket = 'BPJS';
                } else {
                    $tipeloket = 'UMUM';
                }
                $mesin_antrian = DB::connection('mysql3')->table('tb_counter')
                    ->where('tgl', $tanggal)
                    ->where('kategori', $tipeloket)
                    ->where('loket', $loket)
                    ->where('lantai', $lantai)
                    ->get();
                if ($mesin_antrian->count() < 1) {
                    $mesin_antrian = DB::connection('mysql3')->table('tb_counter')->insert([
                        'tgl' => $tanggal,
                        'kategori' => $tipeloket,
                        'loket' => $loket,
                        'counterloket' => $urutan,
                        'lantai' => $lantai,
                        'mastercount' => $urutan,
                        'sound' => 'PLAY',
                    ]);
                } else {
                    DB::connection('mysql3')->table('tb_counter')
                        ->where('tgl', $tanggal)
                        ->where('kategori', $tipeloket)
                        ->where('loket', $loket)
                        ->where('lantai', $lantai)
                        ->limit(1)
                        ->update([
                            // 'counterloket' => $antrian->first()->mastercount + 1,
                            'counterloket' => $urutan,
                            // 'mastercount' => $antrian->first()->mastercount + 1,
                            'mastercount' => $urutan,
                            'sound' => 'PLAY',
                        ]);
                }
            } catch (\Throwable $th) {
                Alert::error('Error', $th->getMessage());
                return redirect()->back();
            }
            Alert::success('Success', 'Panggilan Berhasil');
            return redirect()->back();
        } else {
            Alert::error('Error', 'Kode Booking tidak ditemukan');
            return redirect()->back();
        }
    }
    public function panggilPendaftaran($kodebooking, $loket, $lantai, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        if ($antrian) {
            $request['kodebooking'] = $antrian->kodebooking;
            $request['taskid'] = 2;
            $now = Carbon::now();
            $request['waktu'] = Carbon::now()->timestamp * 1000;
            $antrian->update([
                'taskid' => 2,
                'loket' => $request->loket,
                'status_api' => 1,
                'loket' => $request->loket,
                'keterangan' => "Panggilan ke loket pendaftaran",
                'taskid2' => $now,
                'user' => Auth::user()->name,
            ]);
            //panggil urusan mesin antrian
            try {
                // notif wa
                // $wa = new WhatsappController();
                // $request['message'] = "Panggilan antrian atas nama pasien " . $antrian->nama . " dengan nomor antrian " . $antrian->angkaantrean . "/" . $antrian->nomorantrean . " untuk melakukan pendaftaran di Loket " . $loket . " Lantai " . $lantai;
                // $request['number'] = $antrian->nohp;
                // $wa->send_message($request);
                $tanggal = now()->format('Y-m-d');
                $urutan = $antrian->angkaantrean;
                if ($antrian->jenispasien == 'JKN') {
                    $tipeloket = 'BPJS';
                } else {
                    $tipeloket = 'UMUM';
                }
                $mesin_antrian = DB::connection('mysql3')->table('tb_counter')
                    ->where('tgl', $tanggal)
                    ->where('kategori', $tipeloket)
                    ->where('loket', $loket)
                    ->where('lantai', $lantai)
                    ->get();
                if ($mesin_antrian->count() < 1) {
                    $mesin_antrian = DB::connection('mysql3')->table('tb_counter')->insert([
                        'tgl' => $tanggal,
                        'kategori' => $tipeloket,
                        'loket' => $loket,
                        'counterloket' => $urutan,
                        'lantai' => $lantai,
                        'mastercount' => $urutan,
                        'sound' => 'PLAY',
                    ]);
                } else {
                    DB::connection('mysql3')->table('tb_counter')
                        ->where('tgl', $tanggal)
                        ->where('kategori', $tipeloket)
                        ->where('loket', $loket)
                        ->where('lantai', $lantai)
                        ->limit(1)
                        ->update([
                            // 'counterloket' => $antrian->first()->mastercount + 1,
                            'counterloket' => $urutan,
                            // 'mastercount' => $antrian->first()->mastercount + 1,
                            'mastercount' => $urutan,
                            'sound' => 'PLAY',
                        ]);
                }
            } catch (\Throwable $th) {
                Alert::error('Error', $th->getMessage());
                return redirect()->back();
            }
            Alert::success('Success', 'Panggilan Berhasil');
            return redirect()->back();
        } else {
            Alert::error('Error', 'Kode Booking tidak ditemukan');
            return redirect()->back();
        }
    }
    public function selesaiPendaftaran($kodebooking, Request $request)
    {
        $antrian = Antrian::where('kodebooking', $kodebooking)->first();
        $request['kodebooking'] = $antrian->kodebooking;
        $request['taskid'] = 3;
        $request['waktu'] = Carbon::now()->timestamp * 1000;

        if ($antrian->jenispasien == 'JKN') {
            $request['keterangan'] = "Silahkan menunggu dipoliklinik";
            $request['status_api'] = 1;
        } else {
            $request['keterangan'] = "Silahkan lakukan pembayaran di Loket Pembayaran, setelah itu dapat menunggu dipoliklinik";
            $request['status_api'] = 0;
        }
        // $response = $vclaim->update_antrean($request);
        // if ($response->metadata->code == 200) {
        // } else {
        //     Alert::error('Error ' . $response->metadata->code, $response->metadata->message);
        // }
        $antrian->update([
            'taskid' => $request->taskid,
            'status_api' => $request->status_api,
            'keterangan' => $request->keterangan,
            'user' => Auth::user()->name,
        ]);
        try {
            // notif wa
            $wa = new WhatsappController();
            $request['message'] = "Anda berhasil di daftarkan atas nama pasien " . $antrian->nama . " dengan nomor antrean " . $antrian->nomorantrean . " telah selesai. " . $request->keterangan;
            $request['number'] = $antrian->nohp;
            $wa->send_message($request);
        } catch (\Throwable $th) {
            //throw $th;
        }
        Alert::success('Success', 'Pasien diteruskan ke poliklinik');
        return redirect()->back();
    }
    public function kunjunganPoliklinik(Request $request)
    {
        $kunjungans = null;
        $surat_kontrols = null;
        if ($request->tanggal) {
            $surat_kontrols = SuratKontrol::whereDate('tglTerbitKontrol', $request->tanggal)->get();
            $kunjungans = Kunjungan::whereDate('tgl_masuk', $request->tanggal)
                ->where('status_kunjungan', "!=", 8)
                ->where('kode_unit', "!=", null)
                ->where('kode_unit', 'LIKE', '10%')
                ->where('kode_unit', "!=", 1002)
                ->where('kode_unit', "!=", 1023)
                ->with(['dokter', 'unit', 'pasien', 'surat_kontrol'])
                ->get();
            if ($request->kodepoli != null) {
                $poli = Unit::where('KDPOLI', $request->kodepoli)->first();
                $kunjungans = $kunjungans->where('kode_unit', $poli->kode_unit);
                $surat_kontrols = $surat_kontrols->where('poliTujuan', $request->kodepoli);
            }
            if ($request->kodedokter != null) {
                $dokter = Paramedis::where('kode_dokter_jkn', $request->kodedokter)->first();
                $kunjungans = $kunjungans->where('kode_paramedis', $dokter->kode_paramedis);
            }
        }
        if ($request->kodepoli == null) {
            $unit = Unit::where('KDPOLI', "!=", null)
                ->where('KDPOLI', "!=", "")
                ->get();
            $dokters = Paramedis::where('kode_dokter_jkn', "!=", null)
                ->where('unit', "!=", null)
                ->get();
        } else {
            $unit = Unit::where('KDPOLI', "!=", null)
                ->where('KDPOLI', "!=", "")
                ->get();
            $poli =   Unit::firstWhere('KDPOLI', $request->kodepoli);
            $dokters = Paramedis::where('unit', $poli->kode_unit)
                ->where('kode_dokter_jkn', "!=", null)
                ->get();
        }
        return view('simrs.poliklinik.poliklinik_suratkontrol', [
            'kunjungans' => $kunjungans,
            'request' => $request,
            'unit' => $unit,
            'dokters' => $dokters,
            'surat_kontrols' => $surat_kontrols,
        ]);
    }
    public function daftarBpjsOffline(Request $request)
    {
        $request['tanggalperiksa'] = now()->format('Y-m-d');
        $request['kodepoli'] = $request->kodesubspesialis;
        $validator = Validator::make(request()->all(), [
            "kodesubspesialis" => "required",
            "kodedokter" => "required",
        ]);
        if ($validator->fails()) {
            Alert::error('Error', $validator->errors()->first());
            return redirect()->route('antrianConsole');
        }
        // get jadwal
        $jadwal = JadwalDokter::where('kodesubspesialis', $request->kodesubspesialis)
            ->where('kodedokter', $request->kodedokter)
            ->where('hari', now()->dayOfWeek)->first();
        if ($jadwal == null) {
            Alert::error('Error',  "Jadwal tidak ditemukan");
            return redirect()->route('antrianConsole');
        }
        $request['jampraktek'] = $jadwal->jadwal;
        $request['jenispasien'] = 'JKN';
        $request['method'] = 'Offline';
        // ambil antrian offline
        $antrian_api = new AntrianController();
        $response = $antrian_api->ambil_antrian($request);
        if ($response->metadata->code == 200) {
            // cek printer
            try {
                $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
                $printer = new Printer($connector);
                $printer->close();
            } catch (\Throwable $th) {
                return $this->sendError('Printer Mesin Antrian Tidak Menyala',  201);
            }
            $antrian = $response->response;
            $this->print_karcis_offline($request, $antrian);
            Alert::success('Success', 'Anda berhasil mendaftar dengan antrian ' . $antrian->angkaantrean . " / " . $antrian->nomorantrean);
            return redirect()->route('antrianConsole');
        } else {
            Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
            return redirect()->route('antrianConsole');
        }
    }
    public function daftarUmumOffline(Request $request)
    {
        $request['tanggalperiksa'] = now()->format('Y-m-d');
        $request['kodepoli'] = $request->kodesubspesialis;
        $validator = Validator::make(request()->all(), [
            "kodesubspesialis" => "required",
            "kodedokter" => "required",
        ]);
        if ($validator->fails()) {
            Alert::error('Error', $validator->errors()->first());
            return redirect()->route('antrianConsole');
        }
        // get jadwal
        $jadwal = JadwalDokter::where('kodesubspesialis', $request->kodesubspesialis)
            ->where('kodedokter', $request->kodedokter)
            ->where('hari', now()->dayOfWeek)->first();
        if ($jadwal == null) {
            Alert::error('Error',  "Jadwal tidak ditemukan");
            return redirect()->route('antrianConsole');
        }
        $request['jampraktek'] = $jadwal->jadwal;
        $request['jenispasien'] = 'NON-JKN';
        $request['method'] = 'Offline';
        // ambil antrian offline
        $antrian_api = new AntrianController();
        $response = $antrian_api->ambil_antrian_offline($request);
        if ($response->metadata->code == 200) {
            // cek printer
            try {
                $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
                $printer = new Printer($connector);
                $printer->close();
            } catch (\Throwable $th) {
                return $this->sendError('Printer Mesin Antrian Tidak Menyala',  201);
            }
            $antrian = $response->response;
            $this->print_karcis_offline($request, $antrian);
            Alert::success('Success', 'Anda berhasil mendaftar dengan antrian ' . $antrian->angkaantrean . " / " . $antrian->nomorantrean);
            return redirect()->route('antrianConsole');
        } else {
            Alert::error('Error ' . $response->metadata->code,  $response->metadata->message);
            return redirect()->route('antrianConsole');
        }
    }
    // API FUNCTION
    public function signature()
    {
        $cons_id =  env('ANTRIAN_CONS_ID');
        $secretKey = env('ANTRIAN_SECRET_KEY');
        $userkey = env('ANTRIAN_USER_KEY');
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = hash_hmac('sha256', $cons_id . "&" . $tStamp, $secretKey, true);
        $encodedSignature = base64_encode($signature);
        $data['user_key'] =  $userkey;
        $data['x-cons-id'] = $cons_id;
        $data['x-timestamp'] = $tStamp;
        $data['x-signature'] = $encodedSignature;
        $data['decrypt_key'] = $cons_id . $secretKey . $tStamp;
        return $data;
    }
    public function stringDecrypt($key, $string)
    {
        $encrypt_method = 'AES-256-CBC';
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        $output = \LZCompressor\LZString::decompressFromEncodedURIComponent($output);
        return $output;
    }
    public function response_decrypt($response, $signature)
    {
        $code = json_decode($response->body())->metadata->code;
        $message = json_decode($response->body())->metadata->message;
        if ($code == 200 || $code == 1) {
            $response = json_decode($response->body())->response ?? null;
            $decrypt = $this->stringDecrypt($signature['decrypt_key'], $response);
            $data = json_decode($decrypt);
            if ($code == 1)
                $code = 200;
            return $this->sendResponse($data, $code);
        } else {
            $response = json_decode($response);
            return json_decode(json_encode($response));
        }
    }
    public function response_no_decrypt($response)
    {
        $response = json_decode($response);
        return json_decode(json_encode($response));
    }
    // API BPJS
    public function ref_poli()
    {
        $url = env('ANTRIAN_URL') . "ref/poli";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function ref_dokter()
    {
        $url = env('ANTRIAN_URL') . "ref/dokter";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function ref_jadwal_dokter(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodepoli" => "required",
            "tanggal" =>  "required|date",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        if (Carbon::parse($request->tanggal)->endOfDay()->isPast()) {
            return $this->sendError("Tanggal periksa sudah terlewat", 400);
        }
        $url = env('ANTRIAN_URL') . "jadwaldokter/kodepoli/" . $request->kodepoli . "/tanggal/" . $request->tanggal;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function ref_poli_fingerprint()
    {
        $url = env('ANTRIAN_URL') . "ref/poli/fp";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function ref_pasien_fingerprint(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "jenisIdentitas" => "required",
            "noIdentitas" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  400);
        }
        $url = env('ANTRIAN_URL') . "ref/pasien/fp/identitas/" . $request->jenisIdentitas . "/noidentitas/" . $request->noIdentitas;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function update_jadwal_dokter(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodepoli" =>  "required",
            "kodesubspesialis" =>  "required",
            "kodedokter" =>  "required",
            "jadwal" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors(), 400);
        }
        $url = env('ANTRIAN_URL') . "jadwaldokter/updatejadwaldokter";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodepoli" => $request->kodepoli,
                "kodesubspesialis" => $request->kodesubspesialis,
                "kodedokter" => $request->kodedokter,
                "jadwal" => $request->jadwal,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    public function tambah_antrean(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "nomorkartu" =>  "required|digits:13|numeric",
            // "nomorreferensi" =>  "required",
            "nik" =>  "required|digits:16|numeric",
            "nohp" => "required|numeric",
            "kodepoli" =>  "required",
            "norm" =>  "required",
            "pasienbaru" =>  "required",
            "tanggalperiksa" =>  "required|date|date_format:Y-m-d",
            "kodedokter" =>  "required",
            "jampraktek" =>  "required",
            "jeniskunjungan" => "required",
            "jenispasien" =>  "required",
            "namapoli" =>  "required",
            "namadokter" =>  "required",
            "nomorantrean" =>  "required",
            "angkaantrean" =>  "required",
            "estimasidilayani" =>  "required",
            "sisakuotajkn" =>  "required",
            "kuotajkn" => "required",
            "sisakuotanonjkn" => "required",
            "kuotanonjkn" => "required",
            "keterangan" =>  "required",
            "nama" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  400);
        }
        $url = env('ANTRIAN_URL') . "antrean/add";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodebooking" => $request->kodebooking,
                "jenispasien" => $request->jenispasien,
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "kodepoli" => $request->kodepoli,
                "namapoli" => $request->namapoli,
                "pasienbaru" => $request->pasienbaru,
                "norm" => $request->norm,
                "tanggalperiksa" => $request->tanggalperiksa,
                "kodedokter" => $request->kodedokter,
                "namadokter" => $request->namadokter,
                "jampraktek" => $request->jampraktek,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "nomorantrean" => $request->nomorantrean,
                "angkaantrean" => $request->angkaantrean,
                "estimasidilayani" => $request->estimasidilayani,
                "sisakuotajkn" => $request->sisakuotajkn,
                "kuotajkn" => $request->kuotajkn,
                "sisakuotanonjkn" => $request->sisakuotanonjkn,
                "kuotanonjkn" => $request->kuotanonjkn,
                "keterangan" => $request->keterangan,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    public function tambah_antrean_farmasi(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "jenisresep" =>  "required",
            "nomorantrean" =>  "required",
            "keterangan" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $url = env('ANTRIAN_URL') . "antrean/farmasi/add";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodebooking" => $request->kodebooking,
                "jenisresep" => $request->jenisresep,
                "nomorantrean" => $request->nomorantrean,
                "keterangan" => $request->keterangan,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    public function update_antrean(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "taskid" =>  "required",
            "waktu" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $url = env('ANTRIAN_URL') . "antrean/updatewaktu";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodebooking" => $request->kodebooking,
                "taskid" => $request->taskid,
                "waktu" => $request->waktu,
                "jenisresep" => $request->jenisresep,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    // bridging pendaftaran pa agil
    public function update_antrean_pendaftaran(Request $request)
    {
        // cek request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "taskid" => "required",
            "waktu" => "required|numeric",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $response = $this->update_antrean($request);
        if ($response->metadata->code == 200) {
            $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
            $antrian->update([
                'taskid' => $request->taskid,
                'status_api' => 1,
                'method' => 'Bridging',
                'keterangan' => "Pendaftaran melalui bridging",
                'user' => 'Pendaftaran',
            ]);
        }
        // kirim notif
        $wa = new WhatsappController();
        $request['notif'] = 'Daftar antrian bridging ' . $request->kodebooking;
        $wa->send_notif($request);
        return response()->json($response);
    }
    public function batal_antrean(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "keterangan" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  400);
        }
        $url = env('ANTRIAN_URL') . "antrean/batal";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodebooking" => $request->kodebooking,
                "keterangan" => $request->keterangan,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    public function taskid_antrean(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $url = env('ANTRIAN_URL') . "antrean/getlisttask";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->post(
            $url,
            [
                "kodebooking" => $request->kodebooking,
            ]
        );
        return $this->response_decrypt($response, $signature);
    }
    public function dashboard_tanggal(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "tanggal" =>  "required|date|date_format:Y-m-d",
            "waktu" => "required|in:rs,server",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $url = env('ANTRIAN_URL') . "dashboard/waktutunggu/tanggal/" . $request->tanggal . "/waktu/" . $request->waktu;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_no_decrypt($response, $signature);
    }
    public function dashboard_bulan(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "bulan" =>  "required|date_format:m",
            "tahun" =>  "required|date_format:Y",
            "waktu" => "required|in:rs,server",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $url = env('ANTRIAN_URL') . "dashboard/waktutunggu/bulan/" . $request->bulan . "/tahun/" . $request->tahun . "/waktu/" . $request->waktu;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_no_decrypt($response);
    }
    public function antrian_tanggal(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "tanggal" =>  "required|date",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $url = env('ANTRIAN_URL') . "antrean/pendaftaran/tanggal/" . $request->tanggal;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function antrian_kodebooking(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodeBooking" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $url = env('ANTRIAN_URL') . "antrean/pendaftaran/kodebooking/" . $request->kodeBooking;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function antrian_belum_dilayani(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "tanggal" =>  "required|date",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $url = env('ANTRIAN_URL') . "antrean/pendaftaran/aktif";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function antrian_pendaftaran(Request $request)
    {
        $url = env('ANTRIAN_URL') . "antrean/pendaftaran/aktif";
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    public function antrian_poliklinik(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodePoli" =>  "required",
            "kodeDokter" =>  "required",
            "hari" =>  "required",
            "jamPraktek" =>  "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $url = env('ANTRIAN_URL') . "antrean/pendaftaran/kodepoli/" . $request->kodePoli . "/kodedokter/" . $request->kodeDokter . "/hari/" . $request->hari . "/jampraktek/" . $request->jamPraktek;
        $signature = $this->signature();
        $response = Http::withHeaders($signature)->get($url);
        return $this->response_decrypt($response, $signature);
    }
    // API SIMRS
    public function token(Request $request)
    {
        if (Auth::attempt(['username' => $request->header('x-username'), 'password' => $request->header('x-password')])) {
            $user = Auth::user();
            $data['token'] =  $user->createToken('MyApp')->plainTextToken;
            return $this->sendResponse($data, 200);
        } else {
            return $this->sendError("Unauthorized (Username dan Password Salah)",  401);
        }
    }
    public function auth_token(Request $request)
    {
        $aktif = Auth::check();
        $tokenexpired = 3600;
        if ($aktif == false) {
            if ($request->hasHeader('x-token')) {
                if ($request->hasHeader('x-username')) {
                    $credentials = $request->header('x-token');
                    $token = PersonalAccessToken::findToken($credentials);
                    if (!$token) {
                        return $response = [
                            "metadata" => [
                                "code" => 201,
                                "message" => "Unauthorized (Token Salah)"
                            ]
                        ];
                    } else {
                        $user = $token->tokenable;
                        if (Carbon::now() >  $token->created_at->addMinute($tokenexpired)) {
                            $token->delete();
                            $response = [
                                "metadata" => [
                                    "code" => 201,
                                    "message" => "Token Expired"
                                ]
                            ];
                            return $response;
                        }
                        if ($user->username != $request->header('x-username')) {
                            return $response = [
                                "metadata" => [
                                    "code" => 201,
                                    "message" => "Unauthorized (Username tidak sesuai dengan token)"
                                ]
                            ];
                        } else {
                            return $response = [
                                "metadata" => [
                                    "code" => 200,
                                    "message" => "OK"
                                ]
                            ];
                        }
                    }
                } else {
                    return $response = [
                        "metadata" => [
                            "code" => 201,
                            "message" => "Silahkan isi header dengan x-username"
                        ]
                    ];
                }
            } else {
                return $response = [
                    "metadata" => [
                        "code" => 201,
                        "message" => "Silahkan isi header dengan x-token"
                    ]
                ];
            }
        } else {
            return $response = [
                "metadata" => [
                    "code" => 200,
                    "message" => "OK"
                ]
            ];
        }
    }
    public function status_antrian(Request $request) #yang dipakai api
    {
        // validator
        $validator = Validator::make(request()->all(), [
            "kodepoli" => "required",
            "kodedokter" => "required",
            "tanggalperiksa" => "required|date",
            "jampraktek" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        // check tanggal backdate
        $request['tanggal'] = $request->tanggalperiksa;
        if (Carbon::parse($request->tanggalperiksa)->endOfDay()->isPast()) {
            return $this->sendError("Tanggal periksa sudah terlewat", 401);
        }
        // get jadwal poliklinik dari simrs
        $jadwals = JadwalDokter::where("hari",  Carbon::parse($request->tanggalperiksa)->dayOfWeek)
            ->where("kodesubspesialis", $request->kodepoli)
            ->get();
        // tidak ada jadwal
        if (!isset($jadwals)) {
            return $this->sendError("Tidak ada jadwal poliklinik dihari tersebut", 404);
        }
        // get jadwal dokter
        $jadwal = $jadwals->where('kodedokter', $request->kodedokter)->first();
        // tidak ada dokter
        if (!isset($jadwal)) {
            return $this->sendError("Tidak ada jadwal dokter dihari tersebut",  404);
        }
        if ($jadwal->libur == 1) {
            return $this->sendError("Jadwal Dokter dihari tersebut sedang diliburkan.",  403);
        }
        // get hitungan antrian
        $antrians = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->where('kodepoli', $request->kodepoli)
            ->where('kodedokter', $request->kodedokter)
            ->where('taskid', '!=', 99)
            ->count();
        // cek kapasitas pasien
        if ($request->method != 'Bridging') {
            if ($antrians >= $jadwal->kapasitaspasien) {
                return $this->sendError("Kuota Dokter Telah Penuh",  201);
            }
        }
        //  get nomor antrian
        $nomorantean = 0;
        $antreanpanggil =  Antrian::where('kodepoli', $request->kodepoli)
            ->where('tanggalperiksa', $request->tanggalperiksa)
            ->where('taskid', 4)
            ->first();
        if (isset($antreanpanggil)) {
            $nomorantean = $antreanpanggil->nomorantrean;
        }
        // get jumlah antrian jkn dan non-jkn
        $antrianjkn = Antrian::where('kodepoli', $request->kodepoli)
            ->where('method', '!=', 'Bridging')
            ->where('tanggalperiksa', $request->tanggalperiksa)
            ->where('taskid', '!=', 99)
            ->where('kodedokter', $request->kodedokter)
            ->where('jenispasien', "JKN")->count();
        $antriannonjkn = Antrian::where('kodepoli', $request->kodepoli)
            ->where('method', '!=', 'Bridging')
            ->where('tanggalperiksa', $request->tanggalperiksa)
            ->where('tanggalperiksa', $request->tanggalperiksa)
            ->where('kodedokter', $request->kodedokter)
            ->where('taskid', '!=', 99)
            ->where('jenispasien', "NON-JKN")->count();
        $response = [
            "namapoli" => $jadwal->namasubspesialis,
            "namadokter" => $jadwal->namadokter,
            "totalantrean" => $antrians,
            "sisaantrean" => $jadwal->kapasitaspasien - $antrians,
            "antreanpanggil" => $nomorantean,
            "sisakuotajkn" => round($jadwal->kapasitaspasien * 80 / 100) -  $antrianjkn,
            "kuotajkn" => round($jadwal->kapasitaspasien * 80 / 100),
            "sisakuotanonjkn" => round($jadwal->kapasitaspasien * 20 / 100) - $antriannonjkn,
            "kuotanonjkn" =>  round($jadwal->kapasitaspasien * 20 / 100),
            "keterangan" => "Informasi antrian poliklinik",
        ];
        return $this->sendResponse($response, 200);
    }
    public function ambil_antrian(Request $request) #ambil antrian api
    {
        $validator = Validator::make(request()->all(), [
            "nomorkartu" => "required|numeric|digits:13",
            "nik" => "required|numeric|digits:16",
            "nohp" => "required",
            "kodepoli" => "required",
            // "norm" => "required",
            "tanggalperiksa" => "required",
            "kodedokter" => "required",
            "jampraktek" => "required",
            "jeniskunjungan" => "required|numeric",
            // "nomorreferensi" => "numeric",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        // check tanggal backdate
        if (Carbon::parse($request->tanggalperiksa)->endOfDay()->isPast()) {
            return $this->sendError("Tanggal periksa sudah terlewat", 400);
        }
        // check tanggal hanya 7 hari
        if (Carbon::parse($request->tanggalperiksa) >  Carbon::now()->addDay(6)) {
            return $this->sendError("Antrian hanya dapat dibuat untuk 7 hari ke kedepan", 400);
        }
        // get poliklinik
        $poli = Poliklinik::where('kodesubspesialis', $request->kodepoli)->first();
        $request['lantaipendaftaran'] = $poli->lantaipendaftaran;
        $request['lokasi'] = $poli->lantaipendaftaran;
        // cek duplikasi nik antrian
        $antrian_nik = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('nik', $request->nik)
            ->where('kodepoli', $request->kodepoli)
            ->where('taskid', '<=', 4)
            ->first();
        if ($antrian_nik) {
            return $this->sendError("Terdapat Antrian (" . $antrian_nik->kodebooking . ") dengan nomor NIK yang sama pada tanggal tersebut yang belum selesai. Silahkan batalkan terlebih dahulu jika ingin mendaftarkan lagi.",  409);
        }
        // cek pasien baru
        $request['pasienbaru'] = 0;
        $pasien = Pasien::where('no_Bpjs',  $request->nomorkartu)->first();
        if (empty($pasien)) {
            return $this->sendError("Nomor Kartu BPJS Pasien termasuk Pasien Baru di RSUD Waled. Silahkan daftar melalui pendaftaran offline",  201);
        }
        // cek no kartu sesuai tidak
        if ($pasien->nik_bpjs != $request->nik) {
            return $this->sendError("NIK anda yang terdaftar di BPJS dengan Di RSUD Waled berbeda. Silahkan perbaiki melalui pendaftaran offline",  201);
        }
        // Cek pasien kronis
        $kunjungan_kronis = Kunjungan::where("no_rm", 'LIKE', '%' . $request->norm)
            ->where('catatan', 'KRONIS')
            ->orderBy('tgl_masuk', 'DESC')
            ->first();
        // cek pasien kronis 30 hari dan beda poli
        if (isset($kunjungan_kronis)) {
            $unit = Unit::firstWhere('kode_unit', $kunjungan_kronis->kode_unit);
            if ($unit->KDPOLI ==  $request->kodepoli) {
                if (now() < Carbon::parse($kunjungan_kronis->tgl_masuk)->addDay(29)) {
                    return $this->sendError("Pada kunjungan sebelumnya di tanggal " . Carbon::parse($kunjungan_kronis->tgl_masuk)->translatedFormat('d F Y') . " anda termasuk pasien KRONIS. Sehingga bisa daftar lagi setelah 30 hari.",  201);
                }
            }
        }
        // cek jika jkn
        if (isset($request->nomorreferensi)) {
            $request['jenispasien'] = 'JKN';
            $vclaim = new VclaimController();
            // kunjungan kontrol
            if ($request->jeniskunjungan == 3) {
                $request['noSuratKontrol'] = $request->nomorreferensi;
                $response =  $vclaim->suratkontrol_nomor($request);
                if ($response->metadata->code == 200) {
                    $suratkontrol = $response->response;
                    $request['nomorRujukan'] = $suratkontrol->sep->provPerujuk->noRujukan;
                    // cek surat kontrol orang lain
                    if ($request->nomorkartu != $suratkontrol->sep->peserta->noKartu) {
                        return $this->sendError("Nomor Kartu di Surat Kontrol dengan Kartu BPJS berberda", 400);
                    }
                    // cek surat tanggal kontrol
                    if (Carbon::parse($suratkontrol->tglRencanaKontrol) != Carbon::parse($request->tanggalperiksa)) {
                        return $this->sendError("Tanggal periksa tidak sesuai dengan surat kontrol. Silahkan pengajuan perubahan tanggal surat kontrol terlebih dahulu.", 400);
                    }
                } else {
                    return $this->sendError($response->metadata->message,  $response->metadata->code);
                }
            }
            // kunjungan rujukan
            else {
                $request['nomorrujukan'] = $request->nomorreferensi;
                // rujukan fktp
                if ($request->jeniskunjungan == 1) {
                    $request['jenisRujukan'] = 1;
                    $response =  $vclaim->rujukan_nomor($request);
                }
                // rujukan antar rs
                else if ($request->jeniskunjungan == 4) {
                    $request['jenisRujukan'] = 2;
                    $response =  $vclaim->rujukan_rs_nomor($request);
                }
                if ($response->metadata->code == 200) {
                    $rujukan  =  $response->response->rujukan;
                    $jumlah_sep  = $vclaim->rujukan_jumlah_sep($request);
                    if ($jumlah_sep->metadata->code == 200) {
                        // cek rujukan telah digunakan atau tidak
                        $jumlah_sep =  $jumlah_sep->response->jumlahSEP;
                        if ($jumlah_sep != 0) {
                            return $this->sendError("Rujukan anda telah digunakan untuk berobat. Untuk kunjungan selanjutnya silahkan gunakan Surat Kontrol yang dibuat di Poliklinik.", 400);
                        }
                    } else {
                        return $this->sendError($jumlah_sep->metadata->message,  $jumlah_sep->metadata->code);
                    }
                } else {
                    return $this->sendError($response->metadata->message,  $response->metadata->code);
                }
            }
        }
        // jika non-jkn
        else {
            $request['jenispasien'] = 'NON-JKN';
        }
        // ambil data pasien
        $request['norm'] = $pasien->no_rm;
        $request['nama'] = $pasien->nama_px;
        // cek jadwal
        $jadwal = $this->status_antrian($request);
        if ($jadwal->metadata->code == 200) {
            $jadwal = $jadwal->response;
            $request['namapoli'] = $jadwal->namapoli;
            $request['namadokter'] = $jadwal->namadokter;
        } else {
            $message = $jadwal->metadata->message;
            // kirim notif
            $wa = new WhatsappController();
            $request['notif'] = 'Method ambil antrian ' . $request->method . ' jadwal , ' . $message . ' kodepoli ' . $request->kodepoli;
            $wa->send_notif($request);
            return $this->sendError('Mohon maaf , ' . $message, 400);
        }
        // menghitung nomor antrian
        $antrian_all = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->count();
        $antrian_poli = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->where('kodepoli', $request->kodepoli)
            ->count();
        $request['nomorantrean'] = $request->kodepoli . "-" .  str_pad($antrian_poli + 1, 3, '0', STR_PAD_LEFT);
        $request['angkaantrean'] = $antrian_all + 1;
        $request['kodebooking'] = strtoupper(uniqid());
        //  menghitung estimasi dilayani
        $timestamp = $request->tanggalperiksa . ' ' . explode('-', $request->jampraktek)[0] . ':00';
        $jadwal_estimasi = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Jakarta')->addMinutes(10 * ($antrian_poli + 1));
        $request['estimasidilayani'] = $jadwal_estimasi->timestamp * 1000;
        $request['sisakuotajkn'] =  $jadwal->sisakuotajkn - 1;
        $request['kuotajkn'] = $jadwal->kuotajkn;
        $request['sisakuotanonjkn'] = $jadwal->sisakuotanonjkn - 1;
        $request['kuotanonjkn'] = $jadwal->kuotanonjkn;
        // keterangan jika offline
        if ($request->method == 'Offline') {
            $request['keterangan'] = "Silahkan menunggu panggilan di loket pendaftaran.";
        }
        // keterangan jika bridging
        else if ($request->method == 'Whatsapp') {
            $request['keterangan'] = "Peserta harap 60 menit lebih awal dari jadwal untuk checkin dekat mesin antrian untuk mencetak tiket antrian.";
        }
        // keterangan jika bridging
        else if ($request->method == 'Bridging') {
            $request['keterangan'] = "Silahkan menunggu panggilan di poliklinik.";
        }
        // keterangan jika jkn
        else {
            $request['keterangan'] = "Peserta harap 60 menit lebih awal dari jadwal untuk checkin dekat mesin antrian untuk mencetak tiket antrian.";
            $request['method'] = "JKN Mobile";
        }
        //tambah antrian bpjs
        // $response = Http::post(route('tambah_antrean'), $request );
        $response = $this->tambah_antrean($request);
        if ($response->metadata->code == 200) {
            // tambah antrian database
            $antrian = Antrian::create([
                "kodebooking" => $request->kodebooking,
                "nomorkartu" => $request->nomorkartu,
                "nik" => $request->nik,
                "nohp" => $request->nohp,
                "kodepoli" => $request->kodepoli,
                "norm" => $request->norm,
                "pasienbaru" => $request->pasienbaru,
                "tanggalperiksa" => $request->tanggalperiksa,
                "kodedokter" => $request->kodedokter,
                "jampraktek" => $request->jampraktek,
                "jeniskunjungan" => $request->jeniskunjungan,
                "nomorreferensi" => $request->nomorreferensi,
                "method" => $request->method,
                "nomorrujukan" => $request->nomorRujukan,
                "nomorsuratkontrol" => $request->noSuratKontrol,
                'nomorsep' => $request->nomorsep,
                "kode_kunjungan" => $request->kode_kunjungan,
                "jenispasien" => $request->jenispasien,
                "namapoli" => $request->namapoli,
                "namadokter" => $request->namadokter,
                "nomorantrean" => $request->nomorantrean,
                "angkaantrean" => $request->angkaantrean,
                "estimasidilayani" => $request->estimasidilayani,
                "sisakuotajkn" => $request->sisakuotajkn,
                "kuotajkn" => $request->kuotajkn,
                "sisakuotanonjkn" => $request->sisakuotanonjkn,
                "kuotanonjkn" => $request->kuotanonjkn,
                "keterangan" => $request->keterangan,
                "lokasi" => $request->lokasi,
                "lantaipendaftaran" => $request->lantaipendaftaran,
                "status_api" => 1,
                "taskid" => 0,
                "user" => "System Antrian",
                "nama" => $request->nama,
            ]);
            // kirim notif wa
            $wa = new WhatsappController();
            $request['message'] = "*Antrian Berhasil di Daftarkan*\nAntrian anda berhasil didaftarkan melalui Layanan " . $request->method . " RSUD Waled dengan data sebagai berikut : \n\n*Kode Antrian :* " . $request->kodebooking .  "\n*Angka Antrian :* " . $request->angkaantrean .  "\n*Nomor Antrian :* " . $request->nomorantrean . "\n*Jenis Pasien :* " . $request->jenispasien .  "\n*Jenis Kunjungan :* " . $request->jeniskunjungan .  "\n\n*Nama :* " . $request->nama . "\n*Poliklinik :* " . $request->namapoli  . "\n*Dokter :* " . $request->namadokter  .  "\n*Jam Praktek :* " . $request->jampraktek  .  "\n*Tanggal Periksa :* " . $request->tanggalperiksa . "\n\n*Keterangan :* " . $request->keterangan  .  "\nTerima kasih. Semoga sehat selalu.\nUntuk pertanyaan & pengaduan silahkan hubungi :\n*Humas RSUD Waled 08983311118*";
            $request['number'] = $request->nohp;
            $wa->send_message($request);
            // kirim batal
            $request['contenttext'] = "Silahkan pilih menu dibawah ini untuk membatalkan antrian.";
            $request['titletext'] = "Pilihan Batal Antrian";
            $request['buttontext'] = 'PILIH MENU';
            $request['rowtitle'] = "BATAL ANTRIAN " . $request->kodebooking;
            $request['rowdescription'] = "@BATALANTRI#" . $request->kodebooking;
            $wa->send_list($request);
            // kirim notif
            $wa = new WhatsappController();
            $request['notif'] = 'Antrian berhasil didaftarkan melalui ' . $request->method . "\n*Kodebooking :* " . $request->kodebooking . "\n*Nama :* " . $request->nama . "\n*Poliklinik :* " . $request->namapoli .  "\n*Tanggal Periksa :* " . $request->tanggalperiksa . "\n*Jenis Kunjungan :* " . $request->jeniskunjungan;
            $wa->send_notif($request);
            $response = [
                "nomorantrean" => $request->nomorantrean,
                "angkaantrean" => $request->angkaantrean,
                "kodebooking" => $request->kodebooking,
                "norm" =>  substr($request->norm, 2),
                "namapoli" => $request->namapoli,
                "namadokter" => $request->namadokter,
                "estimasidilayani" => $request->estimasidilayani,
                "sisakuotajkn" => $request->sisakuotajkn,
                "kuotajkn" => $request->kuotajkn,
                "sisakuotanonjkn" => $request->sisakuotanonjkn,
                "kuotanonjkn" => $request->kuotanonjkn,
                "keterangan" => $request->keterangan,
            ];
            return $this->sendResponse($response, 200);
        } else {
            return $this->sendError($response->metadata->message, 400);
        }
    }
    public function ambil_antrian_offline(Request $request) #ambil antrian mesin antrian
    {
        $validator = Validator::make(request()->all(), [
            "kodepoli" => "required",
            "tanggalperiksa" => "required",
            "kodedokter" => "required",
            "jampraktek" => "required",
            "jenispasien" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        // check tanggal
        if (Carbon::parse($request->tanggalperiksa)->endOfDay()->isPast()) {
            return $this->sendError("Tanggal periksa sudah terlewat", 400);
        }
        if (Carbon::parse($request->tanggalperiksa) >  Carbon::now()->addDay(6)) {
            return $this->sendError("Antrian hanya dapat dibuat untuk 7 hari ke kedepan", 400);
        }
        $poli = Poliklinik::where('kodesubspesialis', $request->kodepoli)->first();
        $request['lantaipendaftaran'] = $poli->lantaipendaftaran;
        $request['lokasi'] = $poli->lantaipendaftaran;
        if ($request->jenispasien == "NON-JKN") {
            $request['lantaipendaftaran'] = 1;
        }
        // cek jadwal
        $jadwal = $this->status_antrian($request);
        if ($jadwal->metadata->code == 200) {
            $jadwal = $jadwal->response;
            $request['namapoli'] = $jadwal->namapoli;
            $request['namadokter'] = $jadwal->namadokter;
        } else {
            $message = $jadwal->metadata->message;
            // kirim notif
            $wa = new WhatsappController();
            $request['notif'] = 'Method ' . $request->method . ' jadwal , ' . $message;
            $wa->send_notif($request);
            return $this->sendError('Mohon maaf , ' . $message, 400);
        }
        $antrian_poli = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('kodepoli', $request->kodepoli)
            ->count();
        $antrian_lantai = Antrian::where('tanggalperiksa', $request->tanggalperiksa)
            ->where('method', $request->method)
            ->where('lantaipendaftaran', $request->lantaipendaftaran)
            ->where('jenispasien', $request->jenispasien)
            ->count();
        $request['nomorantrean'] = $request->kodepoli . "-" .  str_pad($antrian_poli + 1, 3, '0', STR_PAD_LEFT);
        $request['angkaantrean'] = $antrian_lantai + 1;
        $request['kodebooking'] = strtoupper(uniqid());
        // estimasi
        $timestamp = $request->tanggalperiksa . ' ' . explode('-', $request->jampraktek)[0] . ':00';
        $jadwal_estimasi = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Asia/Jakarta')->addMinutes(10 * ($antrian_poli + 1));
        $request['estimasidilayani'] = $jadwal_estimasi->timestamp * 1000;
        $request['sisakuotajkn'] =  $jadwal->sisakuotajkn - 1;
        $request['kuotajkn'] = $jadwal->kuotajkn;
        $request['sisakuotanonjkn'] = $jadwal->sisakuotanonjkn - 1;
        $request['kuotanonjkn'] = $jadwal->kuotanonjkn;
        $request['keterangan'] = "Silahkan menunggu panggilan di loket Pendaftaran Lantai " . $request->lantaipendaftaran;
        // tambah antrian database
        $antrian = Antrian::create([
            "kodebooking" => $request->kodebooking,
            "nomorkartu" => $request->nomorkartu,
            "nik" => $request->nik,
            "nohp" => $request->nohp,
            "kodepoli" => $request->kodepoli,
            "norm" => $request->norm,
            "pasienbaru" => $request->pasienbaru,
            "tanggalperiksa" => $request->tanggalperiksa,
            "kodedokter" => $request->kodedokter,
            "jampraktek" => $request->jampraktek,
            "jeniskunjungan" => 0,
            "nomorreferensi" => $request->nomorreferensi,
            "method" => $request->method,
            "nomorrujukan" => $request->nomorRujukan,
            "nomorsuratkontrol" => $request->noSuratKontrol,
            'nomorsep' => $request->nomorsep,
            "kode_kunjungan" => $request->kode_kunjungan,
            "jenispasien" => $request->jenispasien,
            "namapoli" => $request->namapoli,
            "namadokter" => $request->namadokter,
            "nomorantrean" => $request->nomorantrean,
            "angkaantrean" => $request->angkaantrean,
            "estimasidilayani" => $request->estimasidilayani,
            "sisakuotajkn" => $request->sisakuotajkn,
            "kuotajkn" => $request->kuotajkn,
            "sisakuotanonjkn" => $request->sisakuotanonjkn,
            "kuotanonjkn" => $request->kuotanonjkn,
            "keterangan" => $request->keterangan,
            "lokasi" => $request->lokasi,
            "lantaipendaftaran" => $request->lantaipendaftaran,
            "status_api" => 1,
            "taskid" => 0,
            "user" => "System Antrian",
            "nama" => $request->nama,
        ]);
        $response = [
            "nomorantrean" => $request->nomorantrean,
            "angkaantrean" => $request->angkaantrean,
            "kodebooking" => $request->kodebooking,
            "norm" => $request->norm,
            "namapoli" => $request->namapoli,
            "namadokter" => $request->namadokter,
            "estimasidilayani" => $request->estimasidilayani,
            "sisakuotajkn" => $request->sisakuotajkn,
            "kuotajkn" => $request->kuotajkn,
            "sisakuotanonjkn" => $request->sisakuotanonjkn,
            "kuotanonjkn" => $request->kuotanonjkn,
            "keterangan" => $request->keterangan,
        ];
        return $this->sendResponse($response, 200);
    }
    public function sisa_antrian(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  400);
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        // antrian ditermukan
        if (isset($antrian)) {
            $sisaantrean = Antrian::where('taskid', "<=", 3)
                ->where('tanggalperiksa', $antrian->tanggalperiksa)
                ->where('kodepoli', $antrian->kodepoli)
                ->where('taskid', ">=", 0)
                ->count();
            $antreanpanggil =  Antrian::where('taskid', "<=", 3)
                ->where('taskid', ">=", 1)
                ->where('kodepoli', $antrian->kodepoli)
                ->where('tanggalperiksa', $antrian->tanggalperiksa)
                ->first();
            if (empty($antreanpanggil)) {
                $antreanpanggil['nomorantrean'] = '0';
            }
            $antrian['waktutunggu'] = 300 +  300 * ($sisaantrean - 1);
            $antrian['keterangan'] = "Info Sisa Antrian";
            $response = [
                "nomorantrean" => $antrian->nomorantrean,
                "namapoli" => $antrian->namapoli,
                "namadokter" => $antrian->namadokter,
                "sisaantrean" => $sisaantrean,
                "antreanpanggil" => $antreanpanggil['nomorantrean'],
                "waktutunggu" => $antrian->waktutunggu,
                "keterangan" => $antrian->keterangan,
            ];
            return $this->sendResponse($response, 200);
        }
        // antrian tidak ditermukan
        else {
            return $this->sendError('Antrian tidak ditemukan', 400);
        }
    }
    public function batal_antrian(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "keterangan" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (isset($antrian)) {
            $response = $this->batal_antrean($request);
            // if ($response->metadata->code == 200) {
            // kirim notif wa
            // $wa = new WhatsappController();
            // $request['message'] = "Kode antrian " . $antrian->kodebooking . " telah dibatakan\n" . $request->keterangan;
            // // $request['message'] = "Kode antrian " . $antrian->kodebooking . " telah dibatakan karena perubahan jadwal.";;
            // $request['number'] = $antrian->nohp;
            // $wa->send_message($request);
            $antrian->update([
                "taskid" => 99,
                "status_api" => 1,
                "keterangan" => $request->keterangan,
            ]);
            return $this->sendError($response->metadata->message, 200);
        }
        // antrian tidak ditemukan
        else {
            return $this->sendError('Antrian tidak ditemukan',  201);
        }
    }
    public function checkin_antrian(Request $request) #checkin antrian api
    {
        // cek printer
        try {
            $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
            $printer = new Printer($connector);
            $printer->close();
        } catch (\Throwable $th) {
            return $this->sendError("Printer mesin antrian mati", 500);
        }
        // checking request
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
            "waktu" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (isset($antrian)) {
            // check backdate
            if (!Carbon::parse($antrian->tanggalperiksa)->isToday()) {
                return $this->sendError("Tanggal periksa bukan hari ini.", 400);
            }
            $now = Carbon::now();
            $unit = Unit::firstWhere('KDPOLI', $antrian->kodepoli);
            $tarifkarcis = TarifLayananDetail::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_karcis);
            $tarifadm = TarifLayananDetail::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_adm);
            if ($antrian->taskid == 99) {
                return $this->sendError("Antrian telah dibatalkan sebelumnya.", 400);
            }
            if ($antrian->pasienbaru) {
                $request['pasienbaru_print'] = 'BARU';
            } else {
                $request['pasienbaru_print'] = 'LAMA';
            }
            // jika pasien jkn
            if ($antrian->jenispasien == "JKN") {
                $request['status_api'] = 1;
                $request['taskid'] = 3;
                $request['keterangan'] = "Untuk pasien peserta JKN silahkan dapat langsung menunggu ke POLIKINIK " . $antrian->namapoli;
                $request['noKartu'] = $antrian->nomorkartu;
                $request['tglSep'] = Carbon::createFromTimestamp($request->waktu / 1000)->format('Y-m-d');
                $request['noMR'] = $antrian->norm;
                $request['norm'] = $antrian->norm;
                $request['nik'] = $antrian->nik;
                $request['nohp'] = $antrian->nohp;
                $request['kodedokter'] = $antrian->kodedokter;
                $vclaim = new VclaimController();
                // daftar pake surat kontrol
                if ($antrian->jeniskunjungan == 3) {
                    $request['nomorreferensi'] = $antrian->nomorsuratkontrol;
                    $request['noSuratKontrol'] = $antrian->nomorsuratkontrol;
                    $request['noTelp'] = $antrian->nohp;
                    $request['user'] = "Mesin Antrian";
                    $suratkontrol = $vclaim->suratkontrol_nomor($request);
                    // berhasil get surat kontrol
                    if ($suratkontrol->metadata->code == 200) {
                        $suratkontrol = $suratkontrol;
                        $request['nomorsuratkontrol'] = $antrian->nomorsuratkontrol;

                        if ($suratkontrol->response->sep->jnsPelayanan == "Rawat Jalan") {
                            $request['nomorrujukan'] = $suratkontrol->response->sep->provPerujuk->noRujukan;
                            $request['jeniskunjungan_print'] = 'KONTROL';
                            $request['nomorreferensi'] = $antrian->nomorrujukan;
                            $data = $vclaim->rujukan_nomor($request);

                            if ($data->metadata->code != 200) {
                                $data = $vclaim->rujukan_rs_nomor($request);
                            }

                            // berhasil get rujukan
                            if ($data->metadata->code == 200) {
                                $data = $data;
                                $rujukan = $data->response->rujukan;
                                $peserta = $rujukan->peserta;
                                $diganosa = $rujukan->diagnosa;
                                $tujuan = $rujukan->poliRujukan;
                                $penjamin = Penjamin::where('nama_penjamin_bpjs', $peserta->jenisPeserta->keterangan)->first(); // get peserta
                                // $request['kodepenjamin'] = 'P13';
                                $request['kodepenjamin'] = $penjamin->kode_penjamin_simrs; // get peserta
                                // tujuan rujukan
                                // ppkPelayanan
                                $request['ppkPelayanan'] = "1018R001";
                                $request['jnsPelayanan'] = "2";
                                // peserta
                                $request['klsRawatHak'] = $peserta->hakKelas->kode; // get peserta
                                $request['klsRawatNaik'] = ""; // get peserta
                                // $request['pembiayaan'] = $peserta->jenisPeserta->kode;
                                // $request['penanggungJawab'] =  $peserta->jenisPeserta->keterangan;
                                // asal rujukan
                                $request['asalRujukan'] = $data->response->asalFaskes; // get surat kontrol
                                $request['tglRujukan'] = $rujukan->tglKunjungan; // get surat kontrol
                                $request['noRujukan'] =   $rujukan->noKunjungan; // get surat kontrol
                                $request['ppkRujukan'] = $rujukan->provPerujuk->kode; // get surat kontrol
                                // diagnosa
                                $request['catatan'] =  $diganosa->nama; // get surat kontrol
                                $request['diagAwal'] =  $diganosa->kode; // get surat kontrol
                                // poli tujuan
                                $request['tujuan'] =  $antrian->kodepoli; // get antrian
                                $request['eksekutif'] =  0;
                                // dpjp

                                $request['tujuanKunj'] = "2";
                                $request['flagProcedure'] = "";
                                $request['kdPenunjang'] = "";
                                $request['assesmentPel'] = "2";
                                $request['noSurat'] = $request->nomorsuratkontrol; // get antrian
                                $request['kodeDPJP'] = $suratkontrol->response->kodeDokter;
                                $request['dpjpLayan'] =  $suratkontrol->response->kodeDokter;
                            }
                            // gagal get rujukan
                            else {
                                return $this->sendError($data->metadata->message,  400);
                            }
                        } else {
                            $request['nomorkartu'] = $antrian->nomorkartu;
                            $request['tanggal'] = $antrian->tanggalperiksa;
                            $data = $vclaim->peserta_nomorkartu($request);
                            // berhasil get rujukan
                            if ($data->metadata->code == 200) {
                                $data = $data;
                                $peserta = $data->response->peserta;
                                $diagnosa = $suratkontrol->response->sep->diagnosa;
                                $asalRujukan = $suratkontrol->response->sep->provPerujuk->asalRujukan;
                                $tglRujukan = $suratkontrol->response->sep->provPerujuk->tglRujukan;
                                $noRujukan = $suratkontrol->response->sep->noSep;
                                $ppkRujukan = $suratkontrol->response->sep->provPerujuk->kdProviderPerujuk;
                                $penjamin = Penjamin::where('nama_penjamin_bpjs', $peserta->jenisPeserta->keterangan)->first(); // get peserta
                                $request['kodepenjamin'] = $penjamin->kode_penjamin_simrs; // get peserta
                                // $request['kodepenjamin'] = 'P13';
                                // tujuan rujukan
                                $request['ppkPelayanan'] = "1018R001";
                                $request['jnsPelayanan'] = "2";
                                // peserta
                                $request['klsRawatHak'] = $peserta->hakKelas->kode; // get peserta
                                $request['klsRawatNaik'] = ""; // get peserta
                                // $request['pembiayaan'] = $peserta->jenisPeserta->kode;
                                // $request['penanggungJawab'] =  $peserta->jenisPeserta->keterangan;
                                // asal rujukan
                                $request['asalRujukan'] = $asalRujukan; // get surat kontrol
                                $request['tglRujukan'] = $tglRujukan; // get surat kontrol
                                $request['noRujukan'] =   $noRujukan; // get surat kontrol
                                $request['ppkRujukan'] = $ppkRujukan; // get surat kontrol
                                // diagnosa
                                $request['catatan'] =  $diagnosa; // get surat kontrol
                                $request['diagAwal'] = str_replace(" ", "", explode('-', $diagnosa)[0]);
                                // poli tujuan
                                $request['tujuan'] =  $antrian->kodepoli; // get antrian
                                $request['eksekutif'] =  0;
                                // dpjp
                                $request['tujuanKunj'] = "0";
                                $request['flagProcedure'] = "";
                                $request['kdPenunjang'] = "";
                                $request['assesmentPel'] = "";
                                $request['noSurat'] = $request->nomorsuratkontrol; // get antrian
                                $request['kodeDPJP'] = $suratkontrol->response->kodeDokter;
                                $request['dpjpLayan'] =  $suratkontrol->response->kodeDokter;
                                // dd($request->all(), $suratkontrol->response->sep->jnsPelayanan, $data);
                            }
                        }
                    }
                    // gagal get surat kontrol
                    else {
                        return $this->sendError($suratkontrol->metadata->message,  400);
                    }
                }
                // daftar pake rujukan
                else {
                    $request['nomorrujukan'] = $antrian->nomorreferensi;
                    $request['nomorreferensi'] = $antrian->nomorreferensi;
                    $request['jeniskunjungan_print'] = 'RUJUKAN';
                    $vclaim = new VclaimController();
                    if ($antrian->jeniskunjungan == 4) {
                        $data = $vclaim->rujukan_rs_nomor($request);
                    } else  if ($antrian->jeniskunjungan == 1) {
                        $data = $vclaim->rujukan_nomor($request);
                    }
                    // berhasil get rujukan
                    if ($data->metadata->code == 200) {
                        $data = $data;
                        $rujukan = $data->response->rujukan;
                        $peserta = $rujukan->peserta;
                        $diganosa = $rujukan->diagnosa;
                        $tujuan = $rujukan->poliRujukan;
                        $penjamin = Penjamin::where('nama_penjamin_bpjs', $peserta->jenisPeserta->keterangan)->first();
                        $request['kodepenjamin'] = $penjamin->kode_penjamin_simrs;
                        // $request['kodepenjamin'] = 'P13';
                        // tujuan rujukan
                        $request['ppkPelayanan'] = "1018R001";
                        $request['jnsPelayanan'] = "2";
                        // peserta
                        $request['klsRawatHak'] = $peserta->hakKelas->kode;
                        $request['klsRawatNaik'] = "";
                        // $request['pembiayaan'] = $peserta->jenisPeserta->kode;
                        // $request['penanggungJawab'] =  $peserta->jenisPeserta->keterangan;
                        // asal rujukan
                        $request['asalRujukan'] = $data->response->asalFaskes;
                        $request['tglRujukan'] = $rujukan->tglKunjungan;
                        $request['noRujukan'] =   $request->nomorreferensi;
                        $request['ppkRujukan'] = $rujukan->provPerujuk->kode;
                        // diagnosa
                        $request['catatan'] =  $diganosa->nama;
                        $request['diagAwal'] =  $diganosa->kode;
                        // poli tujuan
                        $request['tujuan'] =  $antrian->kodepoli;
                        $request['eksekutif'] =  0;
                        // dpjp
                        $request['tujuanKunj'] = "0";
                        $request['flagProcedure'] = "";
                        $request['kdPenunjang'] = "";
                        $request['assesmentPel'] = "";
                        // $request['noSurat'] = "";
                        $request['kodeDPJP'] = "";
                        $request['dpjpLayan'] = $antrian->kodedokter;
                        $request['noTelp'] = $antrian->nohp;
                        $request['user'] = "Mesin Antrian";
                    }
                    // gagal get rujukan
                    else {
                        return $this->sendError($data->metadata->message,  400);
                    }
                }
                // create sep
                $sep = $vclaim->sep_insert($request);
                // dd($request->all(), $sep);
                // berhasil buat sep
                if ($sep->metadata->code == 200) {
                    // update antrian sep
                    $sep = $sep->response->sep;
                    $request["nomorsep"] = $sep->noSep;
                    $antrian->update([
                        "nomorsep" => $request->nomorsep
                    ]);
                    // print sep
                    $this->print_sep($request, $sep);
                }
                // gagal buat sep
                else {
                    if (!isset($antrian->nomorsep)) {
                    } else {
                        $requests["nomorsep"] = $antrian->nomorsep;
                        // return $this->sendError("Gagal Buat SEP : " . $sep->metadata->message,  400);
                    }
                }
                // rj jkn tipe transaki 2 status layanan 2 status layanan detail opn
                $tipetransaksi = 2;
                $statuslayanan = 2;
                // rj jkn masuk ke tagihan penjamin
                $tagihanpenjamin_karcis = $tarifkarcis->TOTAL_TARIF_NEW;
                $tagihanpenjamin_adm = $tarifadm->TOTAL_TARIF_NEW;
                $totalpenjamin =  $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
                $tagihanpribadi_karcis = 0;
                $tagihanpribadi_adm = 0;
                $totalpribadi =  0;
            }
            // jika pasien non jkn
            else {
                $request['taskid'] = 3;
                $request['status_api'] = 0;
                $request['kodepenjamin'] = "P01";
                $request['jeniskunjungan_print'] = 'KUNJUNGAN UMUM';
                $request['keterangan'] = "Untuk pasien peserta NON-JKN silahkan lakukan pembayaran terlebih dahulu di Loket Pembayaran samping BJB";
                // rj umum tipe transaki 1 status layanan 1 status layanan detail opn
                $tipetransaksi = 1;
                $statuslayanan = 1;
                // rj umum masuk ke tagihan pribadi
                $tagihanpenjamin_karcis = 0;
                $tagihanpenjamin_adm = 0;
                $totalpenjamin =  0;

                $tagihanpribadi_karcis = $tarifkarcis->TOTAL_TARIF_NEW;
                $tagihanpribadi_adm = $tarifadm->TOTAL_TARIF_NEW;
                $totalpribadi = $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW;
            }
            if ($antrian->taskid == 3) {
                $this->print_ulang($request);
                return $this->sendError("Printer Ulang",  201);
            }
            // update antrian bpjs
            $response = $this->update_antrean($request);
            // jika antrian berhasil diupdate di bpjs
            if ($response->metadata->code == 200) {
                // insert simrs create kunjungan
                try {
                    $paramedis = Paramedis::firstWhere('kode_dokter_jkn', $antrian->kodedokter);
                    // hitung counter kunjungan
                    $kunjungan = Kunjungan::where('no_rm', $antrian->norm)->orderBy('counter', 'DESC')->first();
                    if (empty($kunjungan)) {
                        $counter = 1;
                    } else {
                        $counter = $kunjungan->counter + 1;
                    }
                    // insert ts kunjungan status 8
                    Kunjungan::create(
                        [
                            'counter' => $counter,
                            'no_rm' => $antrian->norm,
                            'kode_unit' => $unit->kode_unit,
                            'tgl_masuk' => $now,
                            'kode_paramedis' => $paramedis->kode_paramedis,
                            'status_kunjungan' => 8,
                            'prefix_kunjungan' => $unit->prefix_unit,
                            'kode_penjamin' => $request->kodepenjamin,
                            'pic' => 1319,
                            'id_alasan_masuk' => 1,
                            'kelas' => 3,
                            'hak_kelas' => $request->klsRawatHak,
                            'no_sep' =>  $request->nomorsep,
                            'no_rujukan' => $antrian->nomorrujukan,
                            'diagx' =>   $request->catatan,
                            'created_at' => $now,
                            'keterangan2' => 'MESIN_2',
                        ]
                    );
                    $kunjungan = Kunjungan::where('no_rm', $antrian->norm)->where('counter', $counter)->first();
                    // get transaksi sebelumnya
                    // $trx_lama = TransaksiDB::where('unit', $unit->kode_unit)
                    //     ->whereBetween('tgl', [Carbon::now()->startOfDay(), [Carbon::now()->endOfDay()]])
                    //     ->count();
                    // get kode layanan
                    // $kodelayanan = $unit->prefix_unit . $now->format('y') . $now->format('m') . $now->format('d')  . str_pad($trx_lama + 1, 6, '0', STR_PAD_LEFT);
                    $kodelayanan = collect(DB::connection('mysql2')->select('CALL GET_NOMOR_LAYANAN_HEADER(' . $unit->kode_unit . ')'))->first()->no_trx_layanan;
                    //  insert transaksi
                    // $trx_baru = TransaksiDB::create([
                    //     'tgl' => $now->format('Y-m-d'),
                    //     'no_trx_layanan' => $kodelayanan,
                    //     'unit' => $unit->kode_unit,
                    // ]);
                    //  insert layanan header
                    $layananbaru = Layanan::create(
                        [
                            'kode_layanan_header' => $kodelayanan,
                            'tgl_entry' => $now,
                            'kode_kunjungan' => $kunjungan->kode_kunjungan,
                            'kode_unit' => $unit->kode_unit,
                            'kode_tipe_transaksi' => $tipetransaksi,
                            'status_layanan' => $statuslayanan,
                            'pic' => '1319',
                            'keterangan' => 'Layanan header melalui antrian sistem',
                        ]
                    );
                    //  insert layanan header dan detail karcis admin konsul 25 + 5 = 30
                    //  DET tahun bulan `tanggal b`aru urutan 6 digit kanan
                    //  insert layanan detail karcis
                    $layanandet = LayananDetail::orderBy('tgl_layanan_detail', 'DESC')->first();
                    $nomorlayanandet = substr($layanandet->id_layanan_detail, 9) + 1;
                    $karcis = LayananDetail::create(
                        [
                            'id_layanan_detail' => "DET" . $now->format('y') . $now->format('m') . $now->format('d')  . $nomorlayanandet,
                            'row_id_header' => $layananbaru->id,
                            'kode_layanan_header' => $layananbaru->kode_layanan_header,
                            'kode_tarif_detail' => $tarifkarcis->KODE_TARIF_DETAIL,
                            'total_tarif' => $tarifkarcis->TOTAL_TARIF_NEW,
                            'jumlah_layanan' => 1,
                            'tagihan_pribadi' => $tagihanpribadi_karcis,
                            'tagihan_penjamin' => $tagihanpenjamin_karcis,
                            'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                            'grantotal_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                            'kode_dokter1' => $paramedis->kode_paramedis, // ambil dari mt paramdeis
                            'tgl_layanan_detail' =>  $now,
                            'status_layanan_detail' => "OPN",
                            'tgl_layanan_detail_2' =>  $now,
                        ]
                    );
                    //  insert layanan detail admin
                    $layanandet = LayananDetail::orderBy('tgl_layanan_detail', 'DESC')->first();
                    $nomorlayanandet = substr($layanandet->id_layanan_detail, 9) + 1;
                    $adm = LayananDetail::create(
                        [
                            'id_layanan_detail' => "DET" . $now->format('y') . $now->format('m') . $now->format('d')  . $nomorlayanandet,
                            'row_id_header' => $layananbaru->id,
                            'kode_layanan_header' => $layananbaru->kode_layanan_header,
                            'kode_tarif_detail' => $tarifadm->KODE_TARIF_DETAIL,
                            'total_tarif' => $tarifadm->TOTAL_TARIF_NEW,
                            'jumlah_layanan' => 1,
                            'tagihan_pribadi' =>  $tagihanpribadi_adm,
                            'tagihan_penjamin' =>  $tagihanpenjamin_adm,
                            'total_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                            'grantotal_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                            'kode_dokter1' => 0,
                            'tgl_layanan_detail' =>  $now,
                            'status_layanan_detail' => "OPN",
                            'tgl_layanan_detail_2' =>  $now,
                        ]
                    );
                    //  update layanan header total tagihan
                    $layananbaru->update([
                        'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW,
                        'tagihan_pribadi' => $totalpribadi,
                        'tagihan_penjamin' => $totalpenjamin,
                    ]);
                } catch (\Throwable $th) {
                    return [
                        "metadata" => [
                            "message" => "Error Create Kunjungan SIMRS : " . $th->getMessage(),
                            "code" => 201,
                        ],
                    ];
                }
                // update antrian kunjungan
                try {
                    $kunjungan->update([
                        'status_kunjungan' => 1,
                    ]);
                    $antrian->update([
                        "kode_kunjungan" => $kunjungan->kode_kunjungan,
                    ]);
                    // insert tracer tc_tracer_header
                    $tracerbaru = Tracer::create([
                        'kode_kunjungan' => $kunjungan->kode_kunjungan,
                        'tgl_tracer' => $now->format('Y-m-d'),
                        'id_status_tracer' => 1,
                        'cek_tracer' => "N",
                    ]);
                } catch (\Throwable $th) {
                    //throw $th;
                    return [
                        "metadata" => [
                            "message" => "Error Update Kunjungan Antrian : " . $th->getMessage(),
                            "code" => 201,
                        ],
                    ];
                }
                // update antrian print tracer wa
                try {
                    $antrian->update([
                        "taskid" => $request->taskid,
                        "status_api" => $request->status_api,
                        "keterangan" => $request->keterangan,
                        "taskid1" => $now,
                    ]);
                    // print antrian
                    $print_karcis = new AntrianController();
                    $request['tarifkarcis'] = $tarifkarcis->TOTAL_TARIF_NEW;
                    $request['tarifadm'] = $tarifadm->TOTAL_TARIF_NEW;
                    $request['norm'] = $antrian->norm;
                    $request['nama'] = $antrian->nama;
                    $request['nik'] = $antrian->nik;
                    $request['nomorkartu'] = $antrian->nomorkartu;
                    $request['nohp'] = $antrian->nohp;
                    $request['nomorrujukan'] = $antrian->nomorrujukan;
                    $request['nomorsuratkontrol'] = $antrian->nomorsuratkontrol;
                    $request['namapoli'] = $antrian->namapoli;
                    $request['namadokter'] = $antrian->namadokter;
                    $request['jampraktek'] = $antrian->jampraktek;
                    $request['tanggalperiksa'] = $antrian->tanggalperiksa;
                    $request['jenispasien'] = $antrian->jenispasien;
                    $request['nomorantrean'] = $antrian->nomorantrean;
                    $request['lokasi'] = $antrian->lokasi;
                    $request['angkaantrean'] = $antrian->angkaantrean;
                    $request['lantaipendaftaran'] = $antrian->lantaipendaftaran;
                    $print_karcis->print_karcis($request, $kunjungan);
                    // notif wa
                    $wa = new WhatsappController();
                    $request['message'] = "Antrian dengan kode booking " . $antrian->kodebooking . " telah melakukan checkin.\n\n" . $request->keterangan;
                    $request['number'] = $antrian->nohp;
                    $wa->send_message($request);
                } catch (\Throwable $th) {
                    return $this->sendError("Error Update Antrian : " . $th->getMessage(), 201);
                }
            } else {
                if ($antrian->kode_kunjungan == null) {
                    // insert simrs create kunjungan
                    try {
                        $paramedis = Paramedis::firstWhere('kode_dokter_jkn', $antrian->kodedokter);
                        // hitung counter kunjungan
                        $kunjungan = Kunjungan::where('no_rm', $antrian->norm)->orderBy('counter', 'DESC')->first();
                        if (empty($kunjungan)) {
                            $counter = 1;
                        } else {
                            $counter = $kunjungan->counter + 1;
                        }
                        // insert ts kunjungan status 8
                        Kunjungan::create(
                            [
                                'counter' => $counter,
                                'no_rm' => $antrian->norm,
                                'kode_unit' => $unit->kode_unit,
                                'tgl_masuk' => $now,
                                'kode_paramedis' => $paramedis->kode_paramedis,
                                'status_kunjungan' => 8,
                                'prefix_kunjungan' => $unit->prefix_unit,
                                'kode_penjamin' => $request->kodepenjamin,
                                'pic' => 1319,
                                'id_alasan_masuk' => 1,
                                'kelas' => 3,
                                'hak_kelas' => $request->klsRawatHak,
                                'no_sep' =>  $request->nomorsep,
                                'no_rujukan' => $antrian->nomorrujukan,
                                'diagx' =>   $request->catatan,
                                'created_at' => $now,
                                'keterangan2' => 'MESIN_2',
                            ]
                        );
                        $kunjungan = Kunjungan::where('no_rm', $antrian->norm)->where('counter', $counter)->first();
                        // get transaksi sebelumnya
                        // $trx_lama = TransaksiDB::where('unit', $unit->kode_unit)
                        //     ->whereBetween('tgl', [Carbon::now()->startOfDay(), [Carbon::now()->endOfDay()]])
                        //     ->count();
                        // get kode layanan
                        // $kodelayanan = $unit->prefix_unit . $now->format('y') . $now->format('m') . $now->format('d')  . str_pad($trx_lama + 1, 6, '0', STR_PAD_LEFT);
                        $kodelayanan = collect(DB::connection('mysql2')->select('CALL GET_NOMOR_LAYANAN_HEADER(' . $unit->kode_unit . ')'))->first()->no_trx_layanan;
                        //  insert transaksi
                        // $trx_baru = TransaksiDB::create([
                        //     'tgl' => $now->format('Y-m-d'),
                        //     'no_trx_layanan' => $kodelayanan,
                        //     'unit' => $unit->kode_unit,
                        // ]);
                        //  insert layanan header
                        $layananbaru = Layanan::create(
                            [
                                'kode_layanan_header' => $kodelayanan,
                                'tgl_entry' => $now,
                                'kode_kunjungan' => $kunjungan->kode_kunjungan,
                                'kode_unit' => $unit->kode_unit,
                                'kode_tipe_transaksi' => $tipetransaksi,
                                'status_layanan' => $statuslayanan,
                                'pic' => '1319',
                                'keterangan' => 'Layanan header melalui antrian sistem',
                            ]
                        );
                        //  insert layanan header dan detail karcis admin konsul 25 + 5 = 30
                        //  DET tahun bulan `tanggal b`aru urutan 6 digit kanan
                        //  insert layanan detail karcis
                        $layanandet = LayananDetail::orderBy('tgl_layanan_detail', 'DESC')->first();
                        $nomorlayanandet = substr($layanandet->id_layanan_detail, 9) + 1;
                        $karcis = LayananDetail::create(
                            [
                                'id_layanan_detail' => "DET" . $now->format('y') . $now->format('m') . $now->format('d')  . $nomorlayanandet,
                                'row_id_header' => $layananbaru->id,
                                'kode_layanan_header' => $layananbaru->kode_layanan_header,
                                'kode_tarif_detail' => $tarifkarcis->KODE_TARIF_DETAIL,
                                'total_tarif' => $tarifkarcis->TOTAL_TARIF_NEW,
                                'jumlah_layanan' => 1,
                                'tagihan_pribadi' => $tagihanpribadi_karcis,
                                'tagihan_penjamin' => $tagihanpenjamin_karcis,
                                'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                                'grantotal_layanan' => $tarifkarcis->TOTAL_TARIF_NEW,
                                'kode_dokter1' => $paramedis->kode_paramedis, // ambil dari mt paramdeis
                                'tgl_layanan_detail' =>  $now,
                                'status_layanan_detail' => "OPN",
                                'tgl_layanan_detail_2' =>  $now,
                            ]
                        );
                        //  insert layanan detail admin
                        $layanandet = LayananDetail::orderBy('tgl_layanan_detail', 'DESC')->first();
                        $nomorlayanandet = substr($layanandet->id_layanan_detail, 9) + 1;
                        $adm = LayananDetail::create(
                            [
                                'id_layanan_detail' => "DET" . $now->format('y') . $now->format('m') . $now->format('d')  . $nomorlayanandet,
                                'row_id_header' => $layananbaru->id,
                                'kode_layanan_header' => $layananbaru->kode_layanan_header,
                                'kode_tarif_detail' => $tarifadm->KODE_TARIF_DETAIL,
                                'total_tarif' => $tarifadm->TOTAL_TARIF_NEW,
                                'jumlah_layanan' => 1,
                                'tagihan_pribadi' =>  $tagihanpribadi_adm,
                                'tagihan_penjamin' =>  $tagihanpenjamin_adm,
                                'total_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                                'grantotal_layanan' => $tarifadm->TOTAL_TARIF_NEW,
                                'kode_dokter1' => 0,
                                'tgl_layanan_detail' =>  $now,
                                'status_layanan_detail' => "OPN",
                                'tgl_layanan_detail_2' =>  $now,
                            ]
                        );
                        //  update layanan header total tagihan
                        $layananbaru->update([
                            'total_layanan' => $tarifkarcis->TOTAL_TARIF_NEW + $tarifadm->TOTAL_TARIF_NEW,
                            'tagihan_pribadi' => $totalpribadi,
                            'tagihan_penjamin' => $totalpenjamin,
                        ]);
                    } catch (\Throwable $th) {
                        return [
                            "metadata" => [
                                "message" => "Error Create Kunjungan SIMRS : " . $th->getMessage(),
                                "code" => 201,
                            ],
                        ];
                    }
                    // update antrian kunjungan
                    try {
                        $kunjungan->update([
                            'status_kunjungan' => 1,
                        ]);
                        $antrian->update([
                            "kode_kunjungan" => $kunjungan->kode_kunjungan,
                        ]);
                        // insert tracer tc_tracer_header
                        $tracerbaru = Tracer::create([
                            'kode_kunjungan' => $kunjungan->kode_kunjungan,
                            'tgl_tracer' => $now->format('Y-m-d'),
                            'id_status_tracer' => 1,
                            'cek_tracer' => "N",
                        ]);
                    } catch (\Throwable $th) {
                        //throw $th;
                        return [
                            "metadata" => [
                                "message" => "Error Update Kunjungan Antrian : " . $th->getMessage(),
                                "code" => 201,
                            ],
                        ];
                    }
                    // update antrian print tracer wa
                    try {
                        $antrian->update([
                            "taskid" => $request->taskid,
                            "status_api" => $request->status_api,
                            "keterangan" => $request->keterangan,
                            "taskid1" => $now,
                        ]);
                        // print antrian
                        $print_karcis = new AntrianController();
                        $request['tarifkarcis'] = $tarifkarcis->TOTAL_TARIF_NEW;
                        $request['tarifadm'] = $tarifadm->TOTAL_TARIF_NEW;
                        $request['norm'] = $antrian->norm;
                        $request['nama'] = $antrian->nama;
                        $request['nik'] = $antrian->nik;
                        $request['nomorkartu'] = $antrian->nomorkartu;
                        $request['nohp'] = $antrian->nohp;
                        $request['nomorrujukan'] = $antrian->nomorrujukan;
                        $request['nomorsuratkontrol'] = $antrian->nomorsuratkontrol;
                        $request['namapoli'] = $antrian->namapoli;
                        $request['namadokter'] = $antrian->namadokter;
                        $request['jampraktek'] = $antrian->jampraktek;
                        $request['tanggalperiksa'] = $antrian->tanggalperiksa;
                        $request['jenispasien'] = $antrian->jenispasien;
                        $request['nomorantrean'] = $antrian->nomorantrean;
                        $request['lokasi'] = $antrian->lokasi;
                        $request['angkaantrean'] = $antrian->angkaantrean;
                        $request['lantaipendaftaran'] = $antrian->lantaipendaftaran;
                        $print_karcis->print_karcis($request, $kunjungan);
                        // notif wa
                        $wa = new WhatsappController();
                        $request['message'] = "Antrian dengan kode booking " . $antrian->kodebooking . " telah melakukan checkin.\n\n" . $request->keterangan;
                        $request['number'] = $antrian->nohp;
                        $wa->send_message($request);
                    } catch (\Throwable $th) {
                        return $this->sendError("Error Update Antrian : " . $th->getMessage(), 201);
                    }
                }
            }
            return $response;
        }
        // jika antrian tidak ditemukan
        else {
            return $this->sendError("Kode booking tidak ditemukan", 400);
        }
    }
    public function info_pasien_baru(Request $request)
    {
        return $this->sendError("Anda belum memiliki No RM di RSUD Waled (Pasien Baru). Silahkan daftar secara offline.", 400);
        // // auth token
        // $auth = $this->auth_token($request);
        // if ($auth['metadata']['code'] != 200) {
        //     return $auth;
        // }
        // // checking request
        // $validator = Validator::make(request()->all(), [
        //     "nik" => "required|digits:16",
        //     "nomorkartu" => "required|digits:13",
        //     "nomorkk" => "required",
        //     "nama" => "required",
        //     "jeniskelamin" => "required",
        //     "tanggallahir" => "required",
        //     "nohp" => "required",
        //     "alamat" => "required",
        //     "kodeprop" => "required",
        //     "namaprop" => "required",
        //     "kodedati2" => "required",
        //     "namadati2" => "required",
        //     "kodekec" => "required",
        //     "namakec" => "required",
        //     "kodekel" => "required",
        //     "namakel" => "required",
        //     "rw" => "required",
        //     "rt" => "required",
        // ]);
        // if ($validator->fails()) {
        //     return [
        //         'metadata' => [
        //             'code' => 201,
        //             'message' => $validator->errors()->first(),
        //         ],
        //     ];
        // }
        // $pasien = PasienDB::where('nik_bpjs', $request->nik)->first();
        // // cek jika pasien baru
        // if (empty($pasien)) {
        //     // proses pendaftaran baru
        //     // try {
        //     //     // checking norm terakhir
        //     //     $pasien_terakhir = PasienDB::latest()->first()->no_rm;
        //     //     $request['status'] = 1;
        //     //     $request['norm'] = $pasien_terakhir + 1;
        //     //     // insert pasien
        //     //     PasienDB::create(
        //     //         [
        //     //             "no_Bpjs" => $request->nomorkartu,
        //     //             "nik_bpjs" => $request->nik,
        //     //             "no_rm" => $request->norm,
        //     //             // "nomorkk" => $request->nomorkk,
        //     //             "nama_px" => $request->nama,
        //     //             "jenis_kelamin" => $request->jeniskelamin,
        //     //             "tgl_lahir" => $request->tanggallahir,
        //     //             "no_tlp" => $request->nohp,
        //     //             "alamat" => $request->alamat,
        //     //             "kode_propinsi" => $request->kodeprop,
        //     //             // "namaprop" => $request->namaprop,
        //     //             "kode_kabupaten" => $request->kodedati2,
        //     //             // "namadati2" => $request->namadati2,
        //     //             "kode_kecamatan" => $request->kodekec,
        //     //             // "namakec" => $request->namakec,
        //     //             "kode_desa" => $request->kodekel,
        //     //             // "namakel" => $request->namakel,
        //     //             // "rw" => $request->rw,
        //     //             // "rt" => $request->rt,
        //     //             // "status" => $request->status,
        //     //         ]
        //     //     );
        //     //     return  $response = [
        //     //         "response" => [
        //     //             "norm" => $request->norm,
        //     //         ],
        //     //         "metadata" => [
        //     //             "message" => "Ok",
        //     //             "code" => 200,
        //     //         ],
        //     //     ];
        //     // } catch (\Throwable $th) {
        //     //     $response = [
        //     //         "metadata" => [
        //     //             "message" => "Gagal Error Code " . $th->getMessage(),
        //     //             "code" => 201,
        //     //         ],
        //     //     ];
        //     //     return $response;
        //     // }
        //     return [
        //         'metadata' => [
        //             'code' => 201,
        //             'message' => 'Mohon maaf untuk pasien baru tidak bisa didaftarkan secara online. Silahkan daftar secara offline dengan datang ke Rumah Sakit',
        //         ],
        //     ];
        // }
        // // cek jika pasien lama
        // else {
        //     $pasien->update([
        //         "no_Bpjs" => $request->nomorkartu,
        //         // "nik_bpjs" => $request->nik,
        //         // "no_rm" => $request->norm,
        //         "nomorkk" => $request->nomorkk,
        //         "nama_px" => $request->nama,
        //         "jenis_kelamin" => $request->jeniskelamin,
        //         "tgl_lahir" => $request->tanggallahir,
        //         "no_tlp" => $request->nohp,
        //         "alamat" => $request->alamat,
        //         "kode_propinsi" => $request->kodeprop,
        //         "namaprop" => $request->namaprop,
        //         "kode_kabupaten" => $request->kodedati2,
        //         "namadati2" => $request->namadati2,
        //         "kode_kecamatan" => $request->kodekec,
        //         "namakec" => $request->namakec,
        //         "kode_desa" => $request->kodekel,
        //         "namakel" => $request->namakel,
        //         "rw" => $request->rw,
        //         "rt" => $request->rt,
        //         // "status" => $request->status,
        //     ]);
        //     return $response = [
        //         "response" => [
        //             "norm" => $pasien->no_rm,
        //         ],
        //         "metadata" => [
        //             "message" => "Ok",
        //             "code" => 200,
        //         ],
        //     ];
        // }
    }
    public function jadwal_operasi_rs(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "tanggalawal" => "required|date",
            "tanggalakhir" => "required|date",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $request['tanggalawal'] = Carbon::parse($request->tanggalawal)->startOfDay();
        $request['tanggalakhir'] = Carbon::parse($request->tanggalakhir)->endOfDay();
        // end auth token
        $jadwalops = JadwalOperasi::whereBetween('tanggal', [$request->tanggalawal, $request->tanggalakhir])->get();
        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            $dokter = ParamedisDB::where('nama_paramedis', $jadwalop->nama_dokter)->first();
            if (isset($dokter)) {
                $unit = UnitDB::where('kode_unit', $dokter->unit)->first();
            } else {
                $unit['KDPOLI'] = 'UGD';
            }
            $jadwals[] = [
                "kodebooking" => $jadwalop->no_book,
                "tanggaloperasi" => Carbon::parse($jadwalop->tanggal)->format('Y-m-d'),
                "jenistindakan" => $jadwalop->jenis,
                "kodepoli" =>  $unit->KDPOLI ?? 'BED',
                // "namapoli" => $jadwalop->ruangan_asal,
                "namapoli" => 'BEDAH',
                "terlaksana" => 0,
                "nopeserta" => $jadwalop->nomor_bpjs,
                "lastupdate" => now()->timestamp * 1000,
            ];
        }
        $response = [
            "list" => $jadwals
        ];
        return $this->sendResponse("OK", $response, 200);
    }
    public function jadwal_operasi_pasien(Request $request)
    {
        // checking request
        $validator = Validator::make(request()->all(), [
            "nopeserta" => "required|digits:13",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),  201);
        }
        $jadwalops = JadwalOperasi::where('nomor_bpjs', $request->nopeserta)->get();
        $jadwals = [];
        foreach ($jadwalops as  $jadwalop) {
            $dokter = ParamedisDB::where('nama_paramedis', $jadwalop->nama_dokter)->first();
            if (isset($dokter)) {
                $unit = UnitDB::where('kode_unit', $dokter->unit)->first();
            } else {
                $unit['KDPOLI'] = 'UGD';
            }
            $jadwals[] = [
                "kodebooking" => $jadwalop->no_book,
                "tanggaloperasi" => Carbon::parse($jadwalop->tanggal)->format('Y-m-d'),
                "jenistindakan" => $jadwalop->jenis,
                "kodepoli" =>  $unit->KDPOLI ?? 'BED',
                "namapoli" => "Penyakit Dalam",
                "terlaksana" => 0,
            ];
        }
        $response = [
            "list" => $jadwals
        ];
        return $this->sendResponse("OK", $response, 200);
    }
    public function ambil_antrian_farmasi(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (empty($antrian)) {
            return $this->sendError("Kode booking tidak ditemukan",  201);
        }
        $request['nomorantrean'] = $antrian->angkaantrean;
        $request['keterangan'] = "resep sistem antrian";
        $request['jenisresep'] = "Racikan/Non Racikan";
        $res = $this->tambah_antrean_farmasi($request);
        $responses = [
            "jenisresep" => $request->jenisresep,
            "nomorantrean" => $request->nomorantrean,
            "keterangan" => $request->keterangan,
        ];
        return $this->sendResponse($responses, 200);
    }
    public function status_antrian_farmasi(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            "kodebooking" => "required",
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), 400);
        }
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        if (empty($antrian)) {
            return $this->sendError("Kode booking tidak ditemukan",  201);
        }
        $totalantrean = Antrian::whereDate('tanggalperiksa', $antrian->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->where('taskid', '!=', 99)
            ->count();
        $antreanpanggil = Antrian::whereDate('tanggalperiksa', $antrian->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->where('taskid', 3)
            ->where('status_api', 0)
            ->first();
        $antreansudah = Antrian::whereDate('tanggalperiksa', $antrian->tanggalperiksa)
            ->where('method', '!=', 'Bridging')
            ->where('taskid', 5)->where('status_api', 1)
            ->count();
        $request['totalantrean'] = $totalantrean ?? 0;
        $request['sisaantrean'] = $totalantrean - $antreansudah ?? 0;
        $request['antreanpanggil'] = $antreanpanggil->angkaantrean ?? 0;
        $request['keterangan'] = $antrian->keterangan;
        $request['jenisresep'] = "Racikan/Non Racikan";
        $responses = [
            "jenisresep" => $request->jenisresep,
            "totalantrean" => $request->totalantrean,
            "sisaantrean" => $request->sisaantrean,
            "antreanpanggil" => $request->antreanpanggil,
            "keterangan" => $request->keterangan,
        ];
        return $this->sendResponse($responses, 200);
    }
    function print_karcis(Request $request,  $kunjungan)
    {
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now();
        $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
        $printer = new Printer($connector);
        $printer->setEmphasis(true);
        $printer->text("ANTRIAN RAWAT JALAN\n");
        $printer->text("RSUD WALED KAB. CIREBON\n");
        $printer->setEmphasis(false);
        $printer->text("================================================\n");
        $printer->text("No. RM : " . $request->norm . "\n");
        $printer->text("Nama : " . $request->nama . "\n");
        // $printer->text("NIK : " . $request->nik . "\n");
        // $printer->text("No. Kartu JKN : " . $request->nomorkartu . "\n");
        $printer->text("No. Telp. : " . $request->nohp . "\n");
        $printer->text("No. Rujukan : " . $request->nomorrujukan . "\n");
        $printer->text("No. Surat Kontrol : " . $request->nomorsuratkontrol . "\n");
        $printer->text("No. SEP : " . $request->nomorsep . "\n");
        $printer->text("================================================\n");
        $printer->text("Jenis Kunj. : " . $request->jeniskunjungan_print . "\n");
        $printer->text("Poliklinik : " . $request->namapoli . "\n");
        $printer->text("Dokter : " . $request->namadokter . "\n");
        $printer->text("Jam Praktek : " . $request->jampraktek . "\n");
        $printer->text("Tanggal : " . Carbon::parse($request->tanggalperiksa)->format('d M Y') . "\n");
        $printer->text("================================================\n");
        $printer->text("Keterangan : \n" . $request->keterangan . "\n");
        if ($request->jenispasien != "JKN") {
            $printer->text("================================================\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("Biaya Karcis Poli : " . money($request->tarifkarcis, 'IDR') . "\n");
            $printer->text("Biaya Administrasi : " . money($request->tarifadm, 'IDR') . "\n");
        }
        $printer->text("================================================\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Jenis Pasien :\n");
        $printer->setTextSize(2, 2);
        $printer->text($request->jenispasien . " " . $request->pasienbaru_print . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("Kode Booking : " . $request->kodebooking . "\n");
        $printer->text("Kode Kunjungan : " . $kunjungan->kode_kunjungan . "\n");
        // $printer->qrCode($request->kodebooking, Printer::QR_ECLEVEL_L, 10, Printer::QR_MODEL_2);
        $printer->text("================================================\n");
        $printer->text("Nomor Antrian Poliklinik :\n");
        $printer->setTextSize(2, 2);
        $printer->text($request->nomorantrean . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("Lokasi Poliklinik Lantai " . $request->lokasi . " \n");
        $printer->text("================================================\n");
        $printer->text("Angka Antrian :\n");
        $printer->setTextSize(2, 2);
        $printer->text($request->angkaantrean . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("Lokasi Pendaftaran Lantai " . $request->lantaipendaftaran . " \n");
        $printer->text("================================================\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cetakan 1 : " . $now . "\n");
        $printer->cut();
        $printer->close();
    }
    function print_sep(Request $request, $sep)
    {
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now();
        $for_sep = ['POLIKLINIK', 'FARMASI', 'ARSIP'];
        // $for_sep = ['PERCOBAAN'];
        foreach ($for_sep as  $value) {
            $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
            $printer = new Printer($connector);
            $printer->setEmphasis(true);
            $printer->text("SURAT ELEGTABILITAS PASIEN (SEP)\n");
            $printer->text("RSUD WALED KAB. CIREBON\n");
            $printer->setEmphasis(false);
            $printer->text("================================================\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("SEP untuk " . $value . "\n");
            $printer->text("Nomor SEP :\n");
            $printer->setTextSize(2, 2);
            $printer->setEmphasis(true);
            $printer->text($sep->noSep . "\n");
            $printer->setEmphasis(false);
            $printer->setTextSize(1, 1);
            $printer->text("Tgl SEP : " . $sep->tglSep . " \n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("================================================\n");
            $printer->text("Nama Pasien : " . $sep->peserta->nama . " \n");
            $printer->text("Nomor Kartu : " . $sep->peserta->noKartu . " \n");
            $printer->text("No. RM : " . $request->norm . "\n");
            $printer->text("No. Telepon : " . $request->nohp . "\n");
            $printer->text("Hak Kelas : " . $sep->peserta->hakKelas . " \n");
            $printer->text("Jenis Peserta : " . $sep->peserta->jnsPeserta . " \n\n");
            $printer->text("Jenis Pelayanan : " . $sep->jnsPelayanan . " \n");
            $printer->text("Poli / Spesialis : " . $sep->poli . "\n");
            $printer->text("COB : -\n");
            $printer->text("Diagnosa Awal : " . $sep->diagnosa . "\n");
            $printer->text("Faskes Perujuk : " . $request->faskesPerujuk . "\n");
            $printer->text("Catatan : " . $sep->catatan . "\n\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text("Cirebon, " . $now->format('d-m-Y') . " \n\n\n\n");
            $printer->text("RSUD Waled \n\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Cetakan : " . $now . "\n");
            $printer->cut();
            $printer->close();
        }
    }
    public function print_ulang(Request $request)
    {
        $antrian = Antrian::firstWhere('kodebooking', $request->kodebooking);
        $unit = Unit::firstWhere('KDPOLI', $antrian->kodepoli);
        $tarifkarcis = TarifLayananDetail::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_karcis);
        $tarifadm = TarifLayananDetail::firstWhere('KODE_TARIF_DETAIL', $unit->kode_tarif_adm);
        // print antrian
        if ($antrian->pasienbaru == 0) {
            $request['pasienbaru_print'] = 'LAMA';
        } else {
            $request['pasienbaru_print'] = 'BARU';
        }
        $request['keterangan'] = "Print Ulang Karcis Antrian, untuk pasien JKN dapat langsung menunggu panggilan dipoliklinik";
        switch ($antrian->jeniskunjungan) {
            case '1':
                $request['jeniskunjungan_print'] = 'RUJUKAN FKTP';
                break;
            case '2':
                $request['jeniskunjungan_print'] = 'RUJUKAN INTERNAL';
                break;
            case '3':
                if (isset($antrian->nomorreferensi)) {
                    $request['jeniskunjungan_print'] = 'KONTROL';
                } else {
                    $request['keterangan'] = "Print Ulang Karcis Antrian, untuk pasien NON-JKN silahkan lakukan pembayaran di Loken Pembayaran";
                    $request['jeniskunjungan_print'] = 'KUNJUNGAN UMUM';
                }
                break;
            case '4':
                $request['jeniskunjungan_print'] = 'RUJUKAN RS';
                break;
            default:
                break;
        }
        $request['tarifkarcis'] = $tarifkarcis->TOTAL_TARIF_NEW;
        $request['tarifadm'] = $tarifadm->TOTAL_TARIF_NEW;
        $request['norm'] = $antrian->norm;
        $request['nama'] = $antrian->nama;
        $request['nik'] = $antrian->nik;
        $request['nomorkartu'] = $antrian->nomorkartu;
        $request['nohp'] = $antrian->nohp;
        $request['nomorrujukan'] = $antrian->nomorrujukan;
        $request['nomorsuratkontrol'] = $antrian->nomorsuratkontrol;
        $request['namapoli'] = $antrian->namapoli;
        $request['namadokter'] = $antrian->namadokter;
        $request['jampraktek'] = $antrian->jampraktek;
        $request['tanggalperiksa'] = $antrian->tanggalperiksa;
        $request['jenispasien'] = $antrian->jenispasien;
        $request['nomorantrean'] = $antrian->nomorantrean;
        $request['lokasi'] = $antrian->lokasi;
        $request['angkaantrean'] = $antrian->angkaantrean;
        $request['lantaipendaftaran'] = $antrian->lantaipendaftaran;
        $request['nomorsep'] = $antrian->nomorsep;
        $request['keterangan'] = $antrian->keterangan;
        $kunjungan = Kunjungan::firstWhere('kode_kunjungan', $antrian->kode_kunjungan);
        // print
        $this->print_karcis($request, $kunjungan);
        if ($antrian->jenispasien == "JKN") {
            $api = new VclaimController();
            $request['noSep'] = $antrian->nomorsep;
            $response = $api->sep_nomor($request);
            if ($response->metadata->code == 200) {
                $sep =  $response->response;
                $this->print_sep($request, $sep);
            } else {
                return  $this->sendError($response->metadata->message, 400);
            }
        }
        return  $this->sendResponse("Print Ulang Berhasil", null, 201);
    }
    function print_karcis_offline(Request $request, $antrian)
    {
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now();
        $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
        $printer = new Printer($connector);
        $printer->setEmphasis(true);
        $printer->text("ANTRIAN RAWAT JALAN\n");
        $printer->text("RSUD WALED KAB. CIREBON\n");
        $printer->setEmphasis(false);
        $printer->text("================================================\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Angka Antrian Pendaftaran :\n");
        $printer->setTextSize(3, 3);
        $printer->text($antrian->angkaantrean . "\n");
        $printer->setTextSize(1, 1);
        $printer->text("Kode Booking : " . $antrian->kodebooking . "\n");
        $printer->text("Lokasi Pendaftaran Lantai " . $request->lantaipendaftaran . " \n");
        $printer->text("================================================\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Jenis Kunj. : " . $request->method . ' ' . $request->jenispasien . "\n");
        $printer->text("No. Antrian Poli : " . $antrian->nomorantrean . "\n");
        $printer->text("Poliklinik : " . $antrian->namapoli . "\n");
        $printer->text("Dokter : " . $antrian->namadokter . "\n");
        $printer->text("Jam, Tanggal : " . $request->jampraktek . ', ' . Carbon::parse($request->tanggalperiksa)->format('d M Y') . "\n");
        $printer->text("================================================\n");
        $printer->text("Keterangan : \n" . $antrian->keterangan . "\n");
        $printer->text("================================================\n");
        $printer->text("Cetakan 1 : " . $now . "\n");
        $printer->cut();
        $printer->close();
    }
    public function cekPrinter()
    {
        try {
            $connector = new WindowsPrintConnector(env('PRINTER_CHECKIN'));
            $printer = new Printer($connector);
            $printer->text("Connector Printer :\n");
            $printer->text(env('PRINTER_CHECKIN') . "\n");
            $printer->text("Test Printer Berhasil.\n");
            $printer->cut();
            $printer->close();
            Alert::success('Success', 'Mesin menyala dan siap digunakan.');
            return redirect()->route('antrianConsole');
        } catch (\Throwable $th) {
            // throw $th;
            Alert::error('Error', 'Mesin antrian tidak menyala. Silahkan hubungi admin.');
            return redirect()->route('antrianConsole');
        }
    }
}
