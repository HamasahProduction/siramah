<?php

use App\Http\Controllers\AntrianController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BukuTamuController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\DokterController;
use App\Http\Controllers\FarmasiController;
use App\Http\Controllers\FileRekamMedisController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JadwalDokterController;
use App\Http\Controllers\JadwalOperasiController;
use App\Http\Controllers\KPOController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\ParamedisController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PoliklinikController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\SuratKontrolController;
use App\Http\Controllers\SuratMasukController;
use App\Http\Controllers\ThermalPrintController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VclaimController;
use App\Http\Controllers\WhatsappController;
use App\Models\JadwalDokter;
use App\Models\Pasien;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// route default
Route::get('', [HomeController::class, 'landingpage'])->name('landingpage'); #ok
Auth::routes(); #ok
Route::get('profile', [UserController::class, 'profile'])->name('profile'); #ok
Route::get('verifikasi_akun', [VerificationController::class, 'verifikasi_akun'])->name('verifikasi_akun');
Route::post('verifikasi_kirim', [VerificationController::class, 'verifikasi_kirim'])->name('verifikasi_kirim');
Route::get('user_verifikasi/{user}', [UserController::class, 'user_verifikasi'])->name('user_verifikasi');
Route::get('delet_verifikasi', [UserController::class, 'delet_verifikasi'])->name('delet_verifikasi');
Route::get('login/google/redirect', [SocialiteController::class, 'redirect'])->middleware(['guest'])->name('login.google'); #redirect google login
Route::get('login/google/callback', [SocialiteController::class, 'callback'])->middleware(['guest'])->name('login.goole.callback'); #callback google login
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home'); #ok
// layanan umum
Route::get('bukutamu', [BukuTamuController::class, 'bukutamu'])->name('bukutamu');
Route::post('bukutamu', [BukuTamuController::class, 'store'])->name('bukutamu_store');

Route::middleware('auth')->group(function () {
// settingan umum
Route::get('get_city', [LaravotLocationController::class, 'get_city'])->name('get_city');
Route::get('get_district', [LaravotLocationController::class, 'get_district'])->name('get_district');
Route::get('get_village', [LaravotLocationController::class, 'get_village'])->name('get_village');
Route::get('cekBarQRCode', [BarcodeController::class, 'cekBarQRCode'])->name('cekBarQRCode');
Route::get('cekThermalPrinter', [ThermalPrintController::class, 'cekThermalPrinter'])->name('cekThermalPrinter');
Route::get('testThermalPrinter', [ThermalPrintController::class, 'testThermalPrinter'])->name('testThermalPrinter');
Route::get('whatsapp', [WhatsappController::class, 'whatsapp'])->name('whatsapp');
// route resource
Route::resource('user', UserController::class);
Route::resource('role', RoleController::class);
Route::resource('permission', PermissionController::class);
Route::resource('poliklinik', PoliklinikController::class);
Route::resource('unit', UnitController::class);
Route::resource('dokter', DokterController::class);
Route::resource('paramedis', ParamedisController::class);
Route::resource('jadwaldokter', JadwalDokterController::class);
Route::resource('suratmasuk', SuratMasukController::class);
Route::resource('suratlampiran', SuratLampiranController::class);
Route::resource('disposisi', DisposisiController::class);
Route::resource('pasien', PasienController::class);
Route::resource('kunjungan', KunjunganController::class);
Route::resource('efilerm', FileRekamMedisController::class);
Route::resource('antrian', AntrianController::class);
Route::resource('suratkontrol', SuratKontrolController::class);
Route::resource('obat', ObatController::class);
Route::resource('kpo', KPOController::class);
// mesin antrian
Route::get('daftarOnline', [SIMRSAntrianController::class, 'daftarOnline'])->name('daftarOnline');
Route::get('antrianConsole', [AntrianController::class, 'antrianConsole'])->name('antrianConsole');
Route::get('jadwaldokterPoli', [JadwalDokterController::class, 'jadwaldokterPoli'])->name('jadwaldokterPoli');
Route::get('daftarBpjsOffline', [AntrianController::class, 'daftarBpjsOffline'])->name('daftarBpjsOffline');
Route::get('daftarUmumOffline', [AntrianController::class, 'daftarUmumOffline'])->name('daftarUmumOffline');
Route::get('cekPrinter', [AntrianController::class, 'cekPrinter'])->name('cekPrinter');
Route::get('checkinUpdate', [AntrianController::class, 'checkinUpdate'])->name('checkinUpdate');
// pendaftaran
Route::get('antrianPendaftaran', [AntrianController::class, 'antrianPendaftaran'])->name('antrianPendaftaran');
Route::get('selanjutnyaPendaftaran/{loket}/{lantai}/{jenispasien}/{tanggal}', [AntrianController::class, 'selanjutnyaPendaftaran'])->name('selanjutnyaPendaftaran');
Route::get('panggilPendaftaran/{kodebooking}/{loket}/{lantai}', [AntrianController::class, 'panggilPendaftaran'])->name('panggilPendaftaran');
Route::get('selesaiPendaftaran/{kodebooking}', [AntrianController::class, 'selesaiPendaftaran'])->name('selesaiPendaftaran');
Route::get('antrianCapaian', [AntrianController::class, 'antrianCapaian'])->name('antrianCapaian');
// poliklinik
Route::get('antrianPoliklinik', [AntrianController::class, 'antrianPoliklinik'])->name('antrianPoliklinik');
Route::get('batalAntrian/{antrian}', [AntrianController::class, 'batalAntrian'])->name('batalAntrian');
Route::get('panggilPoliklinik/{antrian}', [AntrianController::class, 'panggilPoliklinik'])->name('panggilPoliklinik');
Route::get('panggilUlangPoliklinik/{antrian}', [AntrianController::class, 'panggilUlangPoliklinik'])->name('panggilUlangPoliklinik');
Route::get('lanjutFarmasi/{antrian}', [AntrianController::class, 'lanjutFarmasi'])->name('lanjutFarmasi');
Route::get('lanjutFarmasiRacikan/{antrian}', [AntrianController::class, 'lanjutFarmasiRacikan'])->name('lanjutFarmasiRacikan');
Route::get('kunjunganPoliklinik', [AntrianController::class, 'kunjunganPoliklinik'])->name('kunjunganPoliklinik');
Route::get('jadwalDokterPoliklinik', [JadwalDokterController::class, 'jadwalDokterPoliklinik'])->name('jadwalDokterPoliklinik');
Route::get('pasienPoliklinik', [PasienController::class, 'index'])->name('pasienPoliklinik');
Route::get('laporanAntrianPoliklinik', [AntrianController::class, 'laporanAntrianPoliklinik'])->name('laporanAntrianPoliklinik');
Route::get('laporanKunjunganPoliklinik', [KunjunganController::class, 'laporanKunjunganPoliklinik'])->name('laporanKunjunganPoliklinik');
Route::get('dashboardTanggalAntrianPoliklinik', [AntrianController::class, 'dashboardTanggalAntrian'])->name('dashboardTanggalAntrianPoliklinik');
Route::get('dashboardBulanAntrianPoliklinik', [AntrianController::class, 'dashboardBulanAntrian'])->name('dashboardBulanAntrianPoliklinik');
Route::get('suratKontrolPrint/{suratkontrol}', [SuratKontrolController::class, 'suratKontrolPrint'])->name('suratKontrolPrint');

// farmasi
Route::get('antrianFarmasi', [AntrianController::class, 'antrianFarmasi'])->name('antrianFarmasi');
Route::get('racikFarmasi/{kodebooking}', [AntrianController::class, 'racikFarmasi'])->name('racikFarmasi');
Route::get('selesaiFarmasi/{kodebooking}', [AntrianController::class, 'selesaiFarmasi'])->name('selesaiFarmasi');
Route::get('tracerOrderObat', [FarmasiController::class, 'tracerOrderObat'])->name('tracerOrderObat');
Route::get('getOrderObat', [FarmasiController::class, 'getOrderObat'])->name('getOrderObat');
Route::get('cetakUlangOrderObat', [FarmasiController::class, 'cetakUlangOrderObat'])->name('cetakUlangOrderObat');
Route::get('kpo/tanggal/{tanggal}', [KPOController::class, 'kunjungan_tanggal'])->name('kpo.kunjungan_tanggal');


// bpjs
Route::get('vclaim', [VclaimController::class, 'vclaim.index'])->name('vclaim.index');
Route::prefix('bpjs')->name('bpjs.')->group(function () {
    // antrian
    Route::prefix('antrian')->name('antrian.')->group(function () {
        Route::get('status', [AntrianController::class, 'statusTokenAntrian'])->name('status');
        Route::get('poli', [PoliklinikController::class, 'poliklikAntrianBpjs'])->name('poli');
        Route::get('dokter', [DokterController::class, 'dokterAntrianBpjs'])->name('dokter');
        Route::get('jadwal_dokter', [JadwalDokterController::class, 'jadwalDokterBpjs'])->name('jadwal_dokter');
        Route::get('fingerprint_peserta', [PasienController::class, 'fingerprintPeserta'])->name('fingerprint_peserta');
        Route::get('antrian', [AntrianAntrianController::class, 'antrian'])->name('antrian');
        Route::get('list_task', [AntrianController::class, 'listTaskID'])->name('list_task');
        Route::get('dashboard_tanggal', [AntrianController::class, 'dashboardTanggalAntrian'])->name('dashboard_tanggal');
        Route::get('dashboard_bulan', [AntrianController::class, 'dashboardBulanAntrian'])->name('dashboard_bulan');
        Route::get('jadwal_operasi', [JadwalOperasiController::class, 'index'])->name('jadwal_operasi');
        Route::get('antrian_per_tanggal', [AntrianController::class, 'antrianPerTanggal'])->name('antrian_per_tanggal');
        Route::get('antrian_per_kodebooking', [AntrianController::class, 'antrianPerKodebooking'])->name('antrian_per_kodebooking');
        Route::get('antrian_belum_dilayani', [AntrianController::class, 'antrianBelumDilayani'])->name('antrian_belum_dilayani');
        Route::get('antrian_per_dokter', [AntrianController::class, 'antrianPerDokter'])->name('antrian_per_dokter');
    });
    // vclaim
    Route::prefix('vclaim')->name('vclaim.')->group(function () {
        Route::get('monitoring_data_kunjungan', [VclaimController::class, 'monitoring_data_kunjungan_index'])->name('monitoring_data_kunjungan');
        Route::get('monitoring_data_klaim', [VclaimController::class, 'monitoring_data_klaim_index'])->name('monitoring_data_klaim');
        Route::get('monitoring_pelayanan_peserta', [VclaimController::class, 'monitoringPelayananPesertaIndex'])->name('monitoring_pelayanan_peserta');
        Route::get('monitoring_klaim_jasaraharja', [VclaimController::class, 'monitoring_klaim_jasaraharja_index'])->name('monitoring_klaim_jasaraharja');
        Route::get('referensi', [VclaimController::class, 'referensi_index'])->name('referensi');
        Route::get('ref_diagnosa_api', [VclaimController::class, 'ref_diagnosa_api'])->name('ref_diagnosa_api');
        Route::get('ref_poliklinik_api', [VclaimController::class, 'ref_poliklinik_api'])->name('ref_poliklinik_api');
        Route::get('ref_faskes_api', [VclaimController::class, 'ref_faskes_api'])->name('ref_faskes_api');
        Route::get('ref_dpjp_api', [VclaimController::class, 'ref_dpjp_api'])->name('ref_dpjp_api');
        Route::get('ref_provinsi_api', [VclaimController::class, 'ref_provinsi_api'])->name('ref_provinsi_api');
        Route::get('ref_kabupaten_api', [VclaimController::class, 'ref_kabupaten_api'])->name('ref_kabupaten_api');
        Route::get('ref_kecamatan_api', [VclaimController::class, 'ref_kecamatan_api'])->name('ref_kecamatan_api');
        Route::get('surat_kontrol', [SuratKontrolController::class, 'surat_kontrol_index'])->name('surat_kontrol');
        Route::post('surat_kontrol_store', [SuratKontrolController::class, 'store'])->name('surat_kontrol_store');
        Route::put('surat_kontrol_update', [SuratKontrolController::class, 'update'])->name('surat_kontrol_update');
        Route::delete('surat_kontrol_delete', [SuratKontrolController::class, 'destroy'])->name('surat_kontrol_delete');
    });
});

});
