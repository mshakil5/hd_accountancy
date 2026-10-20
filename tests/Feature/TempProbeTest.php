<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class TempProbeTest extends TestCase {
    public function test_probe(): void {
        fwrite(STDERR, "\nconnection: ".DB::connectionName()."\n");
        fwrite(STDERR, "database:  ".DB::connection()->getDatabaseName()."\n");
        fwrite(STDERR, "clients:   ".DB::table('clients')->count()."\n");
        fwrite(STDERR, "env file:  ".app()->environmentFile()."\n");
        fwrite(STDERR, "app env:   ".app()->environment()."\n");
        $this->assertTrue(true);
    }
}
