import re
from pathlib import Path

import pandas as pd


BASE_DIR = Path(__file__).resolve().parents[1]
RAW_FILE = BASE_DIR / "data" / "precios_unitarios_raw.txt"
OUTPUT_FILE = BASE_DIR / "precios_unitarios.xlsx"


def parse_amount(value: str) -> int | None:
    cleaned = value.replace(".", "").replace(",", "").strip()
    if not cleaned:
        return None
    return int(cleaned)


def main() -> None:
    raw_text = RAW_FILE.read_text(encoding="utf-8")

    current_category = ""
    rows: list[dict[str, object]] = []

    for line in raw_text.splitlines():
        stripped = line.strip()
        if not stripped:
            continue
        if stripped.upper().startswith("ANEXO"):
            break
        if stripped.upper().startswith("OFERTA PARA"):
            break
        if stripped.upper().startswith("CONTRATO DE"):
            break
        if stripped.upper().startswith("INFRAESTRUCTURA"):
            break
        if stripped.upper().startswith("ÍTEM"):
            continue
        if stripped.upper().startswith("VALOR NETO"):
            break

        match = re.match(r"^(\d+(?:\.\d+)*)\s+(.+)$", stripped)
        if not match:
            continue

        code = match.group(1)
        rest = match.group(2).strip()

        if "$" not in rest:
            current_category = f"{code} {rest}"
            continue

        desc_unit_part, prices_part = rest.split("$", 1)
        desc_unit_part = desc_unit_part.strip()

        unit_match = re.search(r"([A-Za-zÁÉÍÓÚÜÑñ0-9\"/°\.]+)$", desc_unit_part)
        if unit_match:
            unit = unit_match.group(1).strip()
            description = desc_unit_part[: unit_match.start()].strip()
        else:
            unit = ""
            description = desc_unit_part

        prices = re.findall(r"\$\s*([\d\.,]+)", "$" + prices_part)
        minimo = parse_amount(prices[0]) if len(prices) > 0 else None
        maximo = parse_amount(prices[1]) if len(prices) > 1 else None
        ofertado = parse_amount(prices[2]) if len(prices) > 2 else None

        rows.append(
            {
                "Categoria": current_category,
                "Codigo": code,
                "Descripcion": description,
                "Unidad": unit,
                "PrecioMinimo": minimo,
                "PrecioMaximo": maximo,
                "PrecioOfertado": ofertado,
            }
        )

    df = pd.DataFrame(rows)
    df.to_excel(OUTPUT_FILE, index=False)
    print(f"Archivo generado: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()


