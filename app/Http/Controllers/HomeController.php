<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if (Auth()->user()->email_verified_at == null) {
            Alert::error('Success', 'Akun SIMRS Waled anda belum diverifikasi, silahkan masukan nomor telepon anda untuk meminta verifikasi.');
            $user = Auth::user();
            Auth::logout();
            return view('vendor.adminlte.auth.verify', compact(['request', 'user']));
            // return redirect()->route('login');

        } else {
            return view('home');
        }
    }
}
