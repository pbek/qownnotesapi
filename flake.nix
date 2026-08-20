{
  description = "Nextcloud qownnotesapi app NixOS VM tests (Nextcloud 32-34)";

  inputs = {
    # NixOS 26.05 provides all supported stable Nextcloud versions
    nixpkgs26_05.url = "github:NixOS/nixpkgs/nixos-26.05";
  };

  outputs =
    {
      nixpkgs26_05,
      ...
    }:
    let
      system = "x86_64-linux";
      pkgs26_05 = import nixpkgs26_05 { inherit system; };
      combinedTest = import ./tests/vm/basic.nix {
        inherit pkgs26_05;
      };
    in
    {
      nixosTests = {
        nextcloud-qownnotesapi = combinedTest;
      };
    };
}
