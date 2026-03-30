#!/usr/bin/env python3
"""Build a local monster compendium from the official 2024 Basic Rules page.

This keeps monster metadata in the app without any runtime dependency on
D&D Beyond. The output intentionally stores factual stat-block metadata and
section names rather than verbatim full rules text.
"""

from __future__ import annotations

from datetime import date
from html import unescape
from pathlib import Path
from typing import Any
from urllib.request import Request, urlopen
import re


SOURCE_URL = "https://www.dndbeyond.com/sources/dnd/br-2024/creature-stat-blocks"
USER_AGENT = "Mozilla/5.0 (compatible; AdventurersLedger/1.0; +local-build)"
REPO_ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = REPO_ROOT / "config" / "dnd_monsters.php"

SECTION_LABELS = {
    "traits": "trait_names",
    "actions": "action_names",
    "bonus actions": "bonus_action_names",
    "reactions": "reaction_names",
    "legendary actions": "legendary_action_names",
    "lair actions": "lair_action_names",
    "mythic actions": "mythic_action_names",
}


def fetch_html(url: str) -> str:
    request = Request(url, headers={"User-Agent": USER_AGENT})

    with urlopen(request, timeout=30) as response:
        return response.read().decode("utf-8", "ignore")


def clean_html(text: str) -> str:
    text = unescape(text)
    text = (
        text.replace("\xa0", " ")
        .replace("\u2019", "'")
        .replace("\u2018", "'")
        .replace("\u201c", '"')
        .replace("\u201d", '"')
        .replace("\u2013", "-")
        .replace("\u2014", "-")
        .replace("\u2212", "-")
    )
    text = re.sub(r"<[^>]+>", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    text = re.sub(r"\s+([,.;:])", r"\1", text)
    text = re.sub(r"\(\s+", "(", text)
    text = re.sub(r"\s+\)", ")", text)
    return text


def extract_stat_blocks(html: str) -> list[str]:
    blocks: list[str] = []
    marker = '<div class="stat-block"'
    position = 0

    while True:
        start = html.find(marker, position)
        if start == -1:
            break

        depth = 0
        cursor = start

        while cursor < len(html):
            next_open = html.find("<div", cursor)
            next_close = html.find("</div>", cursor)

            if next_close == -1:
                raise RuntimeError("Could not find the end of a monster stat block.")

            if next_open != -1 and next_open < next_close:
                depth += 1
                cursor = next_open + 4
                continue

            depth -= 1
            cursor = next_close + len("</div>")

            if depth == 0:
                blocks.append(html[start:cursor])
                position = cursor
                break

    return blocks


def first_match(pattern: str, text: str) -> str:
    match = re.search(pattern, text, re.S)
    return clean_html(match.group(1)) if match else ""


def parse_label_paragraphs(block: str) -> dict[str, str]:
    labels: dict[str, str] = {}

    for paragraph in re.findall(r"<p\b[^>]*>(.*?)</p>", block, re.S):
        match = re.match(r"\s*<strong>([^<]+)</strong>\s*(.*)", paragraph, re.S)
        if not match:
            continue

        label = clean_html(match.group(1)).rstrip(":")
        if label in {"AC", "HP", "Speed"}:
            continue

        labels[label] = clean_html(match.group(2))

    return labels


def extract_section_names(block: str) -> dict[str, list[str]]:
    matches = list(re.finditer(r'<p class="monster-header"[^>]*>(.*?)</p>', block, re.S))
    sections = {value: [] for value in SECTION_LABELS.values()}

    for index, match in enumerate(matches):
        raw_title = clean_html(match.group(1)).lower()
        key = SECTION_LABELS.get(raw_title)
        if key is None:
            continue

        start = match.end()
        end = matches[index + 1].start() if index + 1 < len(matches) else len(block)
        section_html = block[start:end]
        names: list[str] = []

        for paragraph in re.findall(r"<p\b[^>]*>(.*?)</p>", section_html, re.S):
            name_match = re.search(r"<strong>\s*<em>(.*?)</em>\s*</strong>", paragraph, re.S)
            if not name_match:
                continue

            name = clean_html(name_match.group(1)).rstrip(".:")
            if name and name not in names:
                names.append(name)

        sections[key] = names

    return sections


def parse_abilities(block: str) -> dict[str, int]:
    abilities: dict[str, int] = {}

    for label, score, _modifier, _save in re.findall(
        r"<tr>\s*<th>(Str|Dex|Con|Int|Wis|Cha)</th>\s*<td>([^<]+)</td>\s*<td>([^<]+)</td>\s*<td>([^<]+)</td>\s*</tr>",
        block,
        re.S,
    ):
        abilities[label.lower()] = int(clean_html(score))

    return abilities


def parse_type_line(type_line: str) -> tuple[str, str, str]:
    if not type_line:
        return "", "", ""

    type_part, alignment = (type_line.split(",", 1) + [""])[:2]
    type_part = type_part.strip()
    alignment = alignment.strip()
    size, creature_type = (type_part.split(" ", 1) + [""])[:2]
    return size.strip(), creature_type.strip(), alignment


def parse_cr_details(value: str) -> tuple[str, str, str]:
    cr = ""
    xp = ""
    proficiency_bonus = ""

    cr_match = re.match(r"([^()]+)", value)
    if cr_match:
        cr = cr_match.group(1).strip()

    xp_match = re.search(r"XP ([^;)]+(?:; [^)]*)?)", value)
    if xp_match:
        xp = xp_match.group(1).strip()

    pb_match = re.search(r"(PB [^)]+)", value)
    if pb_match:
        proficiency_bonus = pb_match.group(1).strip()

    return cr, xp, proficiency_bonus


def build_summary(monster: dict[str, Any]) -> str:
    bits = []

    if monster["size"] or monster["creature_type"]:
        identity = " ".join(filter(None, [monster["size"], monster["creature_type"]])).strip()
        if monster["alignment"]:
            bits.append(f"{identity}, {monster['alignment']}".strip(", "))
        else:
            bits.append(identity)

    combat_bits = []
    if monster["ac"]:
        combat_bits.append(f"AC {monster['ac']}")
    if monster["hp"]:
        combat_bits.append(f"HP {monster['hp']}")
    if monster["speed"]:
        combat_bits.append(f"Speed {monster['speed']}")
    if monster["cr"]:
        combat_bits.append(f"CR {monster['cr']}")

    if combat_bits:
        bits.append(", ".join(combat_bits))

    spotlight = []
    for key in ("trait_names", "action_names", "legendary_action_names"):
        spotlight.extend(monster.get(key, []))
        if len(spotlight) >= 3:
            break

    if spotlight:
        bits.append("Highlights: " + ", ".join(spotlight[:3]))

    return ". ".join(bit.rstrip(".") for bit in bits if bit) + "."


def parse_monster(block: str) -> dict[str, Any]:
    name_match = re.search(
        r'<a class="tooltip-hover monster-tooltip" href="([^"]+)"[^>]*>([^<]+)</a>',
        block,
        re.S,
    )
    if not name_match:
        raise RuntimeError("A stat block was missing its monster name.")

    path = name_match.group(1)
    name = clean_html(name_match.group(2))
    paragraphs = [clean_html(entry) for entry in re.findall(r"<p\b[^>]*>(.*?)</p>", block, re.S)]
    type_line = paragraphs[0] if paragraphs else ""

    ac = first_match(r"<strong>AC</strong>\s*([^<]+?)\s*<strong>Initiative</strong>", block)
    initiative = first_match(r"<strong>Initiative</strong>\s*([^<]+)", block)
    hp = first_match(r"<strong>HP</strong>\s*([^<]+)", block)
    speed = first_match(r"<strong>Speed</strong>\s*([^<]+)", block)

    labels = parse_label_paragraphs(block)
    size, creature_type, alignment = parse_type_line(type_line)
    cr, xp, proficiency_bonus = parse_cr_details(labels.get("CR", ""))
    sections = extract_section_names(block)

    monster = {
        "name": name,
        "summary": "",
        "size": size,
        "creature_type": creature_type,
        "alignment": alignment,
        "ac": ac,
        "initiative": initiative,
        "hp": hp,
        "speed": speed,
        "cr": cr,
        "xp": xp,
        "proficiency_bonus": proficiency_bonus,
        "skills": labels.get("Skills", ""),
        "senses": labels.get("Senses", ""),
        "languages": labels.get("Languages", ""),
        "resistances": labels.get("Resistances", ""),
        "immunities": labels.get("Immunities", ""),
        "condition_immunities": labels.get("Condition Immunities", ""),
        "vulnerabilities": labels.get("Vulnerabilities", ""),
        "gear": labels.get("Gear", ""),
        "abilities": parse_abilities(block),
        "trait_names": sections["trait_names"],
        "action_names": sections["action_names"],
        "bonus_action_names": sections["bonus_action_names"],
        "reaction_names": sections["reaction_names"],
        "legendary_action_names": sections["legendary_action_names"],
        "lair_action_names": sections["lair_action_names"],
        "mythic_action_names": sections["mythic_action_names"],
        "path": path,
    }
    monster["summary"] = build_summary(monster)
    return monster


def to_php(value: Any, indent: int = 0) -> str:
    padding = " " * indent
    next_padding = " " * (indent + 4)

    if isinstance(value, dict):
        if not value:
            return "[]"

        lines = ["["]
        for key, nested_value in value.items():
            lines.append(f"{next_padding}{to_php(str(key))} => {to_php(nested_value, indent + 4)},")
        lines.append(f"{padding}]")
        return "\n".join(lines)

    if isinstance(value, list):
        if not value:
            return "[]"

        lines = ["["]
        for nested_value in value:
            lines.append(f"{next_padding}{to_php(nested_value, indent + 4)},")
        lines.append(f"{padding}]")
        return "\n".join(lines)

    if isinstance(value, bool):
        return "true" if value else "false"

    if isinstance(value, int):
        return str(value)

    if value is None:
        return "null"

    escaped = str(value).replace("\\", "\\\\").replace("'", "\\'")
    return f"'{escaped}'"


def write_output(monsters: list[dict[str, Any]]) -> None:
    header = (
        "<?php\n\n"
        f"// Generated by scripts/build_dnd_monsters.py on {date.today().isoformat()}.\n"
        f"// Source: {SOURCE_URL}\n\n"
        "return "
    )
    OUTPUT_PATH.write_text(header + to_php(monsters, 0) + ";\n", encoding="utf-8")


def main() -> int:
    html = fetch_html(SOURCE_URL)
    deduped: dict[str, dict[str, Any]] = {}
    for block in extract_stat_blocks(html):
        monster = parse_monster(block)
        deduped[monster["path"]] = monster

    monsters = sorted(deduped.values(), key=lambda entry: entry["name"])

    if len(monsters) < 300:
        raise RuntimeError(f"Expected a large monster list from the official stat-block page, found only {len(monsters)} entries.")

    write_output(monsters)
    print(f"Wrote {len(monsters)} monsters to {OUTPUT_PATH}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
