<?php

namespace App\Helpers;

class DiseaseCatalog
{
    /**
     * Informasi agronomis & ciri visual untuk 10 kelas penyakit daun tomat.
     *
     * Setiap entri memuat:
     * - name_id            : nama tampilan dalam Bahasa Indonesia
     * - name_en            : nama kelas sesuai label model
     * - description        : penjelasan singkat penyakit
     * - pathogen           : penyebab penyakit
     * - visual             : ciri visual utama
     * - color              : token warna untuk badge dan grafik
     * - symptom_group      : pengelompokan pola gejala untuk analisis kemiripan
     * - model_cue          : ciri visual spesifik yang dibaca oleh model CNN
     * - key_differentiator : pembeda praktis di lapangan dibanding kelas lain
     */
    private const CATALOG = [
        'Bacterial Spot' => [
            'name_id' => 'Bercak Bakteri (Bacterial Spot)',
            'name_en' => 'Bacterial Spot',
            'description' => 'Disebabkan bakteri Xanthomonas campestris, ditandai bercak kecil berair yang lambat laun menghitam pada daun dan buah.',
            'pathogen' => 'Bakteri Xanthomonas campestris',
            'visual' => 'bercak kecil agak basah berwarna kehitaman dengan pinggiran kuning tipis',
            'color' => 'orange',
            'symptom_group' => 'necrotic_spot',
            'model_cue' => 'Pada foto ini, sistem melihat kumpulan titik kecil berwarna gelap atau kecoklatan yang tampak agak basah dan sering dikelilingi pinggiran kuning tipis.',
            'key_differentiator' => 'Perhatikan ukuran bercaknya. Bercak bakteri umumnya kecil sekitar 1 sampai 3 milimeter, tampak agak basah atau mengilap gelap, dan tidak membentuk pola lingkaran berlapis.',
        ],
        'Early Blight' => [
            'name_id' => 'Bercak Kering (Early Blight)',
            'name_en' => 'Early Blight',
            'description' => 'Penyakit jamur Alternaria solani dengan pola cincin konsentris kecoklatan mirip papan target pada daun tua.',
            'pathogen' => 'Jamur Alternaria solani',
            'visual' => 'bercak coklat dengan garis lingkaran berlapis mirip papan sasaran',
            'color' => 'amber',
            'symptom_group' => 'necrotic_spot',
            'model_cue' => 'Sistem melihat bercak kecoklatan yang permukaannya tampak berlapis seperti lingkaran papan sasaran dengan sedikit warna menguning di bagian tepi.',
            'key_differentiator' => 'Cari garis melingkar yang berlapis di bagian dalam bercak coklat tua, dan cek apakah gejala ini lebih banyak muncul di daun tua bagian bawah.',
        ],
        'Healthy' => [
            'name_id' => 'Sehat (Healthy)',
            'name_en' => 'Healthy',
            'description' => 'Daun tomat dalam kondisi prima dan sehat tanpa tanda-tanda serangan hama atau infeksi patogen.',
            'pathogen' => 'Tidak ada patogen',
            'visual' => 'permukaan daun hijau segar dan bersih tanpa bercak penyakit',
            'color' => 'emerald',
            'symptom_group' => 'healthy_foliage',
            'model_cue' => 'Sistem melihat warna hijau daun yang tampak merata dengan permukaan utuh tanpa bintik mencolok, bercak mati, ataupun bagian yang menguning.',
            'key_differentiator' => 'Pastikan langsung bahwa daun memang hijau merata dan tidak ada bintik kecil di pinggir atau di sisi balik daun yang tertutup bayangan.',
        ],
        'Late Blight' => [
            'name_id' => 'Hawar Daun (Late Blight)',
            'name_en' => 'Late Blight',
            'description' => 'Disebabkan oomycete Phytophthora infestans, menimbulkan bercak basah keabu-abuan yang menyebar cepat pada cuaca lembab.',
            'pathogen' => 'Oomycete Phytophthora infestans',
            'visual' => 'bercak lebar basah berwarna gelap keabu-abuan yang cepat meluas',
            'color' => 'red',
            'symptom_group' => 'blight_lesion',
            'model_cue' => 'Sistem menemukan bercak gelap kehijauan atau keabu-abuan yang melebar tidak beraturan, terutama mulai dari ujung atau tepi helai daun.',
            'key_differentiator' => 'Bercak hawar biasanya berukuran lebar, tampak basah atau cepat membusuk tanpa lingkaran berlapis, dan sering muncul serbuk putih tipis di balik daun saat udara lembap.',
        ],
        'Leaf Mold' => [
            'name_id' => 'Kapang Daun (Leaf Mold)',
            'name_en' => 'Leaf Mold',
            'description' => 'Disebabkan jamur Passalora fulva, memunculkan bercak kuning di permukaan atas daun dan lapisan berbeludru abu-abu di bawah daun.',
            'pathogen' => 'Jamur Passalora fulva',
            'visual' => 'bercak menguning pucat di atas daun dengan lapisan jamur di sisi bawahnya',
            'color' => 'lime',
            'symptom_group' => 'chlorosis_mold',
            'model_cue' => 'Sistem menangkap area pucat kekuningan berbatas samar pada bagian atas daun serta perubahan warna hijau yang tampak tidak merata.',
            'key_differentiator' => 'Balikkan helai daun tepat di bagian yang menguning untuk melihat ada atau tidaknya lapisan halus seperti beludru berwarna kecoklatan atau abu-abu di sisi bawah.',
        ],
        'Tomato Mosaic Virus' => [
            'name_id' => 'Virus Mosaik Tomat (Tomato Mosaic Virus)',
            'name_en' => 'Tomato Mosaic Virus',
            'description' => 'Penyakit virus yang menyebabkan daun belang hijau muda-tua menyerupai pola mosaik, mengeriting, dan pertumbuhan kerdil.',
            'pathogen' => 'Tobamovirus (ToMV)',
            'visual' => 'daun belang hijau tua dan hijau pucat yang tersebar acak',
            'color' => 'pink',
            'symptom_group' => 'viral_deformation',
            'model_cue' => 'Sistem membaca pola belang yang jelas antara warna hijau tua dan hijau muda atau kekuningan yang tersebar acak di permukaan daun.',
            'key_differentiator' => 'Perhatikan apakah daun tampak belang warna tanpa jaringan yang mengering atau membusuk, serta cek apakah daun muda tumbuh lebih sempit.',
        ],
        'Septoria Leaf Spot' => [
            'name_id' => 'Bercak Daun Septoria (Septoria Leaf Spot)',
            'name_en' => 'Septoria Leaf Spot',
            'description' => 'Infeksi jamur Septoria lycopersici dengan banyak bercak lingkaran kecil berpusat abu-abu terang dan bertepi gelap.',
            'pathogen' => 'Jamur Septoria lycopersici',
            'visual' => 'banyak bintik bulat kecil dengan bagian tengah berwarna abu-abu pucat',
            'color' => 'purple',
            'symptom_group' => 'necrotic_spot',
            'model_cue' => 'Sistem melihat banyak bercak bulat kecil yang tersebar dengan pinggiran gelap mengelilingi titik tengah berwarna pucat atau abu-abu.',
            'key_differentiator' => 'Lihat bagian tengah tiap bercak kecil. Ciri khas Septoria adalah titik tengahnya berwarna abu-abu keputihan dan jumlah bintiknya cukup banyak.',
        ],
        'Spider Mites' => [
            'name_id' => 'Tungau Laba-Laba (Spider Mites)',
            'name_en' => 'Spider Mites',
            'description' => 'Serangan hama Tetranychus urticae, memicu bintik-bintik kuning keperakan halus dan benang jaring laba-laba halus di bawah daun.',
            'pathogen' => 'Hama Tetranychus urticae',
            'visual' => 'bintik halus keperakan rapat sehingga daun terlihat kusam',
            'color' => 'stone',
            'symptom_group' => 'pest_stippling',
            'model_cue' => 'Sistem membaca bintik sangat halus berwarna pucat keperakan yang tersebar rapat sehingga permukaan daun tampak kusam berbintik.',
            'key_differentiator' => 'Amati bagian bawah daun dengan teliti. Serangan tungau umumnya disertai titik halus akibat hisapan hama dan terkadang terlihat benang jaring tipis.',
        ],
        'Target Spot' => [
            'name_id' => 'Bercak Sasaran (Target Spot)',
            'name_en' => 'Target Spot',
            'description' => 'Disebabkan Corynespora cassiicola, membentuk bercak bulat coklat dengan zona konsentris tegas menyerupai lingkaran sasaran tembak.',
            'pathogen' => 'Jamur Corynespora cassiicola',
            'visual' => 'bercak bulat coklat dengan pinggiran gelap yang tegas',
            'color' => 'cyan',
            'symptom_group' => 'necrotic_spot',
            'model_cue' => 'Sistem menemukan bercak bulat berwarna coklat dengan batas luar gelap yang tegas dan warna bagian tengah yang sedikit lebih pudar.',
            'key_differentiator' => 'Bercak bulatnya memiliki tepi gelap yang tegas dan bagian tengahnya terkadang tampak kering rapuh atau mulai berlubang kecil.',
        ],
        'Tomato Yellow Leaf Curl Virus (TYLCV)' => [
            'name_id' => 'Virus Kuning Keriting (TYLCV)',
            'name_en' => 'Tomato Yellow Leaf Curl Virus (TYLCV)',
            'description' => 'Ditularkan oleh kutu kebul (Bemisia tabaci), membuat daun menguning ke atas, tepi daun menggulung, dan bunga mudah rontok.',
            'pathogen' => 'Begomovirus melalui kutu kebul',
            'visual' => 'daun menguning cerah dengan tepi menggulung ke atas dan mengerut',
            'color' => 'yellow',
            'symptom_group' => 'viral_deformation',
            'model_cue' => 'Sistem mengenali warna kuning mencolok di pinggir atau sela tulang daun yang disertai bentuk helai daun mengerut dan tepian melengkung.',
            'key_differentiator' => 'Periksa bagian pucuk tanaman. Daun muda biasanya mengecil, menguning di bagian tepi, melengkung seperti mangkuk, dan sering ada kutu putih kecil di baliknya.',
        ],
    ];

    /**
     * Label ringkas kelompok gejala untuk narasi analisis Top-5.
     */
    private const SYMPTOM_GROUP_LABELS = [
        'necrotic_spot' => 'gejala bercak gelap yang mengering di permukaan daun',
        'blight_lesion' => 'bercak basah gelap yang meluas di jaringan daun',
        'chlorosis_mold' => 'perubahan warna daun menjadi pucat kekuningan',
        'viral_deformation' => 'daun belang kuning atau bentuknya berubah keriting',
        'pest_stippling' => 'permukaan daun berbintik halus rapat dan tampak kusam',
        'healthy_foliage' => 'daun hijau bersih tanpa bercak penyakit',
        'unknown' => 'perpaduan ciri warna dan tekstur daun',
    ];

    /**
     * Alasan kemiripan visual antar pasangan penyakit yang paling sering bersaing ketat.
     * Kunci diurutkan alfabetis: "ClassA|ClassB".
     */
    private const PAIRWISE_REASONS = [
        'Early Blight|Target Spot' => 'Keduanya sama-sama memunculkan bercak coklat bulat yang berlapis di dalam daun. Ketika difoto dari jarak sedang, bentuk lingkaran bercak pada dua penyakit ini terlihat sangat mirip.',
        'Early Blight|Septoria Leaf Spot' => 'Keduanya sama-sama menimbulkan bercak coklat kehitaman di daun. Bila bintik Septoria berkumpul rapat atau gejala Bercak Kering masih berukuran kecil, tampilannya mudah tertukar.',
        'Bacterial Spot|Septoria Leaf Spot' => 'Keduanya menghasilkan banyak bintik kecil berwarna gelap di permukaan daun, sehingga batas antara titik berair Bercak Bakteri dan bintik Septoria tampak mirip jika foto kurang tajam.',
        'Bacterial Spot|Early Blight' => 'Bintik kecil gelap dari Bercak Bakteri yang saling menyatu dan dikelilingi tepi kuning sering menyerupai fase awal Bercak Kering.',
        'Bacterial Spot|Target Spot' => 'Bercak gelap berukuran kecil hingga sedang pada dua penyakit ini punya tingkat kontras yang hampir sama terhadap warna hijau daun.',
        'Septoria Leaf Spot|Target Spot' => 'Keduanya sama-sama membentuk bercak bulat bertepi gelap dengan bagian tengah yang tampak lebih terang atau pudar.',
        'Early Blight|Late Blight' => 'Keduanya memicu jaringan daun berubah kecoklatan hingga gelap dengan tepi agak menguning. Saat garis lingkaran Bercak Kering tidak terlihat jelas, warnanya mirip dengan Hawar Daun.',
        'Late Blight|Target Spot' => 'Bercak coklat tua pada Bercak Sasaran yang mulai meluas memiliki warna gelap yang berdekatan dengan ciri Hawar Daun.',
        'Bacterial Spot|Late Blight' => 'Bagian daun yang gelap dan terlihat basah akibat Bercak Bakteri dapat terbaca mirip dengan tepian Hawar Daun yang mulai membusuk.',
        'Late Blight|Septoria Leaf Spot' => 'Kumpulan bintik gelap Septoria yang berdekatan atau terkena bayangan gelap dapat membentuk gumpalan warna gelap yang menyerupai Hawar Daun.',
        'Leaf Mold|Tomato Yellow Leaf Curl Virus (TYLCV)' => 'Keduanya sama-sama menonjolkan warna daun yang menguning pada permukaan atas tanpa banyak bercak coklat tua. Jika sisi bawah daun atau pucuk tidak terlihat jelas, keduanya mudah tertukar.',
        'Leaf Mold|Tomato Mosaic Virus' => 'Bercak kuning tidak merata pada Kapang Daun sering terlihat mirip dengan pola belang hijau dan kuning milik Virus Mosaik Tomat.',
        'Tomato Mosaic Virus|Tomato Yellow Leaf Curl Virus (TYLCV)' => 'Kedua penyakit virus ini sama-sama membuat daun tampak belang menguning dan bentuk helainya berubah keriting.',
        'Leaf Mold|Spider Mites' => 'Daun yang mulai menguning pucat akibat Kapang Daun dan warna kusam kekuningan akibat serangan Tungau Laba-laba sama-sama membuat warna hijau daun memudar tidak merata.',
        'Spider Mites|Tomato Mosaic Virus' => 'Bintik halus kekuningan yang menyebar dari serangan Tungau dapat terbaca menyerupai pola belang tidak teratur pada Virus Mosaik.',
        'Spider Mites|Tomato Yellow Leaf Curl Virus (TYLCV)' => 'Daun yang menguning kusam, mengerut, dan agak melengkung akibat serangan tungau berat tampak mirip dengan gejala Virus Kuning Keriting.',
        'Bacterial Spot|Spider Mites' => 'Banyaknya titik halus yang tersebar rapat di helai daun membuat dua kondisi ini sama-sama memunculkan pola permukaan daun yang berbintik.',
        'Septoria Leaf Spot|Spider Mites' => 'Titik kecil keabu-abuan atau pucat yang banyak tersebar di seluruh daun memberi kesan tekstur bintik yang mirip antara Septoria dan serangan tungau.',
        'Bacterial Spot|Leaf Mold' => 'Pinggiran kuning tipis di sekitar Bercak Bakteri atau bagian daun yang mulai menguning bisa terbaca mirip dengan gejala kuning awal Kapang Daun.',
        'Early Blight|Leaf Mold' => 'Warna kuning di sekitar bercak coklat pada Bercak Kering sering kali ikut terbaca mirip dengan warna kuning pucat khas Kapang Daun.',
    ];

    /**
     * Label alasan gate kualitas citra ke narasi Bahasa Indonesia.
     */
    private const GATE_QUALITY_NOTES = [
        'blurry' => 'foto terlihat agak buram sehingga tepi bercak pada daun kurang tajam',
        'too_dark' => 'pencahayaan foto tergolong gelap sehingga warna bercak dan bayangan sulit dibedakan',
        'too_bright' => 'pencahayaan foto terlalu silau sehingga warna kuning dan hijau pucat tampak samar',
        'too_small' => 'ukuran atau ketajaman gambar kecil sehingga bintik halus kurang terlihat jelas',
        'low_confidence' => 'keyakinan sistem masih di bawah batas aman sehingga perlu dicek lagi',
        'ambiguous' => 'kemiripan gejala pada foto membuat selisih skor antar tebakan teratas sangat tipis',
    ];

    /**
     * Ambil seluruh katalog penyakit.
     */
    public static function all(): array
    {
        return self::CATALOG;
    }

    /**
     * Ambil informasi satu penyakit berdasarkan nama kelas model.
     */
    public static function getInfo(string $diseaseName): array
    {
        return self::CATALOG[$diseaseName] ?? [
            'name_id' => $diseaseName,
            'name_en' => $diseaseName,
            'description' => 'Klasifikasi penyakit terdeteksi oleh sistem.',
            'pathogen' => 'Belum tercatat di katalog',
            'visual' => 'ciri visual khusus pada permukaan daun',
            'color' => 'emerald',
            'symptom_group' => 'unknown',
            'model_cue' => 'Sistem membaca pola warna dan tekstur daun yang paling mendekati jenis penyakit ini.',
            'key_differentiator' => 'Periksa langsung helai daun atau ambil ulang foto dengan cahaya yang lebih merata agar gejala terlihat jelas.',
        ];
    }

    /**
     * Analisis menyeluruh terhadap seluruh 5 tebakan teratas (Top-5):
     * - Menilai seberapa sengit persaingan skor antar seluruh peringkat (7 kondisi tanpa celah)
     * - Menjelaskan rantai hubungan visual dari #1, #2, #3, #4, hingga #5
     * - Menjelaskan faktor kualitas foto (confidence gate)
     * - Menyusun alasan sistem & selisih berantai untuk masing-masing kandidat #1 s.d. #5
     *
     * @param  array<int, array<string, mixed>>  $topPredictions
     * @param  array<string, mixed>  $gate
     * @return array<string, mixed>
     */
    public static function analyzeTopPredictions(array $topPredictions, array $gate = []): array
    {
        $candidates = self::normalizeTopCandidates(array_slice($topPredictions, 0, 5));

        if ($candidates === []) {
            return [
                'status' => 'single_only',
                'is_fierce' => false,
                'tone' => 'emerald',
                'badge_label' => 'Data Belum Tersedia',
                'margin_top2' => 0.0,
                'top1_score' => 0.0,
                'top2_score' => 0.0,
                'top2_sum' => 0.0,
                'step_margins' => [
                    'm12' => 0.0,
                    'm23' => 0.0,
                    'm34' => 0.0,
                    'm45' => 0.0,
                    'm14' => 0.0,
                    'm15' => 0.0,
                    'm25' => 0.0,
                ],
                'fierce_ranks' => [],
                'headline' => 'Belum ada kemungkinan penyakit yang bisa dijelaskan pada hasil ini.',
                'pairwise_reason' => 'Sistem belum menerima daftar skor penyakit dari hasil pemeriksaan foto.',
                'cluster_reason' => 'Unggah foto daun lalu jalankan pemeriksaan untuk melihat penjelasan lima kemungkinan penyakit teratas.',
                'quality_factor' => self::buildQualityFactorExplanation($gate, false),
                'top5_chain_reasons' => [
                    'Belum ada daftar penyakit yang bisa dibandingkan.',
                ],
                'candidates' => [],
            ];
        }

        $count = count($candidates);
        $first = $candidates[0];
        $second = $candidates[1] ?? null;

        $p1 = $first['confidence'];
        $p2 = $second['confidence'] ?? 0.0;
        $p3 = $candidates[2]['confidence'] ?? 0.0;
        $p4 = $candidates[3]['confidence'] ?? 0.0;
        $pLast = $candidates[$count - 1]['confidence'] ?? 0.0;

        $stepMargins = [
            'm12' => $count >= 2 ? round($p1 - $p2, 2) : 0.0,
            'm23' => $count >= 3 ? round($p2 - $p3, 2) : 0.0,
            'm34' => $count >= 4 ? round($p3 - $p4, 2) : 0.0,
            'm45' => $count >= 5 ? round($p4 - $pLast, 2) : 0.0,
            'm14' => $count >= 4 ? round($p1 - $p4, 2) : ($count >= 2 ? round($p1 - $p2, 2) : 0.0),
            'm15' => round($p1 - $pLast, 2),
            'm25' => $count >= 2 ? round($p2 - $pLast, 2) : 0.0,
        ];

        $top2Sum = round($p1 + $p2, 2);
        $classification = self::classifyCompetitionStatus($candidates, $stepMargins);
        $isFierce = $classification['is_fierce'];

        return [
            'status' => $classification['status'],
            'is_fierce' => $isFierce,
            'tone' => $classification['tone'],
            'badge_label' => $classification['badge_label'],
            'margin_top2' => $stepMargins['m12'],
            'top1_score' => $p1,
            'top2_score' => $p2,
            'top2_sum' => $top2Sum,
            'step_margins' => $stepMargins,
            'fierce_ranks' => $classification['fierce_ranks'],
            'headline' => self::buildCompetitionHeadline($classification['status'], $candidates, $stepMargins),
            'pairwise_reason' => self::buildPairwiseExplanation($candidates, $classification['status'], $stepMargins),
            'cluster_reason' => self::buildTop5ClusterExplanation($candidates, $classification['status']),
            'quality_factor' => self::buildQualityFactorExplanation($gate, $isFierce),
            'top5_chain_reasons' => self::buildTop5ChainReasons($candidates, $classification['status']),
            'candidates' => self::buildCandidateBreakdowns(
                $candidates,
                $classification['status'],
                $classification['fierce_ranks'],
                $p1,
                $stepMargins['m12'],
            ),
        ];
    }

    /**
     * Normalisasi struktur kandidat Top-5 agar memiliki persentase (0-100) dan metadata katalog lengkap.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeTopCandidates(array $items): array
    {
        if ($items === []) {
            return [];
        }

        // Deteksi apakah input memakai skala fraksi 0..1 tanpa field percentage/confidence_raw.
        $allSmall = true;
        $sumConfidence = 0.0;
        foreach ($items as $item) {
            if (isset($item['percentage']) || isset($item['confidence_raw'])) {
                $allSmall = false;
                break;
            }
            $val = (float) ($item['confidence'] ?? 0.0);
            if ($val > 1.0) {
                $allSmall = false;
                break;
            }
            $sumConfidence += $val;
        }
        $treatRawAsFraction = $allSmall && $sumConfidence <= 1.05;

        $normalized = [];
        foreach ($items as $item) {
            $className = (string) ($item['diseaseEn'] ?? $item['class'] ?? 'Unknown');
            $info = self::getInfo($className);

            if (isset($item['percentage'])) {
                $scorePct = round((float) $item['percentage'], 2);
            } elseif (isset($item['confidence_raw'])) {
                $scorePct = round((float) $item['confidence'], 2);
            } else {
                $raw = (float) ($item['confidence'] ?? 0.0);
                $scorePct = $treatRawAsFraction ? round($raw * 100, 2) : round($raw, 2);
            }

            $normalized[] = [
                'disease' => (string) ($item['disease'] ?? $info['name_id']),
                'diseaseEn' => $className,
                'confidence' => max(0.0, min(100.0, $scorePct)),
                'color' => (string) ($item['color'] ?? $info['color']),
                'visual' => $info['visual'],
                'pathogen' => $info['pathogen'],
                'symptom_group' => $info['symptom_group'],
                'model_cue' => $info['model_cue'],
                'key_differentiator' => $info['key_differentiator'],
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return $normalized;
    }

    /**
     * Klasifikasikan tingkat persaingan di seluruh Top-5 menjadi tepat 1 dari 7 status (tanpa celah).
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<string, float>  $stepMargins
     * @return array{status: string, is_fierce: bool, tone: string, badge_label: string, fierce_ranks: array<int, int>}
     */
    private static function classifyCompetitionStatus(array $candidates, array $stepMargins): array
    {
        $count = count($candidates);
        if ($count < 2) {
            return [
                'status' => 'single_only',
                'is_fierce' => false,
                'tone' => 'emerald',
                'badge_label' => 'Hanya Satu Kemungkinan',
                'fierce_ranks' => [1],
            ];
        }

        $p1 = $candidates[0]['confidence'];
        $p2 = $candidates[1]['confidence'];
        $p3 = $candidates[2]['confidence'] ?? 0.0;
        $p4 = $candidates[3]['confidence'] ?? 0.0;

        $m12 = $stepMargins['m12'];
        $m23 = $stepMargins['m23'];
        $m14 = $stepMargins['m14'];
        $m25 = $stepMargins['m25'];

        if ($p1 >= 85.0 && $m12 >= 60.0) {
            return [
                'status' => 'dominant',
                'is_fierce' => false,
                'tone' => 'emerald',
                'badge_label' => 'Sangat Yakin dan Jelas',
                'fierce_ranks' => [1],
            ];
        }

        if ($p1 >= 60.0 && $m12 >= 20.0) {
            if ($count >= 3 && $p2 >= 5.0 && ($m23 < 8.0 || $m25 < 15.0)) {
                return [
                    'status' => 'lead_with_fierce_tail',
                    'is_fierce' => true,
                    'tone' => 'indigo',
                    'badge_label' => 'Urutan 1 Jelas, Sisanya Mirip',
                    'fierce_ranks' => range(2, $count),
                ];
            }

            return [
                'status' => 'clear_lead',
                'is_fierce' => false,
                'tone' => 'indigo',
                'badge_label' => 'Urutan 1 Unggul Jelas',
                'fierce_ranks' => [1, 2],
            ];
        }

        if ($count >= 4 && ($p1 < 35.0 || $p4 >= 10.0 || $m14 < 25.0)) {
            return [
                'status' => 'fierce_top5',
                'is_fierce' => true,
                'tone' => 'rose',
                'badge_label' => 'Banyak Tebakan Bersaing Ketat',
                'fierce_ranks' => range(1, $count),
            ];
        }

        if ($count >= 3 && (($m12 < 20.0 && $m23 < 12.0) || ($p3 >= 15.0 && $m12 < 25.0))) {
            return [
                'status' => 'fierce_top3',
                'is_fierce' => true,
                'tone' => 'amber',
                'badge_label' => 'Tiga Tebakan Teratas Bersaing Ketat',
                'fierce_ranks' => [1, 2, 3],
            ];
        }

        return [
            'status' => 'duel_top2',
            'is_fierce' => true,
            'tone' => 'amber',
            'badge_label' => 'Dua Tebakan Teratas Bersaing Ketat',
            'fierce_ranks' => [1, 2],
        ];
    }

    /**
     * Ringkasan utama status persaingan untuk semua 7 kondisi dengan bahasa awam.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<string, float>  $stepMargins
     */
    private static function buildCompetitionHeadline(
        string $status,
        array $candidates,
        array $stepMargins,
    ): string {
        $count = count($candidates);
        $first = $candidates[0];
        $name1 = self::friendlyName($first);
        $p1Text = self::fmtPct($first['confidence']);

        if ($status === 'single_only' || $count < 2) {
            return "Sistem hanya menampilkan satu hasil yaitu {$name1} dengan tingkat keyakinan {$p1Text}% tanpa pilihan pembanding lainnya.";
        }

        $second = $candidates[1];
        $third = $candidates[2] ?? null;
        $last = $candidates[$count - 1];

        $name2 = self::friendlyName($second);
        $nameLast = self::friendlyName($last);
        $p2Text = self::fmtPct($second['confidence']);
        $m12Text = self::fmtPct($stepMargins['m12']);
        $m15Text = self::fmtPct($stepMargins['m15']);
        $m25Text = self::fmtPct($stepMargins['m25']);

        return match ($status) {
            'dominant' => "Hasilnya sangat jelas dan tidak ada persaingan ketat. {$name1} memperoleh skor {$p1Text}%, unggul jauh sebesar {$m12Text} poin dari pesaing terdekatnya yaitu {$name2} di angka {$p2Text}%.",
            'lead_with_fierce_tail' => "{$name1} unggul jelas di urutan pertama dengan skor {$p1Text}%, sedangkan pilihan urutan 2 sampai {$count} saling berdekatan dalam rentang {$m25Text} poin karena memiliki gejala yang agak mirip.",
            'clear_lead' => "{$name1} memimpin cukup jelas dengan skor {$p1Text}% atau unggul {$m12Text} poin dari {$name2} sebesar {$p2Text}%, sementara urutan 3 sampai {$count} memiliki kemungkinan jauh lebih kecil.",
            'fierce_top5' => "Persaingan terjadi di hampir semua lima tebakan teratas. Skor tertinggi {$name1} hanya mencapai {$p1Text}%, dan jaraknya hingga urutan {$count} yaitu {$nameLast} di angka " . self::fmtPct($last['confidence']) . "% cuma terpaut {$m15Text} poin.",
            'fierce_top3' => "Tiga tebakan teratas bersaing ketat satu sama lain. {$name1} meraih {$p1Text}%, diikuti sangat dekat oleh {$name2} sebesar {$p2Text}% dan " . ($third ? self::friendlyName($third) . ' sebesar ' . self::fmtPct($third['confidence']) . '%' : 'urutan ketiga') . ', sehingga ketiganya sama-sama perlu diperhatikan.',
            default => "Dua tebakan teratas bersaing sangat ketat. {$name1} meraih {$p1Text}% dan {$name2} menyusul rapat di angka {$p2Text}% dengan selisih cuma {$m12Text} poin, sedangkan tiga urutan di bawahnya tertinggal cukup jauh.",
        };
    }

    /**
     * Deskripsikan kemiripan visual spesifik antara dua kandidat dengan bahasa sederhana.
     */
    private static function describePairOverlap(array $candA, array $candB): string
    {
        $nameA = $candA['diseaseEn'];
        $nameB = $candB['diseaseEn'];
        $labelA = self::friendlyName($candA);
        $labelB = self::friendlyName($candB);

        if ($nameA === $nameB) {
            return "Keduanya merujuk pada ciri daun {$labelA} yang sama.";
        }

        $pair = [$nameA, $nameB];
        sort($pair);
        $pairKey = "{$pair[0]}|{$pair[1]}";

        if (isset(self::PAIRWISE_REASONS[$pairKey])) {
            return self::PAIRWISE_REASONS[$pairKey];
        }

        if ($nameA === 'Healthy' || $nameB === 'Healthy') {
            $diseaseCandidate = $nameA === 'Healthy' ? $candB : $candA;
            $diseaseLabel = self::friendlyName($diseaseCandidate);

            return "Sebagian besar permukaan daun masih terlihat hijau segar, namun ada sedikit bagian yang terkena bayangan, pantulan cahaya, atau perubahan warna awal yang menyerupai ciri {$diseaseLabel} berupa {$diseaseCandidate['visual']}.";
        }

        if ($candA['symptom_group'] === $candB['symptom_group']) {
            $groupLabel = self::SYMPTOM_GROUP_LABELS[$candA['symptom_group']] ?? self::SYMPTOM_GROUP_LABELS['unknown'];

            return "Keduanya sama sama menunjukkan {$groupLabel}, yaitu tampilan {$candA['visual']} pada {$labelA} yang cukup mirip dengan {$candB['visual']} pada {$labelB}.";
        }

        return "Foto daun memperlihatkan campuran tanda antara {$candA['visual']} milik {$labelA} dan {$candB['visual']} milik {$labelB}. Kondisi ini sering muncul bila cahaya foto kurang merata, ada bayangan daun, atau gejala masih tahap awal.";
    }

    /**
     * Jelaskan alasan di balik pola skor 5 tebakan saat ini dengan kalimat yang mudah dicerna.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<string, float>  $stepMargins
     */
    private static function buildPairwiseExplanation(
        array $candidates,
        string $status,
        array $stepMargins,
    ): string {
        $count = count($candidates);
        $first = $candidates[0];
        $name1 = self::friendlyName($first);

        if ($count < 2) {
            return "Sistem berfokus penuh pada {$name1}. {$first['model_cue']}";
        }

        $second = $candidates[1];
        $name2 = self::friendlyName($second);
        $overlap12 = self::describePairOverlap($first, $second);
        $m12Text = self::fmtPct($stepMargins['m12']);
        $differentiator = "Untuk memastikannya langsung pada tanaman: {$first['key_differentiator']}";

        if ($status === 'fierce_top5') {
            $last = $candidates[$count - 1];
            $nameLast = self::friendlyName($last);
            $overlapTopTail = self::describePairOverlap($first, $last);

            return "Skor terpecah hingga kelima kemungkinan karena ciri di permukaan daun tampak mirip antara satu sama lain. {$name1} sebagai urutan pertama punya kemiripan dengan {$name2}, bahkan hingga urutan {$count} yaitu {$nameLast}. {$overlapTopTail} {$differentiator}";
        }

        if ($status === 'fierce_top3' && isset($candidates[2])) {
            $third = $candidates[2];
            $name3 = self::friendlyName($third);
            $overlap23 = self::describePairOverlap($second, $third);
            $m23Text = self::fmtPct($stepMargins['m23']);

            return "Tiga penyakit teratas saling menempel karena kemiripan gejalanya. {$name1} dan {$name2} cuma terpaut {$m12Text} poin, ditambah {$name3} yang membuntuti dengan jarak {$m23Text} poin di belakangnya. {$overlap12} {$overlap23} {$differentiator}";
        }

        if ($status === 'lead_with_fierce_tail' && isset($candidates[2])) {
            $third = $candidates[2];
            $overlap23 = self::describePairOverlap($second, $third);
            $m25Text = self::fmtPct($stepMargins['m25']);

            return "Meskipun {$name1} sudah unggul {$m12Text} poin, empat kemungkinan di bawahnya masih berdekatan dalam rentang {$m25Text} poin karena ciri sekundernya beririsan. {$overlap23} {$differentiator}";
        }

        if ($status === 'duel_top2') {
            return "Selisih antara {$name1} dan {$name2} sangat tipis sebesar {$m12Text} poin karena tampilan gejalanya mudah tertukar. {$overlap12} {$differentiator}";
        }

        return "{$overlap12} {$differentiator}";
    }

    /**
     * Susun rantai penjelasan berurutan dari urutan 1 sampai 5 tanpa simbol rumit.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, string>
     */
    private static function buildTop5ChainReasons(array $candidates, string $status): array
    {
        $count = count($candidates);
        $first = $candidates[0];
        $name1 = self::friendlyName($first);

        if ($count === 1) {
            return [
                "Urutan 1 jatuh pada {$name1} dengan skor " . self::fmtPct($first['confidence']) . "%. {$first['model_cue']}",
            ];
        }

        $second = $candidates[1];
        $name2 = self::friendlyName($second);
        $m12Text = self::fmtPct($first['confidence'] - $second['confidence']);
        $chain = [];

        $chain[] = "Urutan 1 {$name1} sebesar " . self::fmtPct($first['confidence']) . "% dibanding urutan 2 {$name2} sebesar " . self::fmtPct($second['confidence']) . "% berselisih {$m12Text} poin. " . self::describePairOverlap($first, $second);

        if ($count >= 3) {
            $third = $candidates[2];
            $name3 = self::friendlyName($third);
            $m23Text = self::fmtPct($second['confidence'] - $third['confidence']);
            $anchor = $status === 'lead_with_fierce_tail' ? $second : $first;
            $chain[] = "Urutan 3 ditempati {$name3} dengan skor " . self::fmtPct($third['confidence']) . "%, berjarak {$m23Text} poin di bawah urutan 2. " . self::describePairOverlap($anchor, $third);
        }

        if ($count >= 4) {
            $fourth = $candidates[3];
            $name4 = self::friendlyName($fourth);
            $m34Text = self::fmtPct($candidates[2]['confidence'] - $fourth['confidence']);
            $pair34 = self::describePairOverlap($candidates[2], $fourth);

            if ($count >= 5) {
                $fifth = $candidates[4];
                $name5 = self::friendlyName($fifth);
                $m45Text = self::fmtPct($fourth['confidence'] - $fifth['confidence']);
                $chain[] = "Urutan 4 diisi {$name4} sebesar " . self::fmtPct($fourth['confidence']) . "% dan urutan 5 diisi {$name5} sebesar " . self::fmtPct($fifth['confidence']) . "%, secara berturut-turut terpaut {$m34Text} poin dan {$m45Text} poin dari urutan di atasnya. {$pair34} Sementara {$name5} ikut terbawa masuk daftar karena tampilan daun memiliki kemiripan ringan dengan ciri {$fifth['visual']}.";
            } else {
                $chain[] = "Urutan 4 diisi {$name4} sebesar " . self::fmtPct($fourth['confidence']) . "%, berjarak {$m34Text} poin dari urutan 3. {$pair34}";
            }
        }

        return $chain;
    }

    /**
     * Jelaskan alasan kelompok 5 penyakit ini masuk daftar secara ramah awam.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private static function buildTop5ClusterExplanation(array $candidates, string $status): string
    {
        $count = count($candidates);
        if ($count === 1) {
            return 'Daftar pembanding belum tersedia karena hanya satu jenis hasil yang diterima.';
        }

        $spotCount = 0;
        $colorOrDeformCount = 0;
        $hasHealthy = false;
        foreach ($candidates as $candidate) {
            $group = $candidate['symptom_group'];
            if (in_array($group, ['necrotic_spot', 'blight_lesion'], true)) {
                $spotCount++;
            } elseif (in_array($group, ['chlorosis_mold', 'viral_deformation', 'pest_stippling'], true)) {
                $colorOrDeformCount++;
            } elseif ($group === 'healthy_foliage') {
                $hasHealthy = true;
            }
        }

        if ($spotCount >= 3) {
            $clusterBody = "Sebagian besar dari {$count} tebakan ini merupakan penyakit yang sama sama menimbulkan bercak coklat atau hitam pada daun, sehingga sistem langsung mengurutkan jenis penyakit bercak yang paling mirip dengan foto Anda.";
        } elseif ($colorOrDeformCount >= 3) {
            $clusterBody = "Sebagian besar dari {$count} tebakan ini termasuk gangguan yang membuat daun menguning, belang, atau berbintik halus, sehingga sistem fokus membandingkan perubahan warna dan bentuk permukaan daun.";
        } elseif ($hasHealthy) {
            $clusterBody = 'Masuknya pilihan daun sehat bersama beberapa penyakit menandakan masih banyak bagian daun yang hijau segar, namun ada sedikit bayangan atau perubahan warna yang agak mirip gejala awal sakit.';
        } else {
            $clusterBody = "Kelima tebakan ini berasal dari gabungan kelompok penyakit bercak dan kelompok perubahan warna daun, yang biasanya muncul saat foto memperlihatkan warna menguning sekaligus bayangan atau titik bercak ringan.";
        }

        if ($status === 'dominant') {
            return "{$clusterBody} Walau urutan pertama sudah sangat meyakinkan, sistem tetap menampilkan empat kemungkinan lain dengan skor kecil agar Anda bisa melihat penyakit apa saja yang masih agak mirip.";
        }

        return "{$clusterBody} Itulah sebabnya kelima nama ini muncul berurutan pada hasil pemeriksaan.";
    }

    /**
     * Jelaskan pengaruh pencahayaan dan ketajaman foto dengan kalimat sederhana.
     *
     * @param  array<string, mixed>  $gate
     */
    private static function buildQualityFactorExplanation(array $gate, bool $isFierce): string
    {
        $reasons = array_values(array_filter(
            array_map('strval', (array) ($gate['reasons'] ?? [])),
            static fn (string $reason): bool => $reason !== '',
        ));

        if ($reasons === []) {
            if ($isFierce) {
                return 'Kualitas foto sudah cukup tajam dan terang. Persaingan skor terjadi murni karena gejala penyakit pada daun memang saling mirip secara alami.';
            }

            return 'Foto sudah terlihat jelas dengan pencahayaan yang baik sehingga sistem mudah mengenali gejala utamanya.';
        }

        $notes = array_map(
            static fn (string $reason): string => self::GATE_QUALITY_NOTES[$reason] ?? "terdapat kendala tampilan foto berupa {$reason}",
            $reasons,
        );
        $joined = implode(', dan ', array_unique($notes));

        return "Kondisi foto juga memengaruhi hasil karena {$joined}. Anda bisa mencoba memotret ulang satu helai daun dengan jarak fokus jelas dan latar yang bersih bila ingin hasil lebih meyakinkan.";
    }

    /**
     * Susun rincian tiap kartu kandidat dari urutan 1 sampai 5 tanpa simbol berlebihan.
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<int, int>  $fierceRanks
     * @return array<int, array<string, mixed>>
     */
    private static function buildCandidateBreakdowns(
        array $candidates,
        string $status,
        array $fierceRanks,
        float $p1,
        float $margin12,
    ): array {
        $breakdowns = [];
        $count = count($candidates);

        foreach ($candidates as $index => $candidate) {
            $rank = $index + 1;
            $prevCandidate = $index > 0 ? $candidates[$index - 1] : null;

            $gapFromTop1 = round(max(0.0, $p1 - $candidate['confidence']), 2);
            $gapFromPrev = $prevCandidate !== null
                ? round(max(0.0, $prevCandidate['confidence'] - $candidate['confidence']), 2)
                : 0.0;
            $isInFierceZone = in_array($rank, $fierceRanks, true);

            if ($index === 0) {
                $gapBadge = 'Posisi teratas';
                $overlapWithPrev = $count >= 2
                    ? 'Unggul ' . self::fmtPct($margin12) . ' poin di atas ' . self::friendlyName($candidates[1]) . '. ' . self::describePairOverlap($candidate, $candidates[1])
                    : 'Merupakan satu satunya kemungkinan yang ditampilkan untuk foto ini.';
            } else {
                $prevRank = $rank - 1;
                $prevName = self::friendlyName($prevCandidate);
                $gapBadge = $rank === 2
                    ? 'Selisih ' . self::fmtPct($gapFromPrev) . ' poin dari urutan 1'
                    : 'Selisih ' . self::fmtPct($gapFromPrev) . " poin dari urutan {$prevRank}, atau " . self::fmtPct($gapFromTop1) . ' poin di bawah urutan 1';
                $overlapWithPrev = "Kemiripan dengan {$prevName} di urutan {$prevRank}: " . self::describePairOverlap($prevCandidate, $candidate);
            }

            $breakdowns[] = [
                'rank' => $rank,
                'disease' => $candidate['disease'],
                'diseaseEn' => $candidate['diseaseEn'],
                'confidence' => $candidate['confidence'],
                'color' => $candidate['color'],
                'visual' => $candidate['visual'],
                'pathogen' => $candidate['pathogen'],
                'gap_from_top1' => $gapFromTop1,
                'gap_from_prev' => $gapFromPrev,
                'gap_badge' => $gapBadge,
                'is_in_fierce_zone' => $isInFierceZone,
                'role_label' => self::buildCandidateRoleLabel($rank, $status, $candidate['confidence'], $isInFierceZone),
                'why_predicted' => self::buildCandidateWhyPredicted($rank, $candidate, $status, $p1, $margin12, $gapFromPrev, $isInFierceZone),
                'overlap_with_prev' => $overlapWithPrev,
                'differentiator' => $candidate['key_differentiator'],
            ];
        }

        return $breakdowns;
    }

    private static function buildCandidateRoleLabel(int $rank, string $status, float $score, bool $isInFierceZone): string
    {
        if ($rank === 1) {
            return in_array($status, ['duel_top2', 'fierce_top3', 'fierce_top5'], true)
                ? 'Sementara Teratas'
                : 'Kemungkinan Utama';
        }

        if ($status === 'lead_with_fierce_tail' && $isInFierceZone) {
            return 'Pesaing Tambahan Mirip';
        }

        if ($isInFierceZone) {
            if ($score >= 10.0 || $rank <= 3) {
                return 'Pesaing Terdekat';
            }

            return 'Turut Bersaing';
        }

        if ($score < 1.0) {
            return 'Kemungkinan Kecil';
        }

        return 'Kemungkinan Lain';
    }

    private static function buildCandidateWhyPredicted(
        int $rank,
        array $candidate,
        string $status,
        float $p1,
        float $margin12,
        float $gapFromPrev,
        bool $isInFierceZone,
    ): string {
        $scoreText = self::fmtPct($candidate['confidence']);
        $name = self::friendlyName($candidate);
        $cue = $candidate['model_cue'];

        if ($rank === 1) {
            if ($status === 'dominant') {
                return "Sistem menempatkan {$name} di urutan pertama dengan keyakinan sangat kuat sebesar {$scoreText}%. {$cue}";
            }
            if (in_array($status, ['clear_lead', 'lead_with_fierce_tail'], true)) {
                $marginText = self::fmtPct($margin12);

                return "{$name} berada di urutan pertama sebesar {$scoreText}% atau unggul {$marginText} poin dari pesaing terdekatnya. {$cue}";
            }

            return "{$name} unggul tipis di posisi pertama sebesar {$scoreText}% di tengah persaingan ketat. {$cue}";
        }

        $prevRank = $rank - 1;
        $gapFromTopText = self::fmtPct(max(0.0, $p1 - $candidate['confidence']));
        $gapFromPrevText = self::fmtPct($gapFromPrev);

        if ($isInFierceZone) {
            $distanceNote = $rank === 2
                ? "hanya terpaut {$gapFromPrevText} poin dari urutan pertama"
                : "terpaut {$gapFromPrevText} poin dari urutan {$prevRank} dan {$gapFromTopText} poin dari urutan pertama";

            return "{$name} ikut bersaing ketat di urutan {$rank} dengan skor {$scoreText}%, {$distanceNote}. {$cue}";
        }

        if ($candidate['confidence'] < 1.0) {
            return "{$name} hanya mendapat skor sangat kecil yaitu {$scoreText}% di urutan {$rank}, dan muncul semata karena masih sedikit lebih mirip dibanding penyakit lain di luar lima besar. {$cue}";
        }

        return "{$name} masuk di urutan {$rank} dengan skor {$scoreText}%, tertinggal {$gapFromTopText} poin di bawah urutan pertama karena ada sedikit kemiripan tampilan daun. {$cue}";
    }

    /**
     * Ambil nama Bahasa Indonesia yang ringkas tanpa tanda kurung bahasa Inggris.
     *
     * @param  array<string, mixed>|string  $diseaseOrCandidate
     */
    private static function friendlyName(array|string $diseaseOrCandidate): string
    {
        $raw = is_array($diseaseOrCandidate)
            ? (string) ($diseaseOrCandidate['disease'] ?? $diseaseOrCandidate['diseaseEn'] ?? 'Penyakit')
            : $diseaseOrCandidate;

        $clean = trim((string) preg_replace('/\s*\([^)]*\)/', '', $raw));

        if ($clean === 'Sehat') {
            return 'Daun Sehat';
        }

        return $clean !== '' ? $clean : $raw;
    }

    private static function fmtPct(float $value): string
    {
        return number_format($value, 2, ',', '');
    }
}
