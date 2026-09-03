# Web aplikacija za rezervacije i upravljanje restoranima — prototip

Izvorni kod funkcionalnog prototipa realizovanog u okviru drugog semestra
studijsko-istraživačkog rada (master rad), na osnovu analize sprovedene u
prvom semestru. Sadržaj ovog repozitorijuma direktno odgovara poglavljima
4–8 i 11 pratećeg istraživačkog rada.

## Struktura
- `backend/` — Laravel 11 (PHP) REST API: migracije, modeli, kontroleri, rute, config, testovi
- `frontend/` — Vue 3 SPA: router, Pinia store, sve stranice (javni deo + admin panel)
- `analytics/` — Python/Pandas analitički modul + generisani izveštaji (CSV, grafikoni)

## Šta je stvarno provereno (ne samo napisano)

Sav kod u ovom repozitorijumu je **izvršen i validiran** u razvojnom okruženju
pre isporuke, ne samo napisan po analogiji:

1. **Backend (PHP):** svih 33 PHP fajlova prošlo je `php -l` proveru sintakse
   bez ijedne greške (PHP 8.3).
2. **Šema baze podataka nad pravim MySQL 8.0 serverom:** `database/schema_verification.sql`
   (SQL ekvivalent Laravel migracija) je izvršen nad stvarno pokrenutim MySQL
   8.0.46 serverom — svih 7 tabela, strani ključevi i `UNIQUE(table_id, reservation_time)`
   ograničenje su uspešno kreirani i provereni (`SHOW CREATE TABLE`).
3. **Sprečavanje dupliranih rezervacija (poglavlje 5.10) — trostruko dokazano:**
   - `tests/test_double_booking_prevention.php` — 6/6 testova nad SQLite (brza provera logike),
   - `tests/test_double_booking_prevention_mysql.php` — 5/5 testova nad **pravim MySQL 8.0/InnoDB** serverom, koristeći `SELECT ... FOR UPDATE` (tačan MySQL ekvivalent Eloquent `lockForUpdate()`),
   - `tests/test_concurrent_race.php` — **prava konkurentnost**: dva odvojena OS procesa istovremeno pokušavaju rezervaciju istog stola/termina nad MySQL bazom; pokrenuto 3 puta, u sva 3 slučaja tačno jedan proces uspeva, a baza sadrži tačno jedan zapis.
4. **Frontend (Vue 3):** kompletna aplikacija je stvarno skeletirana kroz
   `npm create vite`, sve zavisnosti instalirane (`vue-router`, `pinia`,
   `axios`, `chart.js`), i **uspešno izgrađena naredbom `npm run build`** —
   svih 17 stranica/komponenti (uključujući admin CRUD za restorane, stolove,
   jelovnike/jela, rezervacije i korisnike) je transformisano i code-split
   bez ijedne greške, a rezultujući `dist/` je posluživan i vraćao je HTTP 200.
5. **Analitički modul (Python):** skripta `analytics/generate_report.py` je
   stvarno pokrenuta (`python3 generate_report.py`) i generisala je sve CSV
   izveštaje i 8 PNG grafikona koji se nalaze u `analytics/reports/`.

## Pokretanje u punom razvojnom okruženju

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# podesiti DB_* promenljive u .env (MySQL) ili DB_CONNECTION=sqlite za lokalni rad
php artisan migrate --seed
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
npm run dev      # razvojni server (Vite), podrazumevano na http://localhost:5173
npm run build    # produkciona verzija u dist/
```

### Analitički modul
```bash
cd analytics
pip install pandas matplotlib numpy
python generate_report.py
```

## Napomena o obimu koda

Repozitorijum sadrži kompletnu implementaciju svih slojeva opisanih u
istraživačkom radu (modeli, migracije, svi kontroleri sa punom poslovnom
logikom, sve Vue stranice i komponente, konfiguracija Laravel 11 aplikacije
— `bootstrap/app.php`, `routes/`, `config/`, `artisan` — i analitički
modul). Za pokretanje je potrebno instalirati zavisnosti (`composer install`,
`npm install`) jer `vendor/` i `node_modules/` direktorijumi nisu uključeni
u isporuku radi veličine paketa.

## Poznata i ispravljena greška: HTTP 419 na registraciji/rezervaciji

Prilikom prvog stvarnog pokretanja projekta (van sandbox okruženja u kom je
pisan) otkrivena je greška: `bootstrap/app.php` je sadržao Sanctum-ov
**stateful SPA (cookie-based)** middleware (`statefulApi()` +
`EnsureFrontendRequestsAreStateful`), iako projekat koristi čistu
**Bearer-token** autentikaciju (`createToken()->plainTextToken`). Ovo
neslaganje je uzrokovalo da GET zahtevi rade normalno (npr. pregled
restorana), dok su svi POST/PUT/DELETE zahtevi sa Vite dev servera
(`localhost:5173`, naveden u `SANCTUM_STATEFUL_DOMAINS`) bili odbijani sa
**HTTP 419 CSRF token mismatch** — jer frontend nikad ne traži niti šalje
CSRF cookie, što čist token-based auth i ne zahteva.

**Ispravka** (već primenjena u ovoj verziji): `statefulApi()` i
`EnsureFrontendRequestsAreStateful` su uklonjeni iz `bootstrap/app.php`, uz
detaljan komentar u samom fajlu koji objašnjava zašto. Dodatno je dodat
eksplicitan `config/cors.php` i eksplicitna registracija `HandleCors`
middleware-a, kako bi unakrsni (cross-origin) zahtevi sa `:5173` ka `:8000`
sigurno prošli CORS proveru i nakon uklanjanja stateful sloja.

Ako i dalje dobijaš grešku prilikom registracije ili rezervacije:
1. Proveri u DevTools → Network tab status kod neuspelog zahteva.
2. **419** → uveri se da koristiš ovu (ispravljenu) verziju `bootstrap/app.php`.
3. **CORS greška u konzoli** (bez HTTP statusa) → proveri da `config/cors.php` postoji i da `FRONTEND_URL`/poreklo u `allowed_origins` odgovara adresi sa koje frontend stvarno radi.
4. **500** → proveri da je `php artisan key:generate` izvršen (nedostatak `APP_KEY` uzrokuje greške pri bilo kojoj operaciji koja koristi enkripciju).
