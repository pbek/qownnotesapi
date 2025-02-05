# Use `just <recipe>` to run a recipe
# https://just.systems/man/en/

# By default, run the `--list` command
default:
    @just --list

# Variables

transferDir := `if [ -d "$HOME/NextcloudPrivate/Transfer" ]; then echo "$HOME/NextcloudPrivate/Transfer"; else echo "$HOME/Nextcloud/Transfer"; fi`
projectName := 'qownnotesapi'

# Open a terminal with the project session
[group('dev')]
term:
    zellij --layout term.kdl attach {{ projectName }} -c

# Kill the project session
[group('dev')]
term-kill:
    zellij delete-session {{ projectName }} -f

# Apply the patch to the project repository
[group('patch')]
git-apply-patch:
    git apply {{ transferDir }}/{{ projectName }}.patch

# Create a patch from the staged changes in the project repository
[group('patch')]
@git-create-patch:
    echo "transferDir: {{ transferDir }}"
    git diff --no-ext-diff --staged --binary > {{ transferDir }}/{{ projectName }}.patch
    ls -l1t {{ transferDir }}/ | head -2

# Run the GitHub Actions test workflow locally with act
[group('dev')]
github-run-test:
    act -W .github/workflows/test.yml

# Open the project in the browser
[group('dev')]
open-browser:
    xdg-open http://localhost:8081

# Format all justfiles
[group('linter')]
just-format:
    #!/usr/bin/env bash
    # Find all files named "justfile" recursively and run just --fmt --unstable on them
    find . -type f -name "justfile" -print0 | while IFS= read -r -d '' file; do
        echo "Formatting $file"
        just --fmt --unstable -f "$file"
    done
