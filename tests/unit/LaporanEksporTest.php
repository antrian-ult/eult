<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi bug unduh laporan: tombol "Unduh Excel" di Laporan per Layanan
 * tidak melakukan apa pun.
 *
 * Akar masalah: guard bindExport memakai `table.find('td[colspan]')` yang
 * memindai seluruh tabel termasuk <tfoot>. Footer total varian layanan
 * memakai `<td colspan="3">`, sehingga guard selalu mengira tabel kosong
 * dan batal diam-diam tepat saat ada data.
 *
 * @internal
 */
final class LaporanEksporTest extends CIUnitTestCase
{
    public function testGuardEksporLaporanHanyaMemeriksaTbody(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/laporan/laporan.js');

        $this->assertStringContainsString("find('tbody td[colspan]')", $js);
        $this->assertStringNotContainsString("find('td[colspan]')", $js);
    }

    public function testGuardEksporDaftarTiketJugaHanyaMemeriksaTbody(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/ticketing/ticketing.js');

        $this->assertStringContainsString("find('tbody td[colspan]')", $js);
        $this->assertStringNotContainsString("find('td[colspan]')", $js);
    }

    public function testResponsLayananTetapMenandaiTabelEkspor(): void
    {
        $html = view('pages/laporanlayanan/response', [
            'datas'  => [['jenislayananNama' => 'J', 'layananNama' => 'L', 'unitNama' => 'U', 'Jumlah' => 1, 'TERIMA' => 1, 'TOLAK' => 0, 'PROSES' => 0, 'SELESAI' => 1]],
            'awal'   => '2026-09-01',
            'akhir'  => '2026-09-16',
        ]);

        $this->assertStringContainsString('id="export"', $html);
        $this->assertStringContainsString('id="table_export"', $html);
    }
}
