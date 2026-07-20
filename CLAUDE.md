# CLAUDE.md

Bu dosya, depoda çalışan geliştiriciler ve AI ajanları için proje kurallarını içerir.

## Proje

Laravel Zero tabanlı bir CLI aracı. İki komut var:

- `create` — etkileşimli menüden kategori seçtirip `changelogs/unreleased/<branch>.md` dosyası oluşturur.
- `publish` — `changelogs/unreleased/` altındaki tüm dosyaları `CHANGELOG.md` içine taşır.

## Phar binary'sini derleme

`builds/changelog-cli` depoda **takip edilen** bir binary. Uygulama kodunda kullanıcıya
yansıyan bir değişiklik yaptıysan phar'ı yeniden derleyip aynı PR'a eklemen gerekir;
aksi halde phar üzerinden kullanan herkes eski davranışı görmeye devam eder.

Doğru komut:

```bash
php -d phar.readonly=0 changelog-cli app:build --build-version=<surum>
```

Derleme sonrası kontrol:

```bash
ls -lh builds/changelog-cli    # ~9 MB olmali
```

### Kurallar

**1. `humbug/box`'ı projeye kurma.**

Laravel Zero, box'ı kendi içinde bağımsız bir binary olarak getiriyor:
`vendor/laravel-zero/framework/bin/box`. `app:build` bunu kullanır.

`composer require --dev humbug/box` çalıştırırsan box ve ~37 bağımlılığı (php-scoper,
amphp, phpstorm-stubs...) `vendor/` altına iner. `box.json` içinde
`"exclude-dev-files": false` olduğu için de bunların hepsi üretilen phar'ın içine
gömülür — yani build aracı, kendi ürettiği binary'nin içinde yer alır. Sonuç:
binary 9 MB yerine ~17 MB olur.

Bu hata daha önce yaşandı. Binary'nin boyutu beklenmedik şekilde büyüdüyse ilk
bakılacak yer burasıdır:

```bash
composer remove --dev humbug/box
composer install
```

**2. `phar.readonly` kapalı olmalı.**

PHP'de varsayılan `On`. Bu hâlde derleme sessizce başarısız olur. Ya komuta
`-d phar.readonly=0` ekle ya da `php.ini` içinde kapat.

**3. `box.json` içine `output` anahtarı ekleme.**

`app:build`, box'ın çıktıyı proje kökünde `changelog-cli.phar` olarak üretmesini ve
kendisinin `builds/` altına taşımasını bekler. `box.json`'a `"output"` eklenirse box
dosyayı doğrudan oraya yazar; `app:build` kökte aradığı `.phar`'ı bulamaz ve
**derleme başarılı olduğu hâlde** şu hatayı fırlatır:

```
RuntimeException: Failed to compile the application.
```

Binary aslında üretilmiştir. Bu yanıltıcı hata daha önce ciddi kafa karışıklığına yol
açtı, o yüzden `output` anahtarı bilinçli olarak kaldırıldı — geri eklenmemeli.

## Test

```bash
vendor/bin/phpunit
```

`phpunit.xml.dist` eski bir şemaya göre yazılmış, bu yüzden her koşuda bir PHPUnit
deprecation uyarısı çıkar. Bu uyarı mevcut durumdur, senin değişikliğinden kaynaklanmaz.

### Komut testleri hakkında

`publish` komutu `$this->artisan('publish')` ile test edilebiliyor. `create` komutu ise
etkileşimli bir menü açtığı için doğrudan test edilemez — TTY olmayan ortamda
`InvalidTerminalException` fırlatır.

Menüyü atlatmak için `ControllableChangelogCommand` (bkz. `tests/Feature/`) sınıfı
`openMenu()`'yu override ediyor. Container'a `bind()` ile takas **çalışmaz**; komutlar
bootstrap sırasında kaydedildiği için geç kalır. Çalışan yöntem komutu doğrudan
kernel'e kaydetmektir:

```php
$this->app[Kernel::class]->registerCommand(new ControllableChangelogCommand($option));

$this->artisan('create')->assertExitCode(0);
```

## Bağımlılıklar

`composer.lock` depoda takip **edilmiyor** (`.gitignore` içinde). Yeni bir paket
eklerken sadece `composer.json`'ı güncellemen yeterli; commit'lenecek bir lock dosyası
yok.

## Changelog

Depo kendi aracını kendi üzerinde kullanır. Kullanıcıya yansıyan değişiklikler
`CHANGELOG.md` içindeki `## [Unreleased]` başlığı altına yazılır.
