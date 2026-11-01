#!/usr/bin/env python3
"""HTTPレスポンスがPHPのバージョンを露出しているか確認するスクリプト。"""

from __future__ import annotations

import argparse
import re
import sys
import urllib.error
import urllib.request


DEFAULT_URL = "http://127.0.0.1:8099/"
DEFAULT_MINIMUM = "8.4.27"
PHP_VERSION_PATTERN = re.compile(r"(?:^|\s)PHP/(\d+\.\d+\.\d+)", re.IGNORECASE)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    """Return redirect responses as-is instead of following them."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001
        return None


def parse_version(value: str) -> tuple[int, int, int]:
    try:
        parts = tuple(map(int, value.split(".")))
    except ValueError as exc:
        raise ValueError(f"バージョン形式を解釈できません: {value!r}") from exc
    if len(parts) != 3:
        raise ValueError(f"バージョン形式を解釈できません: {value!r}")
    return parts


def get_powered_by(url: str, timeout: float) -> tuple[int | None, str | None, str | None]:
    request = urllib.request.Request(
        url,
        headers={"User-Agent": "php-version-exposure-check/1.0"},
        method="GET",
    )
    opener = urllib.request.build_opener(NoRedirect)
    try:
        with opener.open(request, timeout=timeout) as response:
            return response.status, response.headers.get("X-Powered-By"), None
    except urllib.error.HTTPError as exc:
        return exc.code, exc.headers.get("X-Powered-By"), None
    except (urllib.error.URLError, TimeoutError, OSError) as exc:
        return None, None, str(exc)


def main() -> int:
    parser = argparse.ArgumentParser(
        description="HTTPレスポンスのX-Powered-ByヘッダーからPHPバージョンの露出を確認します。"
    )
    parser.add_argument("url", nargs="?", default=DEFAULT_URL, help=f"確認URL（既定: {DEFAULT_URL}）")
    parser.add_argument(
        "--minimum", default=DEFAULT_MINIMUM,
        help=f"露出時に比較する最低バージョン（既定: {DEFAULT_MINIMUM}）",
    )
    parser.add_argument("--timeout", type=float, default=5.0, help="タイムアウト秒（既定: 5）")
    args = parser.parse_args()

    if args.timeout <= 0:
        parser.error("--timeout は0より大きい値を指定してください")
    try:
        minimum = parse_version(args.minimum)
    except ValueError as exc:
        parser.error(str(exc))

    status, powered_by, error = get_powered_by(args.url, args.timeout)
    if error is not None:
        print(f"ERROR: {args.url} を確認できませんでした: {error}", file=sys.stderr)
        return 2

    if not powered_by:
        print(f"OK: X-Powered-Byヘッダーはありません（HTTP {status}）。")
        print("INFO: HTTP経由ではPHPバージョンを取得できません。")
        return 0

    print(f"NG: X-Powered-Byヘッダーが公開されています: {powered_by}（HTTP {status}）", file=sys.stderr)
    match = PHP_VERSION_PATTERN.search(powered_by)
    if match is None:
        print("INFO: ヘッダーからPHPのパッチバージョンを判定できません。")
        return 1

    current_text = match.group(1)
    current = parse_version(current_text)
    if current < minimum:
        print(f"NG: PHP {current_text} は最低要件 {args.minimum} を満たしていません。", file=sys.stderr)
    else:
        print(f"INFO: PHP {current_text} は最低要件 {args.minimum} を満たしています。")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
