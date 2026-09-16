<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi remediasi review komprehensif 2026-09-17.
 *
 * Tiap test menjaga satu perbaikan agar tidak kembali terhapus saat
 * refactoring berikutnya (pola assertion statis yang sama dengan
 * LaporanEksporTest/RatingNomorTiketTest).
 *
 * @internal
 */
final class RemediasiReview2026Test extends CIUnitTestCase
{
    public function testLoginMeregenerasiSessionId(): void
    {
        $php   = (string) file_get_contents(APPPATH . 'Controllers/Otentifikasi.php');
        $regen = strpos($php, 'session()->regenerate()');
        $set   = strpos($php, "session()->set('logged_in'");

        $this->assertNotFalse($regen);
        $this->assertNotFalse($set);
        // Regenerasi harus terjadi sebelum data sesi dipasang (session
        // fixation: ID pra-login tidak boleh terbawa pasca-login).
        $this->assertLessThan($set, $regen);
    }

    public function testSubjekEmailDibersihkanDariCrlf(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Libraries/PengirimEmail.php');

        $this->assertStringContainsString("preg_replace('/[\\r\\n]+/', ' ', \$subjek)", $php);
    }

    public function testUploadMemeriksaMagicByte(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Helpers/eult_upload_helper.php');

        $this->assertStringContainsString("'%PDF-'", $php);
        $this->assertStringContainsString('eult_upload_konten_valid', $php);
    }

    public function testKondisiGrupTidakSelaluBenar(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Controllers/Ticketing.php');

        // `!== 'ADMIN' || !== 'OPERATOR'` selalu true (logika yang salah).
        $this->assertStringNotContainsString("!== 'ADMIN' || ", $php);
        // Bentuk yang benar dipakai di create()/update().
        $this->assertStringContainsString("\$grup !== 'ADMIN' && strpos(\$grup, 'OPERATOR') === false", $php);
    }

    public function testFlagStrposDisimpanSebagaiBoolean(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Controllers/Ticketing.php');

        // strpos() mengembalikan int|false — kecocokan di posisi 0 adalah 0
        // (falsy), sehingga flag harus disimpan sebagai perbandingan eksplisit.
        $this->assertStringContainsString("strpos(\$this->pengguna['susrSgroupNama'], 'PRODUKSI') !== false", $php);
        $this->assertStringContainsString("strpos(\$this->pengguna['susrSgroupNama'], 'VERIFIKATOR') !== false", $php);
    }

    public function testNomorTiketDialokasikanDenganKunci(): void
    {
        $model = (string) file_get_contents(APPPATH . 'Models/ModelTicketing.php');

        $this->assertStringContainsString('GET_LOCK', $model);
        $this->assertStringContainsString('RELEASE_LOCK', $model);
        $this->assertStringContainsString('nomorTiketBerikutnya', $model);
    }

    public function testValidasifileJsMengescapeNamaFile(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/validasifile.js');

        $this->assertStringContainsString('escHtml(', $js);
        // Interpolasi mentah nilai server ke atribut HTML tidak boleh kembali.
        $this->assertStringNotContainsString('value="${data}"', $js);
        $this->assertStringNotContainsString('data-file="${data}"', $js);
    }

    public function testFormPenggunaMengescapeValue(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Views/pages/pengguna/form.php');

        $this->assertStringNotContainsString("value=\"<?= \$datas", $php);
    }

    public function testPreviewValidasifileMenolakTipeTakDikenal(): void
    {
        $php = (string) file_get_contents(APPPATH . 'Controllers/Validasifile.php');

        // Ekstensi tak dikenal harus 404, bukan disajikan sebagai octet-stream.
        $this->assertStringNotContainsString("'application/octet-stream'", $php);
        $this->assertStringContainsString('X-Content-Type-Options', $php);
    }
}
