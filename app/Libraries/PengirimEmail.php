<?php

namespace App\Libraries;

use Config\Services;

/**
 * Pengirim email EULT (porting CI3 application/libraries/Sendemail.php).
 * Konfigurasi SMTP diambil dari .env (EULT_MAIL_*) via Config\Email,
 * bukan password hardcoded seperti di CI3.
 */
class PengirimEmail
{
    /**
     * Konfigurasi email efektif: Config\Email ditimpa .env bila ada.
     *
     * @return array<string, mixed>
     */
    private function konfigurasi(): array
    {
        $config  = config('Email');
        $baca    = static fn (string $kunci, string $bawaan = ''): string => ($v = env($kunci)) !== null && $v !== '' ? (string) $v : $bawaan;

        return [
            'dari'  => $baca('EULT_MAIL_FROM', $config->fromEmail),
            'nama'  => $baca('EULT_MAIL_NAME', $config->fromName),
        ];
    }

    /**
     * Subjek dibersihkan dari CR/LF sebagai defense-in-depth terhadap
     * email header injection — saat ini seluruh pemanggil memakai subjek
     * buatan server, tetapi nilai apa pun yang lewat sini tidak boleh
     * sampai ke header dalam bentuk mentah.
     */
    private function subjekBersih(string $subjek): string
    {
        return trim((string) preg_replace('/[\r\n]+/', ' ', $subjek));
    }

    /**
     * Mengirim email HTML generik.
     */
    public function kirimText(string $email, string $subjek, string $pesan): bool
    {
        $emailService = Services::email();
        $konfig       = $this->konfigurasi();
        $subjek       = $this->subjekBersih($subjek);

        $emailService->setFrom($konfig['dari'], $konfig['nama']);
        $emailService->setTo($email);
        $emailService->setSubject($subjek);
        $emailService->setMessage($pesan);

        return $this->kirim($emailService, $email, $subjek);
    }

    /**
     * Mengirim email + lampiran dari folder ticketing.
     */
    public function kirimLampiran(string $email, string $subjek, string $pesan, string|false $lampiran): bool
    {
        $emailService = Services::email();
        $konfig       = $this->konfigurasi();
        $subjek       = $this->subjekBersih($subjek);

        $emailService->setFrom($konfig['dari'], $konfig['nama']);
        $emailService->setTo($email);
        $emailService->setSubject($subjek);

        if ($lampiran !== false && $lampiran !== '') {
            $berkas = WRITEPATH . 'uploads/ticketing/' . $lampiran;
            if (is_file($berkas)) {
                $emailService->attach($berkas);
            }
        }

        $emailService->setMessage($pesan);

        return $this->kirim($emailService, $email, $subjek);
    }

    /**
     * Pengiriman tunggal: kegagalan send() wajib tercatat di log agar
     * gangguan SMTP tidak hilang diam-diam (seluruh pemanggil selama ini
     * mengabaikan nilai kembaliannya).
     */
    private function kirim(\CodeIgniter\Email\Email $emailService, string $email, string $subjek): bool
    {
        $terkirim = $emailService->send();

        if (! $terkirim) {
            log_message('error', 'Gagal mengirim email ke {email} dengan subjek {subjek}: {debug}', [
                'email'   => $email,
                'subjek'  => $subjek,
                'debug'   => $emailService->printDebugger(['headers', 'subject']),
            ]);
        }

        return $terkirim;
    }

    /**
     * Email tiket baru (memakai view layouts/template_surat_create).
     */
    public function buat(string $email, string $subjek, mixed $param): bool
    {
        return $this->kirimText($email, $subjek, view('layouts/template_surat_create', ['datas' => $param]));
    }

    /**
     * Email tiket selesai + lampiran (view layouts/template_surat_validated).
     */
    public function selesai(string $email, string $subjek, mixed $param, string|false $lampiran): bool
    {
        return $this->kirimLampiran($email, $subjek, view('layouts/template_surat_validated', ['datas' => $param]), $lampiran);
    }
}
