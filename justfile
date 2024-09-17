# Use `just <recipe>` to run a recipe
# https://just.systems/man/en/

# By default, run the `--choose` command
default:
    @just --choose

# Variables

transferDir := `if [ -d "$HOME/NextcloudPrivate/Transfer" ]; then echo "$HOME/NextcloudPrivate/Transfer"; else echo "$HOME/Nextcloud/Transfer"; fi`

# Open a terminal with the qownnotesapi session
term:
    zellij --layout term.kdl attach qownnotesapi -c

# Kill the qownnotesapi session
term-kill:
    zellij delete-session qownnotesapi -f

git-apply-patch:
    git apply {{ transferDir }}/qownnotesapi.patch

# Format all justfiles
just-format:
    #!/usr/bin/env bash
    # Find all files named "justfile" recursively and run just --fmt --unstable on them
    find . -type f -name "justfile" -print0 | while IFS= read -r -d '' file; do
        echo "Formatting $file"
        just --fmt --unstable -f "$file"
    done
