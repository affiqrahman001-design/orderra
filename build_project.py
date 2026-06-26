from __future__ import annotations

import os
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

PROJECT_ROOT = Path(__file__).resolve().parent
ARCHIVE_NAME = "project_jutawan.zip"

EXCLUDED_DIR_NAMES = {
    ".git",
    ".idea",
    ".vscode",
    ".cursor",
    ".nova",
    ".zed",
    "node_modules",
    "vendor",
    "dist",
    "build",
    "coverage",
    "__pycache__",
    ".pytest_cache",
    ".mypy_cache",
    ".phpunit.cache",
    ".vite",
    ".cache",
}

EXCLUDED_FILE_NAMES = {
    ".DS_Store",
    "Thumbs.db",
    "desktop.ini",
    ".phpunit.result.cache",
    ARCHIVE_NAME,
    "database.sqlite",
    "database.sqlite3",
    "laravel.log",
}

EXCLUDED_SUFFIXES = {
    ".pyc",
    ".pyo",
    ".log",
    ".tmp",
    ".swp",
    ".swo",
    ".bak",
    ".orig",
    ".tsbuildinfo",
    ".sqlite",
    ".sqlite3",
    ".zip",
}

LARAVEL_RUNTIME_DIR_PARTS = (
    ("backend", "storage", "logs"),
    ("backend", "storage", "framework", "cache"),
    ("backend", "storage", "framework", "cache", "data"),
    ("backend", "storage", "framework", "sessions"),
    ("backend", "storage", "framework", "views"),
    ("backend", "bootstrap", "cache"),
)


def is_env_file(path: Path) -> bool:
    return path.name.startswith(".env") and path.name != ".env.example"


def is_laravel_runtime_output(relative_path: Path) -> bool:
    parts = relative_path.parts

    for runtime_parts in LARAVEL_RUNTIME_DIR_PARTS:
        if parts[: len(runtime_parts)] == runtime_parts:
            return relative_path.name != ".gitignore"

    return False


def should_skip(relative_path: Path) -> bool:
    if any(part in EXCLUDED_DIR_NAMES for part in relative_path.parts):
        return True

    if relative_path.name in EXCLUDED_FILE_NAMES:
        return True

    if relative_path.suffix.lower() in EXCLUDED_SUFFIXES:
        return True

    if is_env_file(relative_path):
        return True

    if is_laravel_runtime_output(relative_path):
        return True

    return False


def collect_files(root: Path) -> list[Path]:
    files: list[Path] = []

    for current_dir, dir_names, file_names in os.walk(root):
        current_path = Path(current_dir)
        relative_dir = current_path.relative_to(root)

        dir_names[:] = sorted(
            dir_name
            for dir_name in dir_names
            if not should_skip(relative_dir / dir_name)
        )

        for file_name in sorted(file_names):
            file_path = current_path / file_name
            relative_path = file_path.relative_to(root)

            if should_skip(relative_path):
                continue

            files.append(file_path)

    return sorted(files)


def build_archive(root: Path, archive_name: str) -> Path:
    archive_path = root / archive_name

    if archive_path.exists():
        archive_path.unlink()

    files = collect_files(root)

    with ZipFile(archive_path, "w", compression=ZIP_DEFLATED) as zip_file:
        for file_path in files:
            zip_file.write(file_path, file_path.relative_to(root))

    return archive_path


def main() -> None:
    archive_path = build_archive(PROJECT_ROOT, ARCHIVE_NAME)
    size_kb = archive_path.stat().st_size / 1024
    print(f"Created {archive_path.name} ({size_kb:.1f} KB)")


if __name__ == "__main__":
    main()
