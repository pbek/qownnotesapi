{
  pkgs,
  config,
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
    hooks = {
      php-cs-fixer = {
        # Override the entry to use vendor version instead of Nix-provided version
        # The Nix version (3.87.2) is too old and incompatible with PHP 8.4
        # The vendor version (3.93.1) works correctly
        entry = "${config.languages.php.package}/bin/php vendor/bin/php-cs-fixer fix";
      };
    };
  };

  # See full reference at https://devenv.sh/reference/options/
}
