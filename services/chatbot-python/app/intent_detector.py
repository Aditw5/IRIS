import re
from typing import List, Optional

from app.nlp_utils import (
    normalize_text,
    generate_search_candidates_from_message,
    extract_identifier,
)


# ============================================================
# BASIC HELPER
# ============================================================

def normalize_role(kelompok_user: Optional[str]) -> str:
    return normalize_text(kelompok_user or "")


def contains_any(text: str, keywords: List[str]) -> bool:
    normalized = normalize_text(text)

    for keyword in keywords:
        keyword = normalize_text(keyword)
        if keyword and keyword in normalized:
            return True

    return False


def has_identifier_or_specific_code(message: str) -> bool:
    """
    True jika pertanyaan punya nomor order, nomor registrasi,
    serial number, norec, UUID, atau angka panjang.
    Ini biasanya berarti user sedang minta data aktual dari database.
    """

    text = normalize_text(message)

    if extract_identifier(text):
        return True

    # Nomor order alat contoh: JS-25.12.055
    if re.search(r"\b[a-z]{1,5}-\d{2}\.\d{1,2}\.\d{1,5}\b", text):
        return True

    # Nomor pendaftaran contoh: J-25-111
    if re.search(r"\b[a-z]{1,5}-\d{2}-\d{1,5}\b", text):
        return True

    # UUID / norec
    if re.search(
        r"\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b",
        text,
    ):
        return True

    # Angka panjang biasanya SN
    if re.search(r"\b\d{5,}\b", text):
        return True

    return False


# ============================================================
# SEARCH KEYWORD
# ============================================================

def extract_search_keyword(message: str) -> str:
    """
    Ambil kata kunci pencarian untuk database.
    """

    identifier = extract_identifier(message)
    if identifier:
        return identifier

    candidates = generate_search_candidates_from_message(message)
    if candidates:
        return candidates[0]

    normalized = normalize_text(message)

    remove_words = [
        "bagaimana",
        "gimana",
        "cara",
        "cek",
        "lihat",
        "melihat",
        "status",
        "progress",
        "progres",
        "sampai",
        "mana",
        "alat",
        "saya",
        "aku",
        "punya",
        "order",
        "pesanan",
        "sertifikat",
        "sertipikat",
        "certificate",
        "laporan",
        "repair",
        "keranjang",
        "apa",
        "saja",
        "ada",
        "terakhir",
        "terbaru",
        "sudah",
        "belum",
        "bisa",
        "dicetak",
        "cetak",
        "print",
        "download",
        "unduh",
        "masukkan",
        "memasukkan",
        "input",
        "tambah",
        "menambahkan",
        "berapa",
        "jumlah",
        "yang",
        "masih",
        "selesai",
        "dikerjakan",
        "diproses",
        "proses",
    ]

    words = normalized.split()
    important = [word for word in words if word not in remove_words]

    return " ".join(important).strip()


def generate_search_candidates(message: str) -> List[str]:
    candidates = generate_search_candidates_from_message(message)

    fallback = extract_search_keyword(message)
    if fallback and fallback not in candidates:
        candidates.append(fallback)

    final = []
    for candidate in candidates:
        candidate = candidate.strip()
        if candidate and candidate not in final:
            final.append(candidate)

    return final


# ============================================================
# MANUAL / FAQ DETECTOR
# ============================================================

def is_manual_howto_question(message: str) -> bool:
    """
    True jika user bertanya cara/panduan/langkah.
    Ini harus dijawab dari DOCX/manual, bukan database.

    Contoh manual:
    - Bagaimana cara memasukkan alat ke keranjang?
    - Bagaimana cara mencetak sertifikat?
    - Cara melihat progress layanan?
    - Cara isi survey kepuasan?
    """

    text = normalize_text(message)

    # Kalau ada nomor order/SN/kode spesifik, biasanya data aktual.
    # Contoh: progress alat 7011669 sampai mana?
    if has_identifier_or_specific_code(text):
        return False

    manual_prefix_patterns = [
        "bagaimana cara",
        "gimana cara",
        "cara ",
        "langkah",
        "langkah langkah",
        "panduan",
        "tutorial",
        "petunjuk",
        "prosedur",
        "alur",
        "bagaimana langkah",
        "gimana langkah",
        "bagaimana menggunakan",
        "cara menggunakan",
        "bagaimana memakai",
        "cara memakai",
        "cara membuka",
        "cara masuk",
        "cara mengakses",
    ]

    if contains_any(text, manual_prefix_patterns):
        return True

    manual_action_patterns = [
        # akun
        "cara login",
        "cara masuk",
        "cara daftar",
        "cara registrasi",
        "cara signup",
        "cara sign up",
        "cara melengkapi profil",
        "cara ubah profil",
        "cara mengubah profil",
        "cara merubah profil",

        # alat
        "cara tambah alat",
        "cara menambahkan alat",
        "cara input alat",
        "cara input master alat",
        "cara upload alat",

        # keranjang / order
        "cara memasukkan alat ke keranjang",
        "cara masukkan alat ke keranjang",
        "cara masukin alat ke keranjang",
        "cara tambah ke keranjang",
        "cara menambahkan ke keranjang",
        "cara order",
        "cara order alat",
        "cara order kalibrasi",
        "cara order repair",
        "cara checkout",
        "cara checkout order",

        # status / history
        "cara melihat status",
        "cara lihat status",
        "cara melihat progress",
        "cara lihat progress",
        "cara melihat progres",
        "cara lihat progres",
        "cara membuka detail",
        "cara lihat history",
        "cara melihat history",
        "cara lihat aktivitas",
        "cara melihat aktivitas",

        # sertifikat / laporan
        "cara cetak sertifikat",
        "cara mencetak sertifikat",
        "cara print sertifikat",
        "cara download sertifikat",
        "cara unduh sertifikat",
        "cara cetak laporan",
        "cara mencetak laporan",
        "cara cetak laporan repair",
        "cara scan qr",
        "cara scan barcode",

        # survey
        "cara isi survey",
        "cara mengisi survey",
        "cara isi survei",
        "cara mengisi survei",
        "cara isi survey kepuasan",
        "cara mengisi survey kepuasan",
    ]

    if contains_any(text, manual_action_patterns):
        return True

    # Pertanyaan fungsi/kegunaan cenderung manual.
    if contains_any(text, [
        "fungsi",
        "kegunaan",
        "untuk apa",
        "apa fungsi",
        "apa kegunaan",
        "barcode untuk apa",
        "qr untuk apa",
    ]):
        return True

    # Pola "bagaimana + kata kerja operasional" juga manual.
    if text.startswith("bagaimana") or text.startswith("gimana"):
        action_words = [
            "login",
            "masuk",
            "registrasi",
            "daftar",
            "signup",
            "sign up",
            "melengkapi",
            "mengisi",
            "isi",
            "ubah",
            "merubah",
            "mengubah",
            "menambahkan",
            "tambah",
            "input",
            "order",
            "checkout",
            "memasukkan",
            "masukkan",
            "masukin",
            "melihat",
            "membuka",
            "scan",
            "cetak",
            "mencetak",
            "print",
            "download",
            "unduh",
            "survey",
            "survei",
        ]

        if contains_any(text, action_words):
            return True

    return False


def is_actual_data_question(message: str) -> bool:
    """
    True jika user meminta data aktual dari database.

    Contoh:
    - Keranjang saya ada apa saja?
    - Keranjang saya ada berapa?
    - Alat yang belum selesai ada berapa?
    - Alat saya yang masih progress ada berapa?
    - Order terakhir saya apa?
    - Progress alat 7011669 sampai mana?
    """

    text = normalize_text(message)

    if has_identifier_or_specific_code(text):
        return True

    actual_data_patterns = [
        # keranjang aktual
        "keranjang saya",
        "isi keranjang saya",
        "keranjang saya ada apa",
        "keranjang saya ada apa saja",
        "keranjang saya ada berapa",
        "berapa isi keranjang",
        "berapa alat di keranjang",
        "apa saja di keranjang saya",
        "alat di keranjang saya",
        "cart saya",

        # alat aktual dengan kata saya
        "alat saya apa saja",
        "alat saya ada apa saja",
        "alat saya ada berapa",
        "berapa alat saya",
        "daftar alat saya",
        "master alat saya",
        "alat yang saya punya",
        "saya punya alat apa",
        "alat saya yang",

        # alat aktual tanpa kata saya
        "alat yang belum selesai",
        "alat belum selesai",
        "alat yang masih progress",
        "alat masih progress",
        "alat yang masih progres",
        "alat masih progres",
        "alat yang sedang dikerjakan",
        "alat sedang dikerjakan",
        "alat yang sedang diproses",
        "alat sedang diproses",
        "alat yang sudah selesai",
        "alat sudah selesai",
        "berapa alat yang belum selesai",
        "berapa alat belum selesai",
        "berapa alat yang masih progress",
        "berapa alat masih progress",
        "berapa alat yang masih progres",
        "berapa alat masih progres",
        "berapa alat yang sedang dikerjakan",
        "berapa alat sedang dikerjakan",
        "berapa alat yang sedang diproses",
        "berapa alat sedang diproses",
        "berapa alat yang sudah selesai",
        "berapa alat sudah selesai",
        "jumlah alat belum selesai",
        "jumlah alat masih progress",
        "jumlah alat masih progres",
        "jumlah alat sedang dikerjakan",
        "jumlah alat sedang diproses",
        "jumlah alat sudah selesai",

        # order aktual
        "order saya apa saja",
        "order saya ada berapa",
        "order terakhir saya",
        "pendaftaran terakhir saya",
        "history order saya",
        "riwayat order saya",
        "history pendaftaran saya",
        "riwayat pendaftaran saya",
        "aktivitas saya",
        "nomor registrasi saya",
        "no registrasi saya",
        "order yang belum selesai",
        "order belum selesai",
        "order yang masih progress",
        "order masih progress",
        "order yang sudah selesai",
        "order sudah selesai",
        "berapa order belum selesai",
        "berapa order yang belum selesai",
        "berapa order masih progress",
        "berapa order yang masih progress",
        "berapa order sudah selesai",
        "berapa order yang sudah selesai",
        "jumlah order belum selesai",
        "jumlah order masih progress",
        "jumlah order sudah selesai",

        # progress/status aktual
        "status alat saya",
        "progress alat saya",
        "progres alat saya",
        "alat saya yang masih progress",
        "alat saya yang masih progres",
        "alat saya yang sedang dikerjakan",
        "alat saya yang belum selesai",
        "alat saya yang sudah selesai",
        "berapa alat yang masih progress",
        "berapa alat yang masih progres",
        "berapa alat yang sudah selesai",
        "berapa alat yang belum selesai",
        "status layanan saya",
        "progress layanan saya",

        # sertifikat aktual
        "sertifikat saya",
        "sertifikat alat saya",
        "sertifikat sudah tersedia",
        "sertifikat belum tersedia",
        "sertifikat sudah ada",
        "sertifikat belum ada",
        "sertifikat sudah bisa dicetak",
        "sertifikat belum bisa dicetak",
        "bisa dicetak belum",
        "sudah bisa dicetak belum",
        "laporan repair sudah tersedia",
        "laporan repair belum tersedia",
    ]

    if contains_any(text, actual_data_patterns):
        return True

    # Pola jumlah/status aktual tanpa kata "saya".
    # Contoh: "Alat yang belum selesai ada berapa?"
    if contains_any(text, ["berapa", "jumlah", "ada berapa"]):
        if contains_any(text, ["alat", "order", "pesanan", "pendaftaran", "layanan"]):
            if contains_any(text, [
                "belum selesai",
                "masih progress",
                "masih progres",
                "sedang dikerjakan",
                "sedang diproses",
                "sudah selesai",
                "selesai",
                "belum verif",
                "belum verifikasi",
                "sudah verif",
                "sudah verifikasi",
                "keranjang",
            ]):
                return True

    # Pola umum: ada kata "saya" + permintaan jumlah/data aktual.
    if "saya" in text and contains_any(text, [
        "ada berapa",
        "berapa",
        "apa saja",
        "ada apa",
        "terakhir",
        "status",
        "progress",
        "progres",
        "sudah selesai",
        "belum selesai",
        "sedang dikerjakan",
        "tersedia",
        "belum tersedia",
        "sudah tersedia",
    ]):
        return True

    return False


# ============================================================
# CUSTOMER DATABASE INTENT
# ============================================================

def classify_customer_database_intent(
    message: str,
    kelompok_user: Optional[str] = None,
) -> Optional[str]:
    role = normalize_role(kelompok_user)
    text = normalize_text(message)

    if role and role != "customer":
        return None

    # ========================================================
    # PRIORITAS 1:
    # Kalau pertanyaan adalah cara/panduan/langkah,
    # WAJIB masuk manual DOCX, bukan database.
    # ========================================================
    if is_manual_howto_question(text):
        return None

    # ========================================================
    # PRIORITAS 2:
    # Jika bukan permintaan data aktual, jangan paksa database.
    # Ini mencegah kata "keranjang", "sertifikat", "progress"
    # langsung dilempar ke endpoint database.
    # ========================================================
    if not is_actual_data_question(text):
        return None

    # Profil customer aktual
    if contains_any(text, [
        "profil saya",
        "profile saya",
        "akun saya",
        "saya terdaftar sebagai apa",
        "unit saya",
        "perusahaan saya",
        "jabatan saya",
        "data customer saya",
    ]):
        return "customer_profile"

    # Summary / dashboard customer aktual
    if contains_any(text, [
        "ringkasan saya",
        "dashboard saya",
        "summary saya",
        "total alat",
        "berapa alat saya",
        "berapa order saya",
        "jumlah order",
    ]):
        return "customer_summary"

    # Keranjang aktual
    if contains_any(text, [
        "keranjang saya",
        "isi keranjang saya",
        "alat di keranjang saya",
        "apa saja di keranjang saya",
        "keranjang saya ada apa",
        "keranjang saya ada berapa",
        "berapa isi keranjang",
        "berapa alat di keranjang",
        "cart saya",
    ]):
        return "customer_keranjang"

    # Alat customer aktual
    if contains_any(text, [
        "alat saya apa saja",
        "alat saya ada apa saja",
        "alat saya ada berapa",
        "berapa alat saya",
        "daftar alat saya",
        "master alat saya",
        "alat yang saya punya",
        "saya punya alat apa",
        "apakah saya punya",
    ]):
        return "customer_alat"

    # Sertifikat / laporan aktual
    if contains_any(text, [
        "sertifikat",
        "sertipikat",
        "certificate",
        "laporan repair",
        "laporan",
        "dokumen",
        "dicetak",
        "download",
        "unduh",
    ]):
        return "customer_certificate_status"

    # History/order group aktual
    if contains_any(text, [
        "order terakhir",
        "pendaftaran terakhir",
        "nomor registrasi saya",
        "no registrasi saya",
        "daftar pendaftaran",
        "history pendaftaran",
        "riwayat pendaftaran",
        "aktivitas saya",
        "history order saya",
        "riwayat order saya",
        "order saya",
    ]):
        return "customer_history_order_group"

    # Progress/status aktual, termasuk pertanyaan jumlah alat belum selesai.
    if contains_any(text, [
        "progress",
        "progres",
        "status",
        "sampai mana",
        "sudah selesai",
        "belum selesai",
        "pengerjaan",
        "sedang dikerjakan",
        "sedang diproses",
        "masih progress",
        "masih progres",
        "status layanan",
        "berapa alat",
        "jumlah alat",
        "berapa order",
        "jumlah order",
    ]):
        return "customer_history_order"

    # Kalau ada identifier tetapi tidak masuk kondisi lain,
    # paling aman cek history order.
    if has_identifier_or_specific_code(text):
        return "customer_history_order"

    return None


# ============================================================
# GENERAL DATABASE INTENT
# ============================================================

def classify_database_intent(
    message: str,
    kelompok_user: Optional[str] = None,
) -> Optional[str]:
    role = normalize_role(kelompok_user)

    # Untuk saat ini database hanya customer.
    # Role lain diarahkan ke manual DOCX.
    if role and role != "customer":
        return None

    return classify_customer_database_intent(message, kelompok_user or "customer")