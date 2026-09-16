-- ============================================================
-- SCRIPT: Buat akun admin test sementara untuk QA
-- DB Target: db_newtiket
-- Dibuat: 2026-09-13
-- ============================================================
-- PERINGATAN:
-- 1. JANGAN jalankan script ini di database produksi.
-- 2. Hapus akun ini setelah sesi QA selesai (bagian [2] di bawah).
-- 3. Password akun test TIDAK disimpan plaintext di repo — ganti
--    hash di bawah bila perlu (buat via:
--    php -r "echo password_hash('<password>', PASSWORD_DEFAULT);").
-- ============================================================

-- [1] BUAT AKUN TEST
INSERT INTO `s_user`
  (`susrNama`, `susrPassword`, `susrSgroupNama`, `susrProfil`, `susrPertanyaan`, `susrJawaban`, `susrAvatar`, `susrRefIndex`, `susrLastLogin`, `susrCategoryId`)
VALUES
  ('test.impeccable', '$2y$12$hqQX3l5Fs46pYXt9PRFqa.xgktnaG1etZ7iILtraXg/bQ5ngxjo1e', 'ADMIN', 'TEST', 'qa', 'qa', '', '', NOW(), NULL)
ON DUPLICATE KEY UPDATE
  `susrPassword`    = VALUES(`susrPassword`),
  `susrSgroupNama`  = 'ADMIN';

-- Verifikasi
SELECT `susrNama`, `susrSgroupNama`, `susrLastLogin`
FROM `s_user`
WHERE `susrNama` = 'test.impeccable';

-- ============================================================
-- [2] HAPUS AKUN TEST (jalankan setelah QA selesai)
-- ============================================================
-- DELETE FROM `s_user` WHERE `susrNama` = 'test.impeccable';
