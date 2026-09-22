import re
from typing import Optional, Dict, Any, List

from app.nlp_utils import normalize_text, extract_identifier


def contains_any(text: str, keywords: List[str]) -> bool:
    text = normalize_text(text)

    for keyword in keywords:
        keyword = normalize_text(keyword)
        if keyword and keyword in text:
            return True

    return False


def has_identifier_or_specific_code(message: str) -> bool:
    text = normalize_text(message)

    if extract_identifier(text):
        return True

    # Contoh serial number / angka panjang
    if re.search(r"\b\d{5,}\b", text):
        return True

    # Contoh nomor order alat: JS-25.12.055
    if re.search(r"\b[a-z]{1,5}-\d{2}\.\d{1,2}\.\d{1,5}\b", text):
        return True

    # Contoh nomor pendaftaran: J-25-111
    if re.search(r"\b[a-z]{1,5}-\d{2}-\d{1,5}\b", text):
        return True

    # UUID / norec
    if re.search(
        r"\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b",
        text,
    ):
        return True

    return False


def is_how_to_question(message: str) -> bool:
    """
    True jika user bertanya cara/panduan/langkah.
    Ini harus dijawab dari DOCX/manual.
    """

    text = normalize_text(message)

    # Kalau ada SN/order/nomor spesifik, biasanya user minta data aktual.
    # Contoh: progress alat 7011669 sampai mana?
    if has_identifier_or_specific_code(text):
        return False

    how_to_patterns = [
        "bagaimana cara",
        "gimana cara",
        "cara ",
        "caranya",
        "langkah",
        "langkah langkah",
        "panduan",
        "tutorial",
        "petunjuk",
        "prosedur",
        "alur",
        "bagaimana menggunakan",
        "cara menggunakan",
        "bagaimana memakai",
        "cara memakai",
        "bagaimana membuka",
        "cara membuka",
        "bagaimana mengakses",
        "cara mengakses",
        "bagaimana login",
        "cara login",
        "bagaimana daftar",
        "cara daftar",
        "bagaimana registrasi",
        "cara registrasi",
        "bagaimana menambahkan",
        "cara menambahkan",
        "bagaimana memasukkan",
        "cara memasukkan",
        "bagaimana masukin",
        "cara masukin",
        "bagaimana checkout",
        "cara checkout",
        "bagaimana mencetak",
        "cara mencetak",
        "cara cetak",
        "bagaimana print",
        "cara print",
        "bagaimana download",
        "cara download",
        "bagaimana unduh",
        "cara unduh",
        "bagaimana isi",
        "cara isi",
        "bagaimana mengisi",
        "cara mengisi",
        "bagaimana melihat",
        "cara melihat",
        "cara lihat",
    ]

    if contains_any(text, how_to_patterns):
        return True

    # Fungsi/kegunaan juga manual.
    function_patterns = [
        "fungsi",
        "kegunaan",
        "untuk apa",
        "apa fungsi",
        "apa kegunaan",
        "menu ini untuk apa",
        "qr untuk apa",
        "barcode untuk apa",
    ]

    if contains_any(text, function_patterns):
        return True

    return False


def is_data_lookup_question(message: str) -> bool:
    """
    True jika user meminta data aktual dari database.
    """

    text = normalize_text(message)

    if has_identifier_or_specific_code(text):
        return True

    database_patterns = [
        # keranjang aktual
        "keranjang saya",
        "isi keranjang saya",
        "keranjang saya ada apa",
        "keranjang saya ada apa saja",
        "keranjang saya ada berapa",
        "berapa isi keranjang",
        "berapa alat di keranjang",
        "apa saja di keranjang",
        "cart saya",

        # alat aktual
        "alat saya apa saja",
        "alat saya ada apa saja",
        "alat saya ada berapa",
        "berapa alat saya",
        "daftar alat saya",
        "master alat saya",
        "alat yang saya punya",
        "saya punya alat apa",

        # jumlah/status alat tanpa kata saya
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

        # order / pendaftaran aktual
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

        # status/progress aktual
        "status alat",
        "progress alat",
        "progres alat",
        "status order",
        "progress order",
        "progres order",
        "status layanan",
        "progress layanan",
        "progres layanan",
        "sampai mana",
        "sudah selesai",
        "belum selesai",
        "sedang dikerjakan",
        "sedang diproses",

        # sertifikat/laporan aktual
        "sertifikat saya",
        "sertifikat alat saya",
        "sertifikat sudah tersedia",
        "sertifikat belum tersedia",
        "sertifikat sudah ada",
        "sertifikat belum ada",
        "sertifikat sudah bisa dicetak",
        "sertifikat belum bisa dicetak",
        "sudah bisa dicetak belum",
        "bisa dicetak belum",
        "laporan repair sudah tersedia",
        "laporan repair belum tersedia",
    ]

    if contains_any(text, database_patterns):
        return True

    # Pola umum jumlah data aktual
    if contains_any(text, ["berapa", "ada berapa", "jumlah"]):
        if contains_any(text, ["alat", "order", "pesanan", "pendaftaran", "layanan", "keranjang"]):
            return True

    # Pola umum: "saya" + permintaan data aktual
    if "saya" in text and contains_any(text, [
        "apa saja",
        "ada apa",
        "ada berapa",
        "berapa",
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


def is_ambiguous_question(message: str) -> bool:
    """
    True jika pertanyaan terlalu pendek dan bisa bermakna manual atau database.
    """

    text = normalize_text(message)

    ambiguous_exact = [
        "keranjang",
        "sertifikat",
        "progress",
        "progres",
        "status",
        "order",
        "alat",
        "survey",
        "survei",
        "checkout",
        "registrasi",
        "login",
    ]

    if text in ambiguous_exact:
        return True

    words = text.split()

    if len(words) <= 2 and contains_any(text, ambiguous_exact):
        return True

    return False


def route_answer_source(message: str, kelompok_user: Optional[str] = None) -> Dict[str, Any]:
    """
    Output utama:
    - source: manual / database / clarify
    - confidence: nilai keyakinan
    - reason: alasan routing
    """

    text = normalize_text(message)
    role = normalize_text(kelompok_user or "")

    if not text:
        return {
            "source": "clarify",
            "confidence": 0.0,
            "reason": "Pertanyaan kosong.",
        }

    if is_how_to_question(text):
        return {
            "source": "manual",
            "confidence": 0.95,
            "reason": "Pertanyaan berisi pola cara/panduan/langkah sehingga dijawab dari manual DOCX.",
        }

    if is_data_lookup_question(text):
        return {
            "source": "database",
            "confidence": 0.95,
            "reason": "Pertanyaan meminta data aktual/status/jumlah sehingga dijawab dari database.",
        }

    if is_ambiguous_question(text):
        return {
            "source": "clarify",
            "confidence": 0.55,
            "reason": "Pertanyaan terlalu singkat dan bisa berarti panduan atau pengecekan data aktual.",
        }

    # Untuk role selain customer, sementara default ke manual.
    if role and role != "customer":
        return {
            "source": "manual",
            "confidence": 0.75,
            "reason": "Kelompok user selain customer diarahkan ke manual sesuai file role.",
        }

    # Default customer kalau tidak jelas: manual dulu.
    return {
        "source": "manual",
        "confidence": 0.65,
        "reason": "Tidak terdeteksi sebagai permintaan data aktual, sehingga diarahkan ke manual.",
    }