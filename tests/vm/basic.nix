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
    print("Has32=${toString has32} Has33=${toString has33} Has34=${toString has34}")
    start_all()

    # Helper to test a Nextcloud node consistently
    def test_version(node, label, pkg_version):
        print(f"Testing Nextcloud {label} ({pkg_version})")
        node.wait_for_unit("phpfpm-nextcloud.service")
        node.wait_for_unit("nginx.service")
        node.succeed("curl -fsSL http://localhost/status.php | grep 'installed' | grep 'true'")
        node.succeed("sudo -u nextcloud nextcloud-occ app:list | grep -i qownnotesapi || (echo 'App missing ({label})'; sudo -u nextcloud nextcloud-occ app:list; exit 1)")
        assert "200" in node.succeed("curl -s -o /dev/null -w '%{http_code}' http://localhost/login"), "Login page needs to show up!"
        node.succeed("sudo -u nextcloud nextcloud-occ status | grep -i 'version:'")
        # Test qownnotesapi app endpoints
        assert "200" in node.succeed("curl -s -o /dev/null -w '%{http_code}' http://admin:adminpass@localhost/index.php/apps/qownnotesapi/api/v1/note/versions?format=json&file_name=/Notes/test.md"), "Version API request failed!"
        assert "200" in node.succeed("curl -s -o /dev/null -w '%{http_code}' http://admin:adminpass@localhost/index.php/apps/qownnotesapi/api/v1/note/trashed?format=json&dir=/Notes"), "Trash API request failed!"
        assert "200" in node.succeed("curl -s -o /dev/null -w '%{http_code}' http://admin:adminpass@localhost/index.php/apps/qownnotesapi/api/v1/note/app_info?notes_path=/Notes"), "App Info API request failed!"

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
