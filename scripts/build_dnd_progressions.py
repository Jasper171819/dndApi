#!/usr/bin/env python3
"""Build local 2024 class progression data from the official D&D Beyond page."""

from __future__ import annotations

from datetime import date
from html import unescape
from pathlib import Path
from typing import Any
from urllib.request import Request, urlopen
import re


SOURCE_URL = "https://www.dndbeyond.com/sources/dnd/br-2024/character-classes"
REPO_ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = REPO_ROOT / "config" / "dnd_progressions.php"
USER_AGENT = "Mozilla/5.0 (compatible; AdventurersLedger/1.0; +local-build)"
CLASS_NAMES = [
    "Barbarian",
    "Bard",
    "Cleric",
    "Druid",
    "Fighter",
    "Monk",
    "Paladin",
    "Ranger",
    "Rogue",
    "Sorcerer",
    "Warlock",
    "Wizard",
]


def fetch_html(url: str) -> str:
    request = Request(url, headers={"User-Agent": USER_AGENT})

    with urlopen(request, timeout=40) as response:
        return response.read().decode("utf-8", "ignore")


def clean_html(text: str) -> str:
    text = unescape(text)
    text = re.sub(r"<[^>]+>", " ", text)
    text = text.replace("\xa0", " ")
    text = text.replace("—", "-").replace("â€”", "-")
    text = text.replace("’", "'").replace("â€™", "'")
    text = text.replace("“", '"').replace("”", '"')
    text = re.sub(r"\s+", " ", text).strip()
    text = re.sub(r"\s+([,.;:])", r"\1", text)
    return text


def slugify(text: str) -> str:
    text = text.lower()
    text = re.sub(r"[^a-z0-9]+", "_", text)
    return text.strip("_")


def parse_features(text: str, class_name: str, subclass_name: str = "Subclass feature") -> list[str]:
    text = text.replace("Subclass feature", subclass_name)
    if text in {"", "-"}:
        return []
    return [feature.strip() for feature in text.split(",") if feature.strip()]


def find_table(tables: list[str], table_id: str) -> str:
    for table in tables:
        if f'id="{table_id}"' in table:
            return table

    raise RuntimeError(f"Could not find table {table_id}")


def parse_traits_table(table_html: str) -> dict[str, str]:
    body_match = re.search(r"<tbody>(.*?)</tbody>", table_html, re.S)
    rows = re.findall(r"<tr>(.*?)</tr>", body_match.group(1), re.S)
    traits: dict[str, str] = {}

    for row in rows:
        heading_match = re.search(r"<th[^>]*>(.*?)</th>", row, re.S)
        value_match = re.search(r"<td[^>]*>(.*?)</td>", row, re.S)

        if not heading_match or not value_match:
            continue

        key = slugify(clean_html(heading_match.group(1)))
        traits[key] = clean_html(value_match.group(1))

    return traits


def normalize_header(header: str) -> str:
    if header.isdigit():
        return f"slot_{header}"

    mapping = {
        "Level": "level",
        "Proficiency Bonus": "proficiency_bonus",
        "Class Features": "class_features",
    }

    return mapping.get(header, slugify(header))


def parse_feature_table(table_html: str, class_name: str) -> dict[int, dict[str, Any]]:
    header_rows = re.findall(r"<tr>(.*?)</tr>", re.search(r"<thead>(.*?)</thead>", table_html, re.S).group(1), re.S)
    headers = [clean_html(cell) for cell in re.findall(r"<th[^>]*>(.*?)</th>", header_rows[-1], re.S)]
    header_keys = [normalize_header(header) for header in headers]

    body_rows = re.findall(r"<tr>(.*?)</tr>", re.search(r"<tbody>(.*?)</tbody>", table_html, re.S).group(1), re.S)
    levels: dict[int, dict[str, Any]] = {}

    for row in body_rows:
        cells = [clean_html(cell) for cell in re.findall(r"<t[dh][^>]*>(.*?)</t[dh]>", row, re.S)]
        if len(cells) != len(header_keys):
            continue

        level = int(cells[0])
        entry: dict[str, Any] = {
            "level": level,
            "proficiency_bonus": int(cells[1].replace("+", "")),
            "features": parse_features(cells[2], class_name),
            "resources": {},
            "spell_slots": {},
        }

        for key, raw_value in zip(header_keys[3:], cells[3:], strict=True):
            if re.fullmatch(r"slot_\d+", key):
                if raw_value not in {"", "-"}:
                    entry["spell_slots"][key.removeprefix("slot_")] = int(raw_value)
                continue

            if raw_value not in {"", "-"}:
                if raw_value.isdigit():
                    entry["resources"][key] = int(raw_value)
                else:
                    entry["resources"][key] = raw_value

        levels[level] = entry

    return levels


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


def write_output(data: dict[str, Any]) -> None:
    header = (
        "<?php\n\n"
        f"// Generated by scripts/build_dnd_progressions.py on {date.today().isoformat()}.\n"
        f"// Source: {SOURCE_URL}\n\n"
        "return "
    )
    OUTPUT_PATH.write_text(header + to_php(data) + ";\n", encoding="utf-8")


def main() -> int:
    html = fetch_html(SOURCE_URL)
    tables = re.findall(r"<table\b.*?</table>", html, re.S)
    classes: dict[str, Any] = {}

    for class_name in CLASS_NAMES:
        traits_table = find_table(tables, f"Core{class_name}Traits")
        features_table = find_table(tables, f"{class_name}Features")

        classes[class_name] = {
            "traits": parse_traits_table(traits_table),
            "levels": parse_feature_table(features_table, class_name),
        }

    data = {
        "verified_at": date.today().isoformat(),
        "source_note": "Generated from the official D&D Beyond 2024 Basic Rules class tables.",
        "source_url": SOURCE_URL,
        "classes": classes,
    }

    write_output(data)
    print(f"Wrote {len(classes)} classes to {OUTPUT_PATH}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
