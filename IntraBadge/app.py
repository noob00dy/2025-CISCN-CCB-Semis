# -*- coding: utf-8 -*-
import base64
from flask import Flask, request, redirect, url_for, render_template, make_response
from jinja2 import TemplateError
from jinja2.sandbox import SandboxedEnvironment

from .utils import get_redis, safe_key, fetch_resource

app = Flask(__name__)
app.config["SECRET_KEY"] = "dev-only-secret"

rdb = get_redis()

MAX_AVATAR = 200000

_user_tpl_env = SandboxedEnvironment(
    autoescape=True,  
)


def render_user_template(tpl: str, **context) -> str:
    template = _user_tpl_env.from_string(tpl or "")
    return template.render(**context)


def _get_user():
    return safe_key(request.cookies.get("user", "guest"))


def _get_tpl(user):
    k = f"tpl:{user}"
    if not rdb.exists(k):
        rdb.set(
            k,
            """
<div class="badge">
  <div class="badge-left">
    <div class="avatar">
      {% if avatar_ok %}
        <img src="/avatar/file" alt="avatar"/>
      {% else %}
        <div class="avatar-ph">No Avatar</div>
      {% endif %}
    </div>
  </div>
  <div class="badge-right">
    <div class="title">{{ name }}</div>
    <div class="sub">IntraBadge · Internal</div>
    <div class="meta">Last refresh: {{ avatar_updated or "never" }}</div>
  </div>
</div>
""",
        )
    return (rdb.get(k) or b"").decode("utf-8", "ignore")


def _get_avatar_url(user):
    return (rdb.get(f"avatar_url:{user}") or b"").decode("utf-8", "ignore")


def _get_avatar_blob(user):
    data = rdb.get(f"avatarbin:{user}") or b""
    ctype = (rdb.get(f"avatarctype:{user}") or b"").decode("utf-8", "ignore")
    updated = (rdb.get(f"avatarupd:{user}") or b"").decode("utf-8", "ignore")
    return data, ctype, updated


@app.get("/prefs")
def prefs():
    u = safe_key(request.args.get("u", "guest"))
    resp = make_response(redirect(url_for("dashboard")))
    resp.set_cookie("user", u, max_age=86400, path="/")
    return resp


@app.get("/")
@app.get("/dashboard")
def dashboard():
    user = _get_user()
    tpl = _get_tpl(user)
    avatar_url = _get_avatar_url(user)
    avatar_data, avatar_ctype, avatar_updated = _get_avatar_blob(user)
    avatar_ok = avatar_ctype.startswith("image/") and len(avatar_data) > 0

    return render_template(
        "dashboard.html",
        user=user,
        tpl=tpl,
        avatar_url=avatar_url,
        avatar_ok=avatar_ok,
        avatar_ctype=avatar_ctype or "-",
        avatar_size=len(avatar_data),
        avatar_updated=avatar_updated or "-",
    )


@app.get("/template")
def template_edit():
    user = _get_user()
    tpl = _get_tpl(user)
    return render_template("template.html", user=user, tpl=tpl)


@app.post("/template")
def template_save():
    user = _get_user()
    tpl = request.form.get("tpl", "") or ""
    rdb.set(f"tpl:{user}", tpl[:5000])
    return redirect(url_for("preview"))


@app.post("/avatar")
def avatar_set():
    user = _get_user()
    url = (request.form.get("avatar_url", "") or "").strip()
    rdb.set(f"avatar_url:{user}", url[:2000])
    return redirect(url_for("dashboard"))


@app.post("/avatar/refresh")
def avatar_refresh():
    user = _get_user()
    url = _get_avatar_url(user)
    if not url:
        return redirect(url_for("dashboard"))

    try:
        data, ctype, meta = fetch_resource(url)
    except Exception:
        rdb.set(f"avatarbin:{user}", b"")
        rdb.set(f"avatarctype:{user}", "application/octet-stream")
        rdb.set(f"avatarupd:{user}", "fetch failed")
        return redirect(url_for("dashboard"))

    rdb.set(f"avatarbin:{user}", data[:MAX_AVATAR])
    rdb.set(f"avatarctype:{user}", ctype[:120])
    rdb.set(f"avatarupd:{user}", "just now")
    rdb.set(f"avatarmeta:{user}", str(meta)[:500])
    return redirect(url_for("dashboard"))


@app.get("/avatar/file")
def avatar_file():
    user = _get_user()
    data, ctype, _ = _get_avatar_blob(user)
    if not (ctype or "").startswith("image/"):
        return ("Unsupported avatar content-type", 415)
    if not data:
        return ("", 204)

    resp = make_response(data)
    resp.headers["Content-Type"] = ctype
    resp.headers["X-Content-Type-Options"] = "nosniff"
    return resp


@app.get("/preview")
def preview():
    user = _get_user()
    tpl = _get_tpl(user)
    avatar_url = _get_avatar_url(user)
    avatar_data, avatar_ctype, avatar_updated = _get_avatar_blob(user)
    avatar_ok = (avatar_ctype or "").startswith("image/") and len(avatar_data) > 0

    def avatar_raw_text():
        try:
            return (avatar_data[:2000]).decode("utf-8", "ignore")
        except Exception:
            return ""

    def avatar_raw_b64():
        return base64.b64encode(avatar_data[:5000]).decode("ascii", "ignore")

    try:
        rendered = render_user_template(
            tpl,
            name=user,
            avatar_url=avatar_url,
            avatar_ok=avatar_ok,
            avatar_ctype=avatar_ctype or "",
            avatar_updated=avatar_updated or "",
            avatar_size=len(avatar_data),
            avatar_raw_text=avatar_raw_text,
            avatar_raw_b64=avatar_raw_b64,
        )
    except TemplateError as e:
        rendered = (
            f"<div class='alert alert-danger'>Template error: {type(e).__name__}</div>"
        )

    return render_template(
        "preview.html",
        user=user,
        rendered=rendered,
        tpl=tpl,
        avatar_url=avatar_url,
        avatar_ctype=avatar_ctype or "-",
        avatar_size=len(avatar_data),
        avatar_updated=avatar_updated or "-",
    )


@app.get("/debug/diag")
def diag():
    meta = (rdb.get(f"avatarmeta:{_get_user()}") or b"").decode("utf-8", "ignore")
    return {
        "service": (rdb.get("service_name") or b"").decode("utf-8", "ignore"),
        "redis": "127.0.0.1:6379",
        "avatar_cache_hint": "Avatar is cached first. Non-image content won't be shown as image.",
        "meta": meta,
    }
