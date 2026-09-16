<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi cache-buster aset: date('YmdHis') berubah tiap detik sehingga
 * URL aset tidak pernah sama antar request dan browser tidak pernah
 * memakai HTTP cache (aset 4 MB dimuat ulang di setiap halaman).
 *
 * eult_versi_aset() menggantikannya dengan filemtime — versi hanya
 * berubah saat filenya berubah.
 *
 * @internal
 */
final class AsetVersiTest extends CIUnitTestCase
{
    public function testAsetAdaMengembalikanStempelWaktuNumerik(): void
    {
        $versi = eult_versi_aset('assets/js/pages/eult-csrf.js');

        $this->assertMatchesRegularExpression('/^\d+$/', $versi);
        $this->assertNotSame('1', $versi);
    }

    public function testAsetTidakAdaJatuhKeSatu(): void
    {
        $this->assertSame('1', eult_versi_aset('assets/tidak-ada-9000.js'));
    }

    public function testVersiStabilAntarPanggilan(): void
    {
        $jalur = 'assets/js/pages/eult-csrf.js';

        $this->assertSame(eult_versi_aset($jalur), eult_versi_aset($jalur));
    }
}
