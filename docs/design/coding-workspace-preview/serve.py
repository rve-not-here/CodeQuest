"""Serve the approved static workspace preview using this checkout's Vite build."""

import argparse
import json
import mimetypes
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import unquote, urlsplit


PREVIEW_ROOT = Path(__file__).resolve().parent
BUILD_ROOT = PREVIEW_ROOT.parents[2] / "public" / "build"
ASSETS = {
    "/preview-assets/app.css": "resources/css/app.css",
    "/preview-assets/student.css": "resources/css/student.css",
    "/preview-assets/editor.js": "resources/js/editor.js",
}


class PreviewHandler(SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=str(PREVIEW_ROOT), **kwargs)

    def do_GET(self):
        request_path = unquote(urlsplit(self.path).path)
        if request_path in ASSETS:
            manifest = json.loads((BUILD_ROOT / "manifest.json").read_text())
            self.send_response(302)
            self.send_header("Location", "/build/" + manifest[ASSETS[request_path]]["file"])
            self.send_header("Cache-Control", "no-store")
            self.end_headers()
            return

        if request_path.startswith("/build/"):
            path = (BUILD_ROOT / request_path.removeprefix("/build/")).resolve()
            if not path.is_relative_to(BUILD_ROOT.resolve()) or not path.is_file():
                self.send_error(404)
                return
            self.send_response(200)
            self.send_header("Content-Type", mimetypes.guess_type(path)[0] or "application/octet-stream")
            self.send_header("Content-Length", str(path.stat().st_size))
            self.end_headers()
            self.wfile.write(path.read_bytes())
            return

        super().do_GET()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--port", type=int, default=8809)
    arguments = parser.parse_args()
    if not (BUILD_ROOT / "manifest.json").is_file():
        parser.error("Vite build missing. Run npm run build first.")
    server = ThreadingHTTPServer(("127.0.0.1", arguments.port), PreviewHandler)
    print(f"Workspace preview: http://127.0.0.1:{arguments.port}/", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()
