{
  description = "Nextcloud qownnotesapi app NixOS VM tests (Nextcloud 28-32)";

  inputs = {
    # Add 24.11 channel for Nextcloud 29
    nixpkgs24_11.url = "github:NixOS/nixpkgs/nixos-24.11";
    # Add 25.05 channel for Nextcloud 30 & 31
    nixpkgs25_05.url = "github:NixOS/nixpkgs/nixos-25.05";
    # Add unstable channel for Nextcloud 32
    nixpkgs25_11.url = "github:NixOS/nixpkgs/daebeba791763abfe3cce5e0f16376ddf1b724d4";
  };

  outputs =
    {
      nixpkgs24_11,
      nixpkgs25_05,
      nixpkgs25_11,
      ...
    }:
    let
      system = "x86_64-linux";
      baseConfig = {
        # config.permittedInsecurePackages = [ "nextcloud-28.0.14" ];
      };
      pkgs24_11 = import nixpkgs24_11 ({ inherit system; } // baseConfig);
      pkgs25_05 = import nixpkgs25_05 ({ inherit system; } // baseConfig);
      pkgs25_11 = import nixpkgs25_11 ({ inherit system; } // baseConfig);
      combinedTest = import ./tests/vm/basic.nix {
        inherit pkgs24_11 pkgs25_05 pkgs25_11;
      };
    in
    {
      nixosTests = {
        nextcloud-qownnotesapi = combinedTest;
      };
    };
}
