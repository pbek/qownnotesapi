# https://wiki.nixos.org/wiki/NixOS_VM_tests
{ pkgs26_05, ... }:

let
  inherit (pkgs26_05) lib;
  # Safe lookup on pkgs26_05 catching eval errors
  tryAttr2605 =
    name:
    if pkgs26_05 != null && builtins.hasAttr name pkgs26_05 then
      (
        let
          t = builtins.tryEval (builtins.getAttr name pkgs26_05);
        in
        if t.success then t.value else null
      )
    else
      null;

  # Flexible PHP package set selection for composer
  phpPkgSet =
    pkgs26_05.php85Packages
      or (pkgs26_05.php84Packages or (pkgs26_05.php83Packages or (pkgs26_05.php82Packages or null)));
  composerPkg =
    if phpPkgSet != null && phpPkgSet ? composer then phpPkgSet.composer else pkgs26_05.composer; # pkgs26_05.composer as last resort
  phpInterp = pkgs26_05.php or (if phpPkgSet != null && phpPkgSet ? php then phpPkgSet.php else null);

  pkg32 = tryAttr2605 "nextcloud32";
  pkg33 = tryAttr2605 "nextcloud33";
  pkg34 = tryAttr2605 "nextcloud34";

  has32 = pkg32 != null;
  has33 = pkg33 != null;
  has34 = pkg34 != null;

  # Build the app once (using primary pkgs set)
  qownnotesapiApp =
    pkgs26_05.runCommand "qownnotesapi-app"
      {
        src = ../../.;
        buildInputs = lib.filter (x: x != null) [
          composerPkg
          phpInterp
        ];
        preferLocalBuild = true;
        allowSubstitutes = false; # ensure we always build locally (still won't rebuild if output already exists)
      }
      ''
        mkdir -p $out
        cp -r $src/* $out/
        chmod -R u+w $out
        if [ -n "$FORCE_REBUILD_NONCE" ]; then
          echo "$FORCE_REBUILD_NONCE" > $out/.force-rebuild-nonce
          echo "Force rebuild nonce embedded: $FORCE_REBUILD_NONCE"
        fi
        export COMPOSER_ALLOW_SUPERUSER=1
        export HOME=$TMPDIR
        if [ -f "$out/composer.json" ]; then
          if [ -d "$out/vendor" ]; then
            echo "Running composer install (offline, expects vendor already vendored)"
            (cd $out && composer install --no-dev --optimize-autoloader --no-interaction || composer dump-autoload --optimize || true)
          else
            echo "No vendor directory found; skipping composer install to avoid network (would fail)"
          fi
        fi
      '';

  mkNode = pkg: name: {
    ${name} = _: {
      services.nextcloud = {
        enable = true;
        package = pkg;
        hostName = "localhost";
        config = {
          adminuser = "admin";
          adminpassFile = "/etc/nextcloud-adminpass";
          dbtype = "sqlite";
          dbname = "nextcloud";
        };
        extraApps = {
          qownnotesapi = qownnotesapiApp;
        };
        extraAppsEnable = true;
      };
      networking.firewall.allowedTCPPorts = [
        80
        443
      ];
      environment.etc."nextcloud-adminpass".text = "adminpass";
    };
  };

  node32 = if has32 then mkNode pkg32 "nextcloud32" else { };
  node33 = if has33 then mkNode pkg33 "nextcloud33" else { };
  node34 = if has34 then mkNode pkg34 "nextcloud34" else { };

in
# Fail early if any required Nextcloud package is missing
assert (lib.assertMsg has32 "Missing required package: nextcloud32 (expected in pkgs26_05)");
assert (lib.assertMsg has33 "Missing required package: nextcloud33 (expected in pkgs26_05)");
assert (lib.assertMsg has34 "Missing required package: nextcloud34 (expected in pkgs26_05)");

pkgs26_05.testers.nixosTest {
  name = "nextcloud_qownnotesapi";
  nodes = node32 // node33 // node34;
  interactive.sshBackdoor.enable = true; # provides ssh-config & vsock access (needs host vsock support)
  testScript = ''
    import json
    import shlex
    import time
    from urllib.parse import urlencode

    print("Has32=${toString has32} Has33=${toString has33} Has34=${toString has34}")
    start_all()

    def dav_request(node, method, path, data=None):
        command = f"curl -fsS -u admin:adminpass -X {method}"
        if data is not None:
            command += f" --data-binary {shlex.quote(data)}"
        command += " " + shlex.quote(f"http://localhost/remote.php/dav/files/admin/{path}")
        return node.succeed(command)

    def api_get(node, endpoint, params=None):
        query = "?" + urlencode(params or {}, doseq=True)
        url = f"http://localhost/index.php/apps/qownnotesapi/api/v1/note/{endpoint}{query}"
        return json.loads(node.succeed("curl -fsS -u admin:adminpass " + shlex.quote(url)))

    def test_version(node, label, pkg_version):
        print(f"Testing Nextcloud {label} ({pkg_version})")
        node.wait_for_unit("phpfpm-nextcloud.service")
        node.wait_for_unit("nginx.service")
        node.succeed("curl -fsSL http://localhost/status.php | grep 'installed' | grep 'true'")
        node.succeed("sudo -u nextcloud nextcloud-occ app:list | grep -i qownnotesapi || (echo 'App missing ({label})'; sudo -u nextcloud nextcloud-occ app:list; exit 1)")
        assert "200" in node.succeed("curl -s -o /dev/null -w '%{http_code}' http://localhost/login"), "Login page needs to show up!"
        node.succeed("sudo -u nextcloud nextcloud-occ status | grep -i 'version:'")

        missing_path_info = api_get(node, "app_info", {"notes_path": "/Missing"})
        assert missing_path_info["user"] == "admin"
        assert missing_path_info["versions_app"] is True
        assert missing_path_info["trash_app"] is True
        assert missing_path_info["versioning"] is True
        assert missing_path_info["app_version"] == "26.8.0"
        assert missing_path_info["server_version"].startswith(label + ".")
        assert missing_path_info["notes_path_exists"] is False

        dav_request(node, "MKCOL", "Notes")
        dav_request(node, "MKCOL", "Other")
        notes_path_info = api_get(node, "app_info", {"notes_path": "/Notes"})
        assert notes_path_info["notes_path_exists"] is True

        original_note = "First version of the note\n"
        current_note = "Current version of the note\n"
        dav_request(node, "PUT", "Notes/versioned.md", original_note)
        time.sleep(1)
        dav_request(node, "PUT", "Notes/versioned.md", current_note)

        versions = api_get(node, "versions", {"file_name": "/Notes/versioned.md"})
        assert versions["file_name"] == "/Notes/versioned.md"
        assert versions["error_messages"] == []
        assert len(versions["versions"]) >= 1
        assert any(version["data"] == original_note for version in versions["versions"])
        assert all(version["timestamp"] > 0 for version in versions["versions"])
        assert all(version["humanReadableTimestamp"] for version in versions["versions"])
        assert all(version["diffHtml"] for version in versions["versions"])

        dav_request(node, "PUT", "Notes/trashed.md", "Trashed Markdown note\n")
        dav_request(node, "PUT", "Notes/custom.qnote", "Custom extension note\n")
        dav_request(node, "PUT", "Notes/ignored.json", '{"ignored": true}\n')
        dav_request(node, "PUT", "Other/outside.md", "Note outside requested directory\n")
        dav_request(node, "DELETE", "Notes/trashed.md")
        dav_request(node, "DELETE", "Notes/custom.qnote")
        dav_request(node, "DELETE", "Notes/ignored.json")
        dav_request(node, "DELETE", "Other/outside.md")

        trash = api_get(node, "trashed", {"dir": "/Notes/", "extensions[]": ["qnote"]})
        assert trash["directory"] == "Notes"
        trashed_notes = {note["fileName"]: note for note in trash["notes"]}
        assert set(trashed_notes) == {"trashed.md", "custom.qnote"}
        assert trashed_notes["trashed.md"]["noteName"] == "trashed"
        assert trashed_notes["trashed.md"]["data"] == "Trashed Markdown note\n"
        assert trashed_notes["custom.qnote"]["data"] == "Custom extension note\n"
        assert all(note["timestamp"] > 0 for note in trash["notes"])
        assert all(note["dateString"] for note in trash["notes"])

        deleted_note = trashed_notes["trashed.md"]
        restore = api_get(node, "restore_trashed", {
            "file_name": "/Notes/trashed.md",
            "timestamp": deleted_note["timestamp"],
        })
        assert restore["result"] is True
        assert restore["filename"] == "trashed.md"
        assert dav_request(node, "GET", "Notes/trashed.md") == "Trashed Markdown note\n"

        trash_after_restore = api_get(node, "trashed", {"dir": "/Notes/", "extensions[]": ["qnote"]})
        remaining_names = {note["fileName"] for note in trash_after_restore["notes"]}
        assert "trashed.md" not in remaining_names
        assert "custom.qnote" in remaining_names

    ${
      if has32 then
        ''test_version(nextcloud32, "32", "${pkg32.version}")''
      else
        ''print("Skipping Nextcloud 32: package not present")''
    }

    ${
      if has33 then
        ''test_version(nextcloud33, "33", "${pkg33.version}")''
      else
        ''print("Skipping Nextcloud 33: package not present")''
    }

    ${
      if has34 then
        ''test_version(nextcloud34, "34", "${pkg34.version}")''
      else
        ''print("Skipping Nextcloud 34: package not present")''
    }
    print("ALL_TESTS_DONE")
  '';
}
