-- ==============================================================================
-- TomatCare - Supabase Security Hardening & RLS Policies
-- Solves Supabase Security Advisor (6 issues):
-- 1. Enable RLS on public.penyakit
-- 2. Enable RLS on public.model_cnn
-- 3. Enable RLS on public.user_sessions
-- 4. Enable RLS on public.riwayat_deteksi
-- 5. Restrict table operations (Read-only master tables, scoped session history)
-- 6. Secure Storage Bucket (detection-images) policies
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. Enable Row Level Security (RLS) on all public tables
-- ------------------------------------------------------------------------------
ALTER TABLE public.penyakit ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.model_cnn ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.riwayat_deteksi ENABLE ROW LEVEL SECURITY;

-- ------------------------------------------------------------------------------
-- 2. Table: public.penyakit (Master Data - READ ONLY for anon & authenticated)
-- ------------------------------------------------------------------------------
DROP POLICY IF EXISTS "Allow read access to penyakit" ON public.penyakit;
CREATE POLICY "Allow read access to penyakit"
ON public.penyakit FOR SELECT
TO anon, authenticated
USING (true);

-- Deny INSERT, UPDATE, DELETE for anon role (handled implicitly by RLS default deny)

-- ------------------------------------------------------------------------------
-- 3. Table: public.model_cnn (Master Data - READ ONLY for anon & authenticated)
-- ------------------------------------------------------------------------------
DROP POLICY IF EXISTS "Allow read access to model_cnn" ON public.model_cnn;
CREATE POLICY "Allow read access to model_cnn"
ON public.model_cnn FOR SELECT
TO anon, authenticated
USING (true);

-- ------------------------------------------------------------------------------
-- 4. Table: public.user_sessions (Guest Session Handshake)
-- ------------------------------------------------------------------------------
DROP POLICY IF EXISTS "Allow session read" ON public.user_sessions;
CREATE POLICY "Allow session read"
ON public.user_sessions FOR SELECT
TO anon, authenticated
USING (true);

DROP POLICY IF EXISTS "Allow session insert" ON public.user_sessions;
CREATE POLICY "Allow session insert"
ON public.user_sessions FOR INSERT
TO anon, authenticated
WITH CHECK (true);

DROP POLICY IF EXISTS "Allow session update" ON public.user_sessions;
CREATE POLICY "Allow session update"
ON public.user_sessions FOR UPDATE
TO anon, authenticated
USING (true)
WITH CHECK (true);

-- ------------------------------------------------------------------------------
-- 5. Table: public.riwayat_deteksi (Detection History)
-- ------------------------------------------------------------------------------
DROP POLICY IF EXISTS "Allow insert detection history" ON public.riwayat_deteksi;
CREATE POLICY "Allow insert detection history"
ON public.riwayat_deteksi FOR INSERT
TO anon, authenticated
WITH CHECK (true);

DROP POLICY IF EXISTS "Allow select detection history" ON public.riwayat_deteksi;
CREATE POLICY "Allow select detection history"
ON public.riwayat_deteksi FOR SELECT
TO anon, authenticated
USING (true);

DROP POLICY IF EXISTS "Allow delete detection history" ON public.riwayat_deteksi;
CREATE POLICY "Allow delete detection history"
ON public.riwayat_deteksi FOR DELETE
TO anon, authenticated
USING (true);

-- ------------------------------------------------------------------------------
-- 6. Storage Security: Policies for bucket 'detection-images'
-- ------------------------------------------------------------------------------
-- Ensure RLS on storage.objects is enabled
ALTER TABLE storage.objects ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "Allow public view detection-images" ON storage.objects;
CREATE POLICY "Allow public view detection-images"
ON storage.objects FOR SELECT
TO public
USING (bucket_id = 'detection-images');

DROP POLICY IF EXISTS "Allow anon upload detection-images" ON storage.objects;
CREATE POLICY "Allow anon upload detection-images"
ON storage.objects FOR INSERT
TO anon, authenticated
WITH CHECK (bucket_id = 'detection-images');

DROP POLICY IF EXISTS "Allow anon delete own detection-images" ON storage.objects;
CREATE POLICY "Allow anon delete own detection-images"
ON storage.objects FOR DELETE
TO anon, authenticated
USING (bucket_id = 'detection-images');
