from __future__ import annotations

import os
import shutil
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT.parent / "orderra-v2-github-clean"
OUT_ZIP = ROOT.parent / "orderra-v2-github-clean.zip"

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
    "project_jutawan.zip",
    OUT_ZIP.name,
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


def copy_clean_tree(root: Path, out_dir: Path) -> int:
    if out_dir.exists():
        shutil.rmtree(out_dir)

    out_dir.mkdir(parents=True)
    copied_files = 0

    for current_dir, dir_names, file_names in os.walk(root):
        current_path = Path(current_dir)
        relative_dir = current_path.relative_to(root)

        dir_names[:] = sorted(
            dir_name
            for dir_name in dir_names
            if not should_skip(relative_dir / dir_name)
        )

        for file_name in sorted(file_names):
            source_path = current_path / file_name
            relative_path = source_path.relative_to(root)

            if should_skip(relative_path):
                continue

            destination_path = out_dir / relative_path
            destination_path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(source_path, destination_path)
            copied_files += 1

    return copied_files


def zip_clean_tree(out_dir: Path, out_zip: Path) -> int:
    if out_zip.exists():
        out_zip.unlink()

    archived_files = 0

    with ZipFile(out_zip, "w", compression=ZIP_DEFLATED) as zip_file:
        for file_path in sorted(out_dir.rglob("*")):
            if not file_path.is_file():
                continue

            zip_file.write(file_path, file_path.relative_to(out_dir.parent))
            archived_files += 1

    return archived_files


def main() -> None:
    copied_files = copy_clean_tree(ROOT, OUT_DIR)
    archived_files = zip_clean_tree(OUT_DIR, OUT_ZIP)
    size_kb = OUT_ZIP.stat().st_size / 1024

    print(f"Clean GitHub backup folder: {OUT_DIR}")
    print(f"Clean GitHub backup zip: {OUT_ZIP}")
    print(f"Copied files: {copied_files}")
    print(f"Archived files: {archived_files}")
    print(f"Zip size: {size_kb:.1f} KB")


if __name__ == "__main__":
    main()
