import os
from typing import Dict, Any, Optional

import requests
from dotenv import load_dotenv


# ============================================================
# LOAD ENV
# ============================================================

BASE_DIR = os.path.dirname(os.path.dirname(__file__))

ENV_CANDIDATES = [
    os.path.join(BASE_DIR, ".env"),
    os.path.join(BASE_DIR, "data", ".env"),
    os.path.join(os.getcwd(), ".env"),
]

for env_path in ENV_CANDIDATES:
    if os.path.exists(env_path):
        load_dotenv(env_path)
        break
else:
    load_dotenv()


BACKEND_BASE_URL = os.getenv("BACKEND_BASE_URL", "http://localhost:8000").rstrip("/")


# ============================================================
# CORE HTTP CLIENT
# ============================================================

def post_backend(
    path: str,
    payload: Optional[Dict[str, Any]] = None,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
    timeout: int = 30,
) -> Dict[str, Any]:
    url = f"{BACKEND_BASE_URL}{path}"

    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
    }

    if authorization_header:
        headers["Authorization"] = authorization_header

    if cookie_header:
        headers["Cookie"] = cookie_header

    try:
        response = requests.post(
            url,
            json=payload or {},
            headers=headers,
            timeout=timeout,
        )

        try:
            data = response.json()
        except Exception:
            data = {
                "raw": response.text
            }

        return {
            "success": 200 <= response.status_code < 300,
            "status_code": response.status_code,
            "url": url,
            "data": data,
        }

    except requests.exceptions.RequestException as e:
        return {
            "success": False,
            "status_code": 0,
            "url": url,
            "error": str(e),
            "data": None,
        }


# ============================================================
# CUSTOMER ENDPOINTS ONLY
# ============================================================

def call_customer_history_order(
    search: str,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
    limit: int = 20,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/history-order",
        payload={
            "search": search,
            "limit": limit,
        },
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_alat(
    search: str,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
    limit: int = 20,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/alat",
        payload={
            "search": search,
            "limit": limit,
        },
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_keranjang(
    search: str,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/keranjang",
        payload={
            "search": search,
        },
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_summary(
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/summary",
        payload={},
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_certificate_status(
    search: str,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
    limit: int = 20,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/certificate-status",
        payload={
            "search": search,
            "limit": limit,
        },
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_profile(
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/profile",
        payload={},
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )


def call_customer_history_order_group(
    search: str,
    authorization_header: Optional[str] = None,
    cookie_header: Optional[str] = None,
    limit: int = 20,
) -> Dict[str, Any]:
    return post_backend(
        path="/service/chatbot/customer/history-order-group",
        payload={
            "search": search,
            "limit": limit,
        },
        authorization_header=authorization_header,
        cookie_header=cookie_header,
    )