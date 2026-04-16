# -*- coding: utf-8 -*-
import re
from urllib.parse import urlparse

import redis
import requests

REDIS_HOST = "127.0.0.1"
REDIS_PORT = 6379


def get_redis():
    return redis.Redis(host=REDIS_HOST, port=REDIS_PORT, db=0, socket_timeout=1)


def safe_key(s: str) -> str:
    s = (s or "").strip()
    if not s:
        return "guest"
    s = re.sub(r"[^a-zA-Z0-9_\-]", "_", s)[:32]
    return s or "guest"


def fetch_resource(url: str, timeout: float = 2.0):
    u = urlparse((url or "").strip())
    scheme = (u.scheme or "").lower()

    if scheme in ("http", "https"):
        resp = requests.get(url, timeout=timeout, allow_redirects=True)
        ctype = resp.headers.get("Content-Type", "application/octet-stream")
        data = (resp.content or b"")[:200000]
        return data, ctype, {"scheme": scheme, "status": resp.status_code}

    if scheme == "redis":
        host = u.hostname or "127.0.0.1"
        port = u.port or 6379
        path = (u.path or "/").lstrip("/")
        parts = path.split("/", 1)
        db = int(parts[0]) if parts and parts[0].isdigit() else 0
        key = parts[1] if len(parts) > 1 else ""

        r = redis.Redis(host=host, port=port, db=db, socket_timeout=1)
        val = r.get(key) or b""
        return val[:200000], "application/octet-stream", {"scheme": "redis", "db": db, "key": key}

    raise ValueError("unsupported scheme")
