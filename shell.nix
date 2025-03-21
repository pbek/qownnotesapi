{
  pkgs ? import <nixpkgs> { },
}:
pkgs.mkShell {
  # nativeBuildInputs is usually what you want -- tools you need to run
  nativeBuildInputs = with pkgs; [
    just
    zellij
    php83
    php83Packages.composer
    docker-slim
    act # Run GitHub Actions locally
  ];
}
