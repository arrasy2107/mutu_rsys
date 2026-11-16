<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HospitalMutuTest extends TestCase
{
    public function test_hospital_routes_accessible_by_komite_mutu(): void
    {
        $user = User::where('role', 'komite_mutu')->orWhere('role', 'superadmin')->first();
        $this->assertNotNull($user, 'User exists');

        $routes = [
            '/dashboard',
            '/indicator-selections',
            '/indicator-selections?tipe=rs',
            '/indicator-selections/create?tipe=rs',
            '/indicators',
            '/indicators/create?prefill_jenis=IMP-RS',
            '/hospital/aggregation',
            '/hospital/analysis',
            '/hospital/pdsa',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302]),
                "Route {$route} returned status {$response->getStatusCode()}"
            );
        }
    }
}
