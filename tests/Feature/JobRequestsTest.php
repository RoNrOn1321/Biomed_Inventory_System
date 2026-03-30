<?php

namespace Tests\Feature;

use App\Models\JobRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_biomed_technician_can_view_job_requests_page(): void
    {
        $user = User::factory()->create([
            'account_type' => 'Biomed_Technician',
        ]);

        $response = $this->actingAs($user)->get('/JobRequests');

        $response->assertOk();
    }

    public function test_end_user_cannot_view_job_requests_page(): void
    {
        $user = User::factory()->create([
            'account_type' => 'End_User',
        ]);

        $response = $this->actingAs($user)->get('/JobRequests');

        $response->assertForbidden();
    }

    public function test_biomed_technician_can_accept_pending_job_request(): void
    {
        $user = User::factory()->create([
            'account_type' => 'Biomed_Technician',
        ]);

        $jobRequest = JobRequest::create([
            'requester_name' => 'Nurse Marie',
            'department' => 'ER',
            'equipment_name' => 'Patient Monitor',
            'issue_summary' => 'Screen flickers during vitals monitoring.',
            'priority' => 'High',
            'status' => 'Pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($user)->put("/JobRequests/{$jobRequest->id}/accept");

        $response->assertRedirect();
        $jobRequest->refresh();

        $this->assertSame('Accepted', $jobRequest->status);
        $this->assertSame($user->id, $jobRequest->accepted_by);
        $this->assertNotNull($jobRequest->accepted_at);
    }
}