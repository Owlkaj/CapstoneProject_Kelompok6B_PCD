<?php

namespace App\Helpers;

class DiseaseCatalog
{
    public static function getInfo(string $diseaseName): array
    {
        $catalog = [
            'Bacterial Spot' => [
                'name_id' => 'Bercak Bakteri (Bacterial Spot)',
                'name_en' => 'Bacterial Spot',
                'description' => 'Disebabkan bakteri Xanthomonas campestris, ditandai bercak kecil berair yang lambat laun menghitam pada daun dan buah.',
                'treatment' => 'Gunakan bakterisida berbahan tembaga, hindari pengairan di atas tajuk daun, dan lakukan sanitasi tanaman yang terinfeksi.',
                'color' => 'orange',
            ],
            'Early Blight' => [
                'name_id' => 'Bercak Kering (Early Blight)',
                'name_en' => 'Early Blight',
                'description' => 'Penyakit jamur Alternaria solani dengan pola cincin konsentris kecoklatan mirip papan target pada daun tua.',
                'treatment' => 'Semprot fungisida berbahan aktif mankozeb atau klorotalonil, pangkas daun bagian bawah yang menyentuh tanah, dan jaga sirkulasi udara.',
                'color' => 'amber',
            ],
            'Healthy' => [
                'name_id' => 'Sehat (Healthy)',
                'name_en' => 'Healthy',
                'description' => 'Daun tomat dalam kondisi prima dan sehat tanpa tanda-tanda serangan hama atau infeksi patogen.',
                'treatment' => 'Pertahankan pemupukan berimbang, kebersihan lahan, serta pemantauan rutin untuk pencegahan hama.',
                'color' => 'emerald',
            ],
            'Late Blight' => [
                'name_id' => 'Hawar Daun (Late Blight)',
                'name_en' => 'Late Blight',
                'description' => 'Disebabkan oomycete Phytophthora infestans, menimbulkan bercak basah keabu-abuan yang menyebar cepat pada cuaca lembab.',
                'treatment' => 'Aplikasikan fungisida sistemik berbasis simoksanil atau dimetomorf, perbaiki drainase bedengan, dan musnahkan daun yang membusuk.',
                'color' => 'red',
            ],
            'Leaf Mold' => [
                'name_id' => 'Kapang Daun (Leaf Mold)',
                'name_en' => 'Leaf Mold',
                'description' => 'Disebabkan jamur Passalora fulva, memunculkan bercak kuning di permukaan atas daun dan lapisan berbeludru abu-abu di bawah daun.',
                'treatment' => 'Kurangi kelembapan greenhouse/lahan, tingkatkan ventilasi udara, dan semprot fungisida protektif.',
                'color' => 'lime',
            ],
            'Tomato Mosaic Virus' => [
                'name_id' => 'Virus Mosaik Tomat (Tomato Mosaic Virus)',
                'name_en' => 'Tomato Mosaic Virus',
                'description' => 'Penyakit virus yang menyebabkan daun belang hijau muda-tua menyerupai pola mosaik, mengeriting, dan pertumbuhan kerdil.',
                'treatment' => 'Tidak ada obat kimia untuk virus; segera cabut dan bakar tanaman terinfeksi, sterilkan alat kerja, dan kendalikan vektor serangga.',
                'color' => 'pink',
            ],
            'Septoria Leaf Spot' => [
                'name_id' => 'Bercak Daun Septoria (Septoria Leaf Spot)',
                'name_en' => 'Septoria Leaf Spot',
                'description' => 'Infeksi jamur Septoria lycopersici dengan banyak bercak lingkaran kecil berpusat abu-abu terang dan bertepi gelap.',
                'treatment' => 'Singkirkan daun bawah yang bergejala, gunakan mulsa plastik untuk mencegah percikan spora dari tanah, dan aplikasikan fungisida tembaga.',
                'color' => 'purple',
            ],
            'Spider Mites' => [
                'name_id' => 'Tungau Laba-Laba (Spider Mites)',
                'name_en' => 'Spider Mites',
                'description' => 'Serangan hama Tetranychus urticae, memicu bintik-bintik kuning keperakan halus dan benang jaring laba-laba halus di bawah daun.',
                'treatment' => 'Gunakan akarisida berbahan aktif abamektin, semprotkan air bertekanan untuk merusak sarang jaring, dan jaga kelembapan lingkungan.',
                'color' => 'stone',
            ],
            'Target Spot' => [
                'name_id' => 'Bercak Sasaran (Target Spot)',
                'name_en' => 'Target Spot',
                'description' => 'Disebabkan Corynespora cassiicola, membentuk bercak bulat coklat dengan zona konsentris tegas menyerupai lingkaran sasaran tembak.',
                'treatment' => 'Aplikasikan fungisida berbahan azoksistrobin atau klorotalonil, serta hindari penanaman terlalu rapat.',
                'color' => 'cyan',
            ],
            'Tomato Yellow Leaf Curl Virus (TYLCV)' => [
                'name_id' => 'Virus Kuning Keriting (TYLCV)',
                'name_en' => 'Tomato Yellow Leaf Curl Virus (TYLCV)',
                'description' => 'Ditularkan oleh kutu kebul (Bemisia tabaci), membuat daun menguning ke atas, tepi daun menggulung, dan bunga mudah rontok.',
                'treatment' => 'Pasang perangkap kuning likat, kendalikan vektor kutu kebul dengan insektisida sistemik, dan gunakan varietas tahan benih.',
                'color' => 'yellow',
            ],
        ];

        return $catalog[$diseaseName] ?? [
            'name_id' => $diseaseName,
            'name_en' => $diseaseName,
            'description' => 'Klasifikasi penyakit terdeteksi oleh sistem.',
            'treatment' => 'Konsultasikan dengan penyuluh pertanian atau agronomi setempat untuk tindakan lebih lanjut.',
            'color' => 'emerald',
        ];
    }
}
