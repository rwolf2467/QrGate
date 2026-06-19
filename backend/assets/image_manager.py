import quart
import config.conf as config # type: ignore
from assets.data import load_show, save_show
from reds_simple_logger import Logger
import os
import hmac
import uuid
from quart import request, jsonify, send_file
from werkzeug.utils import secure_filename
from werkzeug.security import safe_join

logger = Logger()
logger.success("Image_manager.py loaded")

ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}
# Only these logical image slots may be (over)written via the upload endpoint.
ALLOWED_IMAGE_TYPES = {'banner', 'logo', 'wallpaper'}
# Hard cap per uploaded image (also enforced globally via MAX_CONTENT_LENGTH).
MAX_IMAGE_BYTES = 8 * 1024 * 1024

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


def sniff_image_type(data):
    """Return a safe MIME type if `data` starts with a known image magic-byte
    signature, else None. Never trust the client-supplied extension alone."""
    if data[:8] == b"\x89PNG\r\n\x1a\n":
        return "image/png"
    if data[:3] == b"\xff\xd8\xff":
        return "image/jpeg"
    if data[:6] in (b"GIF87a", b"GIF89a"):
        return "image/gif"
    if data[:4] == b"RIFF" and data[8:12] == b"WEBP":
        return "image/webp"
    return None

def upload_image(app=quart.Quart):
    @app.route("/api/image/upload", methods=["POST"]) # pyright: ignore[reportCallIssue]
    async def upload_image():
        try:
            
            print("Upload-Anfrage erhalten!")

            
            auth_key = request.headers.get("Authorization")
            if not auth_key or not hmac.compare_digest(str(auth_key), str(config.Auth.auth_key)):
                return jsonify({"status": "error", "message": "Unauthorized"}), 401


            files = await request.files

            if 'file' not in files:
                return jsonify({"status": "error", "message": "Keine Datei im Request"}), 400

            file = files['file']
            if file.filename == '':
                return jsonify({"status": "error", "message": "Kein Dateiname"}), 400

            if not allowed_file(file.filename):
                return jsonify({"status": "error", "message": "Dateityp nicht erlaubt"}), 400


            form = await request.form
            image_type = form.get('type', 'banner')

            # Restrict to known image slots to prevent path traversal / arbitrary file overwrite
            if image_type not in ALLOWED_IMAGE_TYPES:
                return jsonify({"status": "error", "message": "Invalid image type"}), 400


            assets_dir = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data", "assets",
            )
            upload_path = safe_join(assets_dir, f"{image_type}.png")
            if upload_path is None:
                return jsonify({"status": "error", "message": "Invalid image type"}), 400


            os.makedirs(os.path.dirname(upload_path), exist_ok=True)

            # Read into memory so we can enforce a size cap and verify the bytes
            # are actually an image before persisting (don't trust the extension):
            # rejects HTML/SVG/script disguised as an image that could later be
            # served and executed in the browser.
            file_data = file.read()
            if len(file_data) > MAX_IMAGE_BYTES:
                return jsonify({"status": "error", "message": "File too large"}), 413
            if sniff_image_type(file_data) is None:
                return jsonify({"status": "error", "message": "Invalid image data"}), 400

            with open(upload_path, "wb") as f:
                f.write(file_data)

            return jsonify({
                "status": "success",
                "message": "Datei erfolgreich hochgeladen",
                "type": image_type,
                "url": f"/api/image/get/{image_type}.png"
            })

        except Exception as e:
            print(f"Fehler beim Upload: {str(e)}")
            return jsonify({"status": "error", "message": f"Interner Serverfehler: {str(e)}"}), 500


    
def get_image(app=quart.Quart):
    @app.route("/api/image/get/<filename>", methods=["GET"]) # type: ignore
    async def get_image(filename):
        try:
            assets_dir = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data", "assets",
            )
            # Prevent path traversal via the <filename> route segment
            image_path = safe_join(assets_dir, filename)
            if image_path is None or not os.path.isfile(image_path):
                return jsonify({"status": "error", "message": "Image not found"}), 404

            with open(image_path, "rb") as f:
                image_data = f.read()
            # Serve with a Content-Type derived from the actual bytes and forbid
            # MIME-sniffing so a non-image that slipped through can't be
            # re-interpreted as HTML/JS by the browser.
            mimetype = sniff_image_type(image_data) or "application/octet-stream"
            response = quart.Response(image_data, mimetype=mimetype)
            response.headers["X-Content-Type-Options"] = "nosniff"
            return response

        except Exception as e:
            logger.error(f"Error getting image: {str(e)}")
            return jsonify({"status": "error", "message": str(e)}), 500

def get_current_images(app=quart.Quart):
    @app.route("/api/image/current", methods=["GET"]) # type: ignore
    async def get_current_images():
        try:
            key = request.headers.get("Authorization")
            if not key or not hmac.compare_digest(str(key), str(config.Auth.auth_key)):
                return jsonify({"status": "error", "message": "Unauthorized"}), 401

            show = load_show()
            base_url = request.host_url.rstrip('/')

            images = {
                "banner": show.get("banner", ""),
                "logo": f"/api/image/get/{show.get('logo', 'logo.png')}" if show.get('logo') else None,
                "wallpaper": f"/api/image/get/{show.get('wallpaper', 'wallpaper.png')}" if show.get('wallpaper') else None
            }

            
            images = {k: v for k, v in images.items() if v is not None}

            return jsonify({
                "status": "success",
                "images": images
            })

        except Exception as e:
            logger.error(f"Error getting current images: {str(e)}")
            return jsonify({"status": "error", "message": str(e)}), 500