{
  pkgs,
  ...
}:

{
  # https://devenv.sh/packages/
  packages = with pkgs; [
    zellij
  ];

  enterShell = ''
    echo "🛠️ QOwnNotesAPI dev shell"
  '';

  # https://devenv.sh/git-hooks/
  git-hooks = {
    excludes = [ "appinfo/signature.json" ];
  };

  # See full reference at https://devenv.sh/reference/options/
}
