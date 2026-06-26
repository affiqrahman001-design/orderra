from __future__ import annotations

import sys
from collections import Counter
from pathlib import Path, PurePosixPath
from zipfile import BadZipFile, ZipFile

DEFAULT_ZIP_PATH = "project_jutawan.zip"
REPORT_LIMIT = 200

RUNTIME_PLACEHOLDER_FILES = {".gitignore"}

BLOCKED_DIR_NAMES = {
    "node_modules": "dependency directory: node_modules/",
    "vendor": "dependency directory: vendor/",
    "dist": "build output directory: dist/",
    "build": "build output directory: build/",
}

LARAVEL_RUNTIME_DIRS = (
    ("storage", "logs"),
    ("storage", "framework", "cache"),
    ("storage", "framework", "sessions"),
    ("storage", "framework", "views"),
)

BLOCKED_EXACT_FILE_NAMES = {
    ".phpunit.result.cache": "PHPUnit result cache",
    "database.sqlite": "local SQLite database",
    "database.sqlite3": "local SQLite database",
    "laravel.log": "Laravel log file",
}

BLOCKED_SUFFIXES = {
    ".tsbuildinfo": "TypeScript build cache",
    ".sqlite": "SQLite database file",
    ".sqlite3": "SQLite database file",
}


def normalize_zip_name(name: str) -> PurePosixPath:
    return PurePosixPath(name.replace("\\", "/").strip("/"))


def contains_sequence(parts: tuple[str, ...], sequence: tuple[str, ...]) -> bool:
    sequence_length = len(sequence)

    if sequence_length == 0 or len(parts) < sequence_length:
        return False

    for index in range(0, len(parts) - sequence_length + 1):
        if parts[index : index + sequence_length] == sequence:
            return True

    return False


def is_allowed_placeholder(path: PurePosixPath) -> bool:
    return path.name in RUNTIME_PLACEHOLDER_FILES


def detect_block_reason(name: str) -> str | None:
    path = normalize_zip_name(name)
    parts = path.parts

    if not parts:
        return None

    filename = path.name

    if filename.startswith(".env") and filename != ".env.example":
        return "environment file: .env / .env.*"

    for part in parts:
        if part in BLOCKED_DIR_NAMES:
            return BLOCKED_DIR_NAMES[part]

    if filename in BLOCKED_EXACT_FILE_NAMES:
        return BLOCKED_EXACT_FILE_NAMES[filename]

    for suffix, reason in BLOCKED_SUFFIXES.items():
        if filename.endswith(suffix):
            return reason

    if is_allowed_placeholder(path):
        return None

    for runtime_dir in LARAVEL_RUNTIME_DIRS:
        if contains_sequence(parts, runtime_dir):
            return "Laravel runtime output directory: " + "/".join(runtime_dir) + "/"

    if contains_sequence(parts, ("bootstrap", "cache")) and filename.endswith(".php"):
        return "Laravel bootstrap cache PHP file"

    return None


def scan_zip(zip_path: Path) -> list[tuple[str, str]]:
    with ZipFile(zip_path) as zip_file:
        hits: list[tuple[str, str]] = []

        for raw_name in zip_file.namelist():
            normalized_name = normalize_zip_name(raw_name).as_posix()
            reason = detect_block_reason(normalized_name)

            if reason is not None:
                hits.append((normalized_name, reason))

    return hits


def main() -> int:
    zip_path = Path(sys.argv[1] if len(sys.argv) > 1 else DEFAULT_ZIP_PATH)

    if not zip_path.exists():
        print("NOT CLEAN")
        print(f"Zip file not found: {zip_path}")
        return 2

    try:
        hits = scan_zip(zip_path)
    except BadZipFile:
        print("NOT CLEAN")
        print(f"Invalid zip file: {zip_path}")
        return 2

    print(f"Scanned: {zip_path}")

    if not hits:
        print("CLEAN")
        return 0

    print("NOT CLEAN")
    print(f"Blocked entries found: {len(hits)}")

    reason_counts = Counter(reason for _, reason in hits)

    print("\nSummary:")
    for reason, count in reason_counts.most_common():
        print(f"- {reason}: {count}")

    print(f"\nBlocked files/directories shown first {min(len(hits), REPORT_LIMIT)}:")
    for name, reason in hits[:REPORT_LIMIT]:
        print(f"- {name} [{reason}]")

    if len(hits) > REPORT_LIMIT:
        print(f"... {len(hits) - REPORT_LIMIT} more blocked entries hidden to keep output readable.")

    return 1


if __name__ == "__main__":
    raise SystemExit(main())
