# Use `just <recipe>` to run a recipe
# https://just.systems/man/en/

# By default, run the `--list` command
default:
    @just --list

# Variables

transferDir := `if [ -d "$HOME/NextcloudPrivate/Transfer" ]; then echo "$HOME/NextcloudPrivate/Transfer"; else echo "$HOME/Nextcloud/Transfer"; fi`

# Open a terminal with the qownnotesapi session
[group('dev')]
term:
    zellij --layout term.kdl attach qownnotesapi -c

# Kill the qownnotesapi session
[group('dev')]
term-kill:
    zellij delete-session qownnotesapi -f

# Apply the patch to the qownnotesapi repository
[group('patch')]
git-apply-patch:
    git apply {{ transferDir }}/qownnotesapi.patch

# Create a patch from the staged changes in the qownnotesapi repository
[group('patch')]
@git-create-patch:
    echo "transferDir: {{ transferDir }}"
    git diff --no-ext-diff --staged --binary > {{ transferDir }}/qownnotesapi.patch
    ls -l1t {{ transferDir }}/ | head -2

# Format all justfiles
[group('linter')]
just-format:
    #!/usr/bin/env bash
    # Find all files named "justfile" recursively and run just --fmt --unstable on them
    find . -type f -name "justfile" -print0 | while IFS= read -r -d '' file; do
        echo "Formatting $file"
        just --fmt --unstable -f "$file"
    done
