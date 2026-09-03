"""
Analitički modul (Python + Pandas) za sistem rezervacija i upravljanja restoranima.

U produkcionom sistemu ovaj skript se povezuje na MySQL bazu (preko
`sqlalchemy` + `pymysql`) i čita podatke iz tabela `reservations`,
`restaurants`, `dishes` i `ratings`. Za potrebe demonstracije i testiranja
analitičkog modula u okviru ovog istraživačkog rada, skript generiše
sintetički skup podataka sa realističnim obrascima (vikend/veče su
popularniji termini, određeni restorani i jela su popularniji od drugih),
zatim primenjuje iste transformacije, agregacije i vizualizacije koje bi
se koristile i nad stvarnim podacima iz MySQL baze.

Izlazi:
  - reports/rezervacije_puni_export.csv   (sirovi, obogaćeni podaci)
  - reports/statistika_pregled.csv        (sažeti statistički pokazatelji)
  - reports/chart_*.png                   (grafički prikazi za rad i dashboard)
"""

import numpy as np
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import matplotlib.dates as mdates
from pathlib import Path

np.random.seed(42)
OUT = Path(__file__).parent / "reports"
OUT.mkdir(exist_ok=True)

# ---------------------------------------------------------------------------
# 1) Generisanje sintetičkih podataka (simulira SELECT iz MySQL baze)
# ---------------------------------------------------------------------------
RESTAURANTS = ["Stara Srpska Kuća", "Sonic Restaurant", "Zlatna Ribica",
               "Trpeza Balkana", "Rustika", "Villa Nova"]
POPULARITY_WEIGHT = [0.28, 0.22, 0.16, 0.14, 0.12, 0.08]  # neki restorani su traženiji
DISHES = {
    "Predjelo": ["Pršuta i sir", "Punjene pečurke", "Šopska salata"],
    "Glavno jelo": ["Karađorđeva šnicla", "Grilovani list", "Rižoto sa plodovima mora", "Pileći medaljoni"],
    "Dezert": ["Baklava", "Palačinke", "Tiramisu"],
    "Piće": ["Domaći sok", "Vino kuće", "Kafa"],
}
DISH_POPULARITY = {"Karađorđeva šnicla": 0.22, "Grilovani list": 0.10, "Rižoto sa plodovima mora": 0.13,
                    "Pileći medaljoni": 0.11, "Pršuta i sir": 0.09, "Punjene pečurke": 0.06,
                    "Šopska salata": 0.08, "Baklava": 0.07, "Palačinke": 0.06, "Tiramisu": 0.04,
                    "Domaći sok": 0.02, "Vino kuće": 0.01, "Kafa": 0.01}

N = 2600
start = pd.Timestamp("2026-01-01")
days = np.random.randint(0, 210, N)
dates = start + pd.to_timedelta(days, unit="D")

# Sati sa težinom ka večernjim terminima (19-21h)
hour_choices = [12, 12, 13, 13, 18, 19, 19, 20, 20, 20, 21, 21]
hours = np.random.choice(hour_choices, N)
minutes = np.random.choice([0, 30], N)

restaurant = np.random.choice(RESTAURANTS, N, p=POPULARITY_WEIGHT)
guest_count = np.random.choice([1, 2, 2, 2, 3, 4, 4, 5, 6], N)
dish = np.random.choice(list(DISH_POPULARITY.keys()), N, p=list(DISH_POPULARITY.values()))

# Status: vikend termini imaju nešto veći procenat potvrđenih, radni dan malo veći % otkazivanja
weekday = dates.dayofweek
status = []
for wd in weekday:
    if wd >= 4:  # petak, subota, nedelja
        status.append(np.random.choice(["confirmed", "completed", "cancelled"], p=[0.55, 0.35, 0.10]))
    else:
        status.append(np.random.choice(["confirmed", "completed", "cancelled"], p=[0.45, 0.35, 0.20]))

stars = np.clip(np.random.normal(4.2, 0.8, N).round().astype(int), 1, 5)

df = pd.DataFrame({
    "datum": dates,
    "sat": hours,
    "minut": minutes,
    "restoran": restaurant,
    "broj_gostiju": guest_count,
    "jelo": dish,
    "status": status,
    "ocena": stars,
})
df["termin"] = pd.to_datetime(df["datum"].dt.strftime("%Y-%m-%d") + " " +
                               df["sat"].astype(str).str.zfill(2) + ":" +
                               df["minut"].astype(str).str.zfill(2))
df["dan_u_nedelji"] = df["termin"].dt.day_name()
df["mesec"] = df["termin"].dt.month_name()

df.to_csv(OUT / "rezervacije_puni_export.csv", index=False, encoding="utf-8-sig")

# ---------------------------------------------------------------------------
# 2) Statistička analiza (Pandas agregacije)
# ---------------------------------------------------------------------------
summary = pd.DataFrame({
    "pokazatelj": [
        "Ukupno rezervacija", "Potvrđene", "Realizovane (completed)", "Otkazane",
        "Stopa otkazivanja (%)", "Prosečan broj gostiju", "Prosečna ocena",
        "Najtraženiji restoran", "Najpopularnije jelo", "Najtraženiji termin (sat)",
    ],
    "vrednost": [
        len(df),
        int((df.status == "confirmed").sum()),
        int((df.status == "completed").sum()),
        int((df.status == "cancelled").sum()),
        round((df.status == "cancelled").mean() * 100, 2),
        round(df.broj_gostiju.mean(), 2),
        round(df.ocena.mean(), 2),
        df.restoran.value_counts().idxmax(),
        df.jelo.value_counts().idxmax(),
        f"{df.sat.value_counts().idxmax()}h",
    ],
})
summary.to_csv(OUT / "statistika_pregled.csv", index=False, encoding="utf-8-sig")
print(summary.to_string(index=False))

# ---------------------------------------------------------------------------
# 3) Vizualizacije (Matplotlib) — dark theme usklađen sa admin dashboard-om
# ---------------------------------------------------------------------------
plt.rcParams.update({
    "figure.facecolor": "#12141c", "axes.facecolor": "#12141c",
    "axes.edgecolor": "#3a3f52", "axes.labelcolor": "#e7e9f0",
    "text.color": "#e7e9f0", "xtick.color": "#b7bccb", "ytick.color": "#b7bccb",
    "grid.color": "#262a3a", "font.size": 11, "axes.titlesize": 13, "axes.titleweight": "bold",
})
ORANGE = "#e08a3e"
GOLD = "#d9b45a"
TEAL = "#4fb3a9"

# 3.1 Rezervacije po satu u danu
fig, ax = plt.subplots(figsize=(7, 4))
hour_counts = df.groupby("sat").size().reindex(range(11, 23), fill_value=0)
ax.bar(hour_counts.index.astype(str) + "h", hour_counts.values, color=ORANGE)
ax.set_title("Broj rezervacija po satu u danu")
ax.set_ylabel("Broj rezervacija")
ax.grid(axis="y", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_rezervacije_po_satu.png", dpi=150)
plt.close()

# 3.2 Rezervacije po danu u nedelji
order = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
labels_sr = ["Pon", "Uto", "Sre", "Čet", "Pet", "Sub", "Ned"]
day_counts = df.groupby("dan_u_nedelji").size().reindex(order, fill_value=0)
fig, ax = plt.subplots(figsize=(7, 4))
ax.bar(labels_sr, day_counts.values, color=GOLD)
ax.set_title("Broj rezervacija po danu u nedelji")
ax.set_ylabel("Broj rezervacija")
ax.grid(axis="y", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_rezervacije_po_danu.png", dpi=150)
plt.close()

# 3.3 Top restorani po broju rezervacija
fig, ax = plt.subplots(figsize=(7, 4))
top_r = df.restoran.value_counts()
ax.barh(top_r.index[::-1], top_r.values[::-1], color=TEAL)
ax.set_title("Broj rezervacija po restoranu")
ax.set_xlabel("Broj rezervacija")
ax.grid(axis="x", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_top_restorani.png", dpi=150)
plt.close()

# 3.4 Najpopularnija jela
fig, ax = plt.subplots(figsize=(7, 4.5))
top_d = df.jelo.value_counts().head(8)
ax.barh(top_d.index[::-1], top_d.values[::-1], color=ORANGE)
ax.set_title("Top 8 najčešće naručivanih jela")
ax.set_xlabel("Broj porudžbina")
ax.grid(axis="x", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_top_jela.png", dpi=150)
plt.close()

# 3.5 Struktura statusa rezervacija (pie)
fig, ax = plt.subplots(figsize=(5.5, 5.5))
status_counts = df.status.value_counts()
status_labels_map = {"confirmed": "Potvrđene", "completed": "Realizovane", "cancelled": "Otkazane"}
ax.pie(status_counts.values, labels=[status_labels_map[s] for s in status_counts.index],
       autopct="%1.1f%%", colors=[ORANGE, TEAL, "#c0546b"],
       textprops={"color": "#e7e9f0"}, wedgeprops={"edgecolor": "#12141c", "linewidth": 2})
ax.set_title("Struktura statusa rezervacija")
plt.tight_layout()
plt.savefig(OUT / "chart_status_struktura.png", dpi=150)
plt.close()

# 3.6 Trend rezervacija kroz vreme (mesečno)
fig, ax = plt.subplots(figsize=(8, 4))
monthly = df.set_index("termin").resample("W").size()
ax.plot(monthly.index, monthly.values, color=ORANGE, linewidth=2, marker="o", markersize=3)
ax.fill_between(monthly.index, monthly.values, color=ORANGE, alpha=0.15)
ax.set_title("Nedeljni trend broja rezervacija")
ax.set_ylabel("Broj rezervacija")
ax.xaxis.set_major_formatter(mdates.DateFormatter("%d.%m"))
fig.autofmt_xdate()
ax.grid(axis="y", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_trend_nedeljno.png", dpi=150)
plt.close()

# 3.7 Prosečna ocena po restoranu
fig, ax = plt.subplots(figsize=(7, 4))
avg_rating = df.groupby("restoran").ocena.mean().sort_values()
bars = ax.barh(avg_rating.index, avg_rating.values, color=GOLD)
ax.set_xlim(0, 5)
ax.set_title("Prosečna ocena po restoranu")
ax.set_xlabel("Prosečna ocena (1-5)")
ax.grid(axis="x", alpha=0.3)
plt.tight_layout()
plt.savefig(OUT / "chart_prosecna_ocena.png", dpi=150)
plt.close()

# 3.8 Heatmap: dan u nedelji x sat (popularnost termina)
pivot = df.pivot_table(index="dan_u_nedelji", columns="sat", values="termin", aggfunc="count").reindex(order).fillna(0)
fig, ax = plt.subplots(figsize=(9, 4.2))
im = ax.imshow(pivot.values, cmap="YlOrBr", aspect="auto")
ax.set_xticks(range(len(pivot.columns)))
ax.set_xticklabels([f"{h}h" for h in pivot.columns])
ax.set_yticks(range(len(labels_sr)))
ax.set_yticklabels(labels_sr)
ax.set_title("Heatmap popularnosti termina rezervacija (dan × sat)")
cbar = fig.colorbar(im, ax=ax)
cbar.set_label("Broj rezervacija")
plt.tight_layout()
plt.savefig(OUT / "chart_heatmap_termini.png", dpi=150, facecolor="#12141c")
plt.close()

print("\nSvi izveštaji i grafikoni su generisani u:", OUT.resolve())
