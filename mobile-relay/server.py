import asyncio
import hmac
import logging
import os
from pathlib import Path
from typing import List, Optional

import httpx
from dotenv import load_dotenv
from fastapi import APIRouter, FastAPI, Header, HTTPException
from fastapi.responses import FileResponse
from pydantic import BaseModel
from starlette.middleware.cors import CORSMiddleware

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / ".env")

logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(name)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)

ESURAT_API_URL = os.environ["ESURAT_API_URL"].rstrip("/")
PUSH_RELAY_SECRET = os.environ["PUSH_RELAY_SECRET"]
PUSH_BASE_URL = "https://integrations.emergentagent.com"
PUSH_KEY = os.environ.get("EMERGENT_PUSH_KEY", "placeholder")
SETUP_SH = Path(os.environ.get("SETUP_SH_PATH", str(ROOT_DIR.parent / "esurat-laravel" / "public" / "setup.sh")))

_push = httpx.AsyncClient(base_url=PUSH_BASE_URL, headers={"X-Push-Key": PUSH_KEY}, timeout=10.0)
_esurat = httpx.AsyncClient(base_url=ESURAT_API_URL, timeout=15.0)

app = FastAPI()
api = APIRouter(prefix="/api")


@app.get("/health", include_in_schema=False)
@app.head("/health", include_in_schema=False)
async def health():
    return {"status": "ok"}


@api.get("/")
async def root():
    return {"app": "E-Surat Tabanan mobile relay", "esurat": ESURAT_API_URL}


def push_user_id(esurat_user_id) -> str:
    return f"esurat-{esurat_user_id}"


class RegisterPushBody(BaseModel):
    platform: str
    device_token: str


@api.post("/register-push", status_code=201)
async def register_push(body: RegisterPushBody, authorization: Optional[str] = Header(None)):
    """Daftarkan token perangkat. User diambil dari token login E-Surat, bukan dari body."""
    if not authorization or not authorization.lower().startswith("bearer "):
        raise HTTPException(401, "Token login diperlukan")
    me = await _esurat.get("/api/m/me", headers={"Authorization": authorization, "Accept": "application/json"})
    if me.status_code != 200:
        raise HTTPException(401, "Sesi E-Surat tidak valid")
    uid = me.json().get("user", {}).get("id")
    if not uid:
        raise HTTPException(401, "Sesi E-Surat tidak valid")
    resp = await _push.post("/api/v1/push/users/register", json={
        "user_id": push_user_id(uid), "platform": body.platform, "device_token": body.device_token,
    })
    if resp.status_code == 401:
        raise HTTPException(500, "EMERGENT_PUSH_KEY missing or invalid")
    if resp.status_code >= 500:
        raise HTTPException(502, "Push provider unavailable")
    resp.raise_for_status()
    return {"status": "registered", "user_id": push_user_id(uid)}


async def send_push(recipients: List[str], data: dict, idempotency_key: Optional[str] = None, _attempt: int = 0) -> None:
    if not recipients:
        return
    if len(recipients) > 100:
        raise ValueError("max 100 recipients per /trigger call; chunk before sending")
    if "title" not in data or "message" not in data:
        raise ValueError("data must include title and message")
    payload: dict = {"recipients": recipients, "data": data}
    if idempotency_key:
        payload["$idempotency_key"] = idempotency_key
    resp = await _push.post("/api/v1/push/trigger", json=payload)
    if resp.status_code == 401:
        raise HTTPException(500, "EMERGENT_PUSH_KEY missing or invalid")
    code = resp.json().get("code", "") if resp.headers.get("content-type", "").startswith("application/json") else ""
    if code == "idempotency_key_in_progress" and idempotency_key and _attempt < 3:
        await asyncio.sleep(3)
        return await send_push(recipients, data, idempotency_key=idempotency_key, _attempt=_attempt + 1)
    if code in ("insufficient_credits", "integration_disabled", "daily_limit_reached"):
        logger.warning(f"Push paused: {code} ({resp.status_code}): {resp.json().get('message', '')}")
        return
    if resp.status_code >= 500:
        raise HTTPException(502, "Push provider unavailable")
    resp.raise_for_status()


class RelayBody(BaseModel):
    user_ids: List[int]
    title: str
    message: Optional[str] = ""
    url: Optional[str] = None


@api.post("/push/send")
async def relay_send(body: RelayBody, x_relay_secret: Optional[str] = Header(None)):
    """Dipanggil server Laravel E-Surat (PushNotifier) saat ada notifikasi surat/disposisi."""
    if not x_relay_secret or not hmac.compare_digest(x_relay_secret, PUSH_RELAY_SECRET):
        raise HTTPException(403, "Relay secret tidak valid")
    recipients = list(dict.fromkeys(push_user_id(i) for i in body.user_ids))
    data = {"title": body.title, "message": body.message or " "}
    if body.url:
        data["action_url"] = body.url
    sent = 0
    for i in range(0, len(recipients), 100):
        chunk = recipients[i:i + 100]
        try:
            await send_push(chunk, data)
            sent += len(chunk)
        except Exception as e:  # push gagal tidak boleh memblokir alur surat
            logger.warning(f"Push failed (non-blocking): {e}")
    return {"status": True, "sent": sent}


@api.get("/download/setup.sh")
async def download_setup():
    if not SETUP_SH.is_file():
        raise HTTPException(404, "setup.sh belum dibuat")
    return FileResponse(SETUP_SH, media_type="application/x-sh", filename="setup.sh")


app.include_router(api)
app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get("CORS_ORIGINS", "*").split(","),
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.on_event("shutdown")
async def shutdown():
    await _push.aclose()
    await _esurat.aclose()
