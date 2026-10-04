# Graph Report - sistem  (2026-10-04)

## Corpus Check
- 25 files · ~32,224 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 127 nodes · 196 edges · 13 communities (11 shown, 2 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 4 edges (avg confidence: 0.96)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Laravel Controllers & Application Layer
- ML Training & EfficientNet Architecture
- FastAPI Inference Service & Routing
- Supabase Integration & Database Service
- Dataset Preparation & Transforms
- Project Documentation & Specifications
- Subsystem 6
- Subsystem 7
- Subsystem 8
- Subsystem 9

## God Nodes (most connected - your core abstractions)
1. `SupabaseService` - 20 edges
2. `run_experiment()` - 14 edges
3. `TomatoDataset` - 10 edges
4. `DetectionController` - 8 edges
5. `HistoryController` - 8 edges
6. `get_eval_transforms()` - 7 edges
7. `DiseaseCatalog` - 7 edges
8. `FastApiService` - 7 edges
9. `build_model()` - 6 edges
10. `InferenceService` - 6 edges

## Surprising Connections (you probably didn't know these)
- `lifespan()` --uses--> `InferenceService`  [INFERRED]
  fastapi/app/main.py → fastapi/app/services/inference.py
- `health_check()` --uses--> `HealthResponse`  [INFERRED]
  fastapi/app/routes/predict.py → fastapi/app/schemas/predict.py
- `predict_image()` --uses--> `PredictionResponse`  [INFERRED]
  fastapi/app/routes/predict.py → fastapi/app/schemas/predict.py
- `DetectionController` --references--> `SupabaseService`  [EXTRACTED]
  laravel/app/Http/Controllers/DetectionController.php → laravel/app/Services/SupabaseService.php
- `HistoryController` --references--> `SupabaseService`  [EXTRACTED]
  laravel/app/Http/Controllers/HistoryController.php → laravel/app/Services/SupabaseService.php

## Import Cycles
- None detected.

## Communities (13 total, 2 thin omitted)

### Community 0 - "Laravel Controllers & Application Layer"
Cohesion: 0.11
Nodes (23): Dataset, evaluate_final_model(), build_model(), freeze_backbone(), Freezes all feature extractor backbone layers for E01 Transfer Learning…, Unfreezes the upper stages of EfficientNetB0 backbone for E02 Fine-Tuning. PRD…, Builds EfficientNetB0 architecture with custom 10-class linear classifier. PRD…, unfreeze_upper_layers() (+15 more)

### Community 1 - "ML Training & EfficientNet Architecture"
Cohesion: 0.16
Nodes (9): Illuminate\Http\JsonResponse, Illuminate\Http\Request, Illuminate\Support\Facades\Route, DiseaseCatalog, Controller, DetectionController, HistoryController, FastApiService (+1 more)

### Community 2 - "FastAPI Inference Service & Routing"
Cohesion: 0.19
Nodes (4): Closure, EnsureTomatoSession, SupabaseService, Symfony\Component\HttpFoundation\Response

### Community 3 - "Supabase Integration & Database Service"
Cohesion: 0.19
Nodes (7): FastAPI, lifespan(), InferenceService, Loads model weights, label mappings, and transforms., Processes image bytes and returns prediction dictionary., create_dummy_image(), run_api_tests()

### Community 4 - "Dataset Preparation & Transforms"
Cohesion: 0.24
Nodes (10): BaseModel, health_check(), predict_image(), Request, Config, HealthResponse, PredictionResponse, get (+2 more)

### Community 5 - "Project Documentation & Specifications"
Cohesion: 0.32
Nodes (5): Illuminate\Http\Client\ConnectionException, Illuminate\Http\UploadedFile, Illuminate\Support\Facades\Http, Illuminate\Support\Str, RuntimeException

### Community 6 - "Subsystem 6"
Cohesion: 0.38
Nodes (5): Database\Factories\UserFactory, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, User

### Community 8 - "Subsystem 8"
Cohesion: 0.83
Nodes (3): compute_file_hash(), prepare_dataset(), Path

## Knowledge Gaps
- **1 isolated node(s):** `Config`
  These have ≤1 connection - possible missing edges or undocumented components.
- **2 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `SupabaseService` connect `FastAPI Inference Service & Routing` to `ML Training & EfficientNet Architecture`, `Project Documentation & Specifications`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **Why does `FastApiService` connect `ML Training & EfficientNet Architecture` to `Project Documentation & Specifications`?**
  _High betweenness centrality (0.025) - this node is a cross-community bridge._
- **Why does `DetectionController` connect `ML Training & EfficientNet Architecture` to `FastAPI Inference Service & Routing`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **What connects `Config` to the rest of the system?**
  _1 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Laravel Controllers & Application Layer` be split into smaller, more focused modules?**
  _Cohesion score 0.11397849462365592 - nodes in this community are weakly interconnected._