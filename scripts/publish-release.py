"""Publish a verified QOwnNotesAPI archive without replacing release assets."""

import argparse
import base64
import json
import os
import re
import subprocess
import sys
import tempfile
import urllib.error
import urllib.request
from hashlib import sha256
from pathlib import Path

from release_metadata import ARCHIVE_NAME, ROOT, VERSION_PATTERN, metadata

STORE_URL = "https://apps.nextcloud.com/api/v1/apps/releases"
MAX_SIZE = 20 * 1024 * 1024


def gh(*args: str, missing_ok: bool = False) -> str | None:
    result = subprocess.run(["gh", *args], capture_output=True, text=True)
    if not result.returncode:
        return result.stdout
    if missing_ok and "HTTP 404" in result.stderr:
        return None
    raise ValueError(f"GitHub CLI failed: gh {' '.join(args[:2])}")


def publish_github(root: Path, repo: str, commit: str) -> None:
    if not re.fullmatch(r"[0-9a-f]{40}", commit):
        raise ValueError("Commit must be a full SHA")
    version, notes = metadata(root)
    tag = f"v{version}"
    archive = root / "build" / ARCHIVE_NAME
    signature = archive.with_name(ARCHIVE_NAME + ".sig")
    checksum = archive.with_name(ARCHIVE_NAME + ".sha256")
    digest = sha256(archive.read_bytes()).hexdigest()
    if checksum.read_text() != f"{digest}  {ARCHIVE_NAME}\n":
        raise ValueError("Signed archive checksum does not match")
    if not signature.stat().st_size:
        raise ValueError("Missing archive signature")

    api = f"repos/{repo}"
    ref = gh("api", f"{api}/git/ref/tags/{tag}", missing_ok=True)
    if ref is not None and json.loads(gh("api", f"{api}/commits/{tag}"))["sha"] != commit:
        raise ValueError(f"Existing {tag} tag points to a different commit")
    # Listing with the write token includes drafts, unlike GET /releases/tags/{tag}.
    releases = json.loads(gh("api", "--paginate", "--slurp", f"{api}/releases?per_page=100"))
    matches = [release for page in releases for release in page if release["tag_name"] == tag]
    if len(matches) > 1:
        raise ValueError(f"Multiple GitHub releases for {tag}")

    with tempfile.TemporaryDirectory() as directory:
        notes_file = Path(directory) / "notes.md"
        notes_file.write_text(notes, encoding="utf-8")
        if matches:
            release = matches[0]
            wrong_draft = release["draft"] and release["target_commitish"] != commit
            missing_tag = not release["draft"] and ref is None
            if release["prerelease"] or wrong_draft or missing_tag:
                raise ValueError(f"Existing release {tag} is not a match for {commit}")
        else:
            gh(
                "release", "create", tag, "--repo", repo, "--target", commit,
                "--title", f"Release {tag}", "--notes-file", str(notes_file), "--draft",
            )
            release = {"draft": True, "assets": []}

        existing = {asset["name"] for asset in release["assets"]}
        for asset in (archive, signature, checksum):
            if asset.name in existing:
                gh(
                    "release", "download", tag, "--repo", repo,
                    "--pattern", asset.name, "--dir", directory,
                )
                if (Path(directory) / asset.name).read_bytes() != asset.read_bytes():
                    raise ValueError(f"Refusing to replace different asset: {asset.name}")
            elif not release["draft"]:
                raise ValueError(f"Published release is missing {asset.name}; refusing to modify it")
            else:
                gh("release", "upload", tag, str(asset), "--repo", repo)
        if release["draft"]:
            gh(
                "release", "edit", tag, "--repo", repo, "--notes-file", str(notes_file),
                "--title", f"Release {tag}", "--draft=false", "--latest",
            )
    print(f"GitHub release {tag} published with verified signed assets")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        # The app-store token must never follow redirects to another endpoint.
        return None


def publish_appstore(repo: str, version: str, archive: Path, token: str) -> None:
    if not re.fullmatch(VERSION_PATTERN, version) or archive.name != ARCHIVE_NAME:
        raise ValueError("Expected a stable app version and the Nextcloud release archive")
    if not token.strip() or "\n" in token or "\r" in token:
        raise ValueError("APPSTORE_TOKEN is missing or invalid")
    if not 0 < archive.stat().st_size <= MAX_SIZE:
        raise ValueError("App Store archive must be nonempty and at most 20 MiB")
    signature = archive.with_name(ARCHIVE_NAME + ".sig").read_text().strip()
    if not base64.b64decode(signature, validate=True):
        raise ValueError("Archive signature is empty")
    url = f"https://github.com/{repo}/releases/download/v{version}/{ARCHIVE_NAME}"
    with urllib.request.urlopen(url, timeout=120) as response:
        if not response.geturl().startswith("https://"):
            raise ValueError("GitHub download redirected to an insecure URL")
        if response.read(MAX_SIZE + 1) != archive.read_bytes():
            raise ValueError("Public GitHub archive differs from the signed archive")

    request = urllib.request.Request(
        STORE_URL,
        data=json.dumps({"download": url, "signature": signature, "nightly": False}).encode(),
        headers={"Authorization": f"Token {token}", "Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.build_opener(NoRedirect()).open(request, timeout=180) as response:
        if response.status not in (200, 201):
            raise ValueError(f"Unexpected App Store HTTP status: {response.status}")
        print(f"App Store release {'created' if response.status == 201 else 'updated'}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", default=os.getenv("GH_REPO"), help="GitHub owner/repository")
    subparsers = parser.add_subparsers(dest="destination", required=True)
    subparsers.add_parser("github").add_argument("--commit", required=True)
    store = subparsers.add_parser("appstore")
    store.add_argument("--version", required=True)
    store.add_argument("--archive", type=Path, default=ROOT / "build" / ARCHIVE_NAME)
    args = parser.parse_args()
    if not re.fullmatch(r"[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+", args.repo or ""):
        parser.error("Set GH_REPO or pass --repo owner/repository")
    try:
        if args.destination == "github":
            publish_github(ROOT, args.repo, args.commit)
        else:
            publish_appstore(args.repo, args.version, args.archive, os.getenv("APPSTORE_TOKEN", ""))
    except urllib.error.HTTPError as error:
        print(f"Publication failed: HTTP {error.code}", file=sys.stderr)
        return 1
    except (OSError, ValueError) as error:
        print(f"Publication failed: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
