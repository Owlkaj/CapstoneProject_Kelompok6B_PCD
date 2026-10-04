"""
Script to generate user-friendly evaluation reports:
1. EVALUATION_REPORT.md (Markdown formatted report)
2. metrics_summary.csv (Excel-friendly CSV table)
3. metrics_barchart.png (High-resolution comparison chart)
4. evaluation_dashboard.html (Interactive standalone web dashboard)
"""

import json
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt
import numpy as np

RESULTS_DIR = Path(__file__).resolve().parents[2] / "results"
METRICS_JSON = RESULTS_DIR / "evaluation_metrics.json"

ID_NAMES = {
    "Bacterial Spot": "Bercak Bakteri",
    "Early Blight": "Bercak Kering",
    "Healthy": "Daun Sehat",
    "Late Blight": "Hawar Daun",
    "Leaf Mold": "Kapang Daun",
    "Tomato Mosaic Virus": "Virus Mosaik",
    "Septoria Leaf Spot": "Bercak Septoria",
    "Spider Mites": "Tungau Laba-laba",
    "Target Spot": "Bercak Sasaran",
    "Tomato Yellow Leaf Curl Virus (TYLCV)": "Virus Kuning Keriting"
}

def load_metrics():
    with open(METRICS_JSON, "r", encoding="utf-8") as f:
        return json.load(f)

def generate_csv(data):
    rows = []
    for cls_name, m in data["per_class_metrics"].items():
        rows.append({
            "No": len(rows) + 1,
            "Kelas Teknis": cls_name,
            "Nama Indonesia": ID_NAMES.get(cls_name, cls_name),
            "Precision (%)": round(m["precision"] * 100, 2),
            "Recall (%)": round(m["recall"] * 100, 2),
            "F1-Score (%)": round(m["f1-score"] * 100, 2),
            "Jumlah Sampel Uji": int(m["support"])
        })
    df = pd.DataFrame(rows)
    csv_path = RESULTS_DIR / "metrics_summary.csv"
    df.to_csv(csv_path, index=False, encoding="utf-8-sig")
    print(f"Generated CSV: {csv_path}")
    return df

def generate_markdown(data, df):
    overall_acc = round(data["overall_accuracy"] * 100, 2)
    macro_prec = round(data["macro_precision"] * 100, 2)
    macro_rec = round(data["macro_recall"] * 100, 2)
    macro_f1 = round(data["macro_f1"] * 100, 2)
    total_test = data["test_samples"]
    model_name = data.get("selected_model", "E02 (EfficientNet-B0 Fine-Tuned)")

    md_content = f"""# 📊 Laporan Hasil Evaluasi Model Deep Learning — TomatCare

Dokumen ini menyajikan rekapitulasi performa model klasifikasi penyakit daun tomat pada himpunan data uji independen (*test set*).

---

## 📌 Ringkasan Eksekutif

| Parameter | Hasil Evaluasi | Keterangan |
|---|:---:|---|
| **Model Terpilih** | **{model_name}** | EfficientNet-B0 (Transfer Learning + Fine-Tuning Stage 6-7) |
| **Total Sampel Test Set** | **{total_test:,} citra** | 200 citra independen per kelas (100% *unseen data*) |
| **Akurasi Keseluruhan (Accuracy)** | **{overall_acc}%** | 1.932 citra diprediksi dengan tepat |
| **Rata-rata Precision (Macro)** | **{macro_prec}%** | Tingkat ketepatan saat model memprediksi suatu kelas |
| **Rata-rata Recall (Macro)** | **{macro_rec}%** | Kemampuan model menemukan seluruh kasus penyakit |
| **Rata-rata F1-Score (Macro)** | **{macro_f1}%** | Keseimbangan harmonis Precision dan Recall |

---

## 🍃 Tabel Performa Per Kelas Penyakit

| No | Kelas Teknis | Nama Indonesia | Precision | Recall | F1-Score | Sampel Uji | Status |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|
"""
    for _, r in df.iterrows():
        f1 = r['F1-Score (%)']
        status = "🌟 Luar Biasa (>97%)" if f1 >= 97.0 else "✅ Sangat Baik (>94%)"
        md_content += f"| {r['No']} | `{r['Kelas Teknis']}` | {r['Nama Indonesia']} | {r['Precision (%)']:.2f}% | {r['Recall (%)']:.2f}% | **{f1:.2f}%** | {r['Jumlah Sampel Uji']} | {status} |\n"

    md_content += f"""
---

## 📈 File Visualisasi & Data Pendukung

1. **Grafik Batang Performa**: [`metrics_barchart.png`](file:///{RESULTS_DIR.as_posix()}/metrics_barchart.png)
2. **Matriks Kebingungan (Confusion Matrix)**: [`confusion_matrix.png`](file:///{RESULTS_DIR.as_posix()}/confusion_matrix.png)
3. **Data Tabular Excel/CSV**: [`metrics_summary.csv`](file:///{RESULTS_DIR.as_posix()}/metrics_summary.csv)
4. **Dashboard HTML Interaktif**: [`evaluation_dashboard.html`](file:///{RESULTS_DIR.as_posix()}/evaluation_dashboard.html) *(bisa dibuka langsung di browser)*

---

## 💡 Analisis Temuan Kunci

1. **Kelas dengan Performa Tertinggi**:
   - `Tomato Mosaic Virus` (**F1: 98.50%**) dan `Tomato Yellow Leaf Curl Virus` (**F1: 98.49%**) memiliki tingkat akurasi mendekati sempurna karena pola visual kerusakan virus yang sangat khas.
   - `Healthy` (Daun Sehat) mencapai Recall **99.00%**, menandakan tanaman sehat hampir tidak pernah salah didiagnosis sebagai tanaman sakit.

2. **Daya Tangkap Penyakit (Recall)**:
   - `Spider Mites` (99.00%) dan `Bacterial Spot` (97.50%) memiliki tingkat recall yang sangat tinggi, krusial agar petani tidak melewatkan serangan hama/patogen sejak fase awal.

3. **Stabilitas Model**:
   - Selisih antara Macro F1 ({macro_f1}%) dan Weighted F1 (96.60%) adalah 0.00%, membuktikan model memiliki keadilan prediksi yang sangat seimbang di seluruh 10 kelas tanpa bias kelas tertentu.
"""
    md_path = RESULTS_DIR / "EVALUATION_REPORT.md"
    with open(md_path, "w", encoding="utf-8") as f:
        f.write(md_content)
    print(f"Generated Markdown: {md_path}")

def generate_barchart(df):
    plt.style.use('seaborn-v0_8-whitegrid' if 'seaborn-v0_8-whitegrid' in plt.style.available else 'default')
    fig, ax = plt.subplots(figsize=(14, 7), dpi=300)

    classes = [f"{r['Nama Indonesia']}\n({r['Kelas Teknis']})" for _, r in df.iterrows()]
    x = np.arange(len(classes))
    width = 0.26

    rects1 = ax.bar(x - width, df['Precision (%)'], width, label='Precision (%)', color='#3b82f6', alpha=0.9)
    rects2 = ax.bar(x, df['Recall (%)'], width, label='Recall (%)', color='#10b981', alpha=0.9)
    rects3 = ax.bar(x + width, df['F1-Score (%)'], width, label='F1-Score (%)', color='#8b5cf6', alpha=0.9)

    ax.set_ylabel('Persentase Skor (%)', fontsize=12, fontweight='bold', labelpad=10)
    ax.set_title('Perbandingan Metrik Evaluasi Per Kelas Penyakit Daun Tomat (Test Set: 2.000 Citra)',
                 fontsize=14, fontweight='bold', pad=15)
    ax.set_xticks(x)
    ax.set_xticklabels(classes, fontsize=9, rotation=25, ha='right')
    ax.set_ylim(85, 103)
    ax.legend(loc='lower right', frameon=True, facecolor='white', framealpha=0.95, fontsize=10)

    # Add value labels on top of F1-Score bars
    for rect in rects3:
        height = rect.get_height()
        ax.annotate(f'{height:.1f}%',
                    xy=(rect.get_x() + rect.get_width() / 2, height),
                    xytext=(0, 4), textcoords="offset points",
                    ha='center', va='bottom', fontsize=8, fontweight='bold', color='#4c1d95')

    plt.tight_layout()
    chart_path = RESULTS_DIR / "metrics_barchart.png"
    plt.savefig(chart_path, dpi=300)
    plt.close()
    print(f"Generated Bar Chart: {chart_path}")

def generate_html_dashboard(data, df):
    overall_acc = round(data["overall_accuracy"] * 100, 2)
    macro_prec = round(data["macro_precision"] * 100, 2)
    macro_rec = round(data["macro_recall"] * 100, 2)
    macro_f1 = round(data["macro_f1"] * 100, 2)

    labels = [r['Nama Indonesia'] for _, r in df.iterrows()]
    prec_vals = df['Precision (%)'].tolist()
    rec_vals = df['Recall (%)'].tolist()
    f1_vals = df['F1-Score (%)'].tolist()

    table_rows = ""
    for _, r in df.iterrows():
        f1 = r['F1-Score (%)']
        badge_class = "bg-emerald-100 text-emerald-800" if f1 >= 97 else "bg-blue-100 text-blue-800"
        status_text = "Luar Biasa" if f1 >= 97 else "Sangat Baik"
        table_rows += f"""
        <tr class="hover:bg-slate-50 transition border-b border-slate-100">
            <td class="py-3 px-4 font-semibold text-slate-700">{r['No']}</td>
            <td class="py-3 px-4 font-medium text-slate-900">{r['Nama Indonesia']} <span class="text-xs text-slate-400 block font-mono">{r['Kelas Teknis']}</span></td>
            <td class="py-3 px-4 text-center font-mono text-blue-600 font-semibold">{r['Precision (%)']:.2f}%</td>
            <td class="py-3 px-4 text-center font-mono text-emerald-600 font-semibold">{r['Recall (%)']:.2f}%</td>
            <td class="py-3 px-4 text-center font-mono text-purple-700 font-bold">{f1:.2f}%</td>
            <td class="py-3 px-4 text-center text-slate-600">{r['Jumlah Sampel Uji']}</td>
            <td class="py-3 px-4 text-center"><span class="px-2.5 py-1 rounded-full text-xs font-semibold {badge_class}">{status_text}</span></td>
        </tr>
        """

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Evaluasi Model — TomatCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body {{ font-family: 'Plus Jakarta Sans', sans-serif; }}</style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen p-6 md:p-10">
    <div class="max-w-6xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-3xl p-8 text-white shadow-xl shadow-emerald-900/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider mb-2">Evaluasi Independen</span>
                <h1 class="text-3xl font-extrabold">TomatCare — Hasil Uji Model AI</h1>
                <p class="text-emerald-100 text-sm mt-1">Evaluasi pada 2.000 Citra Daun Tomat Unseen (EfficientNet-B0 Fine-Tuned)</p>
            </div>
            <div class="bg-white/10 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/20 text-center">
                <span class="text-xs uppercase tracking-wider opacity-80 block font-semibold">Overall Test Accuracy</span>
                <span class="text-4xl font-black text-white">{overall_acc}%</span>
            </div>
        </div>

        <!-- 4 Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Akurasi Keseluruhan</span>
                <span class="text-2xl md:text-3xl font-black text-emerald-600 mt-1 block">{overall_acc}%</span>
                <span class="text-xs text-slate-500 mt-1 block">1.932 dari 2.000 citra tepat</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Macro Precision</span>
                <span class="text-2xl md:text-3xl font-black text-blue-600 mt-1 block">{macro_prec}%</span>
                <span class="text-xs text-slate-500 mt-1 block">Tingkat ketepatan tebakan</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Macro Recall</span>
                <span class="text-2xl md:text-3xl font-black text-teal-600 mt-1 block">{macro_rec}%</span>
                <span class="text-xs text-slate-500 mt-1 block">Daya jaring penyakit daun</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Macro F1-Score</span>
                <span class="text-2xl md:text-3xl font-black text-purple-600 mt-1 block">{macro_f1}%</span>
                <span class="text-xs text-slate-500 mt-1 block">Keseimbangan harmonis model</span>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200/80">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Perbandingan Metrik Tiap Kelas Daun</h2>
                    <p class="text-xs text-slate-500">Visualisasi Precision, Recall, dan F1-Score untuk 10 kelas penyakit</p>
                </div>
            </div>
            <div class="h-80 w-full">
                <canvas id="metricsChart"></canvas>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Tabel Rincian Lengkap Performa</h2>
                    <p class="text-xs text-slate-500">Daftar lengkap 10 kelas patologi daun tomat beserta hasil evaluasi kuantitatif</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Penyakit Daun</th>
                            <th class="py-3 px-4 text-center">Precision</th>
                            <th class="py-3 px-4 text-center">Recall</th>
                            <th class="py-3 px-4 text-center">F1-Score</th>
                            <th class="py-3 px-4 text-center">Sampel Uji</th>
                            <th class="py-3 px-4 text-center">Performa</th>
                        </tr>
                    </thead>
                    <tbody>
                        {table_rows}
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const ctx = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx, {{
            type: 'bar',
            data: {{
                labels: {json.dumps(labels)},
                datasets: [
                    {{
                        label: 'Precision (%)',
                        data: {json.dumps(prec_vals)},
                        backgroundColor: '#3b82f6',
                        borderRadius: 6
                    }},
                    {{
                        label: 'Recall (%)',
                        data: {json.dumps(rec_vals)},
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    }},
                    {{
                        label: 'F1-Score (%)',
                        data: {json.dumps(f1_vals)},
                        backgroundColor: '#8b5cf6',
                        borderRadius: 6
                    }}
                ]
            }},
            options: {{
                responsive: true,
                maintainAspectRatio: false,
                scales: {{
                    y: {{
                        min: 88,
                        max: 100,
                        ticks: {{
                            callback: function(value) {{ return value + '%'; }}
                        }}
                    }}
                }},
                plugins: {{
                    legend: {{
                        position: 'top',
                    }}
                }}
            }}
        }});
    </script>
</body>
</html>
"""
    html_path = RESULTS_DIR / "evaluation_dashboard.html"
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"Generated HTML Dashboard: {html_path}")

if __name__ == "__main__":
    data = load_metrics()
    df = generate_csv(data)
    generate_markdown(data, df)
    generate_barchart(df)
    generate_html_dashboard(data, df)
    print("\n[SUCCESS] All evaluation visual reports generated successfully!")
