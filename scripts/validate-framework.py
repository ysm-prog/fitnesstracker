#!/usr/bin/env python3
"""Validate the structural rules this framework relies on.

The framework is documentation, so the usual build and test gates do not apply.
These are the invariants that have actually broken in practice:

1. Every skill and agent carries the frontmatter Claude Code needs to load it,
   with a name matching its location on disk.
2. Every repository path mentioned in the documentation resolves, so instructions
   such as "use `.claude/templates/project-claude.md`" cannot silently rot.
3. `.claude/VERSION` matches the newest version in `CHANGELOG.md`, so a project can
   tell which framework version it copied.
4. The catalogue in `docs/README.md` lists everything the framework actually ships,
   so a new skill or workflow cannot be added and then go undiscovered.
5. Every workflow has a command skill that invokes it, so a workflow cannot be added
   without an entry point.
6. The YSM scaffold files referenced by the documentation exist together.

Local divergence from the upstream framework: this repository is an application,
not the framework itself, so dependency directories are excluded from the
documented-path scan. See docs/decisions/ADR-0003-vendored-framework-validator.md.

Run from the repository root: python3 scripts/validate-framework.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent

PATH_PREFIXES = (".claude/", "docs/", "scripts/")
INLINE_CODE = re.compile(r"`([^`\n]+)`")
CHANGELOG_VERSION = re.compile(r"^## \[(\d+\.\d+\.\d+)\]")

errors: list[str] = []


def error(message: str) -> None:
    errors.append(message)


def relative(path: Path) -> str:
    """Report paths relative to the repository root so output is stable in CI."""
    try:
        return path.relative_to(REPO_ROOT).as_posix()
    except ValueError:
        return path.as_posix()


def parse_frontmatter(path: Path) -> dict[str, str] | None:
    """Return the top-level key/value pairs of a file's YAML frontmatter."""
    lines = path.read_text().splitlines()
    if not lines or lines[0].strip() != "---":
        return None
    try:
        closing = lines.index("---", 1)
    except ValueError:
        return None

    fields: dict[str, str] = {}
    for line in lines[1:closing]:
        if not line.strip() or line.startswith((" ", "\t")):
            continue
        key, separator, value = line.partition(":")
        if separator:
            fields[key.strip()] = value.strip()
    return fields


def check_frontmatter(path: Path, expected_name: str, kind: str) -> None:
    fields = parse_frontmatter(path)
    if fields is None:
        error(f"{relative(path)}: {kind} is missing YAML frontmatter delimited by ---")
        return
    for key in ("name", "description"):
        if not fields.get(key):
            error(f"{relative(path)}: {kind} frontmatter is missing a non-empty '{key}'")
    name = fields.get("name")
    if name and name != expected_name:
        error(f"{relative(path)}: frontmatter name '{name}' does not match '{expected_name}'")


def check_skills() -> None:
    skills_dir = REPO_ROOT / ".claude" / "skills"
    if not skills_dir.is_dir():
        error(f"{relative(skills_dir)}: directory not found")
        return
    for skill_dir in sorted(p for p in skills_dir.iterdir() if p.is_dir()):
        skill_file = skill_dir / "SKILL.md"
        if not skill_file.is_file():
            error(f"{relative(skill_dir)}: skill directory has no SKILL.md")
            continue
        check_frontmatter(skill_file, skill_dir.name, "skill")


def check_agents() -> None:
    agents_dir = REPO_ROOT / ".claude" / "agents"
    if not agents_dir.is_dir():
        error(f"{relative(agents_dir)}: directory not found")
        return
    for agent_file in sorted(agents_dir.glob("*.md")):
        check_frontmatter(agent_file, agent_file.stem, "agent")


def referenced_paths(markdown: str):
    """Yield repository paths mentioned in inline code, skipping fenced blocks."""
    in_fence = False
    for line in markdown.splitlines():
        if line.lstrip().startswith("```"):
            in_fence = not in_fence
            continue
        if in_fence:
            continue
        for token in INLINE_CODE.findall(line):
            token = token.strip()
            if not token.startswith(PATH_PREFIXES):
                continue
            if "<" in token or "*" in token:
                continue
            yield token.rstrip("/")


# Directories whose Markdown belongs to somebody else. Dependency trees carry
# their own docs referencing their own paths, which say nothing about this
# repository's documentation staying accurate.
EXCLUDED_DIRS = (".git/", "vendor/", "node_modules/", "storage/", "frontend/.next/")


def is_excluded(path: Path) -> bool:
    posix = path.relative_to(REPO_ROOT).as_posix() if path.is_relative_to(REPO_ROOT) else path.as_posix()
    return any(posix.startswith(d) or f"/{d}" in f"/{posix}" for d in EXCLUDED_DIRS)


def check_documented_paths() -> None:
    for markdown_file in sorted(REPO_ROOT.rglob("*.md")):
        if is_excluded(markdown_file):
            continue
        for token in referenced_paths(markdown_file.read_text()):
            if not (REPO_ROOT / token).exists():
                error(f"{relative(markdown_file)}: references '{token}', which does not exist")


def check_version() -> None:
    version_file = REPO_ROOT / ".claude" / "VERSION"
    changelog = REPO_ROOT / "CHANGELOG.md"
    if not version_file.is_file():
        error(f"{relative(version_file)}: file not found")
        return
    if not changelog.is_file():
        error(f"{relative(changelog)}: file not found")
        return

    version = version_file.read_text().strip()
    released = [
        match.group(1)
        for match in (CHANGELOG_VERSION.match(line) for line in changelog.read_text().splitlines())
        if match
    ]
    if not released:
        error("CHANGELOG.md: no '## [x.y.z]' release heading found")
    elif version != released[0]:
        error(
            f".claude/VERSION is {version} but the newest CHANGELOG.md release is {released[0]}"
        )


def check_catalogue() -> None:
    """Every skill, standard, workflow, agent, and template must be catalogued."""
    catalogue = REPO_ROOT / "docs" / "README.md"
    if not catalogue.is_file():
        error(f"{relative(catalogue)}: file not found")
        return

    listed = catalogue.read_text()
    shipped = [
        *(REPO_ROOT / ".claude" / "skills").glob("*/SKILL.md"),
        *(REPO_ROOT / ".claude" / "standards").glob("*.md"),
        *(REPO_ROOT / ".claude" / "workflows").glob("*.md"),
        *(REPO_ROOT / ".claude" / "agents").glob("*.md"),
        *(REPO_ROOT / ".claude" / "templates").glob("*.md"),
    ]
    for path in sorted(shipped):
        if relative(path) not in listed:
            error(f"docs/README.md: catalogue does not list '{relative(path)}'")


def check_workflow_commands() -> None:
    """Every workflow needs a command skill, or nothing invokes it."""
    for workflow in sorted((REPO_ROOT / ".claude" / "workflows").glob("*.md")):
        command = REPO_ROOT / ".claude" / "skills" / workflow.stem / "SKILL.md"
        if not command.is_file():
            error(f"{relative(workflow)}: no command skill at '{relative(command)}' invokes it")
        elif relative(workflow) not in command.read_text():
            error(f"{relative(command)}: does not reference '{relative(workflow)}'")


def check_ysm_scaffold() -> None:
    """Keep the YSM scaffold documentation and template present together."""
    required = [
        REPO_ROOT / ".claude" / "templates" / "ysm-project-claude.md",
        REPO_ROOT / "docs" / "YSM-PROJECT-SCAFFOLD.md",
    ]
    for path in required:
        if not path.is_file():
            error(f"YSM scaffold: required file '{relative(path)}' is missing")


def main() -> int:
    check_skills()
    check_agents()
    check_documented_paths()
    check_version()
    check_catalogue()
    check_workflow_commands()
    check_ysm_scaffold()

    if errors:
        print(f"Framework validation failed with {len(errors)} problem(s):\n")
        for message in errors:
            print(f"  - {message}")
        return 1

    print("Framework validation passed.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
