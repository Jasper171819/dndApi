#!/usr/bin/env python3
"""Build a local 2024 spell compendium from the official D&D Beyond listing.

This script pulls the public 5.5e Core Rules spell index from D&D Beyond,
captures factual card metadata, and writes a local PHP data file that the app
can ship without any runtime dependency on the external site.
"""

from __future__ import annotations

from datetime import date
from html import unescape
from pathlib import Path
from typing import Any
from urllib.parse import urlencode
from urllib.request import Request, urlopen
import re


BASE_ENDPOINT = "https://www.dndbeyond.com/spells"
SOURCE_CATEGORY_ID = "24"
SOURCE_CATEGORY_NAME = "5.5e Core Rules"
REPO_ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = REPO_ROOT / "config" / "dnd_spells.php"
USER_AGENT = "Mozilla/5.0 (compatible; AdventurersLedger/1.0; +local-build)"

AOE_LABELS = {
    "aoe-cone": "cone",
    "aoe-cube": "cube",
    "aoe-cylinder": "cylinder",
    "aoe-emanation": "emanation",
    "aoe-hemisphere": "hemisphere",
    "aoe-line": "line",
    "aoe-radius": "radius",
    "aoe-sphere": "sphere",
}


def build_url(*, page: int | None = None, class_id: str | None = None) -> str:
    params: list[tuple[str, str]] = [("filter-source-category", SOURCE_CATEGORY_ID)]

    if class_id:
        params.append(("filter-class", class_id))

    if page and page > 1:
        params.append(("page", str(page)))

    return f"{BASE_ENDPOINT}?{urlencode(params)}"


def fetch_html(url: str) -> str:
    request = Request(url, headers={"User-Agent": USER_AGENT})

    with urlopen(request, timeout=30) as response:
        return response.read().decode("utf-8", "ignore")


def clean_html(text: str) -> str:
    text = re.sub(
        r'<i class="i-(aoe-[a-z-]+)"></i>',
        lambda match: f" {AOE_LABELS.get(match.group(1), match.group(1).replace('aoe-', '').replace('-', ' '))} ",
        text,
    )
    text = unescape(text)
    text = re.sub(r"<[^>]+>", " ", text)
    text = text.replace("\xa0", " ")
    text = re.sub(r"\s+", " ", text).strip()
    text = re.sub(r"\(\s+", "(", text)
    text = re.sub(r"\s+\)", ")", text)
    text = re.sub(r"\s+([,.;:])", r"\1", text)
    return text


def find_page_count(html: str) -> int:
    page_numbers = [int(value) for value in re.findall(r"[?&]page=(\d+)", html)]
    return max(page_numbers) if page_numbers else 1


def parse_level(level_label: str) -> int:
    if level_label.lower() == "cantrip":
        return 0

    match = re.match(r"(\d+)", level_label)

    if not match:
        raise ValueError(f"Could not parse spell level from {level_label!r}")

    return int(match.group(1))


def build_summary(spell: dict[str, Any]) -> str:
    def sentence_value(value: str) -> str:
        return value.rstrip(".")

    if spell["level"] == 0:
        bits = [f"{spell['school']} cantrip"]
    else:
        bits = [f"{spell['level_label']}-level {spell['school'].lower()} spell"]

    bits.append(f"Cast: {sentence_value(spell['casting_time'])}")
    bits.append(f"Range: {sentence_value(spell['range'])}")
    bits.append(f"Duration: {sentence_value(spell['duration'])}")

    if spell["attack_save"]:
        bits.append(f"Attack/Save: {spell['attack_save']}")

    if spell["damage_effect"]:
        bits.append(f"Effect: {spell['damage_effect']}")

    if spell["ritual"]:
        bits.append("Ritual")

    if spell["concentration"]:
        bits.append("Concentration")

    return ". ".join(bits) + "."


def parse_spell_cards(html: str) -> list[dict[str, Any]]:
    spells: list[dict[str, Any]] = []

    for raw_segment in html.split('<div class="info"')[1:]:
        segment = '<div class="info"' + raw_segment

        path_match = re.search(r'<a href="([^"]+)" class="link">(.*?)</a>', segment, re.S)
        level_match = re.search(r'<div class="row spell-level">\s*<span>(.*?)</span>', segment, re.S)
        school_match = re.search(r'<div class="row spell-name">.*?<span>\s*<span>(.*?)</span>', segment, re.S)
        components_match = re.search(r'<div class="row spell-name">.*?&bull;</span>\s*<span>(.*?)</span>', segment, re.S)
        casting_time_match = re.search(r'<div class="row spell-cast-time">\s*<span>(.*?)</span>', segment, re.S)
        duration_match = re.search(r'<div class="row spell-duration">\s*<span>(.*?)</span>', segment, re.S)
        range_match = re.search(r'<div class="row spell-range">(.*?)</div>', segment, re.S)
        attack_save_match = re.search(r'<div class="row spell-attack-save">(.*?)</div>', segment, re.S)
        damage_effect_match = re.search(r'<div class="row spell-damage-effect">(.*?)</div>', segment, re.S)

        if not all([path_match, level_match, school_match, casting_time_match, duration_match, range_match]):
            continue

        level_label = clean_html(level_match.group(1))
        spell = {
            "name": clean_html(path_match.group(2)),
            "summary": "",
            "level": parse_level(level_label),
            "level_label": level_label,
            "school": clean_html(school_match.group(1)),
            "components": clean_html(components_match.group(1)) if components_match else "",
            "casting_time": clean_html(casting_time_match.group(1)),
            "duration": clean_html(duration_match.group(1)),
            "range": clean_html(range_match.group(1)),
            "attack_save": clean_html(attack_save_match.group(1)) if attack_save_match else "",
            "damage_effect": clean_html(damage_effect_match.group(1)) if damage_effect_match else "",
            "ritual": 'class="i-ritual"' in segment,
            "concentration": 'class="i-concentration"' in segment,
            "classes": [],
            "source_category": SOURCE_CATEGORY_NAME,
            "path": path_match.group(1),
        }
        spell["summary"] = build_summary(spell)
        spells.append(spell)

    return spells


def parse_core_class_filters(html: str) -> list[tuple[str, str]]:
    select_match = re.search(r'<select id="filter-class".*?</select>', html, re.S)

    if not select_match:
        raise RuntimeError("Could not find the spell class filter list.")

    group_match = re.search(r'<optgroup label="5\.5e Core Rules">(.*?)</optgroup>', select_match.group(0), re.S)

    if not group_match:
        raise RuntimeError("Could not find the 5.5e Core Rules class options.")

    return [
        (clean_html(option_name), option_value)
        for option_value, option_name in re.findall(
            r'<option\s+value="(\d+)"[^>]*>\s*([^<]+?)\s*</option>',
            group_match.group(1),
            re.S,
        )
    ]


def fetch_spell_index(*, class_id: str | None = None) -> list[dict[str, Any]]:
    first_page = fetch_html(build_url(class_id=class_id))
    page_count = find_page_count(first_page)
    spells: list[dict[str, Any]] = []

    for page in range(1, page_count + 1):
        page_html = first_page if page == 1 else fetch_html(build_url(page=page, class_id=class_id))
        spells.extend(parse_spell_cards(page_html))

    return spells


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


def write_output(spells: list[dict[str, Any]]) -> None:
    header = (
        "<?php\n\n"
        f"// Generated by scripts/build_dnd_spells.py on {date.today().isoformat()}.\n"
        f"// Source: {build_url()}\n\n"
        "return "
    )
    OUTPUT_PATH.write_text(header + to_php(spells, 0) + ";\n", encoding="utf-8")


def main() -> int:
    spells = fetch_spell_index()

    if len(spells) != 391:
        raise RuntimeError(f"Expected 391 spells from the 5.5e Core Rules listing, found {len(spells)}.")

    core_classes = parse_core_class_filters(fetch_html(build_url()))
    spells_by_name = {spell["name"]: spell for spell in spells}

    for class_name, class_id in core_classes:
        for spell in fetch_spell_index(class_id=class_id):
            if spell["name"] in spells_by_name:
                spells_by_name[spell["name"]]["classes"].append(class_name)

    for spell in spells:
        spell["classes"] = sorted(set(spell["classes"]))

    write_output(spells)
    print(f"Wrote {len(spells)} spells to {OUTPUT_PATH}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
