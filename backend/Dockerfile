# QrGate backend — Python / Quart async API server.
FROM python:3.12-slim

# Pillow (pulled in by qrcode[pil]) and reportlab ship manylinux wheels, so no
# build toolchain is needed. libfreetype/libjpeg runtime libs are bundled in the
# wheels. Keep the image slim.
ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

WORKDIR /app

# Install deps first so the layer is cached across code changes.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Persisted at runtime via volumes (see docker-compose.yml).
RUN mkdir -p /app/data /app/codes

EXPOSE 1654

CMD ["python", "main.py"]
