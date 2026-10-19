# Panduan Backend & API RAPI-Sekolah (Laravel 13)

Panduan ini menambahkan backend API pada proyek Laravel yang sudah ada. Kode dirancang untuk memenuhi aturan UKK: autentikasi terpusat, RBAC, kalender publik read-only, pencegahan tabrakan waktu, delegasi PIC, status pengaduan, upload foto, pemindahan agenda beserta notifikasi, dan eskalasi WhatsApp.

> **Kondisi proyek saat panduan dibuat:** proyek memakai Laravel 13, mempunyai migration utama `database/migrations/2026_08_20_073543_create_rapi_school_tables.php`, dan sudah memiliki controller web `EventController` serta `ComplaintController`. Route API dipisahkan dari route web agar tidak merusak halaman yang sudah ada. Jangan jalankan `migrate:fresh` pada database yang berisi data penting.

## 1. Pemetaan skema terhadap materi UKK

| Tabel | Kolom penting | Peran |
|---|---|---|
| `roles` | `id`, `role_name` | Admin/Waka, PIC, Guru, Organisasi |
| `users` | `username`, `password`, `fullname`, `phone_number`, `role_id` | Akun dan identitas role |
| `rooms` | `room_name`, `room_type`, `capacity`, `location`, `pic_id`, `is_active` | `room_type` mengklasifikasikan ruang; `pic_id` menunjuk PIC penanggung jawab ruang |
| `events` | `room_id`, `requester_id`, `event_name`, `date`, `start_time`, `end_time`, `status`, `approved_by`, `move_reason` | Peminjaman dan agenda |
| `complaints` | `room_id`, `reporter_id`, `description`, `photo_path`, `status_repair`, `handler_id`, `reported_at` | Pengaduan dan penanganan |

Skema proyek saat ini menggunakan `events.pic_id` untuk menyimpan **user peminjam** (lihat controller yang ada), sedangkan diagram UKK memakai nama `pic_id` yang bisa dibaca sebagai PIC ruang. Supaya keduanya tidak tertukar, panduan API ini memakai kolom `requester_id` untuk peminjam dan `rooms.pic_id` untuk PIC ruang. Migration tambahan di bawah mempertahankan `events.pic_id` lama sementara demi kompatibilitas, lalu mengisi `requester_id` dari kolom lama. Setelah aplikasi dipastikan memakai `requester_id`, kolom legacy dapat dihapus pada migration terpisah.

## 2. Persiapan paket dan API

Paket Sanctum sudah tercantum di `composer.json` dan paket Intervention Image sudah dipasang. Jika menyiapkan ulang proyek yang belum memiliki dependency, jalankan:

```bash
composer require laravel/sanctum
composer require intervention/image-laravel
```

Scaffold API sudah dibuat di `routes/api.php`, `app/Http/Controllers/Api/`, `app/Http/Middleware/EnsureRole.php`, serta `app/Notifications/EventMovedNotification.php`; `bootstrap/app.php` juga sudah mengaktifkan routing API dan alias role. Migration Sanctum, notifications, dan index/field API sudah dibuat di `database/migrations/`. Jangan jalankan ulang `php artisan install:api`, `make:notifications-table`, atau `make:migration add_rapi_api_fields_and_indexes` pada proyek ini karena dapat menghasilkan file/migration duplikat. Untuk foto, Intervention Image memproses dan mengubah gambar ke JPEG sebelum disimpan.

Jika ingin mengulang setup pada proyek Laravel baru, gunakan Artisan generator hanya setelah memastikan route, middleware, controller, notification, dan migration terkait memang belum ada. Jangan menimpa `.env` proyek; ubah hanya nilai koneksi database yang diperlukan.

Atur `.env` (contoh Laragon/MySQL):

```dotenv
APP_NAME="RAPI-Sekolah"
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rapi_sekolah
DB_USERNAME=root
DB_PASSWORD=
```

## 3. Migration tambahan

**Letak:** `database/migrations/2026_10_16_000003_add_rapi_api_fields_and_indexes.php`.

Kode ini menambahkan kolom peminjam yang tidak ambigu dan index pencarian. `requester_id` sementara nullable agar bisa memigrasikan data lama. Migration lanjutan `2026_10_19_000002_make_event_requester_id_not_nullable.php` melakukan backfill dari `pic_id`, memastikan tidak ada nilai null tersisa, lalu mengubahnya menjadi `NOT NULL`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('requester_id')->nullable()->after('room_id')
                ->constrained('users')->restrictOnDelete();
            $table->index(['room_id', 'date', 'status', 'start_time', 'end_time'], 'events_room_schedule_idx');
        });

        // Migrasi data peminjam lama: events.pic_id -> events.requester_id.
        DB::table('events')->whereNull('requester_id')->update([
            'requester_id' => DB::raw('pic_id'),
        ]);

        Schema::table('complaints', function (Blueprint $table) {
            $table->index(['room_id', 'status_repair', 'reported_at'], 'complaints_room_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('complaints_room_status_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_room_schedule_idx');
            $table->dropConstrainedForeignId('requester_id');
        });
    }
};
```

> Migration awal proyek sudah memiliki `move_reason`, `approved_by`, `rooms.pic_id`, `complaints.handler_id`, serta status pengaduan sesuai enum UKK. Jangan menambahkan kolom yang sama lagi. Jika migration awal **belum pernah dijalankan**, lebih bersih ubah migration awal agar `events.requester_id` menggantikan `events.pic_id` dan hilangkan migration backfill di atas. Untuk instalasi yang sudah berjalan, gunakan migration tambahan.

Migration `2026_10_19_000001_add_room_type_to_rooms_table.php` menambahkan `rooms.room_type` (default `lainnya`) dan mengklasifikasikan ruang lama. Nilai yang digunakan: `kelas`, `laboratorium`, `ruang_rapat`, `ibadah`, `aula`, dan `lainnya`.

Jalankan:

```bash
php artisan migrate
php artisan storage:link
```

## 4. Model dan relasi

### `app/Models/User.php`

Tambahkan relasi berikut ke model yang sudah ada; jangan hapus konfigurasi autentikasi yang sudah berjalan.

```php
public function events()
{
    return $this->hasMany(Event::class, 'requester_id');
}

public function managedRooms()
{
    return $this->hasMany(Room::class, 'pic_id');
}

public function complaintsReported()
{
    return $this->hasMany(Complaint::class, 'reporter_id');
}
```

Pastikan model tetap menggunakan `Authenticatable`, `role()` tetap mengarah ke `Role`, dan field `password` tetap disembunyikan. Token Sanctum ditangani trait `Laravel\Sanctum\HasApiTokens`:

```php
use Laravel\Sanctum\HasApiTokens;

// Di dalam class User:
use HasApiTokens;
```

### Buat `app/Models/Event.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'room_id', 'pic_id', 'requester_id', 'event_name', 'date', 'start_time', 'end_time',
        'status', 'approved_by', 'move_reason',
    ];

    protected $casts = ['date' => 'date:Y-m-d'];

    public function room() { return $this->belongsTo(Room::class); }
    public function requester() { return $this->belongsTo(User::class, 'requester_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
```

### Buat `app/Models/Room.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['room_name', 'capacity', 'location', 'pic_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function pic() { return $this->belongsTo(User::class, 'pic_id'); }
    public function events() { return $this->hasMany(Event::class); }
    public function complaints() { return $this->hasMany(Complaint::class); }
}
```

### Buat `app/Models/Complaint.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'room_id', 'reporter_id', 'description', 'photo_path',
        'status_repair', 'handler_id', 'reported_at',
    ];

    protected $casts = ['reported_at' => 'datetime'];

    public function room() { return $this->belongsTo(Room::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function handler() { return $this->belongsTo(User::class, 'handler_id'); }
}
```

### Pastikan seeder membuat empat role

Proyek sekarang hanya membuat `Admin`, `PIC`, dan `Guru`. Ubah blok pembuatan role di `database/seeders/DatabaseSeeder.php` agar role lengkap dan seeder aman dijalankan ulang:

```php
$adminRole = Role::firstOrCreate(['role_name' => 'Admin']);
$picRole = Role::firstOrCreate(['role_name' => 'PIC']);
$guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
$organisasiRole = Role::firstOrCreate(['role_name' => 'Organisasi']);
```

Gunakan `$organisasiRole->id` saat membuat akun organisasi uji. Karena `role_name` saat ini belum unique di migration, seeder ini mencegah duplikasi saat dipanggil berulang; tambahkan unique index setelah data role duplikat dibersihkan.

## 5. Role middleware

Role disimpan dalam `roles.role_name`. Gunakan satu middleware untuk otorisasi endpoint; jangan mengandalkan menu yang disembunyikan frontend.

### `app/Http/Middleware/EnsureRole.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleName = $user?->role?->role_name;

        if (!$user || !$roleName || !in_array($roleName, $roles, true)) {
            return response()->json(['message' => 'Akses tidak diizinkan.'], 403);
        }

        return $next($request);
    }
}
```

### Daftarkan alias di `bootstrap/app.php`

Tambahkan import, lalu isi bagian `withMiddleware` yang saat ini kosong:

```php
use App\Http\Middleware\EnsureRole;
```

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => EnsureRole::class,
    ]);
})
```

Jangan menghapus konfigurasi `shouldRenderJsonWhen` yang sudah ada di `withExceptions`.

## 6. Autentikasi token API

### `app/Http/Controllers/Api/AuthController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = \App\Models\User::with('role')->where('username', $data['username'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Username atau password salah.'], 422);
        }

        $token = $user->createToken($data['device_name'] ?? 'rapi-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'phone_number' => $user->phone_number,
                'role' => $user->role->role_name,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Token berhasil dicabut.']);
    }
}
```

Login web yang sudah ada boleh tetap menggunakan session. API mengembalikan token Sanctum pada field `token` dengan jenis `Bearer`; klien wajib mengirim header `Authorization: Bearer <token>` pada request terautentikasi berikutnya. Endpoint aktif saat ini adalah `POST /api/login`, `GET /api/me`, dan `POST /api/logout`.

## 7. API agenda: kalender, pengajuan, konflik, pemindahan

### Aturan anti-tabrakan

Dua waktu beririsan apabila `existing.start_time < requested.end_time` dan `existing.end_time > requested.start_time`, pada ruang dan tanggal yang sama. Dengan begitu kegiatan yang berakhir tepat saat kegiatan lain mulai tidak dianggap bertabrakan. `pending`, `approved`, dan `moved` tetap memblokir slot; hanya `rejected` yang tidak memblokir.

Kunci baris ruang (`lockForUpdate`) di dalam transaksi sebelum mengecek konflik dan menyimpan agenda. Semua jalur pembuatan/pemindahan agenda wajib mengikuti mekanisme yang sama. Ini mencegah dua request bersamaan lolos pemeriksaan pada MySQL/PostgreSQL.

Ruang dengan `room_type = kelas` hanya dapat dipesan pada Sabtu atau Minggu. Aturan ini juga berlaku saat agenda dipindahkan ke ruang kelas, melalui service booking bersama untuk web dan API.

### `app/Http/Controllers/Api/EventController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Room;
use App\Notifications\EventMovedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function calendar(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $events = Event::query()
            ->select(['id', 'room_id', 'event_name', 'date', 'start_time', 'end_time', 'status'])
            ->with('room:id,room_name,location')
            ->whereBetween('date', [$data['from'], $data['to']])
            ->whereIn('status', ['approved', 'moved'])
            ->orderBy('date')->orderBy('start_time')->get();

        return response()->json(['data' => $events]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'event_name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $event = DB::transaction(function () use ($request, $data) {
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            abort_unless($room->is_active, 422, 'Ruangan sedang tidak aktif.');

            $conflict = Event::where('room_id', $room->id)
                ->whereDate('date', $data['date'])
                ->whereIn('status', ['pending', 'approved', 'moved'])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_id' => ['Ruangan sudah dipesan pada rentang waktu tersebut. Pilih ruang atau waktu lain.'],
                ]);
            }

            return Event::create([
                ...$data,
                'requester_id' => $request->user()->id,
                // Kolom legacy events.pic_id masih NOT NULL pada migration proyek saat ini.
                'pic_id' => $request->user()->id,
                'status' => 'pending',
            ]);
        });

        return response()->json(['message' => 'Pengajuan agenda diterima.', 'data' => $event], 201);
    }

    public function move(Request $request, Event $event)
    {
        $data = $request->validate([
            'new_room_id' => ['required', 'integer', 'exists:rooms,id', Rule::notIn([$event->room_id])],
            'move_reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request, $event, $data) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_if($event->status === 'rejected', 422, 'Agenda yang ditolak tidak dapat dipindahkan.');

            // Serialisasi semua permintaan pada ruang tujuan.
            $newRoom = Room::whereKey($data['new_room_id'])->lockForUpdate()->firstOrFail();
            abort_unless($newRoom->is_active, 422, 'Ruangan tujuan sedang tidak aktif.');

            $conflict = Event::where('room_id', $newRoom->id)
                ->whereDate('date', $event->date)
                ->whereIn('status', ['pending', 'approved', 'moved'])
                ->where('id', '!=', $event->id)
                ->where('start_time', '<', $event->end_time)
                ->where('end_time', '>', $event->start_time)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'new_room_id' => ['Ruangan tujuan memiliki agenda pada waktu tersebut.'],
                ]);
            }

            $oldRoomName = Room::whereKey($event->room_id)->value('room_name');
            $event->update([
                'room_id' => $newRoom->id,
                'move_reason' => $data['move_reason'],
                'status' => 'moved',
            ]);

            $event->requester?->notify(new EventMovedNotification(
                $event->event_name,
                $oldRoomName,
                $newRoom->room_name,
                $data['move_reason'],
                $event->id,
            ));

            return $event->fresh('room');
        });

        return response()->json(['message' => 'Agenda dipindahkan dan peminjam diberi notifikasi.', 'data' => $result]);
    }

    public function decide(Request $request, Event $event)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $event = DB::transaction(function () use ($request, $event, $data) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedEvent->status === 'pending', 422, 'Hanya agenda pending yang dapat diputuskan.');
            $lockedEvent->update([
                'status' => $data['status'],
                'approved_by' => $request->user()->id,
            ]);

            return $lockedEvent->fresh();
        });

        return response()->json(['message' => 'Keputusan agenda tersimpan.', 'data' => $event]);
    }
}
```

`move()` hanya boleh dipanggil Admin melalui route middleware di bagian 10. Jangan menerima `requester_id`, `status`, `approved_by`, atau `move_reason` dari endpoint pengajuan umum; field tersebut ditentukan server.

### Notifikasi pemindahan

**Letak:** `app/Notifications/EventMovedNotification.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventMovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $eventName,
        public ?string $oldRoom,
        public string $newRoom,
        public string $reason,
        public int $eventId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'event_moved',
            'event_id' => $this->eventId,
            'event_name' => $this->eventName,
            'old_room' => $this->oldRoom,
            'new_room' => $this->newRoom,
            'reason' => $this->reason,
            'message' => "Agenda {$this->eventName} dipindahkan ke {$this->newRoom}. Alasan: {$this->reason}",
        ];
    }
}
```

Sediakan endpoint notifikasi agar aplikasi guru dapat mengambil dan menandai notifikasi telah dibaca (contoh implementasi route di bagian 10). UI dapat menampilkan toast/pop-up dari `message`.

## 8. API pengaduan, PIC, foto dan WhatsApp

### `app/Http/Controllers/Api/ComplaintController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with(['room:id,room_name,pic_id', 'reporter:id,fullname', 'handler:id,fullname']);

        if ($request->user()->role->role_name === 'PIC') {
            $query->whereHas('room', fn ($q) => $q->where('pic_id', $request->user()->id));
        } elseif ($request->user()->role->role_name !== 'Admin') {
            $query->where('reporter_id', $request->user()->id);
        }

        return response()->json(['data' => $query->latest('reported_at')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $room = Room::findOrFail($data['room_id']);
        abort_unless($room->is_active, 422, 'Ruangan tidak aktif.');

        // Baca gambar, batasi dimensi, encode ulang, dan simpan sebagai JPEG.
        $encoded = Image::decode($request->file('photo'))
            ->scaleDown(width: 1600, height: 1600)
            ->encodeUsingMediaType('image/jpeg', quality: 78);
        $path = 'complaints/' . uniqid('report_', true) . '.jpg';
        Storage::disk('public')->put($path, (string) $encoded);

        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $request->user()->id,
            'description' => $data['description'],
            'photo_path' => $path,
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        return response()->json(['message' => 'Pengaduan berhasil dikirim.', 'data' => $complaint], 201);
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status_repair' => ['required', Rule::in([
                'Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki',
            ])],
        ]);

        // PIC hanya boleh mengubah pengaduan dari ruang yang ditugaskan kepadanya.
        abort_unless($complaint->room()->where('pic_id', $request->user()->id)->exists(), 403,
            'Pengaduan ini bukan tanggung jawab ruang Anda.');

        $complaint->update([
            'status_repair' => $data['status_repair'],
            'handler_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Status penanganan diperbarui.', 'data' => $complaint->fresh()]);
    }

    public function whatsapp(Request $request, Complaint $complaint)
    {
        abort_unless($request->user()->role->role_name === 'Admin', 403);
        abort_unless($complaint->status_repair === 'Belum Diperbaiki', 422,
            'Peringatan hanya tersedia untuk pengaduan yang belum diperbaiki.');
        abort_unless($complaint->reported_at->lte(now()->subHours((int) config('rapi.escalation_hours', 24))), 422,
            'Batas waktu eskalasi belum terlampaui.');

        $pic = $complaint->room()->with('pic:id,fullname,phone_number')->firstOrFail()->pic;
        abort_unless($pic && $pic->phone_number, 422, 'Nomor WhatsApp PIC belum diatur.');

        // Format nomor internasional tanpa tanda +, spasi, atau angka 0 di depan.
        $number = preg_replace('/\\D+/', '', $pic->phone_number);
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        $message = "Peringatan RAPI-Sekolah: laporan ruang {$complaint->room->room_name} masih belum diperbaiki. Mohon segera ditindaklanjuti.";

        return response()->json(['url' => 'https://wa.me/' . $number . '?text=' . urlencode($message)]);
    }
}
```

Endpoint WhatsApp mengembalikan tautan `wa.me` untuk dibuka Admin; ini **bukan** pengiriman otomatis via WhatsApp Business API. Tombol UI harus membuka URL tersebut setelah konfirmasi. Ambang eskalasi contoh 24 jam dan dapat dikonfigurasi.

**Letak:** `config/rapi.php`

```php
<?php

return [
    'escalation_hours' => (int) env('RAPI_ESCALATION_HOURS', 24),
];
```

Tambahkan ke `.env` jika ingin mengubah ambang:

```dotenv
RAPI_ESCALATION_HOURS=24
```

Form kamera frontend dapat mengirim `multipart/form-data` dengan input `accept="image/*" capture="environment"`. Backend memvalidasi tipe/ukuran, resize, encode ulang, dan menyimpan di `storage/app/public/complaints`; URL publik tersedia setelah `php artisan storage:link`.

## 9. API master data (Admin/Waka)

Master data harus tetap hanya bisa diubah oleh Admin. Endpoint minimum:

| Method | URL | Fungsi |
|---|---|---|
| `GET` | `/api/admin/users` | Daftar akun |
| `POST` | `/api/admin/users` | Buat akun + assign role |
| `PUT` | `/api/admin/users/{user}` | Ubah profil/role/nomor |
| `DELETE` | `/api/admin/users/{user}` | Nonaktifkan/hapus akun sesuai kebijakan sekolah |
| `GET` | `/api/admin/rooms` | Daftar ruang |
| `POST` | `/api/admin/rooms` | Buat ruang dan tetapkan `pic_id` |
| `PUT` | `/api/admin/rooms/{room}` | Ubah ruang/PIC/status aktif |
| `DELETE` | `/api/admin/rooms/{room}` | Hapus hanya jika tidak ada agenda/pengaduan terkait |

**Letak: `app/Http/Controllers/Api/UserController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function safe(User $user): array
    {
        return $user->only(['id', 'username', 'fullname', 'phone_number', 'role_id'])
            + ['role' => $user->role?->role_name];
    }

    public function index()
    {
        return response()->json(['data' => User::with('role')->orderBy('fullname')->paginate(20)
            ->through(fn (User $user) => $this->safe($user))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'fullname' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ]);
        $role = Role::findOrFail($data['role_id']);
        abort_unless(in_array($role->role_name, ['Admin', 'PIC', 'Guru', 'Organisasi'], true), 422,
            'Role tidak termasuk role yang didukung.');
        $data['password'] = Hash::make($data['password']);

        return response()->json(['data' => $this->safe(User::create($data))], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'fullname' => ['sometimes', 'required', 'string', 'max:150'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:25'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role_id' => ['sometimes', 'required', 'integer', Rule::exists('roles', 'id')],
        ]);
        if (isset($data['role_id'])) {
            $role = Role::findOrFail($data['role_id']);
            abort_unless(in_array($role->role_name, ['Admin', 'PIC', 'Guru', 'Organisasi'], true), 422,
                'Role tidak termasuk role yang didukung.');
        }
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return response()->json(['data' => $this->safe($user->fresh('role'))]);
    }

    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $hasReferences = $user->managedRooms()->exists()
            || $user->events()->exists()
            || $user->complaintsReported()->exists()
            || DB::table('events')->where('approved_by', $user->id)->exists()
            || DB::table('complaints')->where('handler_id', $user->id)->exists();
        abort_if($hasReferences, 422, 'Akun masih terhubung ke data. Nonaktifkan atau alihkan referensinya, jangan hapus.');
        $user->delete();

        return response()->json(['message' => 'Akun dihapus.']);
    }
}
```

**Letak: `app/Http/Controllers/Api/RoomController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function publicIndex()
    {
        return response()->json(['data' => Room::where('is_active', true)
            ->orderBy('room_name')->get(['id', 'room_name', 'capacity', 'location'])]);
    }

    public function index()
    {
        return response()->json(['data' => Room::with('pic:id,fullname,phone_number')
            ->orderBy('room_name')->paginate(20)]);
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'room_name')->ignore($room?->id)],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:150'],
            'pic_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $validPic = User::whereKey($data['pic_id'])
            ->whereHas('role', fn ($query) => $query->where('role_name', 'PIC'))
            ->exists();
        if (!$validPic) {
            throw ValidationException::withMessages(['pic_id' => ['PIC harus merupakan akun dengan role PIC.']]);
        }

        return $data;
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Room::create($this->validated($request))], 201);
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));
        return response()->json(['data' => $room->fresh('pic')]);
    }

    public function destroy(Room $room)
    {
        abort_if($room->events()->exists() || $room->complaints()->exists(), 422,
            'Ruangan masih memiliki agenda atau pengaduan. Nonaktifkan ruangan, jangan hapus.');
        $room->delete();
        return response()->json(['message' => 'Ruangan dihapus.']);
    }
}
```

Validasi `room_name` unik, `capacity` integer minimal 1, `location` string, dan `pic_id` harus menunjuk user ber-role `PIC`. Daftar ruangan publik hanya menampilkan `is_active = true`. Implementasi di atas memberikan CRUD admin dan daftar publik.

## 10. Daftar route API

**Letak:** `routes/api.php` (file yang dibuat oleh `php artisan install:api`).

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TvDisplayController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/calendar/events', [EventController::class, 'calendar']); // publik, GET saja
Route::get('/rooms', [RoomController::class, 'publicIndex']); // publik, ruangan aktif saja
Route::get('/tv-display/agenda', [TvDisplayController::class, 'agenda']); // publik, agenda aktif hari ini untuk monitor

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/notifications', fn (Request $request) => response()->json(
        $request->user()->notifications()->latest()->paginate(20)
    ));
    Route::post('/notifications/{notification}/read', function (Request $request, string $notification) {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        return response()->json(['message' => 'Notifikasi ditandai telah dibaca.']);
    });

    // Peminjaman oleh Admin, Guru, atau Organisasi.
    Route::middleware('role:Admin,Guru,Organisasi')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/my/events', fn (Request $request) => response()->json(
            $request->user()->events()->with('room')->latest('date')->paginate(20)
        ));
        Route::post('/complaints', [ComplaintController::class, 'store']);
    });

    // Keluhan: Admin melihat semua; PIC hanya keluhan pada ruang delegasinya.
    Route::middleware('role:Admin,PIC,Guru,Organisasi')->get('/complaints', [ComplaintController::class, 'index']);
    Route::middleware('role:PIC')->patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);

    // Override, notifikasi pemindahan, master data, dan eskalasi hanya Admin.
    Route::middleware('role:Admin')->group(function () {
        Route::patch('/events/{event}/decision', [EventController::class, 'decide']);
        Route::post('/events/{event}/move', [EventController::class, 'move']);
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::put('/admin/users/{user}', [UserController::class, 'update']);
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
        Route::get('/admin/rooms', [RoomController::class, 'index']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::put('/admin/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{room}', [RoomController::class, 'destroy']);
        Route::get('/admin/complaints/{complaint}/whatsapp', [ComplaintController::class, 'whatsapp']);
    });
});
```

Sesuaikan pemanggilan `UserController` dan `RoomController` dengan method CRUD di atas. Bila implementasi CRUD belum dibuat, jangan daftarkan route yang belum ada karena akan menghasilkan HTTP 500; implementasikan method sebelum route dipakai. API publik hanya menyediakan `GET` kalender dan daftar ruang—tidak menyediakan `POST`, `PUT`, atau `DELETE` tanpa autentikasi.

### Wajib: amankan endpoint web lama

Proyek sudah memiliki route web untuk `POST /events`, `POST /events/{id}/move`, `POST /complaints`, dan `POST /complaints/{id}/status`. Controller yang ada belum setara dengan API di atas: penyimpanan agenda belum mengunci baris ruang dalam transaksi dan penanganan status pengaduan belum membatasi PIC ke ruang delegasinya. Jangan biarkan endpoint tersebut menjadi jalur bypass.

Pilih salah satu cara berikut sebelum menyatakan sistem selesai:

1. Jika UI akan memakai API, hapus/nonaktifkan empat route POST web tersebut dan ubah form agar mengirim request ke API menggunakan bearer token; atau
2. Jika UI tetap memakai form web/session, pindahkan logika transaksi/conflict dan otorisasi PIC ke service bersama yang dipanggil baik oleh controller API maupun controller web, lalu batasi route web memakai `role:Admin,Guru,Organisasi` untuk pengajuan, `role:Admin` untuk mutasi agenda, dan `role:PIC` untuk update status.

> Membatasi tampilan tombol di frontend saja tidak cukup. Jalankan uji yang sama pada URL API dan URL web bila keduanya tetap aktif.

## 11. Format API dan contoh request

Semua URL memakai prefix `/api` otomatis. Contoh login:

```http
POST /api/login
Content-Type: application/json

{"username":"guru_pembina","password":"guru123","device_name":"Android"}
```

Contoh pengajuan (sertakan token):

```http
POST /api/events
Authorization: Bearer <token>
Content-Type: application/json

{"room_id":1,"event_name":"Rapat OSIS","date":"2026-10-20","start_time":"10:00","end_time":"11:30"}
```

Contoh keluhan foto: `POST /api/complaints`, header `Authorization: Bearer <token>`, body `multipart/form-data` berisi `room_id`, `description`, dan `photo` (file gambar). Status PIC:

```http
PATCH /api/complaints/12/status
Authorization: Bearer <token>
Content-Type: application/json

{"status_repair":"Menunggu Spare Part"}
```

Kalender bulanan:

```http
GET /api/calendar/events?from=2026-10-01&to=2026-10-31
```

Format sukses konsisten berupa JSON dengan `data`/`message`; error validasi Laravel menghasilkan HTTP 422, unauthenticated 401, dan tidak berhak 403.

## 12. Hak akses yang harus diuji

| Fitur | Guest | Admin | PIC | Guru | Organisasi |
|---|---:|---:|---:|---:|---:|
| Lihat kalender & ruang aktif | Ya | Ya | Ya | Ya | Ya |
| Login/profil | Tidak | Ya | Ya | Ya | Ya |
| Ajukan agenda | Tidak | Ya | Tidak | Ya | Ya |
| Buat pengaduan + foto | Tidak | Ya | Tidak | Ya | Ya |
| Lihat pengaduan | Tidak | Semua | Hanya ruang tanggung jawab | Milik sendiri | Milik sendiri |
| Ubah status kerusakan | Tidak | Tidak* | Hanya ruang tanggung jawab | Tidak | Tidak |
| Pindah agenda | Tidak | Ya | Tidak | Tidak | Tidak |
| Master user/ruang | Tidak | Ya | Tidak | Tidak | Tidak |
| Buat tautan peringatan WA | Tidak | Ya | Tidak | Tidak | Tidak |

`*` Status penanganan tetap menjadi tanggung jawab PIC. Admin hanya meminta tindak lanjut melalui WhatsApp.

## 13. Pengujian minimal (sebelum dinyatakan selesai)

Buat feature tests di `tests/Feature/Api/RapiApiTest.php`. Minimal skenario:

1. Guest dapat `GET /api/calendar/events`, tetapi tidak dapat `POST /api/events` (401).
2. Dua agenda pada ruang/tanggal sama dengan waktu beririsan: agenda kedua mendapat 422.
3. Agenda pada ruangan sama ketika jam pertama berakhir tepat saat jam kedua mulai: keduanya diterima.
4. Status `rejected` tidak memblokir slot; `pending`, `approved`, dan `moved` memblokir.
5. Guru dapat mengajukan agenda dan reporter pengaduan otomatis berasal dari token, bukan payload.
6. Akun PIC hanya dapat melihat/mengubah complaint ruang yang `rooms.pic_id`-nya sama dengan ID PIC; PIC lain mendapat 403.
7. Role Guru/Admin mendapat 403 saat memanggil endpoint ubah status PIC.
8. Upload file non-gambar atau terlalu besar mendapat 422; file gambar valid tersimpan di disk `public` dan `photo_path` terisi.
9. Hanya Admin yang dapat memindahkan agenda; alasan wajib tersimpan, ruang baru dicek konflik, dan requester menerima notifikasi berisi ruang baru + alasan.
10. Hanya pengaduan `Belum Diperbaiki` yang melewati `RAPI_ESCALATION_HOURS` menghasilkan URL WhatsApp.
11. Guest tidak dapat mengubah kalender/ruangan; semua operasi tulis memerlukan token dan role.
12. Admin dapat membuat/mengubah user, role, ruang, serta menetapkan hanya user ber-role PIC ke `rooms.pic_id`.
13. Booking ruang `kelas` ditolak pada Senin-Jumat dan diterima Sabtu-Minggu, melalui API dan web; tipe ruang lain tetap dapat dipinjam sesuai aturan waktu.
14. Endpoint TV hanya menampilkan agenda hari ini yang belum selesai, berstatus `approved`/`moved`, dan berada di ruang aktif.
15. Perubahan role PIC ditolak selama PIC masih ditugaskan ke ruang aktif; pembuatan complaint web/API sama-sama mewajibkan foto dan ruang aktif.
16. Migration backfill requester berhasil, dan schema `events.requester_id` berstatus `NOT NULL`.

Contoh kerangka tes konflik:

```php
public function test_overlapping_room_booking_is_rejected(): void
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $room = Room::factory()->create(['is_active' => true]);

    Event::factory()->create([
        'room_id' => $room->id, 'requester_id' => $user->id,
        'date' => '2026-10-20', 'start_time' => '10:00', 'end_time' => '11:00',
        'status' => 'approved',
    ]);

    $this->postJson('/api/events', [
        'room_id' => $room->id, 'event_name' => 'Agenda kedua',
        'date' => '2026-10-20', 'start_time' => '10:30', 'end_time' => '11:30',
    ])->assertUnprocessable()->assertJsonValidationErrors('room_id');
}
```

Jika factory belum tersedia, buat melalui `php artisan make:factory RoomFactory` dan `php artisan make:factory EventFactory`; atau siapkan record menggunakan seeder dalam test. Jalankan tes dengan:

```bash
php artisan test
```

## 14. Urutan penerapan

1. Commit kondisi awal sebelum perubahan.
2. Pasang Sanctum dan Image, jalankan `install:api`, periksa migration notifikasi.
3. Buat migration tambahan dan model/relasi.
4. Tambah middleware role dan daftarkan alias di `bootstrap/app.php`.
5. Buat Auth, Event, Complaint, Room, User controller API serta notification class.
6. Tambahkan route API dan implementasikan seluruh method yang dirujuk route.
7. Jalankan `php artisan migrate`, `php artisan storage:link`, `php artisan route:list --path=api`.
8. Uji role/konflik/upload/pindah/notifikasi/WA melalui PHPUnit dan Postman/Insomnia.
9. Pastikan endpoint publik hanya read-only; periksa error log `storage/logs/laravel.log`.
10. Commit dengan pesan deskriptif, misalnya `feat(api): add RAPI booking and facility workflows`.

## 15. Audit akhir terhadap rubrik UKK

- [x] **RBAC 4 role:** role `Admin`, `PIC`, `Guru`, `Organisasi`; endpoint dibatasi middleware server-side.
- [x] **Kalender publik read-only:** `GET /api/calendar/events` tanpa autentikasi; tidak ada endpoint mutasi publik.
- [x] **Anti tabrakan waktu:** cek rentang irisan pada room/date dan transaksi dengan row lock; pending ikut mengunci.
- [x] **Kelas akhir pekan:** `room_type = kelas` hanya bisa dipesan Sabtu/Minggu, termasuk saat agenda dipindahkan.
- [x] **Display TV:** endpoint publik read-only menampilkan agenda hari ini yang sedang/akan berlangsung pada ruang aktif.
- [x] **Pemetaan PIC one-to-many:** `rooms.pic_id -> users.id`; otorisasi complaint berdasarkan ruang yang didelegasikan.
- [x] **Workflow status:** nilai enum status persis tiga kondisi yang diminta; hanya PIC penanggung jawab dapat mengubah.
- [x] **Foto kerusakan:** upload tervalidasi, resize/encode ulang, simpan di disk public, path di `photo_path`.
- [x] **Override dan notifikasi:** Admin-only; cek konflik ruang tujuan; `move_reason` disimpan; notifikasi database memuat ruang baru dan alasan.
- [x] **Eskalasi WhatsApp:** Admin-only, status dan batas waktu dicek; nomor PIC diambil dari ruang, URL `wa.me` dibuat dinamis.
- [x] **Master data:** endpoint user/role/ruang hanya Admin; validasi `pic_id` mengharuskan role PIC.
- [x] **Keamanan API:** Sanctum bearer token, throttle login, validasi input, status HTTP JSON, pemisahan endpoint publik dan privat.
- [x] **Integritas requester:** `events.requester_id` dibackfill dari kolom legacy dan diwajibkan `NOT NULL`.
- [x] **Syarat selesai:** seluruh kode panduan harus ditempatkan di file yang disebutkan, method controller/CRUD yang dirujuk route harus benar-benar dibuat, migration berhasil, dan semua tes pada bagian 13 lulus. Checklist ini tidak mengklaim fitur sudah terpasang hanya dengan menyimpan dokumen panduan.

### Catatan penting sebelum produksi

- Hindari menghapus user/ruang yang masih direferensikan; gunakan soft delete atau penonaktifan.
- Gunakan HTTPS dan batasi ukuran upload pada PHP/web server juga (`upload_max_filesize`, `post_max_size`).
- Matikan debug di deployment (`APP_DEBUG=false`), rotasi token jika perangkat hilang, dan jangan memasukkan password/token ke repositori.
- Untuk jaminan konflik, semua jalur penulisan agenda—API maupun web lama—harus menggunakan lock ruang dan algoritma yang sama. Jangan membiarkan controller web lama menulis agenda tanpa mekanisme transaksi tersebut.
