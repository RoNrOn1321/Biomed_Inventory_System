<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_inventory_export_as_pdf(): void
    {
        $user = User::factory()->create();

        Equipment::create([
            'location' => 'ER',
            'description' => 'Patient Monitor',
            'brand' => 'Mindray',
            'model' => 'iMEC 12',
            'serial_number' => 'PM-001',
            'tag_number' => 'TAG-001',
            'pm_date_done' => '2026-02-10',
            'calibration' => 'Calibrated',
            'status' => 'Functional',
        ]);

        $response = $this->actingAs($user)->get('/equipment/export?format=pdf&from=2026-01&to=2026-03');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_download_inventory_export_as_excel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/equipment/export?format=excel&from=2026-01&to=2026-03');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_authenticated_user_can_download_inventory_export_as_word(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/equipment/export?format=word&from=2026-01&to=2026-03');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}