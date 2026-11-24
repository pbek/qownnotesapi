import base64
import hashlib
import importlib.util
import json
import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import MagicMock, patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "scripts"))
from release_metadata import ARCHIVE_NAME, check_manifest, metadata

spec = importlib.util.spec_from_file_location(
    "publish_release", ROOT / "scripts/publish-release.py"
)
publish = importlib.util.module_from_spec(spec)
spec.loader.exec_module(publish)

VERSION = "26.10.1"
REPO = "pbek/qownnotesapi"
COMMIT = "a" * 40
URL = f"https://github.com/{REPO}/releases/download/v{VERSION}/{ARCHIVE_NAME}"


class ReleaseTest(unittest.TestCase):
    def setUp(self):
        temp = tempfile.TemporaryDirectory()
        self.addCleanup(temp.cleanup)
        self.root = Path(temp.name)
        (self.root / "appinfo").mkdir()
        (self.root / "appinfo/info.xml").write_text(
            f"<info><id>qownnotesapi</id><version>{VERSION}</version></info>"
        )
        self.changelog = self.root / "CHANGELOG.md"
        self.changelog.write_text(f"# Changelog\n\n## {VERSION}\n\n- Release notes\n")
        (self.root / "build").mkdir()
        self.archive = self.root / "build" / ARCHIVE_NAME
        self.archive.write_bytes(b"the signed artifact")
        self.signature = self.archive.with_name(ARCHIVE_NAME + ".sig")
        self.signature.write_text(base64.b64encode(b"signature").decode())
        digest = hashlib.sha256(self.archive.read_bytes()).hexdigest()
        self.archive.with_name(ARCHIVE_NAME + ".sha256").write_text(
            f"{digest}  {ARCHIVE_NAME}\n"
        )

    def test_metadata_extracts_only_the_current_notes(self):
        self.changelog.write_text(
            self.changelog.read_text() + "\n## 26.8.1\n\nOld notes\n"
        )
        self.assertEqual(metadata(self.root), (VERSION, "- Release notes\n"))

    def test_metadata_rejects_empty_duplicate_and_out_of_order_sections(self):
        for text in (
            f"## {VERSION}\n\n## 26.8.1\n\nOld notes\n",
            f"## {VERSION}\n\nNotes\n\n## {VERSION}\n\nDuplicate\n",
            f"## Unreleased\n\nNotes\n\n## {VERSION}\n\nNotes\n",
        ):
            with self.subTest(text=text), self.assertRaises(ValueError):
                self.changelog.write_text(text)
                metadata(self.root)

    def test_manifest_rejects_symlinks_and_old_signatures(self):
        entries = ["appinfo/info.xml", "appinfo/routes.php", "CHANGELOG.md", "COPYING"]
        (self.root / "appinfo/routes.php").touch()
        (self.root / "COPYING").touch()
        (self.root / "link").symlink_to("COPYING")
        (self.root / "appinfo/signature.json").touch()
        manifest = self.root / "release-files.txt"
        manifest.write_text("\n".join(entries) + "\n")
        check_manifest(self.root)
        for entry in ("link", "appinfo/signature.json", "../outside", "COPYING"):
            with self.subTest(entry=entry), self.assertRaises(ValueError):
                manifest.write_text("\n".join(entries + [entry]) + "\n")
                check_manifest(self.root)

    def test_github_uploads_all_assets_to_a_draft_before_publishing(self):
        with patch.object(publish, "gh", side_effect=[None, "[]"] + [""] * 5) as gh:
            publish.publish_github(self.root, REPO, COMMIT)
        calls = [call.args for call in gh.call_args_list]
        self.assertEqual(calls[2][:3], ("release", "create", f"v{VERSION}"))
        self.assertIn(COMMIT, calls[2])
        self.assertIn("--draft", calls[2])
        self.assertTrue(all(call[:2] == ("release", "upload") for call in calls[3:6]))
        self.assertIn("--draft=false", calls[6])

    def test_github_refuses_old_tag_or_tampered_artifact(self):
        with patch.object(publish, "gh", side_effect=["{}", '{"sha":"old"}']) as gh:
            with self.assertRaisesRegex(ValueError, "different commit"):
                publish.publish_github(self.root, REPO, COMMIT)
            self.assertEqual(gh.call_count, 2)
        self.archive.write_bytes(b"tampered")
        with patch.object(publish, "gh") as gh:
            with self.assertRaisesRegex(ValueError, "checksum"):
                publish.publish_github(self.root, REPO, COMMIT)
            gh.assert_not_called()

    def test_github_resumes_draft_only_when_uploaded_bytes_match(self):
        draft = {
            "tag_name": f"v{VERSION}",
            "draft": True,
            "prerelease": False,
            "target_commitish": COMMIT,
            "assets": [{"name": ARCHIVE_NAME}],
        }
        for existing, upload_count in ((self.archive.read_bytes(), 2), (b"changed", 0)):
            def gh(*args, **kwargs):
                if args[0] == "api":
                    return json.dumps([[draft]]) if args[1] == "--paginate" else None
                if args[:2] == ("release", "download"):
                    (Path(args[-1]) / ARCHIVE_NAME).write_bytes(existing)
                return ""

            with patch.object(publish, "gh", side_effect=gh) as mock:
                if upload_count:
                    publish.publish_github(self.root, REPO, COMMIT)
                else:
                    with self.assertRaisesRegex(ValueError, "different asset"):
                        publish.publish_github(self.root, REPO, COMMIT)
            self.assertEqual(
                sum(call.args[:2] == ("release", "upload") for call in mock.call_args_list),
                upload_count,
            )

    def test_github_rejects_wrong_draft_and_will_not_modify_published_assets(self):
        release = {
            "tag_name": f"v{VERSION}",
            "draft": True,
            "prerelease": False,
            "target_commitish": "b" * 40,
            "assets": [],
        }
        with patch.object(
            publish, "gh", side_effect=[None, json.dumps([[release]])]
        ) as gh:
            with self.assertRaisesRegex(ValueError, "not a match"):
                publish.publish_github(self.root, REPO, COMMIT)
            self.assertEqual(gh.call_count, 2)
        release["draft"] = False
        with patch.object(
            publish,
            "gh",
            side_effect=["{}", json.dumps({"sha": COMMIT}), json.dumps([[release]])],
        ) as gh:
            with self.assertRaisesRegex(ValueError, "refusing to modify"):
                publish.publish_github(self.root, REPO, COMMIT)
            self.assertEqual(gh.call_count, 3)

    def test_appstore_submits_signature_only_for_matching_public_bytes(self):
        with (
            patch.object(publish.urllib.request, "urlopen") as download,
            patch.object(publish.urllib.request, "build_opener") as opener,
        ):
            response = download.return_value.__enter__.return_value
            response.geturl.return_value = URL
            response.read.return_value = b"changed"
            with self.assertRaisesRegex(ValueError, "differs"):
                publish.publish_appstore(REPO, VERSION, self.archive, "test-token")
            opener.assert_not_called()

            response.read.return_value = self.archive.read_bytes()
            post = MagicMock()
            post.__enter__.return_value.status = 201
            opener.return_value.open.return_value = post
            publish.publish_appstore(REPO, VERSION, self.archive, "test-token")
            self.assertEqual(download.call_args.args, (URL,))
            request = opener.return_value.open.call_args.args[0]
            self.assertEqual(request.full_url, publish.STORE_URL)
            self.assertEqual(request.get_header("Authorization"), "Token test-token")
            self.assertEqual(
                json.loads(request.data),
                {"download": URL, "signature": self.signature.read_text(), "nightly": False},
            )

    def test_appstore_blocks_untrusted_assets_before_submission(self):
        with (
            patch.object(publish.urllib.request, "urlopen") as download,
            patch.object(publish.urllib.request, "build_opener") as opener,
        ):
            response = download.return_value.__enter__.return_value
            response.geturl.return_value = "http://example.com/archive"
            with self.assertRaisesRegex(ValueError, "insecure URL"):
                publish.publish_appstore(REPO, VERSION, self.archive, "test-token")
            opener.assert_not_called()
            download.reset_mock()
            self.signature.write_text("not base64")
            with self.assertRaises(ValueError):
                publish.publish_appstore(REPO, VERSION, self.archive, "test-token")
            download.assert_not_called()


if __name__ == "__main__":
    unittest.main()
