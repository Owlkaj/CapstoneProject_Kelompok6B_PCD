# 📈 Panduan Berkas Riwayat Pelatihan (`ml/results/training_history/`)

Folder ini menyimpan catatan metrik pelatihan (*training & validation loss/accuracy*) per epoch beserta grafik kurvanya untuk ketiga tahapan pelatihan berjenjang model **EfficientNet-B0**:

| Tahap | Berkas CSV | Grafik Akurasi & Loss | Deskripsi Pelatihan | Hasil Terbaik (Validation) |
|---|---|---|---|---|
| **E01** | [`E01_history.csv`](./E01_history.csv) | [`E01_accuracy.png`](./E01_accuracy.png)<br>[`E01_loss.png`](./E01_loss.png) | **Tahap 1 — Transfer Learning (Classifier Head Only)**<br>Backbone ImageNet dibekukan (*frozen*); melatih head klasifikasi 10 kelas pada dataset laboratorium PlantVillage (10 epoch, `lr = 1e-3`). | **Val Acc: 90,70%**<br>Val Loss: `0.3127` (Epoch 10) |
| **E02** | [`E02_history.csv`](./E02_history.csv) | [`E02_accuracy.png`](./E02_accuracy.png)<br>[`E02_loss.png`](./E02_loss.png) | **Tahap 2 — Fine-Tuning Blok Atas (PlantVillage)**<br>Melanjutkan dari `E01_best.pt` dengan membuka *unfreeze* blok atas EfficientNet-B0 pada PlantVillage (10 epoch, `lr = 1e-4`). Menghasilkan model baseline laboratorium `E02_best.pt`. | **Val Acc: 95,60%**<br>Val Loss: `0.1353` (Epoch 10) |
| **E03** | [`E03_history.csv`](./E03_history.csv) | [`E03_accuracy.png`](./E03_accuracy.png)<br>[`E03_loss.png`](./E03_loss.png) | **Tahap 3 — Domain Adaptation Lapangan (Group-Disjoint)**<br>Melanjutkan dari `E02_best.pt` pada gabungan 9.600 citra lapangan group-disjoint + 9.600 PlantVillage (3 epoch, `lr = 1e-5`). Menghasilkan model produksi final `model_yolo3_grouped.pt`. | **Val Acc: 94,72%**<br>Val Loss: `0.1567` (Epoch 3) |
