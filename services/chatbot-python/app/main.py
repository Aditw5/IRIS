from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, Dict, Any, List
import os

from app.pdf_loader import (
    list_docx_files,
    load_all_manual_chunks,
    extract_text_from_docx,
)
from app.text_search import detect_topic_from_filename
from app.semantic_search import semantic_search, build_best_answer
from app.intent_detector import (
    classify_database_intent,
    extract_search_keyword,
    generate_search_candidates,
)
from app.backend_client import (
    call_customer_history_order,
    call_customer_alat,
    call_customer_keranjang,
    call_customer_summary,
    call_customer_certificate_status,
    call_customer_profile,
    call_customer_history_order_group,
)
from app.nlp_utils import has_specific_search_target, normalize_text
from app.source_router import route_answer_source


app = FastAPI(
    title="ULAB Chatbot Service",
    version="0.1.0",
)

# =========================================================
# CORS
# =========================================================
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://127.0.0.1:5173",
        "https://ulabumro.id",
        "https://www.ulabumro.id",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

BASE_DIR = os.path.dirname(os.path.dirname(__file__))
MANUALS_DIR = os.path.join(BASE_DIR, "data", "manuals")


class ChatRequest(BaseModel):
    message: str
    kelompokUser: Optional[str] = None


# =========================================================
# BASIC ENDPOINT
# =========================================================

@app.get("/")
def root():
    return {
        "success": True,
        "message": "ULAB Chatbot Python service is running",
    }


@app.get("/manual/check")
def check_manuals():
    if not os.path.exists(MANUALS_DIR):
        return {
            "success": False,
            "message": "Folder manuals belum ditemukan",
            "manuals_dir": MANUALS_DIR,
        }

    docx_files = list_docx_files(MANUALS_DIR)

    if not docx_files:
        return {
            "success": False,
            "message": "Belum ada file DOCX di folder manuals",
            "manuals_dir": MANUALS_DIR,
            "files": [],
        }

    chunks = load_all_manual_chunks(MANUALS_DIR)

    return {
        "success": True,
        "message": "DOCX manuals ditemukan dan berhasil diproses",
        "manuals_dir": MANUALS_DIR,
        "total_files": len(docx_files),
        "files": docx_files,
        "total_chunks": len(chunks),
    }


@app.get("/manual/debug")
def debug_manuals():
    if not os.path.exists(MANUALS_DIR):
        return {
            "success": False,
            "message": "Folder manuals belum ditemukan",
            "manuals_dir": MANUALS_DIR,
        }

    docx_files = list_docx_files(MANUALS_DIR)
    results = []

    for file_name in docx_files:
        docx_path = os.path.join(MANUALS_DIR, file_name)

        try:
            text = extract_text_from_docx(docx_path)
            cleaned = " ".join(text.split())

            results.append({
                "file_name": file_name,
                "topic": detect_topic_from_filename(file_name),
                "text_length": len(cleaned),
                "preview": cleaned[:500],
            })
        except Exception as e:
            results.append({
                "file_name": file_name,
                "topic": detect_topic_from_filename(file_name),
                "text_length": 0,
                "preview": "",
                "error": str(e),
            })

    return {
        "success": True,
        "total_files": len(docx_files),
        "results": results,
    }


# =========================================================
# BACKEND RESPONSE HELPER
# =========================================================

def unwrap_laravel_response(backend_response: Dict[str, Any]) -> Dict[str, Any]:
    raw_data = backend_response.get("data") or {}

    if isinstance(raw_data, dict) and isinstance(raw_data.get("response"), dict):
        return raw_data.get("response") or {}

    if isinstance(raw_data, dict):
        return raw_data

    return {}


def get_backend_total_data(backend_response: Dict[str, Any]) -> int:
    data = unwrap_laravel_response(backend_response)

    possible_keys = [
        "totalData",
        "total_data",
        "total",
        "count",
    ]

    for key in possible_keys:
        try:
            if key in data:
                return int(data.get(key, 0) or 0)
        except Exception:
            pass

    try:
        if isinstance(data.get("data"), list):
            return len(data.get("data") or [])
    except Exception:
        pass

    return 0


def get_backend_summary(backend_response: Dict[str, Any], fallback_message: str) -> str:
    if not backend_response.get("success"):
        error = backend_response.get("error")
        if error:
            return f"Maaf, saya belum bisa mengambil data dari sistem. Detail: {error}"

        return "Maaf, saya belum bisa mengambil data dari sistem saat ini."

    data = unwrap_laravel_response(backend_response)

    if isinstance(data, dict):
        summary = data.get("chatbot_summary")
        if summary:
            return summary

        message = data.get("message")
        if message and not data.get("data"):
            return message

    return fallback_message


# =========================================================
# AUTH HEADER / COOKIE HELPER
# =========================================================

def get_forward_headers_from_request(request: Request) -> Dict[str, Optional[str]]:
    authorization_header = request.headers.get("Authorization")
    cookie_header = request.headers.get("Cookie")

    # Opsional untuk local development.
    # Jika frontend mengirim header token, bantu teruskan sebagai Authorization dan Cookie token.
    header_token = (
        request.headers.get("token")
        or request.headers.get("Token")
        or request.headers.get("x-token")
        or request.headers.get("X-Token")
    )

    if header_token:
        raw_token = header_token.strip()

        if not authorization_header:
            if raw_token.lower().startswith("bearer "):
                authorization_header = raw_token
            else:
                authorization_header = f"Bearer {raw_token}"

        if not cookie_header:
            if raw_token.lower().startswith("bearer "):
                raw_token = raw_token[7:].strip()

            cookie_header = f"token={raw_token}"

    return {
        "authorization_header": authorization_header,
        "cookie_header": cookie_header,
    }


# =========================================================
# DATABASE INTENT EXECUTOR
# =========================================================

def call_backend_by_intent(
    intent: str,
    search: str,
    authorization_header: Optional[str],
    cookie_header: Optional[str],
) -> Dict[str, Any]:
    if intent == "customer_history_order":
        return call_customer_history_order(
            search=search,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
            limit=20,
        )

    if intent == "customer_alat":
        return call_customer_alat(
            search=search,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
            limit=20,
        )

    if intent == "customer_keranjang":
        return call_customer_keranjang(
            search=search,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

    if intent == "customer_summary":
        return call_customer_summary(
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

    if intent == "customer_certificate_status":
        return call_customer_certificate_status(
            search=search,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
            limit=20,
        )

    if intent == "customer_profile":
        return call_customer_profile(
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

    if intent == "customer_history_order_group":
        return call_customer_history_order_group(
            search=search,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
            limit=20,
        )

    return {
        "success": False,
        "status_code": 0,
        "data": None,
        "error": f"Intent '{intent}' belum didukung.",
    }


def should_retry_with_candidates(intent: str) -> bool:
    return intent in {
        "customer_history_order",
        "customer_alat",
        "customer_keranjang",
        "customer_certificate_status",
        "customer_history_order_group",
    }


def intent_can_use_empty_search(intent: str) -> bool:
    return intent in {
        "customer_keranjang",
        "customer_alat",
        "customer_history_order_group",
        "customer_history_order",
        "customer_certificate_status",
    }


def intent_no_search_needed(intent: str) -> bool:
    return intent in {
        "customer_summary",
        "customer_profile",
    }


def fallback_database_intent(message: str) -> str:
    """
    Fallback jika source_router sudah memutuskan database,
    tetapi intent_detector belum menemukan endpoint spesifik.

    Ini mencegah pertanyaan data aktual jatuh ke manual.
    """

    text = normalize_text(message)

    if "keranjang" in text or "cart" in text:
        return "customer_keranjang"

    if (
        "sertifikat" in text
        or "sertipikat" in text
        or "certificate" in text
        or "laporan" in text
        or "dokumen" in text
        or "dicetak" in text
        or "download" in text
        or "unduh" in text
    ):
        return "customer_certificate_status"

    if (
        "profil" in text
        or "profile" in text
        or "akun saya" in text
        or "unit saya" in text
        or "perusahaan saya" in text
        or "jabatan saya" in text
    ):
        return "customer_profile"

    if (
        "ringkasan" in text
        or "summary" in text
        or "dashboard saya" in text
        or "total alat" in text
        or "total order" in text
    ):
        return "customer_summary"

    if (
        "order terakhir" in text
        or "pendaftaran terakhir" in text
        or "history pendaftaran" in text
        or "riwayat pendaftaran" in text
        or "history order saya" in text
        or "riwayat order saya" in text
        or "aktivitas saya" in text
    ):
        return "customer_history_order_group"

    if (
        "alat saya apa saja" in text
        or "alat saya ada apa saja" in text
        or "daftar alat saya" in text
        or "master alat saya" in text
        or "alat yang saya punya" in text
    ):
        return "customer_alat"

    # Default untuk data aktual customer:
    # status, progress, jumlah alat selesai/belum selesai, SN, order, dan sejenisnya.
    return "customer_history_order"


def execute_database_intent(
    intent: str,
    message: str,
    authorization_header: Optional[str],
    cookie_header: Optional[str],
) -> Dict[str, Any]:
    if intent_no_search_needed(intent):
        backend_response = call_backend_by_intent(
            intent=intent,
            search="",
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

        return {
            "backend_response": backend_response,
            "used_search_keyword": "",
            "search_candidates": [],
        }

    if intent_can_use_empty_search(intent) and not has_specific_search_target(message):
        backend_response = call_backend_by_intent(
            intent=intent,
            search="",
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

        return {
            "backend_response": backend_response,
            "used_search_keyword": "",
            "search_candidates": [],
        }

    search_candidates = generate_search_candidates(message)

    fallback_search = extract_search_keyword(message)
    if fallback_search and fallback_search not in search_candidates:
        search_candidates.append(fallback_search)

    if not search_candidates:
        search_candidates = [""]

    backend_response = None
    used_search_keyword = ""

    for candidate in search_candidates:
        result = call_backend_by_intent(
            intent=intent,
            search=candidate,
            authorization_header=authorization_header,
            cookie_header=cookie_header,
        )

        backend_response = result
        used_search_keyword = candidate

        if not should_retry_with_candidates(intent):
            break

        total_data = get_backend_total_data(result)

        if total_data > 0:
            break

    if backend_response is None:
        backend_response = {
            "success": False,
            "status_code": 0,
            "data": None,
        }

    return {
        "backend_response": backend_response,
        "used_search_keyword": used_search_keyword,
        "search_candidates": search_candidates,
    }


def run_database_mode(
    payload: ChatRequest,
    request: Request,
    source_route: Dict[str, Any],
) -> Dict[str, Any]:
    forwarded_headers = get_forward_headers_from_request(request)
    authorization_header = forwarded_headers["authorization_header"]
    cookie_header = forwarded_headers["cookie_header"]

    database_intent = classify_database_intent(
        message=payload.message,
        kelompok_user=payload.kelompokUser,
    )

    intent_fallback_used = False

    if not database_intent:
        database_intent = fallback_database_intent(payload.message)
        intent_fallback_used = True

    db_result = execute_database_intent(
        intent=database_intent,
        message=payload.message,
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )

    backend_response = db_result["backend_response"]
    used_search_keyword = db_result["used_search_keyword"]
    search_candidates = db_result["search_candidates"]

    answer = get_backend_summary(
        backend_response=backend_response,
        fallback_message=(
            f"Saya belum menemukan data yang sesuai "
            f"dengan kata kunci '{used_search_keyword}'."
        ),
    )

    return {
        "success": True,
        "mode": "database",
        "intent": database_intent,
        "intent_fallback_used": intent_fallback_used,
        "question": payload.message,
        "kelompokUser": payload.kelompokUser,
        "search": used_search_keyword,
        "search_candidates": search_candidates,
        "answer": answer,
        "router": source_route,
        "backend_status_code": backend_response.get("status_code"),
        "backend_url": backend_response.get("url"),
        "backend_response": backend_response.get("data"),
    }


# =========================================================
# MANUAL MODE
# =========================================================

def role_manual_exists(docx_files: List[str], kelompok_user: Optional[str]) -> bool:
    if not kelompok_user:
        return True

    target = kelompok_user.lower().strip()

    for file_name in docx_files:
        if detect_topic_from_filename(file_name) == target:
            return True

    return False


def normalize_role_name(role: Optional[str]) -> str:
    return str(role or "").strip().lower()


def is_registrasi_manual_chunk(chunk: Dict[str, Any], kelompok_user: Optional[str] = None) -> bool:
    """
    Khusus untuk manual Registrasi saja.
    Customer, Pelaksana, Manager, dan role lain tidak ikut memakai logika ini.
    """

    role = normalize_role_name(kelompok_user)
    file_name = str(chunk.get("file_name", "") or "").lower()

    if role == "registrasi":
        return True

    if "registrasi" in file_name:
        return True

    return False


def clean_full_manual_content_for_answer(
    content: str,
    remove_short_answer: bool = False,
) -> str:
    """
    Membersihkan isi section tanpa memotong langkah.

    Penting:
    - Tidak mengambil 5 kalimat pertama.
    - Tidak memotong langkah bernomor.
    - Untuk Registrasi, baris Jawaban singkat dibuang.
    - Metadata 'Pertanyaan yang dijawab' tidak dimunculkan ke user.
    """

    lines = str(content or "").splitlines()
    final_lines: List[str] = []

    for line in lines:
        clean = " ".join(str(line or "").strip().split())

        if not clean:
            continue

        lowered = clean.lower()

        if remove_short_answer:
            if lowered.startswith("jawaban singkat:"):
                continue

            if lowered.startswith("jawaban singkat untuk chatbot:"):
                continue

        # Ini metadata pencarian untuk chatbot, bukan isi jawaban yang perlu ditampilkan.
        if lowered.startswith("pertanyaan yang dijawab:"):
            continue

        final_lines.append(clean)

    return "\n".join(final_lines).strip()


def build_full_manual_answer(chunk: Dict[str, Any], kelompok_user: Optional[str] = None) -> str:
    """
    Mengembalikan 1 section manual secara penuh.
    Dipakai khusus untuk Registrasi agar semua langkah DOCX muncul lengkap.
    """

    heading = str(chunk.get("heading", "") or "").strip()

    content = (
        chunk.get("content")
        or chunk.get("text")
        or chunk.get("body")
        or ""
    )
    content = str(content or "").strip()

    remove_short_answer = is_registrasi_manual_chunk(chunk, kelompok_user)
    cleaned_content = clean_full_manual_content_for_answer(
        content=content,
        remove_short_answer=remove_short_answer,
    )

    if heading and cleaned_content:
        return f"{heading}\n{cleaned_content}"

    if cleaned_content:
        return cleaned_content

    return heading


def run_manual_mode(
    payload: ChatRequest,
    source_route: Dict[str, Any],
) -> Dict[str, Any]:
    if not os.path.exists(MANUALS_DIR):
        return {
            "success": False,
            "mode": "manual",
            "question": payload.message,
            "kelompokUser": payload.kelompokUser,
            "answer": "Folder manual belum tersedia.",
            "router": source_route,
            "matched_files": [],
            "sources": [],
        }

    docx_files = list_docx_files(MANUALS_DIR)

    if not docx_files:
        return {
            "success": False,
            "mode": "manual",
            "question": payload.message,
            "kelompokUser": payload.kelompokUser,
            "answer": "Belum ada file DOCX manual di folder data/manuals.",
            "router": source_route,
            "matched_files": [],
            "sources": [],
        }

    if payload.kelompokUser and not role_manual_exists(docx_files, payload.kelompokUser):
        return {
            "success": True,
            "mode": "manual",
            "question": payload.message,
            "kelompokUser": payload.kelompokUser,
            "answer": f"Manual untuk kelompok user '{payload.kelompokUser}' belum tersedia.",
            "router": source_route,
            "matched_files": [],
            "sources": [],
        }

    all_chunks = load_all_manual_chunks(MANUALS_DIR)

    results = semantic_search(
        query=payload.message,
        manuals_dir=MANUALS_DIR,
        all_chunks=all_chunks,
        kelompok_user=payload.kelompokUser,
        top_k=5,
    )

    if not results:
        return {
            "success": True,
            "mode": "manual",
            "question": payload.message,
            "kelompokUser": payload.kelompokUser,
            "answer": (
                "Saya belum menemukan jawaban yang relevan pada manual kelompok user ini. "
                "Coba gunakan pertanyaan yang lebih spesifik sesuai menu atau proses kerja."
            ),
            "router": source_route,
            "matched_files": [],
            "sources": [],
        }

    # Default lama tetap dipakai untuk customer, pelaksana, manager, dan role lain.
    answer = build_best_answer(payload.message, results)

    # Khusus Registrasi: jangan pakai ringkasan build_best_answer karena langkah DOCX bisa kepotong.
    # Ambil 1 section terbaik secara penuh agar langkah 1, 2, 3, dst terbaca semua.
    best_result = results[0]
    if is_registrasi_manual_chunk(best_result, payload.kelompokUser):
        full_answer = build_full_manual_answer(best_result, payload.kelompokUser)
        if full_answer:
            answer = full_answer

    matched_files = []
    for item in results:
        if item["file_name"] not in matched_files:
            matched_files.append(item["file_name"])

    simple_sources = []
    for item in results:
        simple_sources.append({
            "file_name": item["file_name"],
            "heading": item.get("heading", ""),
            "chunk_index": item["chunk_index"],
            "semantic_score": item.get("semantic_score"),
            "lexical_score": item.get("lexical_score"),
            "score": item.get("score"),
        })

    return {
        "success": True,
        "mode": "manual",
        "question": payload.message,
        "kelompokUser": payload.kelompokUser,
        "answer": answer,
        "router": source_route,
        "matched_files": matched_files,
        "sources": simple_sources,
    }


# =========================================================
# CLARIFY MODE
# =========================================================

def run_clarify_mode(
    payload: ChatRequest,
    source_route: Dict[str, Any],
) -> Dict[str, Any]:
    return {
        "success": True,
        "mode": "clarify",
        "question": payload.message,
        "kelompokUser": payload.kelompokUser,
        "answer": (
            "Saya perlu memastikan maksud pertanyaan Anda. "
            "Apakah Anda ingin melihat panduan penggunaan fitur, "
            "atau ingin mengecek data aktual pada akun Anda?"
        ),
        "router": source_route,
        "matched_files": [],
        "sources": [],
    }


# =========================================================
# ASK CHATBOT
# =========================================================

@app.post("/ask")
def ask_chatbot(payload: ChatRequest, request: Request):
    source_route = route_answer_source(
        message=payload.message,
        kelompok_user=payload.kelompokUser,
    )

    source = source_route.get("source")

    # =====================================================
    # 1. CLARIFY
    # Pertanyaan terlalu pendek/ambigu.
    # Contoh: "sertifikat", "keranjang", "progress"
    # =====================================================
    if source == "clarify":
        return run_clarify_mode(
            payload=payload,
            source_route=source_route,
        )

    # =====================================================
    # 2. DATABASE
    # Hanya jika source_router memutuskan user minta data aktual.
    # Contoh:
    # - Keranjang saya ada apa saja?
    # - Alat yang belum selesai ada berapa?
    # - Progress alat 7011669 sampai mana?
    # =====================================================
    if source == "database":
        return run_database_mode(
            payload=payload,
            request=request,
            source_route=source_route,
        )

    # =====================================================
    # 3. MANUAL
    # Default untuk pertanyaan cara/panduan.
    # Contoh:
    # - Bagaimana cara memasukkan alat ke keranjang?
    # - Bagaimana cara mencetak sertifikat?
    # - Cara melihat progress layanan bagaimana?
    # =====================================================
    return run_manual_mode(
        payload=payload,
        source_route=source_route,
    )