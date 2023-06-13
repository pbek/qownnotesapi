{ pkgs ? import <nixpkgs> {} }:
  pkgs.mkShell {
    # nativeBuildInputs is usually what you want -- tools you need to run
    nativeBuildInputs = [
      pkgs.gnumake
      pkgs.php82
      pkgs.php82Packages.composer
    ];
}

