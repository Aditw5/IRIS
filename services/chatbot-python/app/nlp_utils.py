import re
from typing import List
from rapidfuzz import process, fuzz

try:
    from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

    _stemmer = StemmerFactory().create_stemmer()
except Exception:
    _stemmer = None


SYNONYM_MAP = {
    # progress / status
    "progres": "progress",
    "progressnya": "progress",
    "progresnya": "progress",
    "statusnya": "status",
    "sampe": "sampai",
    "sampi": "sampai",
    "smpe": "sampai",
    "smpek": "sampai",
    "sdh": "sudah",
    "udh": "sudah",
    "udah": "sudah",
    "blm": "belum",
    "belom": "belum",
    "gmn": "gimana",

    # sertifikat / dokumen
    "sertipikat": "sertifikat",
    "sertfikat": "sertifikat",
    "sertifikatnya": "sertifikat",
    "certificate": "sertifikat",
    "certifikat": "sertifikat",
    "certif": "sertifikat",
    "dok": "dokumen",
    "document": "dokumen",
    "report": "laporan",
    "repair report": "laporan repair",

    # cetak / download
    "print": "cetak",
    "ngeprint": "cetak",
    "nyetak": "cetak",
    "dicetak": "cetak",
    "mencetak": "cetak",
    "download": "unduh",
    "donlot": "unduh",

    # alat umum
    "termal": "thermal",
    "thermal kamera": "thermal imager",
    "kamera thermal": "thermal imager",
    "kamera suhu": "thermal imager",
    "thermal camera": "thermal imager",
    "therml": "thermal",
    "imagernya": "imager",
    "kaliper": "caliper",
    "jangka sorong": "caliper",
    "mikrometer": "micrometer",
    "pressure gage": "pressure gauge",
    "presure gauge": "pressure gauge",
    "termometer": "thermometer",
    "higrometer": "hygrometer",
    "thermohigrometer": "thermohygrometer",

    # menu / aktivitas
    "aktifitas": "aktivitas",
    "activity": "aktivitas",
    "histori": "history",
    "riwayat": "history",
    "pesanan": "order",
    "pemesanan": "order",
    "keranjangnya": "keranjang",
    "cart": "keranjang",

    # pelaksana / pekerjaan
    "pekerjaanku": "pekerjaan",
    "pekerjaan saya": "pekerjaan",
    "lembar kerjanya": "lembar kerja",
    "lembar kerja nya": "lembar kerja",
    "lk": "lembar kerja",
    "diisi": "isi",
    "mengisi": "isi",
    "pengisian": "isi",
    "sudah isi": "isi",
    "sudah diisi": "isi",
    "belum isi": "isi",
    "belum diisi": "isi",

    # profil
    "profile": "profil",
    "account": "akun",
}


FILLER_WORDS = {
    # tanya umum
    "bagaimana",
    "gimana",
    "gmn",
    "cara",
    "cek",
    "lihat",
    "melihat",
    "mau",
    "ingin",
    "tolong",
    "dong",
    "min",
    "kak",
    "bang",
    "admin",
    "apa",
    "saja",
    "aja",
    "berapa",
    "mana",

    # waktu / urutan umum
    "terakhir",
    "akhir",
    "terbaru",
    "baru",
    "sekarang",
    "saat",
    "ini",

    # progress/status
    "progress",
    "status",
    "sampai",
    "sudah",
    "belum",
    "selesai",
    "dikerjakan",
    "pengerjaan",

    # subject umum customer
    "alat",
    "saya",
    "aku",
    "punya",
    "milik",
    "customer",
    "layanan",

    # subject umum pelaksana
    "pekerjaan",
    "pekerja",
    "kerja",
    "orderan",
    "tugas",
    "penugasan",
    "pelaksana",

    # order/history umum
    "order",
    "pesanan",
    "history",
    "riwayat",
    "pendaftaran",
    "registrasi",

    # dokumen umum
    "sertifikat",
    "laporan",
    "repair",
    "dokumen",
    "cetak",
    "dicetak",
    "unduh",
    "download",
    "tersedia",
    "ada",
    "bisa",

    # lembar kerja umum
    "lembar",
    "kerja",
    "lembar kerja",
    "lk",
    "isi",
    "diisi",
    "mengisi",
    "pengisian",
    "hasil",
    "kalibrasi",

    # verifikasi umum
    "verifikasi",
    "verif",
    "diverifikasi",
    "setuju",
    "disetujui",
    "persetujuan",
    "tolak",
    "ditolak",
    "revisi",
    "perbaikan",

    # keranjang
    "keranjang",
    "checkout",
    "cart",

    # identifier words
    "dengan",
    "pakai",
    "menggunakan",
    "berdasarkan",
    "sn",
    "serial",
    "number",
    "serialnumber",
    "nomor",
    "no",
    "seri",

    # kata sambung
    "yang",
    "di",
    "ke",
    "dan",
    "atau",
    "untuk",
    "dari",
    "itu",
    "nya",
    "nih",
}


KNOWN_EQUIPMENT_TERMS = [
    "thermal imager",
    "thermal camera",
    "caliper",
    "vernier caliper",
    "micrometer",
    "outside micrometer",
    "thermometer",
    "thermohygrometer",
    "hygrometer",
    "humidity meter",
    "multimeter",
    "digital multimeter",
    "clamp meter",
    "pressure gauge",
    "pressure transmitter",
    "tachometer",
    "vibration meter",
    "sound level meter",
    "torque wrench",
    "balance",
    "timbangan",
    "dry block",
    "dryblock",
    "dial indicator",
    "bore gauge",
    "insulation meter",
    "temperature indicator",
    "ultrasonic thickness",
    "thickness gauge",
]


GENERIC_VALUES = {
    "",
    "alat",
    "status",
    "progress",
    "sertifikat",
    "laporan",
    "dokumen",
    "order",
    "history",
    "keranjang",
    "layanan",
    "apa",
    "saja",
    "apa saja",
    "ada apa",
    "ada apa saja",
    "terakhir",
    "akhir",
    "order terakhir",
    "pendaftaran terakhir",

    # pelaksana generic
    "pekerjaan",
    "kerja",
    "pekerjaan saya",
    "kerja saya",
    "order saya",
    "tugas saya",
    "lembar",
    "lembar kerja",
    "lk",
    "isi",
    "diisi",
    "lembar kerja isi",
    "lembar kerja diisi",
    "hasil",
    "hasil kalibrasi",
}


def normalize_spaces(text: str) -> str:
    return " ".join(str(text or "").strip().split())


def normalize_text(text: str) -> str:
    text = str(text or "").lower()
    text = text.replace("_", " ")
    text = re.sub(r"[^a-z0-9\-\.\s]", " ", text)
    text = normalize_spaces(text)

    for src, dst in sorted(SYNONYM_MAP.items(), key=lambda x: len(x[0]), reverse=True):
        pattern = r"\b" + re.escape(src) + r"\b"
        text = re.sub(pattern, dst, text)

    return normalize_spaces(text)


def stem_word(word: str) -> str:
    if not _stemmer:
        return word

    try:
        return _stemmer.stem(word)
    except Exception:
        return word


def stem_text(text: str) -> str:
    normalized = normalize_text(text)
    words = normalized.split()
    stemmed_words = [stem_word(word) for word in words]
    return normalize_spaces(" ".join(stemmed_words))


def extract_identifier(message: str) -> str:
    normalized = normalize_text(message)

    # Nomor order alat seperti JS-25.12.055
    match = re.search(r"\b[a-z]{1,5}-\d{2}\.\d{1,2}\.\d{1,5}\b", normalized)
    if match:
        return match.group(0).upper()

    # Nomor pendaftaran seperti J-25-111
    match = re.search(r"\b[a-z]{1,5}-\d{2}-\d{1,5}\b", normalized)
    if match:
        return match.group(0).upper()

    # UUID / norec detail
    match = re.search(r"\b[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\b", normalized)
    if match:
        return match.group(0)

    # SN / serial number
    match = re.search(
        r"\b(?:sn|serial|serial number|serialnumber|nomor seri|no seri)\s*[:\-]?\s*(\d{4,})\b",
        normalized,
    )
    if match:
        return match.group(1)

    # angka panjang umum
    match = re.search(r"\b\d{5,}\b", normalized)
    if match:
        return match.group(0)

    return ""


def remove_filler_words(text: str) -> str:
    normalized = normalize_text(text)
    words = re.findall(r"[a-zA-Z0-9\-\.]+", normalized)

    important_words = []
    for word in words:
        if word in FILLER_WORDS:
            continue
        important_words.append(word)

    result = normalize_spaces(" ".join(important_words))

    if result in GENERIC_VALUES:
        return ""

    return result


def fuzzy_match_equipment_term(text: str, min_score: int = 82) -> str:
    """
    Mengembalikan nama alat kalau text memang mirip nama alat.
    Contoh:
    - therml imager -> thermal imager
    - termal kamera -> thermal imager
    - kaliper -> caliper
    """

    normalized = normalize_text(text)
    cleaned = remove_filler_words(normalized)

    if not cleaned:
        return ""

    # Cocokkan phrase langsung dari text awal, bukan hanya cleaned,
    # agar "lembar kerja thermal imager sudah diisi belum"
    # tetap menghasilkan "thermal imager".
    for term in KNOWN_EQUIPMENT_TERMS:
        if term in normalized:
            return term

    for term in KNOWN_EQUIPMENT_TERMS:
        if term in cleaned:
            return term

    match = process.extractOne(
        cleaned,
        KNOWN_EQUIPMENT_TERMS,
        scorer=fuzz.WRatio,
    )

    if match:
        best_term, score, _ = match
        if score >= min_score:
            return best_term

    return ""


def generate_search_candidates_from_message(message: str) -> List[str]:
    candidates: List[str] = []

    identifier = extract_identifier(message)
    if identifier:
        candidates.append(identifier)

    normalized = normalize_text(message)

    equipment_term = fuzzy_match_equipment_term(normalized)
    if equipment_term and equipment_term not in candidates:
        candidates.append(equipment_term)

    cleaned = remove_filler_words(normalized)
    if cleaned and cleaned not in candidates:
        candidates.append(cleaned)

    stemmed = stem_text(cleaned)
    if stemmed and stemmed not in candidates and stemmed not in GENERIC_VALUES:
        candidates.append(stemmed)

    final_candidates = []
    for candidate in candidates:
        candidate = normalize_spaces(candidate)

        if not candidate:
            continue

        if candidate in GENERIC_VALUES:
            continue

        if candidate not in final_candidates:
            final_candidates.append(candidate)

    return final_candidates


def has_specific_search_target(message: str) -> bool:
    """
    True hanya kalau ada target pencarian spesifik:
    - SN
    - nomor order
    - nomor pendaftaran
    - UUID/norec
    - nama alat yang dikenali

    Kata umum seperti:
    - pekerjaan saya ada apa saja
    - order saya apa saja
    - lembar kerja sudah diisi belum

    tidak dianggap sebagai target pencarian.
    """

    if extract_identifier(message):
        return True

    equipment_term = fuzzy_match_equipment_term(message)
    if equipment_term:
        return True

    cleaned = remove_filler_words(message)
    if cleaned and cleaned not in GENERIC_VALUES:
        return True

    return False