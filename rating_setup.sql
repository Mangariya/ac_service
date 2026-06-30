-- ============================================================
-- TABEL RATINGS - Sistem Rating Teknisi AC Service
-- Jalankan query ini di database Supabase / pgAdmin
-- ============================================================

CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    booking_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    teknisi_id INTEGER NOT NULL,
    layanan VARCHAR(100) NOT NULL,
    bintang INTEGER NOT NULL CHECK (bintang >= 1 AND bintang <= 5),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(booking_id)  -- Satu booking hanya bisa diberi satu rating
);

-- Index untuk pencarian cepat berdasarkan teknisi
CREATE INDEX IF NOT EXISTS idx_ratings_teknisi ON ratings(teknisi_id);
CREATE INDEX IF NOT EXISTS idx_ratings_booking ON ratings(booking_id);
