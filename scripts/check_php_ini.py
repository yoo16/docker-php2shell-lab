#!/usr/bin/env python3
"""Docker の Nginx に対して有効な PHP 設定を確認するスクリプト"""

from __future__ import annotations

import argparse
import shutil
import subprocess
import sys
from collections.abc import Callable


INI_KEYS = [
    "expose_php",
    "display_errors",
    "display_startup_errors",
    "log_errors",
    "error_log",
    "error_reporting",
    "allow_url_fopen",
    "allow_url_include",
    "open_basedir",
    "disable_functions",
    "file_uploads",
    "max_execution_time",
    "memory_limit",
    "session.use_strict_mode",
    "session.use_only_cookies",
    "session.cookie_httponly",
    "session.cookie_samesite",
    "session.upload_progress.enabled",
    "session.cookie_secure",
]

REQUIRED_DISABLED_FUNCTIONS = {
    "exec", "passthru", "shell_exec", "system", "proc_open", "popen", "pcntl_exec",
}


def is_on(value: str) -> bool:
    return value.strip().lower() in {"1", "on", "yes", "true"}


def is_off(value: str) -> bool:
    return value.strip().lower() in {"", "0", "off", "no", "false"}


def read_effective_ini(service: str) -> tuple[dict[str, str] | None, str | None]:
    if shutil.which("docker") is None:
        return None, "docker コマンドが見つかりません"

    # CLI SAPIはmax_execution_timeなど一部の値を上書きするため、FPMの情報を取得する。
    command = ["docker", "compose", "exec", "-T", service, "php-fpm", "-i"]
    try:
        result = subprocess.run(
            command,
            check=False,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
        )
    except OSError as exc:
        return None, str(exc)

    if result.returncode != 0:
        message = result.stderr.strip() or result.stdout.strip() or "PHPを実行できませんでした"
        return None, message

    values: dict[str, str] = {}
    wanted = set(INI_KEYS)
    for line in result.stdout.splitlines():
        parts = line.split(" => ")
        if len(parts) >= 3 and parts[0] in wanted and parts[0] not in values:
            values[parts[0]] = "" if parts[1] == "no value" else parts[1]

    missing = wanted - values.keys()
    if missing:
        return None, f"PHP-FPMの出力に設定値がありません: {', '.join(sorted(missing))}"

    e_all_command = [
        "docker", "compose", "exec", "-T", service,
        "php", "-r", "echo E_ALL;",
    ]
    e_all_result = subprocess.run(
        e_all_command,
        check=False,
        capture_output=True,
        text=True,
        encoding="utf-8",
        errors="replace",
    )
    if e_all_result.returncode != 0:
        return None, e_all_result.stderr.strip() or "E_ALLの値を取得できませんでした"
    values["__E_ALL"] = e_all_result.stdout.strip()
    return values, None


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Docker Compose上で実際に有効なphp.iniのセキュリティ設定を確認します。"
    )
    parser.add_argument("--service", default="php", help="Composeサービス名（既定: php）")
    parser.add_argument(
        "--require-secure-cookie",
        action="store_true",
        help="HTTPS環境として session.cookie_secure=On も必須にする",
    )
    args = parser.parse_args()

    values, error = read_effective_ini(args.service)
    if error is not None or values is None:
        print(f"ERROR: php.ini設定を確認できませんでした: {error}", file=sys.stderr)
        return 2

    checks: list[tuple[str, str, Callable[[str], bool]]] = [
        ("expose_php", "Off", is_off),
        ("display_errors", "Off", is_off),
        ("display_startup_errors", "Off", is_off),
        ("log_errors", "On", is_on),
        ("error_log", "/proc/self/fd/2", lambda value: value == "/proc/self/fd/2"),
        ("error_reporting", "E_ALL", lambda value: value == values["__E_ALL"]),
        ("allow_url_fopen", "Off", is_off),
        ("allow_url_include", "Off", is_off),
        ("open_basedir", "/var/www/html と /tmp を含む", lambda value: {"/var/www/html", "/tmp"}.issubset(set(value.split(":")))),
        ("file_uploads", "Off", is_off),
        ("max_execution_time", "1〜10秒", lambda value: value.isdigit() and 1 <= int(value) <= 10),
        ("memory_limit", "128M", lambda value: value.upper() == "128M"),
        ("session.use_strict_mode", "On", is_on),
        ("session.use_only_cookies", "On", is_on),
        ("session.cookie_httponly", "On", is_on),
        ("session.cookie_samesite", "Lax または Strict", lambda value: value.lower() in {"lax", "strict"}),
        ("session.upload_progress.enabled", "Off", is_off),
    ]
    if args.require_secure_cookie:
        checks.append(("session.cookie_secure", "On", is_on))

    failed = 0
    for key, expected, validator in checks:
        actual = values.get(key, "")
        ok = validator(actual)
        failed += not ok
        print(f"{'OK' if ok else 'NG'}: {key} = {actual or '(空)'}（期待値: {expected}）")

    disabled = {item.strip() for item in values["disable_functions"].split(",") if item.strip()}
    missing = sorted(REQUIRED_DISABLED_FUNCTIONS - disabled)
    if missing:
        failed += 1
        print(f"NG: disable_functions で未無効化: {', '.join(missing)}")
    else:
        print("OK: disable_functions で主要な危険関数を無効化済み")

    if not args.require_secure_cookie and not is_on(values["session.cookie_secure"]):
        print("INFO: session.cookie_secure = Off（HTTPS化した場合は --require-secure-cookie で検査）")

    if failed:
        print(f"RESULT: {failed}件の設定不備があります。", file=sys.stderr)
        return 1
    print("RESULT: php.iniのセキュリティ設定に問題はありません。")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
