<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi bug pengguna: "tidak bisa hapus dan reset password".
 *
 * Akar masalah ganda:
 * 1. pengguna.js mengikat klik secara langsung ($(".ts_reset_row").click),
 *    padahal tabel dipaginasi DataTables — baris yang muncul belakangan
 *    (cari/halaman/urut) tidak punya handler sehingga klik menjadi navigasi
 *    GET ke URL khusus-POST (404).
 * 2. Pengguna::delete() hanya menghapus s_user sehingga selalu gagal
 *    foreign key s_user_group_user (ON DELETE RESTRICT).
 * Kedua kegagalan tampil diam karena AJAX tanpa error handler.
 *
 * @internal
 */
final class PenggunaAksiTest extends CIUnitTestCase
{
    public function testPenggunaJsMemakaiDelegasiKlik(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/user/pengguna.js');

        $this->assertStringContainsString(".on('click.penggunaReset', '.ts_reset_row'", $js);
        $this->assertStringContainsString(".on('click.penggunaDelete', '.ts_remove_row'", $js);
        $this->assertStringNotContainsString('$(".ts_reset_row").click(', $js);
        $this->assertStringNotContainsString('$(".ts_remove_row").click(', $js);
    }

    public function testPenggunaJsMenanganiGalatAjax(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/user/pengguna.js');

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($js, 'error:'),
            'Aksi hapus dan reset masing-masing wajib punya error handler agar kegagalan terlihat.'
        );
    }

    public function testHapusPenggunaMenghapusBarisAnakTerlebihDulu(): void
    {
        $sumber = (string) file_get_contents(APPPATH . 'Controllers/Pengguna.php');

        $this->assertStringContainsString(
            "hapus('s_user_group_user'",
            $sumber,
            'Baris anak s_user_group_user wajib dihapus dulu (FK ON DELETE RESTRICT).'
        );
    }

    public function testHapusDanResetMenolakKunciTidakValid(): void
    {
        $sumber = (string) file_get_contents(APPPATH . 'Controllers/Pengguna.php');

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($sumber, 'Kunci pengguna tidak valid.'),
            'delete() dan resetpassword() wajib menolak kunci gagal-decode secara eksplisit.'
        );
    }

    public function testHapusGagalTidakDiklaimDeleted(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/user/pengguna.js');

        $this->assertStringContainsString(
            "if (res.status === 'success')",
            $js,
            'Judul "Deleted!" dan reload hanya boleh jalan bila server mengembalikan success.'
        );
    }
}
