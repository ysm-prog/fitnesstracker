#!/usr/bin/env bash
#
# Install this framework's .claude directory into a target repository without
# overwriting anything already there.
#
# A plain `cp -R` is destructive: any repository that already uses Claude Code
# has its own .claude directory, and a file that exists on both sides — an agent
# or skill of the same name — is silently replaced by the framework's generic
# version. That guidance is almost always worse than what the project wrote for
# itself, and nothing tells the operator it happened.
#
# This script never overwrites. Files that exist on both sides are reported as
# collisions and left alone, for a human to reconcile deliberately.
#
# Usage:
#   scripts/install-into.sh <target-repo>            # preview, changes nothing
#   scripts/install-into.sh <target-repo> --apply    # copy the new files only

set -euo pipefail

FRAMEWORK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SOURCE="${FRAMEWORK_ROOT}/.claude"

usage() {
    echo "usage: $(basename "$0") <target-repo> [--apply]" >&2
    echo >&2
    echo "  Previews by default. Pass --apply to copy the new files." >&2
    echo "  Existing files are never overwritten." >&2
}

if [ $# -lt 1 ] || [ $# -gt 2 ]; then
    usage
    exit 1
fi

TARGET_REPO="$1"
APPLY="no"

if [ $# -eq 2 ]; then
    if [ "$2" = "--apply" ]; then
        APPLY="yes"
    else
        echo "error: unknown option '$2'" >&2
        usage
        exit 1
    fi
fi

if [ ! -d "$TARGET_REPO" ]; then
    echo "error: target '$TARGET_REPO' is not a directory" >&2
    exit 1
fi

TARGET_REPO="$(cd "$TARGET_REPO" && pwd)"

if [ "$TARGET_REPO" = "$FRAMEWORK_ROOT" ]; then
    echo "error: target is the framework itself" >&2
    exit 1
fi

if [ ! -d "$SOURCE" ]; then
    echo "error: no .claude directory at '$SOURCE'" >&2
    exit 1
fi

version="unknown"
[ -f "${SOURCE}/VERSION" ] && version="$(cat "${SOURCE}/VERSION")"

new_files=()
collisions=()

while IFS= read -r relative; do
    if [ -e "${TARGET_REPO}/.claude/${relative}" ]; then
        collisions+=("$relative")
    else
        new_files+=("$relative")
    fi
done < <(cd "$SOURCE" && find . -type f | sed 's|^\./||' | sort)

echo "Framework ${version}  ->  ${TARGET_REPO}"
echo

if [ ${#new_files[@]} -gt 0 ]; then
    if [ "$APPLY" = "yes" ]; then
        echo "Installed ${#new_files[@]} file(s):"
    else
        echo "Would install ${#new_files[@]} file(s):"
    fi
    for relative in "${new_files[@]}"; do
        echo "  + .claude/${relative}"
        if [ "$APPLY" = "yes" ]; then
            mkdir -p "$(dirname "${TARGET_REPO}/.claude/${relative}")"
            cp "${SOURCE}/${relative}" "${TARGET_REPO}/.claude/${relative}"
        fi
    done
    echo
fi

if [ ${#collisions[@]} -gt 0 ]; then
    echo "Skipped ${#collisions[@]} file(s) that already exist — NOT overwritten:"
    for relative in "${collisions[@]}"; do
        echo "  ! .claude/${relative}"
    done
    echo
    echo "  Each of these exists in both the framework and the project. The project's"
    echo "  version is usually the better one, because it knows the domain. Compare them"
    echo "  by hand and keep what serves the project; do not assume the framework wins."
    echo
fi

if [ ${#new_files[@]} -eq 0 ] && [ ${#collisions[@]} -eq 0 ]; then
    echo "Nothing to install."
    exit 0
fi

# Skill count is worth seeing: guidance stops helping once there is too much of
# it to choose from, and a wholesale install can easily double a project's set.
if [ -d "${TARGET_REPO}/.claude/skills" ]; then
    project_skills=$(find "${TARGET_REPO}/.claude/skills" -mindepth 1 -maxdepth 1 -type d | wc -l | tr -d ' ')
    framework_skills=$(find "${SOURCE}/skills" -mindepth 1 -maxdepth 1 -type d | wc -l | tr -d ' ')
    if [ "$APPLY" = "yes" ]; then
        echo "The project now has ${project_skills} skill(s)."
    else
        echo "The project has $((project_skills)) skill(s); this would add up to ${framework_skills} more."
    fi
    echo "  Review the result. Copying every standard and skill to gain the two or three"
    echo "  a project actually lacks makes the rest harder to choose between. Installing"
    echo "  only what is missing is a legitimate and usually better adoption."
    echo
fi

if [ "$APPLY" != "yes" ]; then
    echo "Preview only — nothing was written. Re-run with --apply to install."
fi
