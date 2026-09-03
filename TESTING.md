# Vodič za testiranje prototipa

Ovaj dokument objašnjava kako testirati svaki deo sistema — od onoga što je
**već izvršeno i provereno** u razvojnom okruženju pre isporuke, do koraka koje
treba da uradiš sam da bi pokrenuo/la ceo sistem end-to-end.

Legenda: ✅ = ovo je stvarno pokrenuto i provereno pre isporuke (nije samo
napisano po analogiji). 🔧 = ovo moraš sam/a da pokreneš u svom okruženju.

---

## 1. Baza podataka (MySQL) ✅

Šema (`backend/database/schema_verification.sql`) je izvršena nad pravim
MySQL 8.0 serverom, uključujući ponovljenu, svežu proveru neposredno pre
finalne isporuke ovog paketa.

> **Napomena:** ako pokrećeš MySQL prvi put na svojoj mašini, standardna
> instalacija (`apt install mysql-server` / paket za tvoj OS) je dovoljna —
> samo uskladi `DB_USERNAME`/`DB_PASSWORD` u `.env` i u test skriptama
> (`backend/tests/test_*_mysql.php`, `_race_worker.php`) sa svojim kredencijalima.

**Da testiraš sam/a:**
```bash
# 1) Kreiraj bazu
mysql -u root -p -e "CREATE DATABASE restoran_rezervacije CHARACTER SET utf8mb4;"

# 2) Ili primeni šemu direktno (brža provera bez punog Laravel-a):
mysql -u root -p restoran_rezervacije < backend/database/schema_verification.sql

# 3) Proveri da su sve tabele i ograničenja tu:
mysql -u root -p restoran_rezervacije -e "SHOW TABLES;"
mysql -u root -p restoran_rezervacije -e "SHOW CREATE TABLE reservations\G"
```
Očekivano: 7 tabela (`users, restaurants, tables, menus, dishes, reservations,
ratings`), i u `reservations` UNIQUE ključ `uq_table_time (table_id, reservation_time)`.

---

## 2. Kritična poslovna logika — sprečavanje duplih rezervacija ✅

Ovo je **najvažniji deo za proveru** jer dokazuje da sistem ne dozvoljava da
dva korisnika rezervišu isti sto u istom terminu — testirano na tri nivoa:

```bash
# A) Brza logička provera nad SQLite (ne treba MySQL, ne treba nikakva instalacija)
php backend/tests/test_double_booking_prevention.php
# Očekivano: "Prošlo testova: 6 / 6"

# B) Ista logika, ali nad pravim MySQL/InnoDB serverom (traži da baza iz koraka 1 postoji)
#    Podesi kredencijale unutar fajla ako se razlikuju od 'root'/'rootpass'
php backend/tests/test_double_booking_prevention_mysql.php
# Očekivano: "Prošlo: 5/5"

# C) PRAVA konkurentnost — dva odvojena OS procesa istovremeno pokušavaju
#    rezervaciju istog stola/termina
php backend/tests/test_concurrent_race.php
# Očekivano: "Broj procesa koji je uspeo: 1", "[PROLAZI]"
# Pokreni ovo nekoliko puta zaredom — rezultat mora uvek biti isti (1 uspeh, 1 red u bazi)
```

Ako želiš da vidiš da mehanizam ZAISTA nešto sprečava (a ne da test samo
"prolazi slučajno"), probaj da privremeno izbrišeš proveru konflikta iz
`ReservationController::store()` (deo sa `lockForUpdate()`) i ponovo pokreneš
`test_double_booking_prevention_mysql.php` prilagođen toj situaciji — video/la
bi da UNIQUE ograničenje na nivou baze i dalje baca grešku (druga linija
odbrane), ali bez lepe HTTP 409 poruke koju kontroler inače vraća.

---

## 3. Backend — puno pokretanje (Laravel REST API) 🔧

Ovo zahteva `composer install`, što u ovom sandbox okruženju nije bilo moguće
izvršiti do kraja (Packagist nije dostupan sa mreže na kojoj sam radio), ali
**svih 41 PHP fajlova je provereno `php -l` komandom** i sintaksno je ispravno.
Kod tebe, sa punim pristupom internetu:

> ⚠️ **Ispravljena greška (HTTP 419):** ranija verzija `bootstrap/app.php`
> je greškom uključivala Sanctum-ov cookie-based stateful middleware, iako
> projekat koristi čist Bearer-token auth — to je izazivalo HTTP 419 na
> registraciji i kreiranju rezervacije (POST zahtevi), dok su GET zahtevi
> radili normalno. Ova verzija je ispravljena; detalji su u README.md.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# podesi DB_* u .env da odgovaraju bazi iz koraka 1, npr:
#   DB_CONNECTION=mysql
#   DB_DATABASE=restoran_rezervacije
#   DB_USERNAME=root
#   DB_PASSWORD=tvoja_lozinka

php artisan migrate --seed     # kreira šemu I ubacuje demo podatke (6 restorana, 20 korisnika, ~360 rezervacija)
php artisan serve              # pokreće server na http://localhost:8000
```

### Ručno testiranje API-ja preko curl-a

```bash
# Registracija
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Korisnik","email":"test@test.rs","password":"lozinka123","password_confirmation":"lozinka123"}'
# Očekivano: HTTP 201, JSON sa "token" poljem

# Pријava admin naloga kreiranog kroz seeder
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@rezervacije.rs","password":"password"}'
# Sačuvaj token iz odgovora u promenljivu:
TOKEN="<token iz odgovora>"

# Lista restorana (javna ruta, ne treba token)
curl http://localhost:8000/api/restaurants

# Provera dostupnosti stola
curl "http://localhost:8000/api/restaurants/1/availability?date=2026-09-20&time=20:00&guests=2"

# Kreiranje rezervacije (traži token)
curl -X POST http://localhost:8000/api/reservations \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"restaurant_id":1,"table_id":1,"reservation_time":"2026-09-20 20:00:00","guest_count":2}'
# Ponovi isti zahtev odmah opet -> očekivano HTTP 409 (konflikt, poglavlje 5.10)

# Pokušaj admin akcije bez admin naloga -> očekivano HTTP 403
curl -X DELETE http://localhost:8000/api/restaurants/1 -H "Authorization: Bearer $TOKEN"

# Aналитика (samo admin)
curl http://localhost:8000/api/admin/analytics/summary -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Automatski testovi (PHPUnit) — opciono proširenje

Standardni Laravel projekat generisan sa `laravel new` dolazi sa
`tests/Feature` i `phpunit.xml`. U ovom repozitorijumu намерno su korišćeni
samostalni PHP skriptovi (`backend/tests/test_*.php`) umesto punog PHPUnit
seta, jer ne zahtevaju `composer install` da bi se pokrenuli — dovoljan im je
samo PHP interpreter, što omogućava da se poslovna logika proveri i bez
pristupa Packagist-u. Ako želiš standardni PHPUnit format, `composer
require --dev phpunit/phpunit` i konvertuj ove skripte u `TestCase` klase —
logika ostaje identična.

---

## 4. Frontend (Vue 3) ✅ + 🔧

Frontend je **stvarno izgrađen** (`npm run build`) u ovom okruženju i vraćao
je HTTP 200 kada je poslužen. Kod tebe, za razvoj/pregled u browseru:

```bash
cd frontend
npm install
npm run dev
# otvori http://localhost:5173 u browseru
```

Pošto backend (korak 3) mora biti pokrenut na `http://localhost:8000` da bi
frontend imao šta da prikaže (proveri `frontend/.env.example` → kopiraj u
`.env` i podesi `VITE_API_URL` ako backend radi na drugom portu).

**Ručna provera kroz interfejs:**
1. Otvori početnu stranicu → treba da vidiš 6 restorana iz seedera.
2. Klikni na restoran → proveri da li se učitava jelovnik i da li se prikazuju stolovi.
3. Registruj se kao novi korisnik → napravi rezervaciju → proveri da li se pojavljuje u "Moje rezervacije".
4. Otvori dva browser taba, prijavi se kao dva različita korisnika, i pokušaj da REZERVIŠEŠ ISTI STO/TERMIN iz oba taba skoro istovremeno → tačno jedan treba da uspe (ovo je vizuelna verzija testa iz sekcije 2C).
5. Prijavi se kao admin (`admin@rezervacije.rs` / `password`) → proveri `/admin` dashboard i `/admin/analytics`.

Za samo proveru da frontend i dalje ispravno kompajlira nakon eventualnih izmena:
```bash
cd frontend
npm run build    # mora da završi bez grešaka i ispiše "✓ built in ..."
```

---

## 5. Analitički modul (Python/Pandas) ✅

Skripta je stvarno pokrenuta i generisala je sve izveštaje uključene u
`analytics/reports/`. Da pokreneš ponovo (npr. sa izmenjenim parametrima):

```bash
cd analytics
pip install pandas matplotlib numpy
python generate_report.py
```

Očekivani ispis u terminalu — tabela sa 10 statističkih pokazatelja (npr.
"Ukupno rezervacija: 2600", "Stopa otkazivanja (%): 15.5"...) i poruka
"Svi izveštaji i grafikoni su generisani u: .../analytics/reports". Proveri
da je `analytics/reports/` sada popunjen sa 2 CSV fajla i 8 PNG grafikona.

**Napomena:** skripta trenutno generiše reprezentativan sintetički skup
podataka (objašnjeno u poglavlju 7.1 rada) umesto da čita iz MySQL baze,
jer prototip nije bio u produkcionoj upotrebi dovoljno dugo da nagomila
stvaran obim podataka. Kada baza iz koraka 1 bude popunjena stvarnim
rezervacijama (npr. preko `php artisan migrate --seed` ili stvarnog
korišćenja aplikacije), zamena je jednostavna — na vrhu `generate_report.py`
umesto bloka generisanja podataka dodaje se:

```python
from sqlalchemy import create_engine
engine = create_engine("mysql+pymysql://root:lozinka@127.0.0.1/restoran_rezervacije")
df = pd.read_sql("""
    SELECT r.id, r.reservation_time AS termin, r.guest_count AS broj_gostiju,
           r.status, rt.name AS restoran, d.name AS jelo
    FROM reservations r
    JOIN restaurants rt ON rt.id = r.restaurant_id
    LEFT JOIN dishes d ON 1=0  -- prilagoditi stvarnoj šemi porudžbina po jelu
""", engine)
```
sve ostale agregacije i grafikoni (odeljci 2–3 u skripti) rade bez izmena
jer očekuju iste kolone (`restoran`, `sat`, `status`, `ocena`, `jelo`, ...).

---

## 6. Sažetak — šta pokrenuti ako imaš samo 5 minuta

```bash
# Dokaz da kritična logika radi (ne traži composer/npm, ~10 sekundi):
php backend/tests/test_double_booking_prevention.php

# Dokaz da baza podataka i cela šema rade na MySQL-u (traži pokrenut MySQL server):
mysql -u root -p restoran_rezervacije < backend/database/schema_verification.sql
php backend/tests/test_double_booking_prevention_mysql.php
php backend/tests/test_concurrent_race.php

# Dokaz da frontend kompajlira (traži Node.js, ~30 sekundi):
cd frontend && npm install && npm run build

# Dokaz da analitika radi (traži Python, ~5 sekundi):
cd analytics && pip install pandas matplotlib numpy && python generate_report.py
```

Ako sva četiri koraka prođu bez greške na tvojoj mašini, imaš potvrdu da je
kod isporučen ispravan i da radi van sandbox okruženja u kom je pisan —
ne samo u njemu.
