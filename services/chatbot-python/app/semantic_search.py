import os
import pickle
from typing import List, Dict, Optional

import numpy as np
from sentence_transformers import SentenceTransformer

from app.text_search import (
    normalize_text,
    score_chunk_lexical,
    build_guided_answer_from_chunk,
    filter_chunks_by_kelompok_user,
)

MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"

_model = None


def get_model() -> SentenceTransformer:
    global _model
    if _model is None:
        _model = SentenceTransformer(MODEL_NAME)
    return _model


def build_chunk_text(chunk: Dict) -> str:
    heading = chunk.get("heading", "")
    content = chunk.get("content", "")
    file_name = chunk.get("file_name", "")

    return f"FILE: {file_name}\nJUDUL: {heading}\nISI: {content}"


def build_signature(manuals_dir: str, chunks: List[Dict]) -> str:
    parts = [MODEL_NAME]

    for chunk in chunks:
        file_name = chunk.get("file_name", "")
        file_path = os.path.join(manuals_dir, file_name)

        mtime = 0
        size = 0

        if os.path.exists(file_path):
            mtime = os.path.getmtime(file_path)
            size = os.path.getsize(file_path)

        parts.append(
            "|".join([
                file_name,
                str(mtime),
                str(size),
                str(chunk.get("chunk_index", "")),
                normalize_text(chunk.get("heading", "")),
                normalize_text(chunk.get("content", ""))[:300],
            ])
        )

    return "||".join(parts)


def get_cache_path(manuals_dir: str, kelompok_user: Optional[str] = None) -> str:
    cache_dir = os.path.join(manuals_dir, "_cache")
    os.makedirs(cache_dir, exist_ok=True)

    role = normalize_text(kelompok_user or "all")
    role = role.replace(" ", "_")

    return os.path.join(cache_dir, f"semantic_index_{role}.pkl")


def load_or_build_index(
    manuals_dir: str,
    chunks: List[Dict],
    kelompok_user: Optional[str] = None,
) -> Dict:
    cache_path = get_cache_path(manuals_dir, kelompok_user)
    signature = build_signature(manuals_dir, chunks)

    if os.path.exists(cache_path):
        try:
            with open(cache_path, "rb") as f:
                cached = pickle.load(f)

            if cached.get("signature") == signature:
                return cached
        except Exception:
            pass

    model = get_model()
    texts = [build_chunk_text(chunk) for chunk in chunks]

    embeddings = model.encode(
        texts,
        convert_to_numpy=True,
        normalize_embeddings=True,
        show_progress_bar=False,
    )

    data = {
        "signature": signature,
        "chunks": chunks,
        "embeddings": embeddings,
    }

    with open(cache_path, "wb") as f:
        pickle.dump(data, f)

    return data


def semantic_search(
    query: str,
    manuals_dir: str,
    all_chunks: List[Dict],
    kelompok_user: Optional[str] = None,
    top_k: int = 5,
) -> List[Dict]:
    filtered_chunks = filter_chunks_by_kelompok_user(all_chunks, kelompok_user)

    if not filtered_chunks:
        return []

    index_data = load_or_build_index(
        manuals_dir=manuals_dir,
        chunks=filtered_chunks,
        kelompok_user=kelompok_user,
    )

    chunks = index_data["chunks"]
    embeddings = index_data["embeddings"]

    model = get_model()
    query_embedding = model.encode(
        [query],
        convert_to_numpy=True,
        normalize_embeddings=True,
        show_progress_bar=False,
    )[0]

    similarities = np.dot(embeddings, query_embedding)

    scored_results = []

    for idx, chunk in enumerate(chunks):
        semantic_score = float(similarities[idx]) * 100.0
        lexical_score = float(score_chunk_lexical(query, chunk))
        final_score = semantic_score + lexical_score

        if final_score > 0:
            scored_results.append({
                "id": chunk["id"],
                "file_name": chunk["file_name"],
                "chunk_index": chunk["chunk_index"],
                "heading": chunk.get("heading", ""),
                "content": chunk["content"],
                "semantic_score": round(semantic_score, 4),
                "lexical_score": round(lexical_score, 4),
                "score": round(final_score, 4),
            })

    scored_results.sort(key=lambda x: x["score"], reverse=True)

    return scored_results[:top_k]


def build_best_answer(query: str, results: List[Dict]) -> str:
    if not results:
        return (
            "Saya belum menemukan jawaban yang relevan pada manual kelompok user ini. "
            "Coba gunakan pertanyaan yang lebih spesifik sesuai menu atau proses kerja."
        )

    best = results[0]

    return build_guided_answer_from_chunk(query, best, max_lines=6)