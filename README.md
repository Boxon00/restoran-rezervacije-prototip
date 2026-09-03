# 🍽️ Sistem za rezervaciju restorana

Full-stack web aplikacija za **rezervaciju stolova i upravljanje restoranima**.

Sistem omogućava korisnicima pregled restorana, proveru dostupnosti stolova i kreiranje rezervacija. Takođe sadrži administratorski interfejs za upravljanje restoranima, stolovima, stavkama menija, rezervacijama i korisnicima.

Projekat se sastoji od **Vue.js frontend-a**, **Laravel REST API backend-a**, **MySQL baze podataka** i zasebnog **Python modula za analitiku**.

---

## ✨ Funkcionalnosti

### 👤 Funkcionalnosti za korisnike

* Registracija i prijava korisnika
* Sigurna autentifikacija pomoću Laravel Sanctum-a
* Pregled dostupnih restorana
* Pregled detalja restorana
* Pregled dostupnih stolova
* Izbor datuma i vremena rezervacije
* Kreiranje rezervacija
* Upravljanje korisničkim nalogom
* Pristup zaštićenim resursima nakon autentifikacije

### 🛠️ Administracija

Administratorski panel omogućava upravljanje glavnim entitetima sistema:

* Restoranima
* Stolovima
* Stavkama menija
* Rezervacijama
* Korisnicima

Administratorski interfejs podržava CRUD operacije i omogućava pregled aktuelnih podataka o restoranima.

### 📊 Analitika

Projekat sadrži zaseban Python modul za obradu podataka o rezervacijama.

Modul omogućava:

* Obradu podataka o rezervacijama
* Analizu podataka
* Generisanje CSV izveštaja
* Izračunavanje statističkih podataka
* Vizuelizaciju podataka
* Generisanje grafikona u PNG formatu

---

## 🏗️ Arhitektura sistema

Aplikacija je podeljena na tri glavne komponente:

```text
                    ┌──────────────────────┐
                    │    Vue.js Frontend   │
                    │                      │
                    │ Vue Router           │
                    │ Pinia                │
                    │ Axios                │
                    └──────────┬───────────┘
                               │
                               │ REST API
                               ▼
                    ┌──────────────────────┐
                    │   Laravel Backend    │
                    │                      │
                    │ Controllers          │
                    │ Models / Eloquent    │
                    │ Authentication       │
                    │ Validation           │
                    │ Business Logic       │
                    └──────────┬───────────┘
                               │
                               │
                               ▼
                    ┌──────────────────────┐
                    │      MySQL 8         │
                    │      Database        │
                    └──────────────────────┘

                               │
                               │ Obrada podataka
                               ▼
                    ┌──────────────────────┐
                    │  Python Analytics    │
                    │                      │
                    │ Pandas               │
                    │ NumPy                │
                    │ Matplotlib           │
                    └──────────────────────┘
```

Frontend komunicira sa Laravel backend-om putem REST API-ja. Backend obrađuje poslovnu logiku, autentifikaciju, validaciju i operacije nad bazom podataka.

Analytics modul je zasebna komponenta koja obrađuje podatke povezane sa rezervacijama i generiše izveštaje i vizuelizacije.

---

## 🧰 Tehnologije

### Backend

* **PHP 8.3**
* **Laravel 11**
* **Laravel Sanctum**
* **MySQL 8**
* **Eloquent ORM**
* REST API
* Laravel Migrations
* Laravel Seeders

### Frontend

* **Vue 3**
* **Vite**
* **Vue Router**
* **Pinia**
* **Axios**
* JavaScript
* HTML5
* CSS3

### Analitika

* **Python 3**
* **Pandas**
* **NumPy**
* **Matplotlib**
* CSV

---

## 📁 Struktura repozitorijuma

```text
restoran-rezervacije-prototip/
│
├── analytics/
│   ├── generate_report.py
│   └── reports/
│       ├── *.csv
│       └── *.png
│
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   └── ...
│   │
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   │
│   ├── routes/
│   ├── config/
│   ├── tests/
│   ├── artisan
│   └── composer.json
│
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── router/
│   │   ├── stores/
│   │   └── ...
│   │
│   ├── public/
│   ├── package.json
│   └── vite.config.js
│
└── README.md
```

### Pregled direktorijuma

| Direktorijum | Opis                                                             |
| ------------ | ---------------------------------------------------------------- |
| `analytics/` | Python modul za obradu podataka, analizu i generisanje izveštaja |
| `backend/`   | Laravel REST API i poslovna logika aplikacije                    |
| `frontend/`  | Vue.js korisnički interfejs                                      |
| `README.md`  | Dokumentacija projekta                                           |

---

## 🔐 Autentifikacija

Autentifikacija je implementirana pomoću **Laravel Sanctum-a**.

Korisnici mogu da se registruju i prijave putem frontend aplikacije. Nakon uspešne autentifikacije, backend generiše autentifikacioni token koji se koristi prilikom pristupa zaštićenim API endpoint-ima.

Autentifikovani zahtevi koriste sledeći format:

```text
Authorization: Bearer <token>
```

Zaštićene funkcionalnosti dostupne su samo autentifikovanim korisnicima, dok su administratorske operacije ograničene na korisnike sa odgovarajućim ovlašćenjima.

---

## 📅 Upravljanje rezervacijama

Sistem za rezervacije omogućava korisniku da izabere:

* Restoran
* Sto
* Datum
* Vreme

i kreira rezervaciju putem aplikacije.

Backend vrši validaciju zahteva pre čuvanja rezervacije u bazi podataka.

Sistem takođe sadrži mehanizme za sprečavanje duplih rezervacija za isti sto i isti termin.

Na nivou baze podataka koristi se jedinstveno ograničenje kao dodatni mehanizam za očuvanje integriteta podataka:

```text
UNIQUE(table_id, reservation_time)
```

Prilikom obrade rezervacija koriste se transakcije baze podataka i zaključavanje redova kako bi se bezbedno obradili konkurentni zahtevi.

---

## 🗄️ Baza podataka

Aplikacija koristi **MySQL 8** kao primarnu relacionu bazu podataka.

Struktura baze se definiše pomoću Laravel migracija, dok se početni podaci mogu uneti pomoću Laravel seedera.

Baza sadrži glavne entitete potrebne za funkcionisanje aplikacije, uključujući:

* Korisnike
* Restorane
* Stolove
* Stavke menija
* Rezervacije

Veze između entiteta predstavljene su pomoću stranih ključeva i Laravel Eloquent relacija.

---

# 🚀 Instalacija

## Preduslovi

Pre pokretanja aplikacije potrebno je instalirati:

* PHP 8.3 ili noviji
* Composer
* Node.js
* npm
* MySQL 8 ili noviji
* Python 3

---

## 1. Kloniranje repozitorijuma

```bash
git clone https://github.com/Boxon00/restoran-rezervacije-prototip.git
cd restoran-rezervacije-prototip
```

---

## 2. Podešavanje backend-a

Preći u direktorijum backend-a:

```bash
cd backend
```

Instalirati PHP zavisnosti:

```bash
composer install
```

Kreirati `.env` fajl:

```bash
cp .env.example .env
```

Generisati Laravel application key:

```bash
php artisan key:generate
```

Podesiti konekciju sa bazom podataka u `.env` fajlu:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restoran
DB_USERNAME=root
DB_PASSWORD=
```

Ukoliko baza još ne postoji, kreirati je u MySQL-u:

```sql
CREATE DATABASE restoran;
```

Pokrenuti migracije i seedere:

```bash
php artisan migrate --seed
```

Pokrenuti Laravel development server:

```bash
php artisan serve
```

Backend će biti dostupan na:

```text
http://127.0.0.1:8000
```

---

## 3. Podešavanje frontend-a

Otvoriti novi terminal i preći u frontend direktorijum:

```bash
cd frontend
```

Instalirati JavaScript zavisnosti:

```bash
npm install
```

Pokrenuti Vite development server:

```bash
npm run dev
```

Frontend će po pravilu biti dostupan na:

```text
http://localhost:5173
```

### Production build

Za kreiranje production build-a koristiti:

```bash
npm run build
```

Generisani fajlovi će biti smešteni u:

```text
frontend/dist/
```

---

## 4. Podešavanje analitike

Preći u analytics direktorijum:

```bash
cd analytics
```

Instalirati potrebne Python biblioteke:

```bash
pip install pandas numpy matplotlib
```

Pokrenuti analytics skriptu:

```bash
python generate_report.py
```

Generisani izveštaji i vizuelizacije čuvaju se u:

```text
analytics/reports/
```

---

# 🧪 Testiranje

Projekat sadrži testove za važne delove aplikacije.

Testiranje obuhvata:

* Proveru PHP sintakse
* Proveru strukture baze podataka
* Proveru API funkcionalnosti
* Validaciju rezervacija
* Sprečavanje duplih rezervacija
* Obradu konkurentnih zahteva
* Production build frontend aplikacije
* Izvršavanje analytics modula

Posebna pažnja posvećena je konkurentnim zahtevima za rezervaciju kako bi se obezbedilo da više korisnika ne može uspešno da rezerviše isti sto za isti termin.

---

# 📊 Generisani izveštaji

Analytics modul generiše različite vrste izlaznih fajlova:

```text
analytics/
└── reports/
    ├── *.csv
    └── *.png
```

CSV fajlovi sadrže obrađene podatke i statističke rezultate, dok PNG fajlovi sadrže generisane vizuelizacije.

Generisani izveštaji mogu se koristiti za analizu obrazaca rezervacija i drugih dostupnih podataka aplikacije.

---

# 🔄 Tok rezervacije

Tipičan proces kreiranja rezervacije izgleda ovako:

```text
Korisnik
   │
   ▼
Otvara aplikaciju
   │
   ▼
Pregled restorana
   │
   ▼
Izbor restorana
   │
   ▼
Izbor stola i termina
   │
   ▼
Slanje rezervacije
   │
   ▼
Laravel REST API
   │
   ▼
Validacija zahteva
   │
   ▼
Provera dostupnosti stola
   │
   ▼
Čuvanje rezervacije u MySQL bazi
   │
   ▼
Vraćanje odgovora
   │
   ▼
Prikaz rezultata u Vue.js aplikaciji
```

---

# 📌 Razvoj projekta

Projekat je organizovan u zasebne frontend, backend i analytics komponente.

Ovakva organizacija omogućava nezavisan razvoj i održavanje svakog dela sistema:

* **Vue.js** upravlja korisničkim interfejsom i stanjem aplikacije.
* **Laravel** upravlja REST API-jem, poslovnom logikom, autentifikacijom i operacijama nad bazom.
* **MySQL** čuva podatke aplikacije.
* **Python** se koristi za obradu podataka, analizu i generisanje izveštaja.

---

# 📄 Licenca

Projekat je namenjen u obrazovne i demonstracione svrhe.
