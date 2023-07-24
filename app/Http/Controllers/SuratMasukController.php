<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class SuratMasukController extends Controller
{
    public function index(Request $request)
    {
        $surats = SuratMasuk::with(['lampiran'])
            ->orderBy('id_surat_masuk', 'desc')
            ->where(function ($query) use ($request) {
                if ($request->search) {
                    $query->where('asal_surat', "like", "%" . $request->search . "%")
                        ->orWhere('perihal', "like", "%" . $request->search . "%");
                }
            })->paginate(25);
        // $surat_total = SuratMasuk::count();
        return view('simrs.bagum.suratmasuk_index', compact([
            'request',
            'surats',
        ]));
    }
    public function store(Request $request)
    {
        $request->validate([
            'no_surat' => 'required',
            'tgl_surat' => 'required|date',
            'asal_surat' => 'required',
            'perihal' => 'required',
            'sifat' => 'required',
            'tgl_disposisi' => 'required|date',
        ]);
        // setting no urut disposisi per bulan
        $tgl_disposisi = Carbon::parse($request->tgl_disposisi);
        $no_urut_bulan = SuratMasuk::whereYear('tgl_disposisi', $tgl_disposisi->year)
            ->whereMonth('tgl_disposisi', $tgl_disposisi->month)
            ->count();
        $request['no_urut'] = $no_urut_bulan + 1;
        // insert surat masuk
        SuratMasuk::create([
            'no_urut' => $request->no_urut,
            'kode' => $request->kode,
            'sifat' => $request->sifat,
            'no_surat' => $request->no_surat,
            'tgl_surat' => $request->tgl_surat,
            'asal_surat' => $request->asal_surat,
            'perihal' => $request->perihal,
            'tgl_disposisi' => $request->tgl_disposisi,
            'user' => Auth::user()->name,
        ]);
        $wa = new WhatsappController();
        $request['number'] = "628122350498@c.us";
        $request['message'] = "Telah diinput surat masuk oleh *" . Auth::user()->name .  "*\n\n*No Surat :* " . $request->no_surat . "\n*Asal Surat :* " . $request->asal_surat . "\n*Perihal :* " . $request->perihal . "\n\nSilahkan untuk mengeceknya dan men-disposisikan dapat diakses dengan link berikut. \nhttp://sim.rsudwaled.id/siramah/disposisi";
        $wa->send_message($request);
        Alert::success('Success', 'Surat Masuk Berhasil Diinputkan');
        return redirect()->back();
    }
    public function show($id)
    {
        $surat = SuratMasuk::find($id);
        return response()->json($surat);
    }
    public function update(Request $request, $id)
    {
        if (isset($request->ttd_direktur)) {
            $request['ttd_direktur'] = now();
        }
        $surat = SuratMasuk::firstWhere('id_surat_masuk', $id);
        $nomor = str_pad($surat->no_urut, 3, '0', STR_PAD_LEFT) . '/' . $surat->kode . '/' . Carbon::parse($surat->tgl_disposisi)->translatedFormat('m/Y');
        if ($request->disposisi && $request->pengolah) {
            $wa = new WhatsappController();
            $request['number'] = "089529909036@c.us";
            $request['message'] = "Telah diupdate Disposisi oleh *" . Auth::user()->name .  "*\n\n*No Surat :* " . $surat->no_surat . "\n*Asal Surat :* " . $surat->asal_surat . "\n*Perihal :* " . $surat->perihal . "\n\n*No Disposisi :* " . $nomor . "\n*Ditujukan Untuk :* " . $request->pengolah . "\n*Disposisi :* " . $request->disposisi . "\n\nSilahkan untuk mengeceknya dengan link berikut. \nhttp://sim.rsudwaled.id/siramah/disposisi";
            $wa->send_message($request);
        }
        $surat->update($request->all());
        Alert::success('Success', 'Surat Berhasil Diupdate');
        return redirect()->back();
    }
    public function destroy($id, Request $request)
    {
        $surat = SuratMasuk::where('id_surat_masuk', $id)->first();
        $request['number'] = "628122350498@c.us";
        $request['message'] = "Telah dihapus surat masuk oleh *" . Auth::user()->name .  "*\n\n*No Surat :* " . $surat->no_surat . "\n*Asal Surat :* " . $surat->asal_surat . "\n*Perihal :* " . $surat->perihal . "\n\nSilahkan untuk mengeceknya dengan link berikut. \nhttp://sim.rsudwaled.id/siramah/disposisi";
        $wa = new WhatsappController();
        $wa->send_message($request);
        $surat->delete();
        Alert::success('Success', 'Surat Berhasil Dihapus');
        return redirect()->back();
    }
    public function get_surat(Request $request)
    {
    }
}
