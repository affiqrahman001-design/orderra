import json
import os
import sys
import shutil
import time
from pathlib import Path
import re
from datetime import datetime, UTC

SETTINGS_PATH = Path(r"c:\Users\affiq\AppData\Roaming\Cursor\User\settings.json")
LOG_PATH = Path(r"d:\orderra-v2\debug-e5a1d2.log")
SESSION_ID = "e5a1d2"
RUN_ID = sys.argv[1] if len(sys.argv) > 1 else "pre-fix-1"


def emit(hypothesis_id: str, location: str, message: str, data: dict) -> None:
    payload = {
        "sessionId": SESSION_ID,
        "runId": RUN_ID,
        "hypothesisId": hypothesis_id,
        "location": location,
        "message": message,
        "data": data,
        "timestamp": int(time.time() * 1000),
    }
    with LOG_PATH.open("a", encoding="utf-8") as f:
        f.write(json.dumps(payload, ensure_ascii=True) + "\n")


def main() -> None:
    #region agent log
    emit("H1", "settings_debug_probe.py:28", "Starting settings probe", {"settingsPath": str(SETTINGS_PATH)})
    #endregion

    raw_text = SETTINGS_PATH.read_text(encoding="utf-8")
    data = json.loads(raw_text)

    #region agent log
    emit("H1", "settings_debug_probe.py:35", "JSON parse succeeded", {"topLevelKeyCount": len(data.keys())})
    #endregion

    update_mode = data.get("update.mode")
    #region agent log
    emit("H2", "settings_debug_probe.py:40", "update.mode value", {"value": update_mode})
    #endregion

    parent_folder_mode = data.get("git.openRepositoryInParentFolders")
    #region agent log
    emit("H3", "settings_debug_probe.py:45", "git.openRepositoryInParentFolders value", {"value": parent_folder_mode})
    #endregion

    tsx_formatter = (data.get("[typescriptreact]") or {}).get("editor.defaultFormatter")
    js_formatter = (data.get("[javascript]") or {}).get("editor.defaultFormatter")
    #region agent log
    emit(
        "H4",
        "settings_debug_probe.py:52",
        "Formatter consistency snapshot",
        {"typescriptreact": tsx_formatter, "javascript": js_formatter},
    )
    #endregion

    php_path = data.get("php.validate.executablePath")
    phpcs_path = data.get("phpcs.executablePath")
    php_exists = bool(shutil.which(php_path)) if isinstance(php_path, str) else False
    phpcs_exists = bool(shutil.which(phpcs_path)) if isinstance(phpcs_path, str) else False
    #region agent log
    emit(
        "H5",
        "settings_debug_probe.py:64",
        "PHP tool availability",
        {"phpPathSetting": php_path, "phpcsPathSetting": phpcs_path, "phpFound": php_exists, "phpcsFound": phpcs_exists},
    )
    #endregion

    allowed_update_modes = {"none", "manual", "start", "default"}
    allowed_parent_repo_modes = {"always", "never", "prompt"}
    #region agent log
    emit(
        "H6",
        "settings_debug_probe.py:77",
        "Enum validity snapshot",
        {
            "updateMode": update_mode,
            "updateModeValid": update_mode in allowed_update_modes,
            "parentRepoMode": parent_folder_mode,
            "parentRepoModeValid": parent_folder_mode in allowed_parent_repo_modes,
        },
    )
    #endregion

    user_home = Path(os.environ.get("USERPROFILE", ""))
    extension_roots = [user_home / ".cursor" / "extensions", user_home / ".vscode" / "extensions"]

    def has_extension(prefix: str) -> bool:
        for root in extension_roots:
            if root.exists():
                for item in root.iterdir():
                    if item.is_dir() and item.name.lower().startswith(prefix.lower() + "-"):
                        return True
        return False

    prettier_installed = has_extension("esbenp.prettier-vscode")
    #region agent log
    emit(
        "H7",
        "settings_debug_probe.py:101",
        "Formatter extension availability",
        {"prettierFormatterId": "esbenp.prettier-vscode", "prettierInstalled": prettier_installed},
    )
    #endregion

    extension_gated_settings = {
        "liveServer.settings.donotShowInfoMsg": "ritwickdey.liveserver",
        "redhat.telemetry.enabled": "redhat.vscode-yaml",
        "cursor.general.gitGraphIndexing": "cursor",
    }
    extension_setting_snapshot = []
    for key, owner in extension_gated_settings.items():
        exists = key in data
        owner_installed = True if owner == "cursor" else has_extension(owner)
        extension_setting_snapshot.append(
            {
                "key": key,
                "present": exists,
                "ownerExtension": owner,
                "ownerInstalled": owner_installed,
            }
        )
    #region agent log
    emit(
        "H9",
        "settings_debug_probe.py:124",
        "Extension-owned setting availability",
        {"items": extension_setting_snapshot},
    )
    #endregion

    # Cursor logs may contain runtime configuration warnings not exposed by schema checks.
    logs_root = user_home / "AppData" / "Roaming" / "Cursor" / "logs"
    patterns = [
        re.compile(r"Unknown Configuration Setting", re.IGNORECASE),
        re.compile(r"is not allowed", re.IGNORECASE),
        re.compile(r"settings\.json", re.IGNORECASE),
        re.compile(r"Cannot read properties of undefined.*configuration", re.IGNORECASE),
    ]
    matches = []
    if logs_root.exists():
        files = [p for p in logs_root.rglob("*.log") if p.is_file()]
        files.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        for file_path in files[:25]:
            try:
                lines = file_path.read_text(encoding="utf-8", errors="ignore").splitlines()
                for line in lines[-300:]:
                    if any(pt.search(line) for pt in patterns):
                        matches.append({"file": str(file_path), "line": line[:500]})
                        if len(matches) >= 8:
                            break
                if len(matches) >= 8:
                    break
            except Exception:
                continue

    #region agent log
    emit(
        "H8",
        "settings_debug_probe.py:136",
        "Cursor runtime config log scan",
        {"logsRootExists": logs_root.exists(), "matchCount": len(matches), "matches": matches},
    )
    #endregion

    telemetry_level = data.get("telemetry.telemetryLevel")
    otel_hits = []
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            recent_renderer = renderer_logs[0]
            lines = recent_renderer.read_text(encoding="utf-8", errors="ignore").splitlines()
            for line in lines[-800:]:
                if "[otel.error]" in line or "Trace spans collection is not enabled for this user" in line:
                    otel_hits.append(line[:500])
            otel_hits = otel_hits[-6:]

    #region agent log
    emit(
        "H10",
        "settings_debug_probe.py:164",
        "Telemetry error snapshot",
        {"telemetryLevel": telemetry_level, "otelErrorCountRecent": len(otel_hits), "samples": otel_hits},
    )
    #endregion

    recent_error_lines = []
    settings_linked_errors = []
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            recent_renderer = renderer_logs[0]
            lines = recent_renderer.read_text(encoding="utf-8", errors="ignore").splitlines()
            for line in lines[-1200:]:
                if "[error]" in line or "[warning]" in line:
                    recent_error_lines.append(line[:500])
                    lower = line.lower()
                    if "settings" in lower or "configuration" in lower or "vscodediagnosticsexecutor" in lower:
                        settings_linked_errors.append(line[:500])
            recent_error_lines = recent_error_lines[-20:]
            settings_linked_errors = settings_linked_errors[-20:]

    #region agent log
    emit(
        "H11",
        "settings_debug_probe.py:191",
        "Recent renderer error classification",
        {
            "capturedAt": datetime.now(UTC).isoformat(),
            "recentErrorCount": len(recent_error_lines),
            "settingsLinkedCount": len(settings_linked_errors),
            "settingsLinkedSamples": settings_linked_errors,
        },
    )
    #endregion

    diagnostics_context = []
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            recent_renderer = renderer_logs[0]
            lines = recent_renderer.read_text(encoding="utf-8", errors="ignore").splitlines()
            for idx, line in enumerate(lines):
                if "[VscodeDiagnosticsExecutor]" in line and "settings.json" in line:
                    start = max(0, idx - 3)
                    end = min(len(lines), idx + 8)
                    block = lines[start:end]
                    diagnostics_context.append({"anchor": line[:240], "context": [x[:300] for x in block]})
            diagnostics_context = diagnostics_context[-3:]

    #region agent log
    emit(
        "H12",
        "settings_debug_probe.py:222",
        "Settings diagnostics executor context",
        {"contextCount": len(diagnostics_context), "contexts": diagnostics_context},
    )
    #endregion

    legacy_problem_keys = [
        "redhat.telemetry.enabled",
        "php.validate.executablePath",
        "phpcs.executablePath",
    ]
    legacy_snapshot = {k: data.get(k) for k in legacy_problem_keys}
    legacy_present = [k for k in legacy_problem_keys if k in data]
    #region agent log
    emit(
        "H13",
        "settings_debug_probe.py:241",
        "Legacy problem key drift check",
        {"presentKeys": legacy_present, "values": legacy_snapshot, "settingsKeyCount": len(data)},
    )
    #endregion

    telemetry_enable = data.get("telemetry.enableTelemetry")
    crash_enable = data.get("telemetry.enableCrashReporter")
    recent_otel_count = 0
    recent_otel_samples = []
    now_epoch = time.time()
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            lines = renderer_logs[0].read_text(encoding="utf-8", errors="ignore").splitlines()
            for line in lines[-1200:]:
                if "[otel.error]" not in line:
                    continue
                ts_text = line[:23]
                try:
                    dt = datetime.strptime(ts_text, "%Y-%m-%d %H:%M:%S.%f").replace(tzinfo=UTC)
                    age_sec = now_epoch - dt.timestamp()
                except Exception:
                    age_sec = 999999
                if age_sec <= 180:
                    recent_otel_count += 1
                    recent_otel_samples.append(line[:300])
    recent_otel_samples = recent_otel_samples[-5:]
    #region agent log
    emit(
        "H14",
        "settings_debug_probe.py:274",
        "Timed OTEL baseline and telemetry flags",
        {
            "telemetry.enableTelemetry": telemetry_enable,
            "telemetry.enableCrashReporter": crash_enable,
            "otelErrorsLast180s": recent_otel_count,
            "samples": recent_otel_samples,
        },
    )
    #endregion

    diagnostics_targets = []
    nonzero_diagnostics = []
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            lines = renderer_logs[0].read_text(encoding="utf-8", errors="ignore").splitlines()
            for idx, line in enumerate(lines):
                if "[VscodeDiagnosticsExecutor] EXECUTE:" in line and "settings.json" in line:
                    diagnostics_targets.append(line[:320])
                    window = lines[idx + 1 : idx + 8]
                    for follow in window:
                        if "[VscodeDiagnosticsExecutor] Returning " in follow and "0 linter errors" not in follow:
                            nonzero_diagnostics.append({"execute": line[:240], "result": follow[:240]})
    diagnostics_targets = diagnostics_targets[-12:]
    nonzero_diagnostics = nonzero_diagnostics[-6:]
    #region agent log
    emit(
        "H15",
        "settings_debug_probe.py:304",
        "All settings diagnostics targets and outcomes",
        {
            "targetCount": len(diagnostics_targets),
            "targets": diagnostics_targets,
            "nonZeroResults": nonzero_diagnostics,
        },
    )
    #endregion

    settings_exec_times = []
    otel_times = []
    if logs_root.exists():
        renderer_logs = [p for p in logs_root.rglob("renderer.log") if p.is_file()]
        renderer_logs.sort(key=lambda p: p.stat().st_mtime, reverse=True)
        if renderer_logs:
            lines = renderer_logs[0].read_text(encoding="utf-8", errors="ignore").splitlines()
            for line in lines[-2000:]:
                ts_text = line[:23]
                try:
                    dt = datetime.strptime(ts_text, "%Y-%m-%d %H:%M:%S.%f").replace(tzinfo=UTC)
                except Exception:
                    continue
                if "[VscodeDiagnosticsExecutor] EXECUTE:" in line and "User\\settings.json" in line:
                    settings_exec_times.append(dt.timestamp())
                if "[otel.error]" in line:
                    otel_times.append(dt.timestamp())
    correlated_windows = 0
    for st in settings_exec_times[-6:]:
        near = [t for t in otel_times if abs(t - st) <= 10]
        if near:
            correlated_windows += 1
    #region agent log
    emit(
        "H16",
        "settings_debug_probe.py:335",
        "Settings diagnostics vs OTEL temporal correlation",
        {
            "recentSettingsExecCount": len(settings_exec_times[-6:]),
            "recentOtelCount": len(otel_times[-200:]),
            "windowsWithNearbyOtel": correlated_windows,
        },
    )
    #endregion

    formatter_keys = {
        "[json]": ((data.get("[json]") or {}).get("editor.defaultFormatter")),
        "[css]": ((data.get("[css]") or {}).get("editor.defaultFormatter")),
        "[html]": ((data.get("[html]") or {}).get("editor.defaultFormatter")),
        "[javascript]": ((data.get("[javascript]") or {}).get("editor.defaultFormatter")),
        "[typescript]": ((data.get("[typescript]") or {}).get("editor.defaultFormatter")),
        "[typescriptreact]": ((data.get("[typescriptreact]") or {}).get("editor.defaultFormatter")),
    }
    accepted_prefixes = ("vscode.", "anysphere.", "cursor.cursor-browser-automation", "ms-vscode.")
    formatter_validity = {
        key: (value is None or any(str(value).startswith(prefix) for prefix in accepted_prefixes))
        for key, value in formatter_keys.items()
    }
    #region agent log
    emit(
        "H17",
        "settings_debug_probe.py:360",
        "Formatter IDs accepted-prefix validation snapshot",
        {"formatters": formatter_keys, "validity": formatter_validity},
    )
    #endregion


if __name__ == "__main__":
    main()
