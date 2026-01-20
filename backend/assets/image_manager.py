import quart
import config.conf as config # type: ignore
from assets.data import load_show, save_show
from reds_simple_logger import Logger
import os
import uuid
from quart import request, jsonify, send_file
from werkzeug.utils import secure_filename

logger = Logger()
logger.success("Image_manager.py loaded")

ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}

def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def upload_image(app=quart.Quart):
    @app.route("/api/image/upload", methods=["POST"]) # pyright: ignore[reportCallIssue]
    async def upload_image():
        try:
            
            print("Upload-Anfrage erhalten!")

            
            auth_key = request.headers.get("Authorization")
            if not auth_key or auth_key != config.Auth.auth_key:
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

            
            upload_path = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data", "assets", f"{image_type}.png"
            )

            
            os.makedirs(os.path.dirname(upload_path), exist_ok=True)

            
            await file.save(upload_path)

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
            image_path = os.path.join(
                os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
                "data", "assets", filename
            )
            if not os.path.exists(image_path):
                return jsonify({"status": "error", "message": "Image not found"}), 404

            
            return await send_file(image_path)

        except Exception as e:
            logger.error(f"Error getting image: {str(e)}")
            return jsonify({"status": "error", "message": str(e)}), 500

def get_current_images(app=quart.Quart):
    @app.route("/api/image/current", methods=["GET"]) # type: ignore
    async def get_current_images():
        try:
            if config.Auth.auth_key != (key := request.headers.get("Authorization")):
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