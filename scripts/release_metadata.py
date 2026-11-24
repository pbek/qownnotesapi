"""Validate release metadata and the archive allowlist."""

import argparse
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ARCHIVE_NAME = "qownnotesapi-nc.tar.gz"
VERSION_PATTERN = r"[0-9]{2}\.(?:[1-9]|1[0-2])\.(?:0|[1-9][0-9]*)"


def metadata(root: Path) -> tuple[str, str]:
    info = ET.parse(root / "appinfo/info.xml").getroot()
    version = info.findtext("version", "")
    if info.findtext("id") != "qownnotesapi" or not re.fullmatch(VERSION_PATTERN, version):
        raise ValueError("appinfo/info.xml must have ID qownnotesapi and a YY.M.patch version")
    changelog = (root / "CHANGELOG.md").read_text(encoding="utf-8")
    sections = list(re.finditer(r"^## ([^\n]+)$", changelog, re.MULTILINE))
    if (
        not sections
        or sections[0][1] != version
        or sum(section[1] == version for section in sections) != 1
    ):
        raise ValueError(f"Changelog must start with exactly one section for {version}")
    end = sections[1].start() if len(sections) > 1 else len(changelog)
    notes = changelog[sections[0].end() : end].strip()
    if not notes:
        raise ValueError(f"Release notes for {version} are empty")
    return version, notes + "\n"


def check_manifest(root: Path) -> None:
    entries = (root / "release-files.txt").read_text(encoding="utf-8").splitlines()
    if len(entries) != len(set(entries)):
        raise ValueError("Duplicate release manifest entries")
    required = {"appinfo/info.xml", "appinfo/routes.php", "CHANGELOG.md", "COPYING"}
    if not required.issubset(entries):
        raise ValueError("Release manifest is missing required app files")
    for entry in entries:
        path = root / entry
        if (
            not re.fullmatch(r"[A-Za-z0-9_][A-Za-z0-9_./-]*", entry)
            or ".." in entry
            or entry == "appinfo/signature.json"
            or not path.is_file()
            or path.resolve() != path
        ):
            raise ValueError(f"Invalid release manifest entry: {entry!r}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--notes-output", type=Path)
    args = parser.parse_args()
    try:
        check_manifest(ROOT)
        version, notes = metadata(ROOT)
        if args.notes_output:
            args.notes_output.write_text(notes, encoding="utf-8")
    except (OSError, ValueError, ET.ParseError) as error:
        print(f"Release preflight failed: {error}", file=sys.stderr)
        return 1
    print(version)
    return 0


if __name__ == "__main__":
    sys.exit(main())
