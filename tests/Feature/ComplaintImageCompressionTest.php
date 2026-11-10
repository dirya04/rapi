<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\ImageCompressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

class ComplaintImageCompressionTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;
    private User $pic;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $guruRole = Role::firstOrCreate(['role_name' => 'Guru']);
        $picRole = Role::firstOrCreate(['role_name' => 'PIC']);

        $this->pic = User::create([
            'username'     => 'pic_sarpras',
            'password'     => Hash::make('secret'),
            'fullname'     => 'PIC Sarpras',
            'phone_number' => '081234567890',
            'role_id'      => $picRole->id,
        ]);

        $this->guru = User::create([
            'username'     => 'guru_test',
            'password'     => Hash::make('secret'),
            'fullname'     => 'Guru Pelapor',
            'phone_number' => '081234567899',
            'role_id'      => $guruRole->id,
        ]);

        $this->room = Room::create([
            'room_name' => 'Laboratorium Komputer 1',
            'room_type' => 'laboratorium',
            'capacity'  => 36,
            'location'  => 'Gedung B',
            'pic_id'    => $this->pic->id,
            'is_active' => true,
        ]);
    }

    public function test_web_uploaded_complaint_photo_is_compressed_and_stored(): void
    {
        // 1. Create a large dummy image (1920x1080)
        $file = UploadedFile::fake()->image('meja_rusak.jpg', 1920, 1080);

        $response = $this->actingAs($this->guru)->post('/complaints', [
            'room_id'     => $this->room->id,
            'description' => 'Kaki meja komputer patah dan kabel stopkontak kendor.',
            'photo'       => $file,
        ]);

        $response->assertRedirect('/guru/dashboard?page=pengaduan');
        $response->assertSessionHas('success');

        $complaint = Complaint::where('description', 'Kaki meja komputer patah dan kabel stopkontak kendor.')->first();
        $this->assertNotNull($complaint);
        $this->assertNotEmpty($complaint->photo_path);

        // Verify stored in public storage
        Storage::disk('public')->assertExists($complaint->photo_path);
        $this->assertStringStartsWith('complaints/', $complaint->photo_path);
        $this->assertStringEndsWith('.jpg', $complaint->photo_path);

        // Verify the stored image was resized down to <= 1280px
        $rawBytes = Storage::disk('public')->get($complaint->photo_path);
        $manager = Image::getFacadeRoot();
        $decoded = $manager->decode($rawBytes);

        $this->assertLessThanOrEqual(1280, $decoded->width());
        $this->assertLessThanOrEqual(1280, $decoded->height());
    }

    public function test_web_uploaded_png_photo_is_compressed_and_preserved_as_png(): void
    {
        $file = UploadedFile::fake()->image('monitor_pecah.png', 1600, 1200);

        $response = $this->actingAs($this->guru)->post('/complaints', [
            'room_id'     => $this->room->id,
            'description' => 'Layar monitor bergaris dan pecah pada pojok kanan atas.',
            'photo'       => $file,
        ]);

        $response->assertRedirect('/guru/dashboard?page=pengaduan');
        $complaint = Complaint::where('description', 'Layar monitor bergaris dan pecah pada pojok kanan atas.')->first();

        $this->assertNotNull($complaint);
        $this->assertStringEndsWith('.png', $complaint->photo_path);
        Storage::disk('public')->assertExists($complaint->photo_path);

        $rawBytes = Storage::disk('public')->get($complaint->photo_path);
        $manager = Image::getFacadeRoot();
        $decoded = $manager->decode($rawBytes);

        $this->assertLessThanOrEqual(1280, $decoded->width());
        $this->assertLessThanOrEqual(1280, $decoded->height());
    }

    public function test_image_compression_service_reduces_file_size_of_large_photo(): void
    {
        $service = app(ImageCompressionService::class);

        // Generate a truecolor image with content using GD
        $im = imagecreatetruecolor(1800, 1200);
        for ($i = 0; $i < 20; $i++) {
            $col = imagecolorallocate($im, rand(50, 255), rand(50, 255), rand(50, 255));
            imagefilledrectangle($im, rand(0, 800), rand(0, 600), rand(800, 1800), rand(600, 1200), $col);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'test_compress') . '.jpg';
        imagejpeg($im, $tmp, 95);
        imagedestroy($im);

        $originalSize = filesize($tmp);
        $uploaded = new UploadedFile($tmp, 'large_camera_photo.jpg', 'image/jpeg', null, true);

        $savedPath = $service->compressAndStore($uploaded, 'complaints', 1280, 1280, 75);
        $savedSize = Storage::disk('public')->size($savedPath);

        $this->assertLessThan($originalSize, $savedSize);
        $this->assertStringStartsWith('complaints/', $savedPath);
        Storage::disk('public')->assertExists($savedPath);

        @unlink($tmp);
    }
}
