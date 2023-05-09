<?php

namespace App\Http\Controllers;

use App\Models\Poliklinik;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PoliklinikController extends Controller
{
    public function poliklikAntrianBpjs()
    {
        $controller = new AntrianController();
        $response = $controller->ref_poli();
        if ($response->status() == 200) {
            $polikliniks = $response->getData()->response;
            Alert::success($response->statusText(), 'Poliklinik Antrian BPJS');
        } else {
            $polikliniks = null;
            Alert::error($response->getData()->metadata->message . ' ' . $response->status());
        }
        $response = $controller->ref_poli_fingerprint();
        if ($response->status() == 200) {
            $fingerprint = $response->getData()->response;
            Alert::success($response->statusText(), 'Poliklinik Antrian BPJS');
        } else {
            $fingerprint = null;
            Alert::error($response->getData()->metadata->message . ' ' . $response->status(),  'Poliklinik Fingerprint Antrian BPJS');
        }
        $poli_jkn_simrs = Poliklinik::get();
        return view('bpjs.antrian.poliklinik', compact([
            'polikliniks',
            'fingerprint',
            'poli_jkn_simrs',
        ]));
    }
}
