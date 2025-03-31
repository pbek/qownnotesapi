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

  shellHook = ''
    # Determine the repository root
    REPO_ROOT=$(git rev-parse --show-toplevel 2>/dev/null)

    # Check if we are in the repository root
    if [ "$REPO_ROOT" = "$(pwd)" ]; then
      # Symlink the pre-commit hook into the .git/hooks directory
      ln -sf ../../scripts/pre-commit.sh .git/hooks/pre-commit
    fi
  '';
}
