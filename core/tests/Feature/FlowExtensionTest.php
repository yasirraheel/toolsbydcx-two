<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\GoogleFlowAccount;
use App\Models\ExtensionPairing;
use App\Models\FlowLoginAttempt;
use App\Services\FlowAccess;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\TestCase;

class FlowExtensionTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Laramin\Utility\Utility::class);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('a',32)), 'cache.default' => 'array']);
        app('db')->purge('sqlite');
        cache()->flush();
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('username'); $t->string('email'); $t->string('firstname')->default('Test');
            $t->string('lastname')->default('User'); $t->integer('status')->default(1);
            $t->integer('ev')->default(1); $t->integer('sv')->default(1); $t->integer('tv')->default(1);
            $t->integer('plan_id')->nullable(); $t->integer('reseller_id')->nullable();
            $t->timestamp('expires_at')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        foreach (glob(database_path('migrations/2026_09_27_*.php')) as $file) (require $file)->up();
        (require database_path('migrations/2026_09_28_000001_bind_flow_attempts_to_pairings.php'))->up();
    }

    private function fixture(): array
    {
        $user = User::forceCreate(['username' => 'test'.User::count(), 'email' => 'test'.User::count().'@example.test', 'expires_at' => now()->addDays(7)]);
        $account = GoogleFlowAccount::create(['email' => 'flow'.$user->id.'@example.test', 'password_encrypted' => Crypt::encryptString('test-password'),
            'totp_secret_encrypted' => Crypt::encryptString('JBSWY3DPEHPK3PXP'), 'backup_codes' => ['12345678', '23456789'],
            'status' => 'active', 'assigned_to_user_id' => $user->id]);
        return [$user,$account];
    }

    private function connect(User $user): string
    {
        $pairing = FlowAccess::issue($user);
        return $this->postJson('/api/dcx-flow/pair', ['code' => $pairing->pairing_code, 'installationId' => 'test-install'])
            ->assertOk()->assertJsonStructure(['accessToken','expiresAt'])->json('accessToken');
    }

    public function test_complete_api_contract_and_one_time_credentials(): void
    {
        [$user,$account] = $this->fixture();
        $token = $this->connect($user);
        $pairing = ExtensionPairing::first();
        $this->assertSame(hash('sha256',$token),$pairing->access_token);
        $this->assertTrue($pairing->expires_at->isAfter(now()->addDays(6)));
        $this->withToken($token)->getJson('/api/dcx-flow/status')->assertOk()->assertJsonPath('connected',true)->assertJsonPath('assignedAccount.email',$account->email);
        $id = $this->withToken($token)->postJson('/api/dcx-flow/start')->assertOk()->assertJsonStructure(['attemptId','expiresAt'])->json('attemptId');
        foreach (['email' => $account->email, 'password' => 'test-password'] as $stage => $value) {
            $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>$stage])->assertOk()->assertJsonPath('value',$value)->assertHeader('Cache-Control','no-store, private');
        }
        for ($i=0;$i<2;$i++) {
            $result=$this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'otp'])->assertOk()->assertJsonStructure(['value','expiresAt']);
            $this->assertMatchesRegularExpression('/^\d{6}$/',$result->json('value'));
        }
        FlowLoginAttempt::whereKey($id)->update(['otp_attempt_count'=>2]);
        $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'otp'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'backup_code'])->assertOk()->assertJsonPath('value','12345678');
        $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'backup_code'])->assertStatus(422);
        $this->assertSame(['23456789'],$account->fresh()->backup_codes);
        $this->withToken($token)->postJson('/api/dcx-flow/finish',['attemptId'=>$id,'outcome'=>'success'])->assertOk();
        $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'password'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/dcx-flow/disconnect')->assertOk();
        $this->withToken($token)->getJson('/api/dcx-flow/status')->assertUnauthorized();
    }

    public function test_codes_are_single_use_expire_and_require_active_plan(): void
    {
        [$user] = $this->fixture();
        $pairing=FlowAccess::issue($user); $code=$pairing->pairing_code;
        $this->postJson('/api/dcx-flow/pair',['code'=>$code,'installationId'=>'one'])->assertOk();
        $this->postJson('/api/dcx-flow/pair',['code'=>$code,'installationId'=>'two'])->assertStatus(422);
        $pairing=FlowAccess::issue($user); $pairing->update(['expires_at'=>now()->subMinute()]);
        $this->postJson('/api/dcx-flow/pair',['code'=>$pairing->pairing_code,'installationId'=>'three'])->assertStatus(422);
        $token=$this->connect($user);
        $user->forceFill(['expires_at'=>now()->subMinute()])->save();
        $this->withToken($token)->getJson('/api/dcx-flow/status')->assertForbidden();
    }

    public function test_attempt_cannot_be_used_by_another_pairing_or_after_reassignment(): void
    {
        [$user,$account]=$this->fixture();
        $first=$this->connect($user);
        $id=$this->withToken($first)->postJson('/api/dcx-flow/start')->json('attemptId');
        $second=$this->connect($user);
        $this->withToken($second)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'password'])->assertStatus(422);
        $this->withToken($second)->postJson('/api/dcx-flow/finish',['attemptId'=>$id,'outcome'=>'success'])->assertNotFound();
        FlowAccess::assign($user,null);
        $this->withToken($first)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'password'])->assertUnauthorized();
        $this->assertSame('cancelled',FlowLoginAttempt::find($id)->status);
    }

    public function test_foreign_assignment_and_reseller_access_are_rejected(): void
    {
        [$owner,$account]=$this->fixture(); [$other]=$this->fixture();
        try { FlowAccess::assign($other,$account->id); $this->fail('Account reassignment should be rejected'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertSame($owner->id,(int)$account->fresh()->assigned_to_user_id); }
        $this->actingAs($other);
        $controller=new \App\Http\Controllers\Reseller\ResellerController();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller->flowRevoke($owner->id);
    }

    public function test_website_pairing_requires_its_proof_and_uninstall_revokes_token(): void
    {
        [$user] = $this->fixture();
        $verifier = str_repeat('a',64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $pairing = FlowAccess::issue($user, $challenge);
        $payload = ['code'=>$pairing->pairing_code, 'installationId'=>'website', 'expectedUserId'=>$user->id];
        $this->postJson('/api/dcx-flow/pair', $payload)->assertStatus(422);
        $result = $this->postJson('/api/dcx-flow/pair', $payload + ['codeVerifier'=>$verifier])->assertOk();
        $this->get('/api/dcx-flow/uninstall?key='.$result->json('uninstallToken'))->assertOk();
        $this->withToken($result->json('accessToken'))->getJson('/api/dcx-flow/status')->assertUnauthorized();
    }

    public function test_unauthenticated_requests_and_brute_force_codes_are_rejected(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.88']);
        $this->getJson('/api/dcx-flow/status')->assertUnauthorized();
        for($i=0;$i<10;$i++) $this->postJson('/api/dcx-flow/pair',['code'=>'000000','installationId'=>'fake'])->assertStatus(422);
        $this->postJson('/api/dcx-flow/pair',['code'=>'000000','installationId'=>'fake'])->assertStatus(429);
    }

    public function test_disabled_account_and_expired_attempt_cannot_deliver_credentials(): void
    {
        [$user,$account]=$this->fixture(); $token=$this->connect($user);
        $id=$this->withToken($token)->postJson('/api/dcx-flow/start')->json('attemptId');
        FlowLoginAttempt::whereKey($id)->update(['expires_at'=>now()->subMinute()]);
        $this->withToken($token)->postJson('/api/dcx-flow/step',['attemptId'=>$id,'stage'=>'password'])->assertStatus(422);
        $account->update(['status'=>'disabled']);
        $this->withToken($token)->postJson('/api/dcx-flow/start')->assertForbidden();
    }
}
