FROM python:3.10-slim

WORKDIR /app

# Environment variables
ENV PYTHONUNBUFFERED=1 \
    PORT=7860 \
    DEVICE=cpu \
    MODEL_PATH=/app/models/final_model.pt \
    LABEL_MAPPING_PATH=/app/models/label_mapping.json \
    PREPROCESSING_CONFIG_PATH=/app/models/preprocessing.json \
    MAX_UPLOAD_SIZE_MB=10

# Install Python dependencies using CPU-only PyTorch for fast, lightweight build
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt --extra-index-url https://download.pytorch.org/whl/cpu

# Copy application and model files
COPY app ./app
COPY models ./models

# Hugging Face Spaces user setup (UID 1000)
RUN useradd -m -u 1000 user && chown -R user:user /app
USER user
ENV HOME=/home/user \
    PATH=/home/user/.local/bin:$PATH

EXPOSE 7860

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "7860"]
