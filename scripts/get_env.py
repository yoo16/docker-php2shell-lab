#!/usr/bin/env python3
"""Webサーバーが .env ファイルを露出していないことを確認するスクリプト。"""

from __future__ import annotations

import argparse
import sys
import urllib.error
import urllib.request


DEFAULT_URL = "http://127.0.0.1:8099/.env"


class NoRedirect(urllib.request.HTTPRedirectHandler):
    """Return redirect responses as-is instead of following them."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001
        return None


def check(url: str, timeout: float) -> int:
    request = urllib.request.Request(
        url,
        headers={"User-Agent": "env-exposure-check/1.0"},
        method="GET",
    )
    opener = urllib.request.build_opener(NoRedirect)

    try:
        with opener.open(request, timeout=timeout) as response:
            status = response.status
            body = response.read()
            charset = response.headers.get_content_charset() or "utf-8"
    except urllib.error.HTTPError as exc:
        status = exc.code
        body = b""
        charset = "utf-8"
    except (urllib.error.URLError, TimeoutError, OSError) as exc:
        print(f"ERROR: {url} を確認できませんでした: {exc}", file=sys.stderr)
        return 2

    if 200 <= status < 300 and body:
        print(f"NG: {url} から内容を取得できました（HTTP {status}）。", file=sys.stderr)
        print("----- .env content -----")
        print(body.decode(charset, errors="replace"), end="" if body.endswith(b"\n") else "\n")
        print("----- end -----")
        return 1

    if 200 <= status < 300:
        print(f"OK: {url} は空の応答でした（HTTP {status}）。")
    else:
        print(f"OK: {url} は公開されていません（HTTP {status}）。")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Webサーバーから .env が取得できないことを確認します。"
    )
    parser.add_argument("url", nargs="?", default=DEFAULT_URL, help=f"確認URL（既定: {DEFAULT_URL}）")
    parser.add_argument("--timeout", type=float, default=5.0, help="タイムアウト秒（既定: 5）")
    args = parser.parse_args()

    if args.timeout <= 0:
        parser.error("--timeout は0より大きい値を指定してください")
    return check(args.url, args.timeout)


if __name__ == "__main__":
    raise SystemExit(main())
